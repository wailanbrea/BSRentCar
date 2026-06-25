<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Panel administrativo');
    }

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Ingresos del mes');
    }

    public function test_customer_cannot_login_to_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->from('/admin/login')->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_admin_can_view_vehicles_index(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->actingAs($this->admin())->get('/admin/vehicles')
            ->assertOk()
            ->assertSee('Nuevo vehículo');
    }

    public function test_admin_can_create_vehicle_via_web(): void
    {
        $this->actingAs($this->admin())->post('/admin/vehicles', [
            'name' => 'Kia Rio 2024',
            'category' => 'economy',
            'transmission' => 'automatic',
            'seats' => 5,
            'plate' => 'WEB-123',
            'daily_price' => 2500,
            'currency' => 'DOP',
            'status' => 'available',
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['plate' => 'WEB-123', 'name' => 'Kia Rio 2024']);
    }

    public function test_admin_can_view_and_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['daily_price' => '2000.00']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.vehicles.edit', $vehicle))
            ->assertOk()
            ->assertSee($vehicle->name);

        $this->actingAs($admin)->put(route('admin.vehicles.update', $vehicle), [
            'name' => $vehicle->name,
            'category' => $vehicle->category->value,
            'transmission' => $vehicle->transmission->value,
            'seats' => $vehicle->seats,
            'plate' => $vehicle->plate,
            'daily_price' => 4200,
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'daily_price' => '4200.00']);
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAs($this->admin())->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_admin_can_view_reservations_customers_and_reports(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/reservations')->assertOk();
        $this->actingAs($admin)->get('/admin/customers')->assertOk();
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Ingresos');
    }

    public function test_admin_can_view_payments_deposits_deliveries_reviews(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/deposits')->assertOk();
        $this->actingAs($admin)->get('/admin/deliveries')->assertOk();
        $this->actingAs($admin)->get('/admin/reviews')->assertOk();
    }

    public function test_admin_can_view_contracts_and_inspections(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/contracts')->assertOk();
        $this->actingAs($admin)->get('/admin/inspections')->assertOk();
    }

    public function test_admin_can_upload_vehicle_photo_via_web(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.vehicles.images.upload', $vehicle), [
                'image' => \Illuminate\Http\UploadedFile::fake()->image('car.jpg', 800, 600),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vehicle_images', [
            'vehicle_id' => $vehicle->id,
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_moderate_review(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $reservation = \App\Models\Reservation::create([
            'reservation_number' => 'RC-REV-1',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-09-01 10:00:00',
            'end_datetime' => '2026-09-03 10:00:00',
            'pickup_type' => 'office',
            'total_amount' => '100.00',
            'reservation_status' => 'completed',
        ]);
        $review = \App\Models\Review::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'rating_vehicle' => 5, 'rating_cleanliness' => 5, 'rating_service' => 5,
            'rating_delivery' => 5, 'rating_overall' => 5,
            'comment' => 'Excelente', 'status' => 'visible',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.reviews.moderate', $review), ['status' => 'hidden'])
            ->assertRedirect();

        $this->assertSame('hidden', $review->fresh()->status->value);
    }

    public function test_admin_can_mark_reservation_paid_from_panel(): void
    {
        $customer = \App\Models\Customer::factory()->create(['birthdate' => '1990-01-01']);
        $customer->documents()->create(['type' => 'license', 'file_path' => 'x', 'status' => 'approved']);
        $vehicle = Vehicle::factory()->create();

        $reservation = app(\App\Services\ReservationService::class)->createForCustomer($customer, [
            'vehicle_id' => $vehicle->id,
            'start_datetime' => '2026-09-01T10:00:00',
            'end_datetime' => '2026-09-03T10:00:00',
        ]);

        $this->actingAs($this->admin())->get(route('admin.reservations.show', $reservation))->assertOk();

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.mark-paid', $reservation))
            ->assertRedirect();

        $this->assertSame('paid', $reservation->fresh()->reservation_status->value);
    }
}
