<?php

namespace Database\Seeders;

use App\Models\User;
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
    }
}
