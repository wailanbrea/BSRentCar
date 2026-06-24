<?php

namespace Tests\Feature\Contract;

use App\Enums\ContractStatus;
use App\Enums\ReservationStatus;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ContractService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    private ContractService $contractService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->contractService = $this->app->make(ContractService::class);
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
            'reservation_number' => 'RES-CTR-123',
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
            'reservation_status' => ReservationStatus::Confirmed, // Elegible para contrato
        ]);
    }

    public function test_admin_can_generate_contract_draft(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reservations/{$reservation->id}/contract");

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('contracts', [
            'reservation_id' => $reservation->id,
            'status' => 'pending',
        ]);

        $reservation->refresh();
        $this->assertEquals(ReservationStatus::ContractPending, $reservation->reservation_status);
        $this->assertEquals(ContractStatus::Pending, $reservation->contract_status);

        Storage::disk('local')->assertExists($reservation->contract->file_path);
    }

    public function test_customer_can_view_contract_details(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        $contract = $this->contractService->generateContract($reservation);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/customer/reservations/{$reservation->id}/contract");

        $response->assertStatus(200)
            ->assertJsonPath('data.number', $contract->number)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_customer_can_sign_contract(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        $contract = $this->contractService->generateContract($reservation);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/customer/reservations/{$reservation->id}/contract/sign", [
                'printed_name' => 'John Doe Firmante',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'signed');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'signed',
        ]);

        $reservation->refresh();
        $this->assertEquals(ReservationStatus::ContractSigned, $reservation->reservation_status);
        $this->assertEquals(ContractStatus::Signed, $reservation->contract_status);

        $contract->refresh();
        $this->assertNotNull($contract->signed_by_customer_at);
        $this->assertEquals('John Doe Firmante', $contract->signature_meta['printed_name']);
        $this->assertNotNull($contract->signature_meta['hash']);
    }

    public function test_contract_download_policy_protection(): void
    {
        [$userA, $customerA] = $this->createCustomerUser();
        [$userB, $customerB] = $this->createCustomerUser();
        $reservationA = $this->createTestReservation($customerA);
        
        $contract = $this->contractService->generateContract($reservationA);

        // Cliente B (no dueño) intenta descargar -> 403
        $response = $this->actingAs($userB)
            ->getJson("/api/v1/customer/reservations/{$reservationA->id}/contract/download");
        $response->assertStatus(403);

        // Cliente A (dueño) descarga -> 200
        $response = $this->actingAs($userA)
            ->getJson("/api/v1/customer/reservations/{$reservationA->id}/contract/download");
        $response->assertStatus(200);

        // Admin descarga -> 200
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $response = $this->actingAs($admin)
            ->getJson("/api/v1/admin/reservations/{$reservationA->id}/contract/download");
        $response->assertStatus(200);
    }

    public function test_cannot_sign_already_signed_contract(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $reservation = $this->createTestReservation($customer);
        
        $contract = $this->contractService->generateContract($reservation);
        
        // Primera firma
        $this->contractService->signContract($contract, 'John Doe', '127.0.0.1', 'Mozilla');

        // Segunda firma -> conflicto
        $response = $this->actingAs($user)
            ->postJson("/api/v1/customer/reservations/{$reservation->id}/contract/sign", [
                'printed_name' => 'John Doe Again',
            ]);

        $response->assertStatus(409);
    }
}
