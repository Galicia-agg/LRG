<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@aceitera.test',
        ]);
        $admin->assignRole('admin');

        $cajero = User::factory()->create([
            'name' => 'Cajero',
            'email' => 'cajero@aceitera.test',
        ]);
        $cajero->assignRole('cajero');

        $this->call(InventoryDemoSeeder::class);
    }
}
