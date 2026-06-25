<?php

namespace Tests\Feature\Client;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function eligibleCustomerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $customer = $user->customer()->create(['birthdate' => '1990-01-01', 'verification_status' => 'verified']);
        $customer->documents()->create(['type' => 'license', 'file_path' => 'x', 'status' => 'approved']);

        return $user;
    }

    public function test_guest_redirected_to_client_login(): void
    {
        $this->get('/mi-cuenta')->assertRedirect('/login');
    }

    public function test_customer_can_register(): void
    {
        $this->post('/registro', [
            'name' => 'Nuevo Cliente',
            'email' => 'nuevo@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
        $user = User::where('email', 'nuevo@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertDatabaseHas('customers', ['user_id' => $user->id]);
    }

    public function test_customer_can_login_and_see_dashboard(): void
    {
        $user = $this->eligibleCustomerUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));

        $this->actingAs($user)->get('/mi-cuenta')->assertOk()->assertSee('Saldo wallet');
    }

    public function test_eligible_customer_can_book_vehicle(): void
    {
        $user = $this->eligibleCustomerUser();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->post('/reservar', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'end_datetime' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'pickup_type' => 'office',
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'vehicle_id' => $vehicle->id,
            'reservation_status' => 'pending_payment',
        ]);
    }

    public function test_ineligible_customer_cannot_book(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $user->customer()->create(['birthdate' => '1990-01-01']); // sin licencia
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->from(route('vehicles.show', $vehicle))->post('/reservar', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'end_datetime' => now()->addDays(5)->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('vehicles.show', $vehicle))->assertSessionHasErrors('booking');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_customer_can_view_account_pages(): void
    {
        $user = $this->eligibleCustomerUser();

        $this->actingAs($user)->get('/mi-cuenta/reservas')->assertOk();
        $this->actingAs($user)->get('/mi-cuenta/wallet')->assertOk()->assertSee('Saldo disponible');
        $this->actingAs($user)->get('/mi-cuenta/perfil')->assertOk()->assertSee('Datos personales');
    }

    public function test_customer_can_update_profile(): void
    {
        $user = $this->eligibleCustomerUser();
        $customer = $user->customer;

        $response = $this->actingAs($user)->put('/mi-cuenta/perfil', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'birthdate' => '1995-05-15',
            'address' => 'Av. Winston Churchill 123',
            'city' => 'Santo Domingo',
            'country' => 'República Dominicana',
            'license_number' => 'DL-999888777',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Perfil actualizado.');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'birthdate' => '1995-05-15 00:00:00',
            'address' => 'Av. Winston Churchill 123',
            'city' => 'Santo Domingo',
            'country' => 'República Dominicana',
            'license_number' => 'DL-999888777',
        ]);
    }

    public function test_profile_page_shows_logged_in_user_indicator(): void
    {
        $user = $this->eligibleCustomerUser();

        $response = $this->actingAs($user)->get('/mi-cuenta/perfil');
        $response->assertOk();
        $response->assertSee('Sesión iniciada como:');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_navigation_shows_username_instead_of_my_account_when_logged_in(): void
    {
        $user = $this->eligibleCustomerUser();

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertDontSee('Mi cuenta');
    }

    public function test_customer_can_book_with_delivery_address(): void
    {
        $user = $this->eligibleCustomerUser();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post('/reservar', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'end_datetime' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'pickup_type' => 'home',
            'pickup_address' => 'Calle Duarte 45, Santo Domingo',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'vehicle_id' => $vehicle->id,
            'pickup_type' => 'home',
            'pickup_address' => 'Calle Duarte 45, Santo Domingo',
        ]);
    }

    public function test_booking_fails_if_delivery_address_is_missing(): void
    {
        $user = $this->eligibleCustomerUser();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post('/reservar', [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'end_datetime' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'pickup_type' => 'home',
            // 'pickup_address' is missing
        ]);

        $response->assertSessionHasErrors(['pickup_address']);
        $this->assertDatabaseCount('reservations', 0);
    }
}
