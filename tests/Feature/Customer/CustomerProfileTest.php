<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        // El perfil se crea al registrarse (igual que en producción).
        $user->customer()->create(['verification_status' => 'unverified']);

        return $user;
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/customer/profile')->assertUnauthorized();
    }

    public function test_show_profile_autocreates_customer(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->getJson('/api/v1/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'unverified');

        $this->assertDatabaseHas('customers', ['user_id' => $user->id]);
    }

    public function test_update_profile_persists_data(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->putJson('/api/v1/customer/profile', [
                'first_name' => 'Ana',
                'last_name' => 'García',
                'phone' => '8095551234',
                'birthdate' => '1995-05-20',
                'city' => 'Santiago',
                'country' => 'DO',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ana')
            ->assertJsonPath('data.city', 'Santiago');
    }

    public function test_update_profile_validates_required_fields(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->putJson('/api/v1/customer/profile', ['first_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_update_profile_rejects_future_birthdate(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->putJson('/api/v1/customer/profile', [
                'first_name' => 'Ana',
                'last_name' => 'García',
                'birthdate' => now()->addDay()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birthdate');
    }

    public function test_upload_document_stores_on_private_disk(): void
    {
        Storage::fake('local');
        $user = $this->customer();

        $response = $this->actingAs($user)->postJson('/api/v1/customer/documents', [
            'type' => 'license',
            'file' => UploadedFile::fake()->create('licencia.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'license')
            ->assertJsonPath('data.status', 'pending');

        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('customer_documents', [
            'customer_id' => $customer->id,
            'type' => 'license',
            'status' => 'pending',
        ]);

        $path = $customer->documents()->first()->file_path;
        Storage::disk('local')->assertExists($path);
    }

    public function test_upload_document_rejects_invalid_type(): void
    {
        Storage::fake('local');
        $user = $this->customer();

        $this->actingAs($user)->postJson('/api/v1/customer/documents', [
            'type' => 'license',
            'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
        ])->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_eligibility_requires_age_and_approved_license(): void
    {
        // Adulto sin licencia aprobada → no elegible por licencia.
        $adult = Customer::factory()->create(['birthdate' => '1990-01-01']);
        $this->assertContains('license_not_approved', $adult->rentalEligibilityErrors());
        $this->assertNotContains('min_age_18', $adult->rentalEligibilityErrors());

        // Menor de 18 → no elegible por edad.
        $minor = Customer::factory()->create(['birthdate' => now()->subYears(16)->toDateString()]);
        $this->assertContains('min_age_18', $minor->rentalEligibilityErrors());
    }
}
