<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;

class TemporadasTableSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $temporadas = [
            [
                'nom_tem' => 'PRIMAVERA-VERANO',
                'cod_tem' => 'PV',
                'des_tem' => 'Piezas ligeras y clima calido.',
                'ord_tem' => 10,
            ],
            [
                'nom_tem' => 'OTOÑO-INVIERNO',
                'cod_tem' => 'OI',
                'des_tem' => 'Abrigo, punto y capas.',
                'ord_tem' => 20,
            ],
            [
                'nom_tem' => 'RESORT',
                'cod_tem' => 'RES',
                'des_tem' => 'Viaje, transicion y silueta relajada.',
                'ord_tem' => 30,
            ],
            [
                'nom_tem' => 'CAPSULE',
                'cod_tem' => 'CAP',
                'des_tem' => 'Edicion corta y curada.',
                'ord_tem' => 40,
            ],
            [
                'nom_tem' => 'PERMANENTE',
                'cod_tem' => 'PER',
                'des_tem' => 'Base continua de fondo de armario.',
                'ord_tem' => 50,
            ],
        ];

        foreach ($temporadas as $temporada) {
            $this->upsertCanonicalMaster(
                'temporadas',
                'id_temporada',
                'nom_tem',
                [
                    'nom_tem' => $temporada['nom_tem'],
                    'cod_tem' => $temporada['cod_tem'],
                    'des_tem' => $temporada['des_tem'],
                    'ord_tem' => $temporada['ord_tem'],
                    'est_tem' => true,
                    'fec_tem' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    ['table' => 'prendas', 'column' => 'cod_temporadas_pre'],
                ],
            );
        }
    }
}
