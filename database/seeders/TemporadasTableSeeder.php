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
                'des_tem' => 'Piezas ligeras y clima cálido.',
                'fec_ini_tem' => '2026-09-01',
                'fec_fin_tem' => '2027-02-28',
                'ord_tem' => 10,
            ],
            [
                'nom_tem' => 'OTOÑO-INVIERNO',
                'cod_tem' => 'OI',
                'des_tem' => 'Abrigo, punto y capas.',
                'fec_ini_tem' => '2026-03-01',
                'fec_fin_tem' => '2026-08-31',
                'ord_tem' => 20,
            ],
            [
                'nom_tem' => 'RESORT',
                'cod_tem' => 'RES',
                'des_tem' => 'Viaje, transición y silueta relajada.',
                'fec_ini_tem' => '2026-11-01',
                'fec_fin_tem' => '2026-12-31',
                'ord_tem' => 30,
            ],
            [
                'nom_tem' => 'CAPSULE',
                'cod_tem' => 'CAP',
                'des_tem' => 'Edición corta y curada.',
                'fec_ini_tem' => '2026-05-01',
                'fec_fin_tem' => '2026-05-31',
                'ord_tem' => 40,
            ],
            [
                'nom_tem' => 'PERMANENTE',
                'cod_tem' => 'PER',
                'des_tem' => 'Base continua de fondo de armario.',
                'fec_ini_tem' => '2026-01-01',
                'fec_fin_tem' => '2026-12-31',
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
                    'fec_ini_tem' => $temporada['fec_ini_tem'],
                    'fec_fin_tem' => $temporada['fec_fin_tem'],
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