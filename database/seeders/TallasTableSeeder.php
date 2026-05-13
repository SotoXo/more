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
            // =========================
            // ADULTO - TALLA ÚNICA
            // =========================
            [
                'nom_tal' => 'UNITALLA',
                'ord_tal' => 10,
                'sis_tal' => 'UNICA',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'ADULTO',
            ],

            // =========================
            // ADULTO - ALFA / SUPERIOR
            // =========================
            [
                'nom_tal' => 'XXXS',
                'ord_tal' => 5,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XXS',
                'ord_tal' => 10,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XS',
                'ord_tal' => 20,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'S',
                'ord_tal' => 30,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'M',
                'ord_tal' => 40,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'L',
                'ord_tal' => 50,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XL',
                'ord_tal' => 60,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XXL',
                'ord_tal' => 70,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XXXL',
                'ord_tal' => 80,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => 'XXXXL',
                'ord_tal' => 90,
                'sis_tal' => 'ALFA',
                'gru_tal' => 'SUPERIOR',
                'seg_tal' => 'ADULTO',
            ],

            // =========================
            // ADULTO - NUMÉRICA / INFERIOR
            // =========================
            [
                'nom_tal' => '32',
                'ord_tal' => 1320,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '34',
                'ord_tal' => 1340,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '36',
                'ord_tal' => 1360,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '38',
                'ord_tal' => 1380,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '40',
                'ord_tal' => 1400,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '42',
                'ord_tal' => 1420,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '44',
                'ord_tal' => 1440,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '46',
                'ord_tal' => 1460,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '48',
                'ord_tal' => 1480,
                'sis_tal' => 'NUMERICA',
                'gru_tal' => 'INFERIOR',
                'seg_tal' => 'ADULTO',
            ],

            // =========================
            // ADULTO - CALZADO US
            // =========================
            [
                'nom_tal' => '6 US',
                'ord_tal' => 3060,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '6.5 US',
                'ord_tal' => 3065,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '7 US',
                'ord_tal' => 3070,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '7.5 US',
                'ord_tal' => 3075,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '8 US',
                'ord_tal' => 3080,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '8.5 US',
                'ord_tal' => 3085,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '9 US',
                'ord_tal' => 3090,
                'sis_tal' => 'CALZADO_US',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],

            // =========================
            // ADULTO - CALZADO EU
            // =========================
            [
                'nom_tal' => '36 EU',
                'ord_tal' => 4036,
                'sis_tal' => 'CALZADO_EU',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '37 EU',
                'ord_tal' => 4037,
                'sis_tal' => 'CALZADO_EU',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '38 EU',
                'ord_tal' => 4038,
                'sis_tal' => 'CALZADO_EU',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '39 EU',
                'ord_tal' => 4039,
                'sis_tal' => 'CALZADO_EU',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],
            [
                'nom_tal' => '40 EU',
                'ord_tal' => 4040,
                'sis_tal' => 'CALZADO_EU',
                'gru_tal' => 'CALZADO',
                'seg_tal' => 'ADULTO',
            ],

            // =========================
            // INFANTIL - EDAD
            // =========================
            [
                'nom_tal' => '2A',
                'ord_tal' => 5020,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '3A',
                'ord_tal' => 5030,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '4A',
                'ord_tal' => 5040,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '5A',
                'ord_tal' => 5050,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '6A',
                'ord_tal' => 5060,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '8A',
                'ord_tal' => 5080,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '10A',
                'ord_tal' => 5100,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '12A',
                'ord_tal' => 5120,
                'sis_tal' => 'EDAD',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],

            // =========================
            // INFANTIL - ALTURA CM
            // =========================
            [
                'nom_tal' => '92 CM',
                'ord_tal' => 6092,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '98 CM',
                'ord_tal' => 6098,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '104 CM',
                'ord_tal' => 6104,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '110 CM',
                'ord_tal' => 6110,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '116 CM',
                'ord_tal' => 6116,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '122 CM',
                'ord_tal' => 6122,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],
            [
                'nom_tal' => '128 CM',
                'ord_tal' => 6128,
                'sis_tal' => 'ALTURA_CM',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'INFANTIL',
            ],

            // =========================
            // BEBÉ - MESES
            // =========================
            [
                'nom_tal' => '0-3M',
                'ord_tal' => 7003,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
            [
                'nom_tal' => '3-6M',
                'ord_tal' => 7006,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
            [
                'nom_tal' => '6-9M',
                'ord_tal' => 7009,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
            [
                'nom_tal' => '9-12M',
                'ord_tal' => 7012,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
            [
                'nom_tal' => '12-18M',
                'ord_tal' => 7018,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
            [
                'nom_tal' => '18-24M',
                'ord_tal' => 7024,
                'sis_tal' => 'MESES',
                'gru_tal' => 'GENERAL',
                'seg_tal' => 'BEBE',
            ],
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
                    'seg_tal' => $talla['seg_tal'],
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