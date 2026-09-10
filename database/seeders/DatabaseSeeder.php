<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@aceitera.test',
            'password' => Hash::make('password'), // Campo obligatorio faltante
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $cajero = User::create([
            'name' => 'Cajero',
            'email' => 'cajero@aceitera.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $cajero->assignRole('cajero');

        $this->call(InventoryDemoSeeder::class);
    }
}
