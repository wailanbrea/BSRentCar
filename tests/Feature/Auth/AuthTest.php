<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_register_creates_user_with_customer_role_and_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'roles'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
        $this->assertTrue(User::where('email', 'juan@example.com')->first()->hasRole('customer'));
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_register_fails_with_short_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Short',
            'email' => 'short@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ok@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ok@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'bad@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'bad@example.com',
            'password' => 'wrong-pass',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
