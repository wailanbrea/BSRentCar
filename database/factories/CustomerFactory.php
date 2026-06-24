<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('809#######'),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
            'address' => fake()->streetAddress(),
            'city' => 'Santo Domingo',
            'country' => 'DO',
            'license_number' => fake()->bothify('LIC-#######'),
            'verification_status' => VerificationStatus::Unverified->value,
        ];
    }
}
