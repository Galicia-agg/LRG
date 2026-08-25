<?php

namespace Database\Seeders;

use App\Models\CommonFailure;
use Illuminate\Database\Seeder;

class CommonFailureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $failures = [
            'Motor' => [
                'Ruido extraño en el motor',
                'El motor no enciende',
                'Sobrecalentamiento del motor',
                'Fuga de aceite',
                'Humo excesivo en el escape',
                'Pérdida de potencia',
                'Consumo excesivo de combustible',
                'Motor se apaga en marcha mínima',
                'Vibración excesiva del motor',
            ],
            'Frenos' => [
                'Frenos chillan o rechinan',
                'Frenos poco efectivos',
                'Pedal o palanca de freno se hunde',
                'Vibración al frenar',
                'Pastillas o bandas de freno desgastadas',
            ],
            'Eléctrico' => [
                'Batería descargada o no carga',
                'Luces no encienden',
                'Falla en el sistema de encendido',
                'Claxon no funciona',
                'Arranque eléctrico no funciona',
                'Alternador no carga',
            ],
            'Transmisión' => [
                'Dificultad para cambiar de marcha',
                'Embrague patina',
                'Ruido en la caja de cambios',
                'Fuga de aceite de transmisión',
            ],
            'Suspensión y dirección' => [
                'Ruido en la suspensión',
                'Vibración al conducir',
                'Dirección dura o floja',
                'Vehículo se va hacia un lado',
            ],
            'Llantas' => [
                'Desgaste irregular de llantas',
                'Pérdida de presión constante',
                'Vibración a alta velocidad',
            ],
            'Enfriamiento' => [
                'Fuga de refrigerante',
                'Radiador tapado u obstruido',
                'Ventilador no funciona',
            ],
            'Moto (específico)' => [
                'Cadena floja o desgastada',
                'Kick o arranque no responde',
                'Ruido en la transmisión final',
            ],
        ];

        foreach ($failures as $category => $descriptions) {
            foreach ($descriptions as $description) {
                CommonFailure::query()->firstOrCreate([
                    'description' => $description,
                    'category' => $category,
                ]);
            }
        }
    }
}
