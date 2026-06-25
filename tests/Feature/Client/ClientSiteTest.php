<?php

namespace Tests\Feature\Client;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_deals(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Explora nuestras ofertas')
            ->assertSee('Reservar');
    }

    public function test_catalog_lists_rentable_vehicles(): void
    {
        Vehicle::factory()->create(['name' => 'Kia Rio Disponible']);
        Vehicle::factory()->create(['status' => 'maintenance']);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Kia Rio Disponible');
    }

    public function test_catalog_filters_by_category(): void
    {
        Vehicle::factory()->create(['name' => 'SUV Test', 'category' => 'suv']);
        Vehicle::factory()->create(['name' => 'Economy Test', 'category' => 'economy']);

        $this->get('/catalogo?category=suv')
            ->assertOk()
            ->assertSee('SUV Test')
            ->assertDontSee('Economy Test');
    }

    public function test_vehicle_detail_renders(): void
    {
        $vehicle = Vehicle::factory()->create(['name' => 'Honda CR-V Detalle']);
        $vehicle->features()->create(['name' => 'GPS']);

        $this->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Honda CR-V Detalle')
            ->assertSee('GPS');
    }
}
