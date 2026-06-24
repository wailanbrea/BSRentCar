<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos del sistema.
 * Fuente: docs/08_ADMIN_PANEL.md y docs/11_SECURITY.md.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos antes de sembrar.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
            'reservations.view', 'reservations.manage',
            'customers.view', 'customers.manage',
            'payments.view', 'payments.refund',
            'wallet.manage',
            'deposits.manage',
            'deliveries.manage',
            'delivery_zones.manage',
            'inspections.manage',
            'contracts.manage',
            'reviews.moderate',
            'reports.view',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // admin: todos los permisos
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // staff: operación (sin configuración, sin reembolsos, sin auditoría)
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'dashboard.view',
            'vehicles.view',
            'reservations.view', 'reservations.manage',
            'customers.view',
            'deliveries.manage',
            'inspections.manage',
        ]);

        // driver: solo sus entregas e inspecciones
        $driver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $driver->syncPermissions([
            'deliveries.manage',
            'inspections.manage',
        ]);

        // customer: sin permisos de panel (acceso de cliente vía políticas)
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
