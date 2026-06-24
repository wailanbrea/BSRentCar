<?php

namespace Database\Factories;

use App\Enums\Transmission;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Toyota Corolla', 'Hyundai Tucson', 'Kia Rio', 'Honda CR-V']),
            'brand' => fake()->randomElement(['Toyota', 'Hyundai', 'Kia', 'Honda']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2018, 2025),
            'category' => fake()->randomElement(VehicleCategory::cases())->value,
            'transmission' => fake()->randomElement(Transmission::cases())->value,
            'seats' => fake()->randomElement([2, 4, 5, 7]),
            'doors' => 4,
            'fuel_type' => 'gasoline',
            'color' => fake()->safeColorName(),
            'plate' => strtoupper(fake()->unique()->bothify('?##-###')),
            'daily_price' => fake()->randomElement(['2500.00', '3000.00', '4500.00', '6000.00']),
            'deposit_amount' => '5000.00',
            'currency' => 'DOP',
            'mileage' => fake()->numberBetween(0, 80000),
            'status' => VehicleStatus::Available->value,
            'description' => fake()->sentence(),
            'rating_avg' => 0,
            'rating_count' => 0,
        ];
    }

    public function status(VehicleStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
