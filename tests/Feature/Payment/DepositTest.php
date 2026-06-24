<?php

namespace Tests\Feature\Payment;

use App\Enums\DepositTransactionStatus;
use App\Enums\DepositTransactionType;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\DepositTransaction;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DepositService;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayResponse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    private DepositService $depositService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->depositService = $this->app->make(DepositService::class);
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

    private function createTestReservation(Customer $customer): Reservation
    {
        $vehicle = Vehicle::factory()->create(['daily_price' => '30.00', 'deposit_amount' => '50.00']);
        return Reservation::create([
            'reservation_number' => 'RES-DEP-99',
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
            'reservation_status' => \App\Enums\ReservationStatus::PendingPayment,
        ]);
    }

    public function test_create_hold_stripe(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        $mock = \Mockery::mock(PaymentGatewayInterface::class);
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(PaymentGatewayResponse::success(
                status: 'requires_action',
                provider: 'stripe',
                amountCents: 5000,
                currency: 'USD',
                providerPaymentId: 'pi_hold_123',
                requiresAction: true,
                clientSecret: 'pi_hold_123_secret_xyz',
                raw: ['client_secret' => 'pi_hold_123_secret_xyz', 'id' => 'pi_hold_123']
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        $deposit = $this->depositService->createHold($reservation, 'stripe');

        $this->assertDatabaseHas('deposit_transactions', [
            'reservation_id' => $reservation->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_hold_123',
            'type' => DepositTransactionType::Hold->value,
            'amount' => '50.00',
            'status' => DepositTransactionStatus::Authorized->value,
        ]);
        $this->assertNotNull($deposit->expires_at);
    }

    public function test_create_hold_paypal(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        $mock = \Mockery::mock(PaymentGatewayInterface::class);
        $mock->shouldReceive('createPayment')
            ->once()
            ->andReturn(PaymentGatewayResponse::success(
                status: 'requires_action',
                provider: 'paypal',
                amountCents: 5000,
                currency: 'USD',
                providerPaymentId: '5O_HOLD_123',
                requiresAction: true,
                clientSecret: 'https://approve.paypal.com/hold',
                raw: ['id' => '5O_HOLD_123', 'status' => 'CREATED']
            ));
        $this->app->instance('payment.gateway.paypal', $mock);

        $deposit = $this->depositService->createHold($reservation, 'paypal');

        $this->assertDatabaseHas('deposit_transactions', [
            'reservation_id' => $reservation->id,
            'provider' => 'paypal',
            'provider_reference' => '5O_HOLD_123',
            'type' => DepositTransactionType::Hold->value,
            'amount' => '50.00',
            'status' => DepositTransactionStatus::Authorized->value,
        ]);
    }

    public function test_capture_full_deposit(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        $deposit = DepositTransaction::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_hold_123',
            'type' => DepositTransactionType::Hold,
            'amount' => '50.00',
            'currency' => 'USD',
            'status' => DepositTransactionStatus::Authorized,
            'expires_at' => now()->addDays(7),
        ]);

        // Mock Stripe capture
        $mock = \Mockery::mock(PaymentGatewayInterface::class);
        $mock->shouldReceive('capturePayment')
            ->once()
            ->with('pi_hold_123', 5000)
            ->andReturn(PaymentGatewayResponse::success(
                status: 'paid',
                provider: 'stripe',
                amountCents: 5000,
                providerPaymentId: 'pi_hold_123',
                raw: ['id' => 'pi_hold_123', 'status' => 'succeeded']
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        // Llamar a través del endpoint de Admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/deposits/{$deposit->id}/capture", [
            'amount' => '50.00',
            'reason' => 'Daños en el parachoques delantero',
        ]);

        $response->assertOk()
            ->assertJsonPath('captured_amount', '50.00')
            ->assertJsonPath('type', DepositTransactionType::Capture->value)
            ->assertJsonPath('status', DepositTransactionStatus::Captured->value);

        $this->assertEquals(DepositTransactionStatus::Captured, $deposit->fresh()->status);
        $this->assertEquals('50.00', $deposit->fresh()->captured_amount);
    }

    public function test_capture_partial_deposit(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        $deposit = DepositTransaction::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_hold_123',
            'type' => DepositTransactionType::Hold,
            'amount' => '50.00',
            'currency' => 'USD',
            'status' => DepositTransactionStatus::Authorized,
            'expires_at' => now()->addDays(7),
        ]);

        // Mock Stripe partial capture ($20.00)
        $mock = \Mockery::mock(PaymentGatewayInterface::class);
        $mock->shouldReceive('capturePayment')
            ->once()
            ->with('pi_hold_123', 2000)
            ->andReturn(PaymentGatewayResponse::success(
                status: 'paid',
                provider: 'stripe',
                amountCents: 2000,
                providerPaymentId: 'pi_hold_123',
                raw: ['id' => 'pi_hold_123', 'status' => 'succeeded']
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/deposits/{$deposit->id}/capture", [
            'amount' => '20.00',
            'reason' => 'Combustible faltante 2 octavos',
        ]);

        $response->assertOk()
            ->assertJsonPath('captured_amount', '20.00')
            ->assertJsonPath('type', DepositTransactionType::PartialCapture->value)
            ->assertJsonPath('status', DepositTransactionStatus::Captured->value);

        $this->assertEquals(DepositTransactionStatus::Captured, $deposit->fresh()->status);
        $this->assertEquals('20.00', $deposit->fresh()->captured_amount);
    }

    public function test_release_deposit(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        $deposit = DepositTransaction::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_hold_123',
            'type' => DepositTransactionType::Hold,
            'amount' => '50.00',
            'currency' => 'USD',
            'status' => DepositTransactionStatus::Authorized,
            'expires_at' => now()->addDays(7),
        ]);

        // Mock Stripe cancel/void
        $mock = \Mockery::mock(PaymentGatewayInterface::class);
        $mock->shouldReceive('cancelPayment')
            ->once()
            ->with('pi_hold_123')
            ->andReturn(PaymentGatewayResponse::success(
                status: 'cancelled',
                provider: 'stripe',
                amountCents: 0,
                providerPaymentId: 'pi_hold_123'
            ));
        $this->app->instance('payment.gateway.stripe', $mock);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/deposits/{$deposit->id}/release", [
            'reason' => 'Devolución del vehículo sin daños',
        ]);

        $response->assertOk()
            ->assertJsonPath('type', DepositTransactionType::Release->value)
            ->assertJsonPath('status', DepositTransactionStatus::Released->value);

        $this->assertEquals(DepositTransactionStatus::Released, $deposit->fresh()->status);
    }

    public function test_scheduler_detects_expiring_deposits(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $reservation = $this->createTestReservation($customer);

        // Depósito que expira en 6 horas
        $expiring = DepositTransaction::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_expiring_123',
            'type' => DepositTransactionType::Hold,
            'amount' => '50.00',
            'currency' => 'USD',
            'status' => DepositTransactionStatus::Authorized,
            'expires_at' => now()->addHours(6),
        ]);

        // Depósito que expira en 5 días (no debe detectarse)
        $safe = DepositTransaction::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'provider' => 'stripe',
            'provider_reference' => 'pi_safe_123',
            'type' => DepositTransactionType::Hold,
            'amount' => '50.00',
            'currency' => 'USD',
            'status' => DepositTransactionStatus::Authorized,
            'expires_at' => now()->addDays(5),
        ]);

        // Ejecutar comando de consola
        $exitCode = Artisan::call('rentcar:check-expired-deposits');

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Deposit #' . $expiring->id, $output);
        $this->assertStringNotContainsString('Deposit #' . $safe->id, $output);
    }
}
