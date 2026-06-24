<?php

namespace Tests\Feature\Vehicle;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_only_rentable_vehicles(): void
    {
        Vehicle::factory()->create(['status' => VehicleStatus::Available->value]);
        Vehicle::factory()->create(['status' => VehicleStatus::Maintenance->value]);

        $this->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_returns_vehicle_detail(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->features()->create(['name' => 'GPS']);

        $this->getJson("/api/v1/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $vehicle->id)
            ->assertJsonPath('data.features.0.name', 'GPS');
    }

    public function test_show_returns_404_for_missing_vehicle(): void
    {
        $this->getJson('/api/v1/vehicles/9999')->assertNotFound();
    }

    public function test_filter_by_category_and_transmission(): void
    {
        Vehicle::factory()->create([
            'category' => VehicleCategory::Suv->value,
            'transmission' => Transmission::Automatic->value,
        ]);
        Vehicle::factory()->create([
            'category' => VehicleCategory::Economy->value,
            'transmission' => Transmission::Manual->value,
        ]);

        $this->getJson('/api/v1/vehicles?category=suv&transmission=automatic')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'suv');
    }

    public function test_filter_by_price_range_and_seats(): void
    {
        Vehicle::factory()->create(['daily_price' => '2000.00', 'seats' => 5]);
        Vehicle::factory()->create(['daily_price' => '9000.00', 'seats' => 2]);

        $this->getJson('/api/v1/vehicles?price_min=1000&price_max=3000&seats_min=4')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.daily_price', '2000.00');
    }

    public function test_filter_by_date_excludes_blocked_vehicle(): void
    {
        $free = Vehicle::factory()->create();
        $blocked = Vehicle::factory()->create();

        // Bloqueo que solapa con el rango solicitado.
        $blocked->availabilityBlocks()->create([
            'start_datetime' => '2026-07-01 08:00:00',
            'end_datetime' => '2026-07-05 08:00:00',
            'reason' => 'maintenance',
        ]);

        $response = $this->getJson('/api/v1/vehicles?start_date=2026-07-02&end_date=2026-07-04')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($free->id, $response->json('data.0.id'));
    }

    public function test_filter_by_date_includes_vehicle_with_non_overlapping_block(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->availabilityBlocks()->create([
            'start_datetime' => '2026-08-01 08:00:00',
            'end_datetime' => '2026-08-05 08:00:00',
            'reason' => 'blocked',
        ]);

        $this->getJson('/api/v1/vehicles?start_date=2026-07-02&end_date=2026-07-04')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_availability_endpoint_returns_quote_when_available(): void
    {
        $vehicle = Vehicle::factory()->create([
            'daily_price' => '3000.00',
            'deposit_amount' => '5000.00',
            'currency' => 'DOP',
        ]);

        $this->getJson("/api/v1/vehicles/{$vehicle->id}/availability?start_date=2026-07-01&end_date=2026-07-03")
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('quote.days', 2)
            ->assertJsonPath('quote.base_price', '6000.00')
            ->assertJsonPath('quote.deposit_amount', '5000.00');
    }

    public function test_availability_endpoint_returns_false_when_blocked(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->availabilityBlocks()->create([
            'start_datetime' => '2026-07-01 00:00:00',
            'end_datetime' => '2026-07-10 00:00:00',
            'reason' => 'maintenance',
        ]);

        $this->getJson("/api/v1/vehicles/{$vehicle->id}/availability?start_date=2026-07-02&end_date=2026-07-04")
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('quote', null);
    }
}
