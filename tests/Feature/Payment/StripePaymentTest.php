<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayResponse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Webhook;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['rentcar.stripe.webhook_secret' => 'whsec_test']);
    }

    /**
     * Helper to create an eligible customer.
     *
     * @return array{0: User, 1: Customer}
     */
    private function eligibleCustomer(): array
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $customer = $user->customer()->create([
            'birthdate' => '1990-01-01',
            'verification_status' => 'verified',
        ]);
        $customer->documents()->create([
            'type' => 'license',
            'file_path' => 'documents/license.pdf',
            'status' => 'approved',
        ]);

        return [$user, $customer];
    }

    /**
     * Helper to post a signed Stripe webhook request.
     */
    private function postWebhook(array $payload, string $secret = 'whsec_test')
    {
        $payloadJson = json_encode($payload);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payloadJson}";
        $hmac = hash_hmac('sha256', $signedPayload, $secret);
        $signature = "t={$timestamp},v1={$hmac}";

        return $this->call(
            method: 'POST',
            uri: '/api/v1/payments/webhooks/stripe',
            parameters: [],
            cookies: [],
            files: [],
            server: [
                'HTTP_STRIPE_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $payloadJson
        );
    }

    public function test_create_intent_returns_client_secret(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create(['daily_price' => '3000.00', 'deposit_amount' => '5000.00']);

        // Create a reservation for this customer in pending_payment state
        $reservation = Reservation::create([
            'reservation_number' => 'RES-12345',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'base_price' => '6000.00',
            'delivery_fee' => '0.00',
            'insurance_fee' => '0.00',
            'deposit_amount' => '5000.00',
            'discount_amount' => '0.00',
            'tax_amount' => '1080.00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        // Mock Stripe Gateway response
        $this->mock(PaymentGatewayInterface::class, function (MockInterface $mock) use ($reservation) {
            $mock->shouldReceive('createPayment')
                ->once()
                ->with(Mockery::on(function ($data) use ($reservation) {
                    return $data['amount_cents'] === 708000
                        && strtolower($data['currency']) === 'dop'
                        && $data['capture_method'] === 'automatic';
                }))
                ->andReturn(PaymentGatewayResponse::success(
                    status: 'requires_action',
                    provider: 'stripe',
                    amountCents: 708000,
                    currency: 'DOP',
                    providerPaymentId: 'pi_test_123',
                    requiresAction: true,
                    clientSecret: 'pi_test_123_secret_xyz',
                    raw: ['client_secret' => 'pi_test_123_secret_xyz', 'id' => 'pi_test_123']
                ));
        });

        $response = $this->actingAs($user)->postJson('/api/v1/payments/stripe/create-intent', [
            'reservation_id' => $reservation->id,
            'payment_type' => 'rent',
        ]);

        $response->assertOk()
            ->assertJsonPath('client_secret', 'pi_test_123_secret_xyz')
            ->assertJsonPath('payment_intent_id', 'pi_test_123')
            ->assertJsonPath('status', PaymentStatus::RequiresAction->value)
            ->assertJsonPath('amount', '7080.00');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'amount' => '7080.00',
            'status' => PaymentStatus::RequiresAction->value,
            'provider_payment_id' => 'pi_test_123',
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'reservation_id' => $reservation->id,
            'status' => PaymentAttemptStatus::RequiresAction->value,
            'provider_reference' => 'pi_test_123',
        ]);
    }

    public function test_create_intent_fails_for_non_payable_reservation(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-12346',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'paid',
            'reservation_status' => ReservationStatus::Paid, // Already Paid!
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/payments/stripe/create-intent', [
            'reservation_id' => $reservation->id,
            'payment_type' => 'rent',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('code', 'NOT_PAYABLE');
    }

    public function test_create_intent_fails_for_non_owner(): void
    {
        [$userA, $customerA] = $this->eligibleCustomer();
        [$userB, $customerB] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservationOfB = Reservation::create([
            'reservation_number' => 'RES-12347',
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        // User A tries to pay for User B's reservation
        $response = $this->actingAs($userA)->postJson('/api/v1/payments/stripe/create-intent', [
            'reservation_id' => $reservationOfB->id,
            'payment_type' => 'rent',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_webhook_succeeded_marks_reservation_paid(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-12348',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_succeeded',
            'amount' => '7080.00',
            'currency' => 'DOP',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_test_succeeded',
            'amount' => '7080.00',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_succeeded_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_succeeded',
                    'object' => 'payment_intent',
                    'amount' => 708000,
                    'currency' => 'dop',
                    'status' => 'succeeded',
                ]
            ]
        ];

        $response = $this->postWebhook($payload);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Paid->value,
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => PaymentAttemptStatus::Succeeded->value,
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'paid',
            'reservation_status' => ReservationStatus::Paid->value,
        ]);
    }

    public function test_webhook_failed_keeps_reservation_pending(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-12349',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_failed',
            'amount' => '7080.00',
            'currency' => 'DOP',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_test_failed',
            'amount' => '7080.00',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_failed_123',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_failed',
                    'object' => 'payment_intent',
                    'amount' => 708000,
                    'currency' => 'dop',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ]
                ]
            ]
        ];

        $response = $this->postWebhook($payload);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Failed->value,
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => PaymentAttemptStatus::Failed->value,
            'error_code' => 'card_declined',
            'error_message' => 'Your card was declined.',
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment->value,
        ]);
    }

    public function test_webhook_idempotent_on_duplicate(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-12350',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_idempotent',
            'amount' => '7080.00',
            'currency' => 'DOP',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_test_idempotent',
            'amount' => '7080.00',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_idempotent_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_idempotent',
                    'object' => 'payment_intent',
                    'amount' => 708000,
                    'currency' => 'dop',
                    'status' => 'succeeded',
                ]
            ]
        ];

        // First call
        $response1 = $this->postWebhook($payload);
        $response1->assertOk();

        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);

        // Second call (duplicate)
        $response2 = $this->postWebhook($payload);
        $response2->assertOk();

        // Ensure nothing changed, state remains Paid, no extra entries created.
        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertEquals(1, PaymentAttempt::where('payment_id', $payment->id)->count());
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = [
            'id' => 'evt_invalid_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
        ];

        // Post with wrong secret, which creates an invalid signature header for whsec_test.
        $response = $this->postWebhook($payload, 'whsec_wrong_secret');

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Invalid signature');
    }

    public function test_double_payment_prevented(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        // Create two overlapping reservations for the same vehicle
        $reservationA = Reservation::create([
            'reservation_number' => 'RES-DOUBLE-A',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $reservationB = Reservation::create([
            'reservation_number' => 'RES-DOUBLE-B',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00', // exact same overlap
            'total_amount' => '7080.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $paymentA = Payment::create([
            'reservation_id' => $reservationA->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_double_a',
            'amount' => '7080.00',
            'currency' => 'DOP',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        PaymentAttempt::create([
            'payment_id' => $paymentA->id,
            'reservation_id' => $reservationA->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_test_double_a',
            'amount' => '7080.00',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $paymentB = Payment::create([
            'reservation_id' => $reservationB->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_double_b',
            'amount' => '7080.00',
            'currency' => 'DOP',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        PaymentAttempt::create([
            'payment_id' => $paymentB->id,
            'reservation_id' => $reservationB->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_test_double_b',
            'amount' => '7080.00',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        // 1. Post webhook for A -> should succeed
        $payloadA = [
            'id' => 'evt_double_a',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_double_a',
                    'object' => 'payment_intent',
                    'amount' => 708000,
                    'currency' => 'dop',
                    'status' => 'succeeded',
                ]
            ]
        ];

        $responseA = $this->postWebhook($payloadA);
        $responseA->assertOk();

        $this->assertEquals(PaymentStatus::Paid, $paymentA->fresh()->status);
        $this->assertEquals(ReservationStatus::Paid, $reservationA->fresh()->reservation_status);

        // 2. Post webhook for B -> should fail because V is now blocked by A
        $payloadB = [
            'id' => 'evt_double_b',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_double_b',
                    'object' => 'payment_intent',
                    'amount' => 708000,
                    'currency' => 'dop',
                    'status' => 'succeeded',
                ]
            ]
        ];

        $responseB = $this->postWebhook($payloadB);
        // Should return 200 (since WebhookController catches domain exceptions and returns 200)
        $responseB->assertOk();

        // But reservation B should NOT be paid, and payment B status should remain pending/rolled-back.
        $this->assertEquals(PaymentStatus::Pending, $paymentB->fresh()->status);
        $this->assertEquals(ReservationStatus::PendingPayment, $reservationB->fresh()->reservation_status);
    }
}
