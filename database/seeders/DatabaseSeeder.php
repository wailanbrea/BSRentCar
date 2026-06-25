<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Usuario administrador inicial.
        $admin = User::firstOrCreate(
            ['email' => 'admin@rentcar.test'],
            ['name' => 'Administrador', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

        // Cliente demo con perfil verificado y licencia aprobada (para probar reservas).
        $customerUser = User::firstOrCreate(
            ['email' => 'cliente@rentcar.test'],
            ['name' => 'Cliente Demo', 'password' => Hash::make('password')]
        );
        $customerUser->assignRole('customer');
        $customer = $customerUser->customer()->firstOrCreate([], [
            'first_name' => 'Cliente',
            'last_name' => 'Demo',
            'birthdate' => '1995-05-20',
            'phone' => '8095551234',
            'city' => 'Santo Domingo',
            'country' => 'DO',
            'verification_status' => 'verified',
        ]);
        $customer->documents()->firstOrCreate(
            ['type' => 'license'],
            ['file_path' => 'documents/demo-license.pdf', 'status' => 'approved']
        );

        // Conductor demo (para asignar entregas).
        User::firstOrCreate(
            ['email' => 'driver@rentcar.test'],
            ['name' => 'Conductor Demo', 'password' => Hash::make('password')]
        )->assignRole('driver');

        // Datos demo de catálogo (solo si la flota está vacía).
        if (Vehicle::count() === 0) {
            $location = Location::firstOrCreate(
                ['name' => 'Santo Domingo'],
                ['type' => 'branch', 'city' => 'Santo Domingo', 'is_active' => true]
            );

            $demo = [
                ['Hyundai Sonata', 'sedan', 'automatic', 5, '60.00'],
                ['Toyota Crown 2023', 'sedan', 'automatic', 5, '70.00'],
                ['Prius Prime SE', 'economy', 'automatic', 5, '50.00'],
                ['Subaru Legacy', 'sedan', 'automatic', 5, '75.00'],
                ['Genesis G80', 'luxury', 'automatic', 5, '80.00'],
                ['Nissan Rogue', 'suv', 'automatic', 5, '90.00'],
                ['Volvo XC60', 'suv', 'automatic', 5, '82.00'],
                ['Corolla Civic Hybrid', 'economy', 'manual', 5, '55.00'],
            ];

            $imageNames = [
                'Hyundai Sonata' => 'vehicles/hyundai_sonata.png',
                'Toyota Crown 2023' => 'vehicles/toyota_crown.png',
                'Prius Prime SE' => 'vehicles/prius_prime.png',
                'Subaru Legacy' => 'vehicles/subaru_legacy.png',
                'Genesis G80' => 'vehicles/genesis_g80.png',
                'Nissan Rogue' => 'vehicles/nissan_rogue.png',
                'Volvo XC60' => 'vehicles/volvo_xc60.png',
                'Corolla Civic Hybrid' => 'vehicles/corolla_civic.png',
            ];

            foreach ($demo as $i => [$name, $category, $transmission, $seats, $price]) {
                $vehicle = Vehicle::create([
                    'name' => $name,
                    'brand' => explode(' ', $name)[0],
                    'category' => $category,
                    'transmission' => $transmission,
                    'seats' => $seats,
                    'doors' => 4,
                    'fuel_type' => 'gasoline',
                    'plate' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'daily_price' => $price,
                    'deposit_amount' => '200.00',
                    'currency' => config('rentcar.currency'),
                    'location_id' => $location->id,
                    'status' => 'available',
                    'description' => 'Vehículo de demostración para el catálogo.',
                ]);

                if (isset($imageNames[$name])) {
                    $vehicle->images()->create([
                        'path' => $imageNames[$name],
                        'is_primary' => true,
                        'sort_order' => 0,
                        'alt' => $name,
                    ]);
                }
            }
        }
    }
}
