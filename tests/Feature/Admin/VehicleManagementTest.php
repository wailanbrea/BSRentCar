<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Toyota Corolla 2024',
            'category' => 'sedan',
            'transmission' => 'automatic',
            'seats' => 5,
            'plate' => 'A123456',
            'daily_price' => 3000,
            'currency' => 'DOP',
        ], $overrides);
    }

    public function test_admin_can_create_vehicle(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/vehicles', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Toyota Corolla 2024');

        $this->assertDatabaseHas('vehicles', ['plate' => 'A123456']);
    }

    public function test_customer_cannot_create_vehicle(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user)
            ->postJson('/api/v1/admin/vehicles', $this->validPayload())
            ->assertForbidden();
    }

    public function test_guest_cannot_access_admin_vehicles(): void
    {
        $this->getJson('/api/v1/admin/vehicles')->assertUnauthorized();
    }

    public function test_create_validates_required_and_unique_plate(): void
    {
        Vehicle::factory()->create(['plate' => 'DUP123']);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/vehicles', $this->validPayload(['plate' => 'DUP123', 'name' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'plate']);
    }

    public function test_admin_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['daily_price' => '2000.00']);

        $this->actingAs($this->admin())
            ->putJson("/api/v1/admin/vehicles/{$vehicle->id}", ['daily_price' => 3500])
            ->assertOk()
            ->assertJsonPath('data.daily_price', '3500.00');
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/admin/vehicles/{$vehicle->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    public function test_admin_can_upload_vehicle_image_as_primary(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/v1/admin/vehicles/{$vehicle->id}/images", [
                'image' => UploadedFile::fake()->image('car.jpg', 800, 600),
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('vehicle_images', [
            'vehicle_id' => $vehicle->id,
            'is_primary' => true,
        ]);
    }
}
