<?php

namespace Tests\Feature\Reservation;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_create_reservation_returns_quote_with_itbis(): void
    {
        [$user] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create(['daily_price' => '3000.00', 'deposit_amount' => '5000.00']);

        $response = $this->actingAs($user)->postJson('/api/v1/customer/reservations', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-03T10:00:00',
            'pickup_type' => 'office',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reservation_status', 'pending_payment')
            ->assertJsonPath('data.totals.base_price', '6000.00')   // 3000 * 2 días
            ->assertJsonPath('data.totals.tax_amount', '1080.00')   // ITBIS 18% de 6000
            ->assertJsonPath('data.totals.total_amount', '7080.00') // 6000 + 1080
            ->assertJsonPath('data.totals.deposit_amount', '5000.00');
    }

    public function test_create_reservation_blocked_when_not_eligible(): void
    {
        // Cliente sin licencia aprobada.
        $user = User::factory()->create();
        $user->assignRole('customer');
        $user->customer()->create(['birthdate' => '1990-01-01']);

        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/customer/reservations', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-03T10:00:00',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'CUSTOMER_NOT_ELIGIBLE')
            ->assertJsonFragment(['reasons' => ['license_not_approved']]);
    }

    public function test_create_reservation_conflict_when_vehicle_blocked(): void
    {
        [$user] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $vehicle->availabilityBlocks()->create([
            'start_datetime' => '2026-07-01 00:00:00',
            'end_datetime' => '2026-07-10 00:00:00',
            'reason' => 'maintenance',
        ]);

        $this->actingAs($user)->postJson('/api/v1/customer/reservations', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-02T10:00:00',
            'end_datetime' => '2026-07-04T10:00:00',
        ])->assertStatus(409)->assertJsonPath('code', 'VEHICLE_NOT_AVAILABLE');
    }

    public function test_double_booking_prevented_at_payment(): void
    {
        [, $customerA] = $this->eligibleCustomer();
        [, $customerB] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $service = app(ReservationService::class);

        $data = [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-05T10:00:00',
        ];

        // Ambas reservas se crean en pending_payment (no bloquean todavía).
        $resA = $service->createForCustomer($customerA, $data);
        $resB = $service->createForCustomer($customerB, $data);

        // A confirma el pago → pasa a 'paid' (bloqueante).
        $service->markAsPaid($resA->fresh());
        $this->assertSame('paid', $resA->fresh()->reservation_status->value);

        // B intenta confirmar el pago → revalidación anti-doble-reserva lo rechaza.
        $this->expectException(\App\Exceptions\VehicleNotAvailableException::class);
        $service->markAsPaid($resB->fresh());
    }

    public function test_touching_ranges_do_not_conflict(): void
    {
        [, $customerA] = $this->eligibleCustomer();
        [, $customerB] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $service = app(ReservationService::class);

        $resA = $service->createForCustomer($customerA, [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-03T10:00:00',
        ]);
        $service->markAsPaid($resA->fresh());

        // Rango que empieza justo cuando termina el anterior: NO solapa.
        $resB = $service->createForCustomer($customerB, [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-03T10:00:00',
            'end_datetime' => '2026-07-05T10:00:00',
        ]);
        $paid = $service->markAsPaid($resB->fresh());

        $this->assertSame('paid', $paid->reservation_status->value);
    }

    public function test_owner_can_view_reservation_others_cannot(): void
    {
        [$userA, $customerA] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $reservation = app(ReservationService::class)->createForCustomer($customerA, [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-03T10:00:00',
        ]);

        $this->actingAs($userA)
            ->getJson("/api/v1/customer/reservations/{$reservation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $reservation->id);

        [$userB] = $this->eligibleCustomer();
        $this->actingAs($userB)
            ->getJson("/api/v1/customer/reservations/{$reservation->id}")
            ->assertForbidden();
    }

    public function test_admin_mark_paid_endpoint_blocks_double_booking(): void
    {
        [, $customerA] = $this->eligibleCustomer();
        [, $customerB] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $service = app(ReservationService::class);

        $data = [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-05T10:00:00',
        ];
        $resA = $service->createForCustomer($customerA, $data);
        $resB = $service->createForCustomer($customerB, $data);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$resA->id}/mark-paid")
            ->assertOk()
            ->assertJsonPath('data.reservation_status', 'paid');

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$resB->id}/mark-paid")
            ->assertStatus(409)
            ->assertJsonPath('code', 'VEHICLE_NOT_AVAILABLE');
    }

    public function test_customer_can_cancel_reservation(): void
    {
        [$user, $customer] = $this->eligibleCustomer();
        $vehicle = Vehicle::factory()->create();
        $reservation = app(ReservationService::class)->createForCustomer($customer, [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-07-01T10:00:00',
            'end_datetime' => '2026-07-03T10:00:00',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/customer/reservations/{$reservation->id}/cancel", ['reason' => 'cambio de planes'])
            ->assertOk()
            ->assertJsonPath('data.reservation_status', 'cancelled');

        $this->assertDatabaseHas('reservation_status_logs', [
            'reservation_id' => $reservation->id,
            'to_status' => 'cancelled',
        ]);
    }
}
