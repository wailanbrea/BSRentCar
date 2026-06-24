<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Santo Domingo', 'Aeropuerto SDQ', 'Punta Cana', 'Santiago']),
            'type' => 'branch',
            'address' => fake()->streetAddress(),
            'city' => 'Santo Domingo',
            'latitude' => 18.4861,
            'longitude' => -69.9312,
            'is_active' => true,
        ];
    }
}
