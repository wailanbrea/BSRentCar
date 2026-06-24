<?php

namespace Tests\Feature\Review;

use App\Enums\ReservationStatus;
use App\Enums\ReviewStatus;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    private function createTestReservation(Customer $customer, Vehicle $vehicle): Reservation
    {
        $location = \App\Models\Location::create([
            'name' => 'SDQ Airport Office',
            'type' => 'airport',
            'is_active' => true,
        ]);
        return Reservation::create([
            'reservation_number' => 'RES-REV-' . rand(100, 999),
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
            'reservation_status' => ReservationStatus::Completed,
        ]);
    }

    public function test_customer_can_create_review_for_completed_reservation(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $vehicle = Vehicle::factory()->create();
        $reservation = $this->createTestReservation($customer, $vehicle);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/reservations/{$reservation->id}/review", [
                'rating_vehicle' => 5,
                'rating_cleanliness' => 4,
                'rating_service' => 5,
                'rating_delivery' => 4,
                'rating_overall' => 5,
                'comment' => 'Great experience, clean car!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.comment', 'Great experience, clean car!')
            ->assertJsonPath('data.rating_overall', 5);

        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $reservation->id,
            'rating_overall' => 5,
            'status' => ReviewStatus::Visible->value,
        ]);

        $vehicle->refresh();
        $this->assertEquals(5.00, $vehicle->rating_avg);
        $this->assertEquals(1, $vehicle->rating_count);
    }

    public function test_cannot_create_review_if_not_owner_or_not_completed(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $vehicle = Vehicle::factory()->create();
        $reservation = $this->createTestReservation($customer, $vehicle);

        // 1. Probar que otro cliente no puede calificarla (403)
        [$otherUser, $otherCustomer] = $this->createCustomerUser();
        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/reservations/{$reservation->id}/review", [
                'rating_vehicle' => 5,
                'rating_cleanliness' => 5,
                'rating_service' => 5,
                'rating_delivery' => 5,
                'rating_overall' => 5,
            ]);
        $response->assertStatus(403);

        // 2. Probar que no se puede calificar si no está completada (409)
        $reservation->update(['reservation_status' => ReservationStatus::Active]);
        $response = $this->actingAs($user)
            ->postJson("/api/v1/reservations/{$reservation->id}/review", [
                'rating_vehicle' => 5,
                'rating_cleanliness' => 5,
                'rating_service' => 5,
                'rating_delivery' => 5,
                'rating_overall' => 5,
            ]);
        $response->assertStatus(409);
    }

    public function test_cannot_review_same_reservation_twice(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $vehicle = Vehicle::factory()->create();
        $reservation = $this->createTestReservation($customer, $vehicle);

        // Crear la primera reseña
        Review::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'rating_vehicle' => 5,
            'rating_cleanliness' => 5,
            'rating_service' => 5,
            'rating_delivery' => 5,
            'rating_overall' => 5,
            'status' => ReviewStatus::Visible->value,
        ]);

        // Intentar crear la segunda reseña
        $response = $this->actingAs($user)
            ->postJson("/api/v1/reservations/{$reservation->id}/review", [
                'rating_vehicle' => 4,
                'rating_cleanliness' => 4,
                'rating_service' => 4,
                'rating_delivery' => 4,
                'rating_overall' => 4,
            ]);
        $response->assertStatus(409);
    }

    public function test_admin_can_moderate_review_visibility(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$user, $customer] = $this->createCustomerUser();
        $vehicle = Vehicle::factory()->create();
        $reservation = $this->createTestReservation($customer, $vehicle);

        // Crear una reseña visible (rating = 5)
        $review = Review::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'rating_vehicle' => 5,
            'rating_cleanliness' => 5,
            'rating_service' => 5,
            'rating_delivery' => 5,
            'rating_overall' => 5,
            'status' => ReviewStatus::Visible->value,
        ]);
        $vehicle->update(['rating_avg' => 5, 'rating_count' => 1]);

        // Admin cambia a hidden
        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/moderate", [
                'status' => 'hidden',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'hidden');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Hidden->value,
        ]);

        $vehicle->refresh();
        $this->assertEquals(0, $vehicle->rating_avg);
        $this->assertEquals(0, $vehicle->rating_count);

        // Admin cambia de vuelta a visible
        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/moderate", [
                'status' => 'visible',
            ]);
        $response->assertStatus(200);

        $vehicle->refresh();
        $this->assertEquals(5, $vehicle->rating_avg);
        $this->assertEquals(1, $vehicle->rating_count);
    }

    public function test_public_can_list_only_visible_reviews(): void
    {
        [$user, $customer] = $this->createCustomerUser();
        $vehicle = Vehicle::factory()->create();

        // 1. Reseña visible
        $reservation1 = $this->createTestReservation($customer, $vehicle);
        Review::create([
            'reservation_id' => $reservation1->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'rating_vehicle' => 4,
            'rating_cleanliness' => 4,
            'rating_service' => 4,
            'rating_delivery' => 4,
            'rating_overall' => 4,
            'comment' => 'Visible review',
            'status' => ReviewStatus::Visible->value,
        ]);

        // 2. Reseña oculta
        $reservation2 = $this->createTestReservation($customer, $vehicle);
        Review::create([
            'reservation_id' => $reservation2->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'rating_vehicle' => 1,
            'rating_cleanliness' => 1,
            'rating_service' => 1,
            'rating_delivery' => 1,
            'rating_overall' => 1,
            'comment' => 'Hidden review',
            'status' => ReviewStatus::Hidden->value,
        ]);

        // Recalcular rating
        $vehicle->update(['rating_avg' => 4, 'rating_count' => 1]);

        // Consumir el endpoint público
        $response = $this->getJson("/api/v1/vehicles/{$vehicle->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'Visible review')
            ->assertJsonPath('rating_avg', 4)
            ->assertJsonPath('rating_count', 1);
    }
}
