<?php

namespace Tests\Feature\Delivery;

use App\Enums\DeliveryRequestStatus;
use App\Enums\DeliveryRequestType;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\DeliveryPickupPoint;
use App\Models\DeliveryRequest;
use App\Models\DeliveryZone;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DeliveryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryService $deliveryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->deliveryService = $this->app->make(DeliveryService::class);
    }

    private function createCustomerUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $customer = $user->customer()->create([
            'birthdate' => '1990-01-01',
            'verification_status' => 'verified',
        ]);
        return [$user, $customer];
    }

    private function createTestReservation(Customer $customer): Reservation
    {
        $location = \App\Models\Location::create([
            'name' => 'SDQ Airport Office',
            'type' => 'airport',
            'is_active' => true,
        ]);
        $vehicle = Vehicle::factory()->create(['daily_price' => '30.00', 'deposit_amount' => '50.00']);
        return Reservation::create([
            'reservation_number' => 'RES-DELIV-123',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_location_id' => $location->id,
            'return_location_id' => $location->id,
            'pickup_type' => 'office',
            'return_type' => 'office',
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
            'payment_status' => 'paid',
            'reservation_status' => ReservationStatus::Confirmed,
        ]);
    }

    public function test_geofencing_detects_inside_outside_points(): void
    {
        // Polígono GeoJSON cuadrado que cubre lat 18.0 a 19.0 y lng -70.0 a -69.0
        // Nota GeoJSON usa [longitude, latitude]
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [-70.0, 18.0],
                    [-69.0, 18.0],
                    [-69.0, 19.0],
                    [-70.0, 19.0],
                    [-70.0, 18.0]
                ]
            ]
        ];

        $zone = DeliveryZone::create([
            'name' => 'Zona Metropolitana',
            'polygon' => $polygon,
            'origin_latitude' => 18.5,
            'origin_longitude' => -69.5,
            'allows_home_delivery' => true,
            'base_fee' => '10.00',
            'free_radius_km' => '5.00',
            'price_per_km' => '2.00',
            'max_distance_km' => '30.00',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Punto dentro: lat 18.5, lng -69.5
        $quoteInside = $this->deliveryService->quoteDelivery('home', 18.5, -69.5);
        $this->assertTrue($quoteInside['eligible']);
        $this->assertEquals($zone->id, $quoteInside['zone_id']);

        // Punto fuera: lat 17.5, lng -68.5
        $quoteOutside = $this->deliveryService->quoteDelivery('home', 17.5, -68.5);
        $this->assertFalse($quoteOutside['eligible']);
        $this->assertEquals('out_of_coverage', $quoteOutside['reason']);
    }

    public function test_haversine_distance_fee_calculation(): void
    {
        // Zona con origen en 18.47, -69.89
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [-70.0, 18.0],
                    [-69.0, 18.0],
                    [-69.0, 19.0],
                    [-70.0, 19.0],
                    [-70.0, 18.0]
                ]
            ]
        ];

        $zone = DeliveryZone::create([
            'name' => 'Zona Santo Domingo',
            'polygon' => $polygon,
            'origin_latitude' => 18.47,
            'origin_longitude' => -69.89,
            'allows_home_delivery' => true,
            'base_fee' => '10.00',
            'free_radius_km' => '5.00', // 5 km gratis
            'price_per_km' => '2.00',    // $2 por km excedente
            'max_distance_km' => '50.00',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        // Cotizar un punto a una distancia calculable (ej: lat 18.47, lng -69.80, aprox 9.5 km)
        $quote = $this->deliveryService->quoteDelivery('home', 18.47, -69.80);
        $this->assertTrue($quote['eligible']);
        
        $distance = $quote['distance_km'];
        $excessDistance = max(0.0, $distance - 5.00);
        $expectedFee = 10.00 + ($excessDistance * 2.00);

        $this->assertEquals(number_format($expectedFee, 2, '.', ''), $quote['fee']);
    }

    public function test_quote_delivery_suggests_nearest_points_on_failure(): void
    {
        // Crear puntos comerciales de recogida activos
        $point1 = DeliveryPickupPoint::create([
            'name' => 'Punto Comercial Norte',
            'latitude' => 18.52,
            'longitude' => -69.91,
            'fee' => '5.00',
            'is_active' => true,
        ]);

        $point2 = DeliveryPickupPoint::create([
            'name' => 'Punto Comercial Sur',
            'latitude' => 18.42,
            'longitude' => -69.87,
            'fee' => '3.00',
            'is_active' => true,
        ]);

        // Cotizar un punto que cae completamente fuera de cobertura (sin zonas creadas)
        $response = $this->postJson('/api/v1/delivery/quote', [
            'type' => 'home',
            'latitude' => 18.0,
            'longitude' => -70.5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('eligible', false)
            ->assertJsonPath('reason', 'out_of_coverage')
            ->assertJsonCount(2, 'suggested_pickup_points');
    }

    public function test_driver_assignment_and_status_transitions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $driver = User::factory()->create();
        $driver->assignRole('driver');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        // Poner la reservación en un estado bloqueante inicial para el flujo logístico
        $reservation->update(['reservation_status' => ReservationStatus::DeliveryAssigned]);

        $deliveryRequest = DeliveryRequest::create([
            'reservation_id' => $reservation->id,
            'direction' => 'pickup',
            'type' => 'home',
            'address' => 'Av. Anacaona, Bella Vista',
            'latitude' => 18.45,
            'longitude' => -69.93,
            'fee' => '15.00',
            'scheduled_date' => '2026-07-01',
            'status' => DeliveryRequestStatus::Requested->value,
        ]);

        // 1. Asignar conductor (Driver)
        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/deliveries/{$deliveryRequest->id}/assign", [
                'driver_id' => $driver->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.assigned_to', $driver->id);

        // 2. Transición a en tránsito
        $response = $this->actingAs($admin)
            ->putJson("/api/v1/admin/deliveries/{$deliveryRequest->id}/status", [
                'status' => 'in_transit',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_transit');

        // 3. Confirmar entrega (delivered) -> Debe cambiar estado de reservación a delivered
        $response = $this->actingAs($admin)
            ->putJson("/api/v1/admin/deliveries/{$deliveryRequest->id}/status", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        $reservation->refresh();
        $this->assertEquals(ReservationStatus::Delivered, $reservation->reservation_status);
    }
}
