<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $aceites = Category::query()->create(['name' => 'Aceites y lubricantes', 'slug' => Str::slug('Aceites y lubricantes')]);
        $filtros = Category::query()->create(['name' => 'Filtros', 'slug' => Str::slug('Filtros')]);
        $repuestos = Category::query()->create(['name' => 'Repuestos', 'slug' => Str::slug('Repuestos')]);

        $supplier = Supplier::query()->create([
            'name' => 'Distribuidora Lubricantes GT',
            'contact_name' => 'Carlos Pérez',
            'phone' => '5555-1234',
            'active' => true,
        ]);

        $products = [
            [
                'category_id' => $aceites->id,
                'sku' => 'ACE-15W40-GAL',
                'name' => 'Aceite 15W40 Mineral',
                'unit' => 'galon',
                'is_bulk' => true,
                'cost_price' => 60,
                'sale_price' => 95,
                'min_stock' => 5,
                'current_stock' => 40,
            ],
            [
                'category_id' => $aceites->id,
                'sku' => 'ACE-20W50-LT',
                'name' => 'Aceite 20W50 para moto',
                'unit' => 'litro',
                'is_bulk' => true,
                'cost_price' => 20,
                'sale_price' => 35,
                'min_stock' => 10,
                'current_stock' => 60,
            ],
            [
                'category_id' => $filtros->id,
                'sku' => 'FIL-ACE-001',
                'name' => 'Filtro de aceite universal',
                'unit' => 'unidad',
                'cost_price' => 15,
                'sale_price' => 28,
                'min_stock' => 8,
                'current_stock' => 25,
            ],
            [
                'category_id' => $filtros->id,
                'sku' => 'FIL-AIRE-001',
                'name' => 'Filtro de aire universal',
                'unit' => 'unidad',
                'cost_price' => 25,
                'sale_price' => 45,
                'min_stock' => 5,
                'current_stock' => 18,
            ],
            [
                'category_id' => $repuestos->id,
                'sku' => 'BUJ-STD-001',
                'name' => 'Bujía estándar',
                'unit' => 'unidad',
                'cost_price' => 12,
                'sale_price' => 22,
                'min_stock' => 10,
                'current_stock' => 50,
            ],
            [
                'category_id' => $repuestos->id,
                'sku' => 'BAT-12V-45',
                'name' => 'Batería 12V 45Ah',
                'unit' => 'unidad',
                'cost_price' => 350,
                'sale_price' => 480,
                'min_stock' => 2,
                'current_stock' => 6,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::query()->create($data);
            $product->suppliers()->attach($supplier->id, ['cost_price' => $data['cost_price']]);
        }
    }
}
