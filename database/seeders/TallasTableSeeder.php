<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;

class TallasTableSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $tallas = [
            ['nom_tal' => 'UNITALLA', 'ord_tal' => 10, 'sis_tal' => 'UNICA', 'gru_tal' => 'GENERAL'],
            ['nom_tal' => 'XS', 'ord_tal' => 20, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => 'S', 'ord_tal' => 30, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => 'M', 'ord_tal' => 40, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => 'L', 'ord_tal' => 50, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => 'XL', 'ord_tal' => 60, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => 'XXL', 'ord_tal' => 70, 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'],
            ['nom_tal' => '32', 'ord_tal' => 132, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '34', 'ord_tal' => 134, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '36', 'ord_tal' => 136, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '38', 'ord_tal' => 138, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '40', 'ord_tal' => 140, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '42', 'ord_tal' => 142, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '44', 'ord_tal' => 144, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '46', 'ord_tal' => 146, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
            ['nom_tal' => '48', 'ord_tal' => 148, 'sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'],
        ];

        foreach ($tallas as $talla) {
            $this->upsertCanonicalMaster(
                'tallas',
                'id_talla',
                'nom_tal',
                [
                    'nom_tal' => $talla['nom_tal'],
                    'ord_tal' => $talla['ord_tal'],
                    'sis_tal' => $talla['sis_tal'],
                    'gru_tal' => $talla['gru_tal'],
                    'est_tal' => true,
                    'fec_tal' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    ['table' => 'prenda_variantes', 'column' => 'cod_tallas_prv'],
                ],
            );
        }
    }
}
