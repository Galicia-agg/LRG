<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'products.view',
            'products.manage',
            'categories.manage',
            'suppliers.manage',
            'customers.manage',
            'sales.create',
            'sales.view',
            'sales.void',
            'cash-sessions.manage',
            'orders.manage',
            'quotes.manage',
            'workshop.manage',
            'reports.view',
            'users.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $cajero = Role::findOrCreate('cajero');
        $cajero->syncPermissions([
            'products.view',
            'sales.create',
            'sales.view',
            'sales.void',
            'cash-sessions.manage',
            'orders.manage',
            'quotes.manage',
            'workshop.manage',
        ]);
    }
}
