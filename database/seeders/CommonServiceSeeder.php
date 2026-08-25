<?php

namespace Database\Seeders;

use App\Models\CommonService;
use Illuminate\Database\Seeder;

class CommonServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            'Servicio menor' => [
                'Cambio de aceite y filtro de aceite',
                'Revisión de niveles de líquidos',
                'Revisión y ajuste de presión de llantas',
                'Limpieza o cambio de filtro de aire',
                'Lubricación de cadena (moto)',
            ],
            'Servicio mayor' => [
                'Cambio de bujías',
                'Cambio de banda de distribución',
                'Cambio de líquido de frenos',
                'Cambio de líquido refrigerante',
                'Revisión y ajuste de frenos',
                'Alineación y balanceo',
                'Cambio de filtro de combustible',
                'Revisión general de suspensión',
            ],
        ];

        foreach ($services as $category => $descriptions) {
            foreach ($descriptions as $description) {
                CommonService::query()->firstOrCreate([
                    'description' => $description,
                    'category' => $category,
                ]);
            }
        }
    }
}
