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
use App\Services\Payments\PaymentService;
use App\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->walletService = $this->app->make(WalletService::class);
        $this->paymentService = $this->app->make(PaymentService::class);
        
        config([
            'rentcar.stripe.secret_key' => 'sk_test_key',
            'rentcar.stripe.webhook_secret' => 'whsec_test',
        ]);
    }

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

    private function postStripeWebhook(array $payload, string $secret = 'whsec_test')
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

    public function test_wallet_created_on_demand(): void
    {
        [$user, $customer] = $this->eligibleCustomer();

        $this->assertDatabaseMissing('wallets', ['customer_id' => $customer->id]);

        $wallet = $this->walletService->getWallet($customer);

        $this->assertDatabaseHas('wallets', [
            'customer_id' => $customer->id,
            'balance' => '0.00',
            'currency' => config('rentcar.currency'),
            'status' => 'active',
        ]);
        $this->assertEquals('0.00', $wallet->balance);
    }

    public function test_credit_increases_balance(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);

        $this->walletService->credit($wallet, '100.50', 'credit', 'Depósito inicial');

        $this->assertEquals('100.50', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => '100.50',
            'balance_after' => '100.50',
            'description' => 'Depósito inicial',
        ]);
    }

    public function test_debit_decreases_balance(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);
        $this->walletService->credit($wallet, '150.00', 'credit', 'Depósito');

        $this->walletService->debit($wallet, '50.25', 'debit', 'Compra');

        $this->assertEquals('99.75', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => '50.25',
            'balance_after' => '99.75',
            'description' => 'Compra',
        ]);
    }

    public function test_debit_fails_on_insufficient_funds(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);
        $this->walletService->credit($wallet, '20.00', 'credit', 'Depósito');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient funds in wallet.');

        $this->walletService->debit($wallet, '30.00', 'debit', 'Compra cara');
    }

    public function test_copay_reservation(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);
        $this->walletService->credit($wallet, '40.00', 'credit', 'Abono');

        $vehicle = Vehicle::factory()->create(['daily_price' => '30.00', 'deposit_amount' => '50.00']);
        $reservation = Reservation::create([
            'reservation_number' => 'RES-COP-99',
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

        // 1. Pagar 40.00 usando la billetera (wallet)
        $resultA = $this->paymentService->initiatePayment(
            reservation: $reservation,
            paymentType: 'rent',
            provider: 'wallet',
            captureMethod: 'automatic',
            customAmount: '40.00'
        );

        $this->assertEquals('paid', $resultA['status']);
        $this->assertEquals('0.00', $wallet->fresh()->balance);

        // La reserva debe continuar en pending ya que el total es 70.80 y solo se pagó 40.00
        $this->assertEquals(ReservationStatus::PendingPayment, $reservation->fresh()->reservation_status);
        $this->assertEquals(PaymentStatus::Pending, $reservation->fresh()->payment_status);

        // 2. Mockear llamada a Stripe para el monto restante (30.80)
        $remaining = $this->paymentService->resolveRemainingAmount($reservation, PaymentType::Rent);
        $this->assertEquals('30.80', $remaining);

        $mock = \Mockery::mock(\App\Services\Payments\PaymentGatewayInterface::class);
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(\App\Services\Payments\PaymentGatewayResponse::success(
                status: 'requires_action',
                provider: 'stripe',
                amountCents: 3080,
                currency: 'USD',
                providerPaymentId: 'pi_test_123',
                requiresAction: true,
                clientSecret: 'pi_test_123_secret_xyz',
                raw: ['client_secret' => 'pi_test_123_secret_xyz', 'id' => 'pi_test_123']
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        // Simulamos iniciar el pago por Stripe para la diferencia
        $resultB = $this->paymentService->initiatePayment(
            reservation: $reservation,
            paymentType: 'rent',
            provider: 'stripe',
            captureMethod: 'automatic'
        );

        $this->assertEquals('requires_action', $resultB['status']);
        $this->assertEquals('30.80', $resultB['amount']);

        // Simulamos webhook exitoso de Stripe para completar el saldo restante
        $payload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $resultB['payment_intent_id'],
                    'status' => 'succeeded',
                    'amount' => 3080,
                    'currency' => 'usd',
                ]
            ]
        ];

        $webhookResponse = $this->postStripeWebhook($payload);
        $webhookResponse->assertOk();

        // Ahora el pago total cubrió los 70.80 de la reserva, por lo tanto debe marcarse como pagada y vehículo ocupado
        $this->assertEquals(ReservationStatus::Paid, $reservation->fresh()->reservation_status);
        $this->assertEquals(PaymentStatus::Paid, $reservation->fresh()->payment_status);
    }

    public function test_wallet_topup_flow(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);
        $this->assertEquals('0.00', $wallet->balance);

        // Mockear llamada a Stripe para recarga
        $mock = \Mockery::mock(\App\Services\Payments\PaymentGatewayInterface::class);
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(\App\Services\Payments\PaymentGatewayResponse::success(
                status: 'requires_action',
                provider: 'stripe',
                amountCents: 5000,
                currency: 'USD',
                providerPaymentId: 'pi_topup_123',
                requiresAction: true,
                clientSecret: 'pi_topup_123_secret_xyz',
                raw: ['client_secret' => 'pi_topup_123_secret_xyz', 'id' => 'pi_topup_123']
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        // 1. Iniciar recarga de $50.00 vía Stripe
        $response = $this->actingAs($user)->postJson('/api/v1/customer/wallet/topup', [
            'amount' => '50.00',
            'provider' => 'stripe',
        ]);

        $response->assertOk()
            ->assertJsonPath('amount', '50.00')
            ->assertJsonPath('status', 'requires_action');

        $paymentIntentId = $response->json('payment_intent_id');
        $this->assertNotNull($paymentIntentId);

        // 2. Ejecutar webhook de Stripe simulando éxito
        $payload = [
            'id' => 'evt_topup_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'status' => 'succeeded',
                    'amount' => 5000,
                    'currency' => 'usd',
                ]
            ]
        ];

        $webhookResponse = $this->postStripeWebhook($payload);
        $webhookResponse->assertOk();

        // 3. Verificar que el saldo de la billetera incrementó a $50.00
        $this->assertEquals('50.00', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => '50.00',
            'balance_after' => '50.00',
        ]);
    }

    public function test_admin_manual_adjustment(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);

        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        // Ajuste positivo
        $responseA = $this->actingAs($adminUser)->postJson("/api/v1/admin/customers/{$customer->id}/wallet/adjust", [
            'amount' => '150.00',
            'description' => 'Bono de bienvenida',
        ]);

        $responseA->assertOk()
            ->assertJsonPath('balance', '150.00')
            ->assertJsonPath('type', 'manual_adjustment')
            ->assertJsonPath('amount', '150.00');

        $this->assertEquals('150.00', $wallet->fresh()->balance);

        // Ajuste negativo
        $responseB = $this->actingAs($adminUser)->postJson("/api/v1/admin/customers/{$customer->id}/wallet/adjust", [
            'amount' => '-40.50',
            'description' => 'Cargo por daños menores',
        ]);

        $responseB->assertOk()
            ->assertJsonPath('balance', '109.50')
            ->assertJsonPath('type', 'manual_adjustment')
            ->assertJsonPath('amount', '40.50');

        $this->assertEquals('109.50', $wallet->fresh()->balance);
    }

    public function test_wallet_reconciliation(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $wallet = $this->walletService->getWallet($customer);

        $this->walletService->credit($wallet, '100.00', 'credit', 'In');
        $this->walletService->debit($wallet, '30.00', 'debit', 'Out');
        $this->walletService->adjust($wallet, '-20.00', 'Adjust Out');

        $this->assertEquals('50.00', $wallet->fresh()->balance);

        // Reconciliación limpia
        $this->assertTrue($this->walletService->reconcile($wallet));

        // Forzar alteración de saldo en BD directamente sin transacción log para simular discrepancia
        $wallet->update(['balance' => '999.00']);
        $this->assertEquals('999.00', $wallet->fresh()->balance);

        // La reconciliación debe detectar y corregir el saldo a 50.00, retornando false
        $this->assertFalse($this->walletService->reconcile($wallet));
        $this->assertEquals('50.00', $wallet->fresh()->balance);
    }
}
