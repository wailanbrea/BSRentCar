<?php

namespace Tests\Feature\Report;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
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

    private function setupReportData(): array
    {
        [$user, $customer] = $this->createCustomerUser();
        
        $location = Location::create([
            'name' => 'HQ office',
            'type' => 'office',
            'is_active' => true,
        ]);

        $vehicle1 = Vehicle::create([
            'name' => 'Toyota Corolla',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'category' => 'sedan',
            'transmission' => 'automatic',
            'seats' => 5,
            'doors' => 4,
            'fuel_type' => 'gasoline',
            'color' => 'white',
            'plate' => 'A123BC',
            'vin' => '1234567890VIN',
            'daily_price' => '50.00',
            'deposit_amount' => '100.00',
            'currency' => 'USD',
            'mileage' => 15000,
            'location_id' => $location->id,
            'status' => 'available',
        ]);

        $vehicle2 = Vehicle::create([
            'name' => 'Honda CR-V',
            'brand' => 'Honda',
            'model' => 'CR-V',
            'year' => 2021,
            'category' => 'suv',
            'transmission' => 'automatic',
            'seats' => 5,
            'doors' => 5,
            'fuel_type' => 'gasoline',
            'color' => 'black',
            'plate' => 'X987YZ',
            'vin' => '9876543210VIN',
            'daily_price' => '80.00',
            'deposit_amount' => '150.00',
            'currency' => 'USD',
            'mileage' => 20000,
            'location_id' => $location->id,
            'status' => 'available',
        ]);

        // 1. Reservación Completada para Corolla (Ingresos y ocupación)
        // Ocupación: 2 días (01 al 03)
        Carbon::setTestNow('2026-07-01 12:00:00');
        $res1 = Reservation::create([
            'reservation_number' => 'RES-REP-1',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle1->id,
            'pickup_location_id' => $location->id,
            'return_location_id' => $location->id,
            'pickup_type' => 'office',
            'return_type' => 'office',
            'start_datetime' => '2026-07-01 10:00:00',
            'end_datetime' => '2026-07-03 10:00:00',
            'base_price' => '100.00',
            'delivery_fee' => '10.00',
            'insurance_fee' => '20.00',
            'deposit_amount' => '100.00',
            'discount_amount' => '0.00',
            'tax_amount' => '18.00',
            'total_amount' => '148.00',
            'currency' => 'USD',
            'payment_status' => PaymentStatus::Paid,
            'reservation_status' => ReservationStatus::Completed,
        ]);

        // 2. Reservación Cancelada para CR-V
        Carbon::setTestNow('2026-07-02 12:00:00');
        $res2 = Reservation::create([
            'reservation_number' => 'RES-REP-2',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle2->id,
            'pickup_location_id' => $location->id,
            'return_location_id' => $location->id,
            'pickup_type' => 'office',
            'return_type' => 'office',
            'start_datetime' => '2026-07-04 10:00:00',
            'end_datetime' => '2026-07-05 10:00:00',
            'base_price' => '80.00',
            'delivery_fee' => '0.00',
            'insurance_fee' => '0.00',
            'deposit_amount' => '150.00',
            'discount_amount' => '0.00',
            'tax_amount' => '14.40',
            'total_amount' => '94.40',
            'currency' => 'USD',
            'payment_status' => PaymentStatus::Pending,
            'reservation_status' => ReservationStatus::Cancelled,
        ]);

        // 3. Reservación Activa para CR-V (Ingresos y ocupación)
        // Ocupación: 1 día (05 al 06)
        Carbon::setTestNow('2026-07-03 12:00:00');
        $res3 = Reservation::create([
            'reservation_number' => 'RES-REP-3',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle2->id,
            'pickup_location_id' => $location->id,
            'return_location_id' => $location->id,
            'pickup_type' => 'office',
            'return_type' => 'office',
            'start_datetime' => '2026-07-05 10:00:00',
            'end_datetime' => '2026-07-06 10:00:00',
            'base_price' => '80.00',
            'delivery_fee' => '0.00',
            'insurance_fee' => '10.00',
            'deposit_amount' => '150.00',
            'discount_amount' => '5.00',
            'tax_amount' => '13.50',
            'total_amount' => '98.50',
            'currency' => 'USD',
            'payment_status' => PaymentStatus::Paid,
            'reservation_status' => ReservationStatus::Active,
        ]);

        Carbon::setTestNow(); // reset

        return [$vehicle1, $vehicle2];
    }

    public function test_admin_can_view_revenue_report(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->setupReportData();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/reports/revenue?start_date=2026-07-01&end_date=2026-07-07');

        $response->assertStatus(200)
            ->assertJsonPath('data.base_revenue', 180) // 100 + 80
            ->assertJsonPath('data.delivery_revenue', 10)
            ->assertJsonPath('data.insurance_revenue', 30) // 20 + 10
            ->assertJsonPath('data.discount_given', 5)
            ->assertJsonPath('data.total_revenue', 246.5) // 148.00 + 98.50
            ->assertJsonPath('data.reservation_count', 2);
    }

    public function test_admin_can_view_occupancy_report(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->setupReportData();

        // Rango de 5 días (01 al 05 de julio de 2026)
        // Corolla reservada 01 al 03 -> 3 días de ocupación en este rango (01, 02, 03)
        // CR-V reservada 05 al 06 -> 1 día de ocupación en este rango (05)
        // Total días ocupados = 4
        // Total vehículos = 2
        // Días totales en rango = 5
        // Máximo posible = 2 * 5 = 10 días
        // Tasa general = (4 / 10) * 100 = 40.0%
        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/reports/occupancy?start_date=2026-07-01&end_date=2026-07-05');

        $response->assertStatus(200)
            ->assertJsonPath('data.occupancy_rate', 40)
            ->assertJsonPath('data.total_vehicles', 2)
            ->assertJsonPath('data.total_days', 5);
    }

    public function test_admin_can_view_top_vehicles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$vehicle1, $vehicle2] = $this->setupReportData();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/reports/top-vehicles?start_date=2026-07-01&end_date=2026-07-07&limit=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $vehicle1->id) // Corolla tiene 148.00 de ingresos
            ->assertJsonPath('data.0.revenue', 148)
            ->assertJsonPath('data.1.id', $vehicle2->id) // CR-V tiene 98.50 de ingresos
            ->assertJsonPath('data.1.revenue', 98.5);
    }

    public function test_admin_can_view_reservation_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->setupReportData();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/reports/stats?start_date=2026-07-01&end_date=2026-07-07');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_reservations', 3)
            ->assertJsonPath('data.completed_reservations', 1)
            ->assertJsonPath('data.cancelled_reservations', 1)
            ->assertJsonPath('data.cancellation_rate', 33.33); // 1/3 * 100
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        [$user, $customer] = $this->createCustomerUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/reports/revenue?start_date=2026-07-01&end_date=2026-07-07');

        $response->assertStatus(403);
    }
}
