<?php

namespace Tests\Feature\Reservation;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireHoldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['rentcar.reservation_hold_minutes' => 30]);
    }

    private function pendingReservation(string $createdAt): Reservation
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $reservation = Reservation::create([
            'reservation_number' => 'RC-TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-08-01 10:00:00',
            'end_datetime' => '2026-08-03 10:00:00',
            'pickup_type' => 'office',
            'base_price' => '6000.00',
            'total_amount' => '7080.00',
            'tax_amount' => '1080.00',
            'deposit_amount' => '5000.00',
            'currency' => 'DOP',
            'payment_status' => 'pending',
            'reservation_status' => 'pending_payment',
            'contract_status' => 'none',
        ]);

        // created_at no es fillable; lo fijamos directamente (sin tocar updated_at).
        Reservation::where('id', $reservation->id)->update(['created_at' => $createdAt]);

        return $reservation->fresh();
    }

    public function test_expires_stale_pending_holds(): void
    {
        $stale = $this->pendingReservation(now()->subHour()->toDateTimeString());
        $fresh = $this->pendingReservation(now()->subMinutes(5)->toDateTimeString());

        $expired = app(ReservationService::class)->expireStaleHolds();

        $this->assertSame(1, $expired);
        $this->assertSame('expired', $stale->fresh()->reservation_status->value);
        $this->assertSame('pending_payment', $fresh->fresh()->reservation_status->value);

        $this->assertDatabaseHas('reservation_status_logs', [
            'reservation_id' => $stale->id,
            'to_status' => 'expired',
        ]);
    }

    public function test_command_runs_and_reports_count(): void
    {
        $this->pendingReservation(now()->subHours(2)->toDateTimeString());

        $this->artisan('rentcar:expire-reservation-holds')
            ->expectsOutputToContain('Reservas expiradas: 1')
            ->assertExitCode(0);
    }
}
