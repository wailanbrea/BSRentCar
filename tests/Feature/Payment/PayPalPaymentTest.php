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
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config([
            'rentcar.paypal.client_id' => 'test_client_id',
            'rentcar.paypal.client_secret' => 'test_client_secret',
            'rentcar.paypal.webhook_id' => 'test_webhook_id',
            'rentcar.paypal.sandbox' => true,
        ]);
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
     * Fake PayPal REST API requests safely in one batch.
     */
    private function fakePayPalRequests(array $extraFakes = []): void
    {
        Http::fake(array_merge([
            '*/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_access_token_123',
                'expires_in' => 3600,
            ], 200),
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ], $extraFakes));
    }

    public function test_create_intent_returns_approve_url(): void
    {
        $this->fakePayPalRequests([
            '*/v2/checkout/orders' => Http::response([
                'id' => '5O_TEST_ORDER_123',
                'status' => 'CREATED',
                'links' => [
                    [
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=5O_TEST_ORDER_123',
                        'rel' => 'approve',
                        'method' => 'GET'
                    ]
                ]
            ], 201),
        ]);

        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create(['daily_price' => '30.00', 'deposit_amount' => '50.00']);

        $reservation = Reservation::create([
            'reservation_number' => 'RES-PP-123',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'base_price' => '60.00',
            'delivery_fee' => '0.00',
            'insurance_fee' => '0.00',
            'deposit_amount' => '50.00',
            'discount_amount' => '0.00',
            'tax_amount' => '10.80',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/payments/paypal/create-intent', [
            'reservation_id' => $reservation->id,
            'payment_type' => 'rent',
        ]);

        $response->assertOk()
            ->assertJsonPath('approve_url', 'https://www.sandbox.paypal.com/checkoutnow?token=5O_TEST_ORDER_123')
            ->assertJsonPath('payment_intent_id', '5O_TEST_ORDER_123')
            ->assertJsonPath('status', 'pending') // mapOrderStatus maps CREATED -> pending
            ->assertJsonPath('amount', '70.80');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'amount' => '70.80',
            'status' => PaymentStatus::Pending->value,
            'provider_payment_id' => '5O_TEST_ORDER_123',
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'reservation_id' => $reservation->id,
            'status' => PaymentAttemptStatus::Initiated->value,
            'provider_reference' => '5O_TEST_ORDER_123',
        ]);
    }

    public function test_create_intent_fails_for_non_payable_reservation(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-PP-124',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'paid',
            'reservation_status' => ReservationStatus::Paid, // Already Paid!
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/payments/paypal/create-intent', [
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
            'reservation_number' => 'RES-PP-125',
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $response = $this->actingAs($userA)->postJson('/api/v1/payments/paypal/create-intent', [
            'reservation_id' => $reservationOfB->id,
            'payment_type' => 'rent',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_webhook_completed_marks_reservation_paid(): void
    {
        $this->fakePayPalRequests();

        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-PP-126',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_payment_id' => '5O_TEST_ORDER_126',
            'amount' => '70.80',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_TEST_ORDER_126',
            'amount' => '70.80',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_completed_123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'cap_completed_123',
                'custom_id' => (string) $payment->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '70.80',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhooks/paypal', $payload, [
            'paypal-transmission-id' => 'trans_123',
            'paypal-transmission-time' => 'time_123',
            'paypal-cert-url' => 'url_123',
            'paypal-auth-algo' => 'algo_123',
            'paypal-transmission-sig' => 'sig_123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Paid->value,
            'provider_capture_id' => 'cap_completed_123',
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

    public function test_webhook_denied_keeps_reservation_pending(): void
    {
        $this->fakePayPalRequests();

        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-PP-127',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_payment_id' => '5O_TEST_ORDER_127',
            'amount' => '70.80',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_TEST_ORDER_127',
            'amount' => '70.80',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_denied_123',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => 'cap_denied_123',
                'custom_id' => (string) $payment->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '70.80',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhooks/paypal', $payload, [
            'paypal-transmission-id' => 'trans_123',
            'paypal-transmission-time' => 'time_123',
            'paypal-cert-url' => 'url_123',
            'paypal-auth-algo' => 'algo_123',
            'paypal-transmission-sig' => 'sig_123',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Failed->value,
        ]);

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => PaymentAttemptStatus::Failed->value,
            'error_code' => 'PAYMENT_DENIED',
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment->value,
        ]);
    }

    public function test_webhook_idempotent_on_duplicate(): void
    {
        $this->fakePayPalRequests();

        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RES-PP-128',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_payment_id' => '5O_TEST_ORDER_128',
            'amount' => '70.80',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_TEST_ORDER_128',
            'amount' => '70.80',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $payload = [
            'id' => 'evt_completed_128',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'cap_completed_128',
                'custom_id' => (string) $payment->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '70.80',
                ]
            ]
        ];

        $headers = [
            'paypal-transmission-id' => 'trans_123',
            'paypal-transmission-time' => 'time_123',
            'paypal-cert-url' => 'url_123',
            'paypal-auth-algo' => 'algo_123',
            'paypal-transmission-sig' => 'sig_123',
        ];

        // Call 1
        $this->postJson('/api/v1/payments/webhooks/paypal', $payload, $headers)->assertOk();
        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);

        // Call 2
        $this->postJson('/api/v1/payments/webhooks/paypal', $payload, $headers)->assertOk();
        $this->assertEquals(PaymentStatus::Paid, $payment->fresh()->status);

        $this->assertEquals(1, PaymentAttempt::where('payment_id', $payment->id)->count());
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        // Fake Verification Failure
        $this->fakePayPalRequests([
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'FAILURE',
            ], 200),
        ]);

        $payload = [
            'id' => 'evt_invalid_123',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ];

        $response = $this->postJson('/api/v1/payments/webhooks/paypal', $payload, [
            'paypal-transmission-id' => 'trans_123',
            'paypal-transmission-time' => 'time_123',
            'paypal-cert-url' => 'url_123',
            'paypal-auth-algo' => 'algo_123',
            'paypal-transmission-sig' => 'sig_invalid',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Invalid PayPal webhook signature.');
    }

    public function test_double_payment_prevented(): void
    {
        $this->fakePayPalRequests();

        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();

        // Two overlapping reservations
        $reservationA = Reservation::create([
            'reservation_number' => 'RES-PP-DOUBLE-A',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $reservationB = Reservation::create([
            'reservation_number' => 'RES-PP-DOUBLE-B',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'total_amount' => '70.80',
            'currency' => 'USD',
            'payment_status' => 'pending',
            'reservation_status' => ReservationStatus::PendingPayment,
        ]);

        $paymentA = Payment::create([
            'reservation_id' => $reservationA->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_payment_id' => '5O_TEST_DOUBLE_A',
            'amount' => '70.80',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        PaymentAttempt::create([
            'payment_id' => $paymentA->id,
            'reservation_id' => $reservationA->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_TEST_DOUBLE_A',
            'amount' => '70.80',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $paymentB = Payment::create([
            'reservation_id' => $reservationB->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_payment_id' => '5O_TEST_DOUBLE_B',
            'amount' => '70.80',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'payment_type' => PaymentType::Rent->value,
        ]);

        PaymentAttempt::create([
            'payment_id' => $paymentB->id,
            'reservation_id' => $reservationB->id,
            'customer_id' => $customer->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_TEST_DOUBLE_B',
            'amount' => '70.80',
            'status' => PaymentAttemptStatus::Initiated->value,
        ]);

        $headers = [
            'paypal-transmission-id' => 'trans_123',
            'paypal-transmission-time' => 'time_123',
            'paypal-cert-url' => 'url_123',
            'paypal-auth-algo' => 'algo_123',
            'paypal-transmission-sig' => 'sig_123',
        ];

        // 1. Post webhook for A -> should succeed
        $payloadA = [
            'id' => 'evt_double_a',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'cap_double_a',
                'custom_id' => (string) $paymentA->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '70.80',
                ]
            ]
        ];

        $this->postJson('/api/v1/payments/webhooks/paypal', $payloadA, $headers)->assertOk();

        $this->assertEquals(PaymentStatus::Paid, $paymentA->fresh()->status);
        $this->assertEquals(ReservationStatus::Paid, $reservationA->fresh()->reservation_status);

        // 2. Post webhook for B -> should fail to mark paid because A has now blocked the vehicle
        $payloadB = [
            'id' => 'evt_double_b',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'cap_double_b',
                'custom_id' => (string) $paymentB->id,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '70.80',
                ]
            ]
        ];

        $this->postJson('/api/v1/payments/webhooks/paypal', $payloadB, $headers)->assertOk();

        $this->assertEquals(PaymentStatus::Pending, $paymentB->fresh()->status);
        $this->assertEquals(ReservationStatus::PendingPayment, $reservationB->fresh()->reservation_status);
    }
}
