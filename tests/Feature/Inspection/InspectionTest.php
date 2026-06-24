<?php

namespace Tests\Feature\Inspection;

use App\Enums\VehicleInspectionType;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\DeliveryRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
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
            'reservation_number' => 'RES-INSP-123',
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

    public function test_admin_can_create_initial_inspection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        // Poner la reservación en un estado bloqueante adecuado para inspección inicial (ej: delivered)
        $reservation->update(['reservation_status' => ReservationStatus::Delivered]);

        // Simular foto subida
        $file = UploadedFile::fake()->image('damage.jpg');

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$reservation->id}/inspections", [
                'type' => 'initial',
                'fuel_level' => '8/8',
                'mileage' => 20000,
                'accepted_by_customer' => true,
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'photos' => [
                    [
                        'file' => $file,
                        'position' => 'front',
                        'note' => 'Front bumper check',
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'initial')
            ->assertJsonPath('data.fuel_level', '8/8')
            ->assertJsonPath('data.mileage', 20000);

        $this->assertDatabaseHas('vehicle_inspections', [
            'reservation_id' => $reservation->id,
            'type' => 'initial',
        ]);

        $reservation->refresh();
        $this->assertEquals(ReservationStatus::Active, $reservation->reservation_status);

        $inspection = $reservation->inspections()->first();
        Storage::disk('local')->assertExists($inspection->signature_path);
        
        $photo = $inspection->photos()->first();
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_admin_can_create_final_inspection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        // Poner la reservación en renta activa
        $reservation->update(['reservation_status' => ReservationStatus::Active]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$reservation->id}/inspections", [
                'type' => 'final',
                'fuel_level' => '7/8', // combustible faltante
                'mileage' => 20500,
                'accepted_by_customer' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'final')
            ->assertJsonPath('data.fuel_level', '7/8');

        $reservation->refresh();
        $this->assertEquals(ReservationStatus::Completed, $reservation->reservation_status);
    }

    public function test_inspections_require_valid_reservation_states(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        // La reserva está en Confirmed (no elegible para inspección final directa)
        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$reservation->id}/inspections", [
                'type' => 'final',
                'fuel_level' => '8/8',
                'mileage' => 20500,
                'accepted_by_customer' => true,
            ]);

        // Error 409 Conflicto por estado inválido de la máquina de estados
        $response->assertStatus(409);
    }

    public function test_photos_are_saved_in_private_disk(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        $reservation->update(['reservation_status' => ReservationStatus::Delivered]);

        $inspection = VehicleInspection::create([
            'reservation_id' => $reservation->id,
            'vehicle_id' => $reservation->vehicle_id,
            'type' => 'initial',
            'fuel_level' => '8/8',
            'mileage' => 15000,
            'inspected_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('back.jpg');

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/inspections/{$inspection->id}/photos", [
                'file' => $file,
                'position' => 'back',
                'note' => 'Rear check',
            ]);

        $response->assertStatus(201);
        
        $photo = $inspection->photos()->first();
        Storage::disk('local')->assertExists($photo->path);
    }
}
