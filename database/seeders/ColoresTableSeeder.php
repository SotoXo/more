<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;

class ColoresTableSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $colores = [
            ['nom_col' => 'NEGRO', 'fam_col' => 'NEGRO', 'hex_col' => '#111111', 'hex_dom_col' => '#111111', 'ord_col' => 10],
            ['nom_col' => 'BLANCO', 'fam_col' => 'BLANCO', 'hex_col' => '#F7F6F2', 'hex_dom_col' => '#F7F6F2', 'ord_col' => 20],
            ['nom_col' => 'CREMA', 'fam_col' => 'BLANCO', 'hex_col' => '#F2E8D8', 'hex_dom_col' => '#F7F6F2', 'ord_col' => 30],
            ['nom_col' => 'BEIGE', 'fam_col' => 'BEIGE', 'hex_col' => '#D8C3A5', 'hex_dom_col' => '#D8C3A5', 'ord_col' => 40],
            ['nom_col' => 'CAMEL', 'fam_col' => 'CAMEL', 'hex_col' => '#B88956', 'hex_dom_col' => '#B88956', 'ord_col' => 50],
            ['nom_col' => 'GRIS', 'fam_col' => 'GRIS', 'hex_col' => '#8A8F98', 'hex_dom_col' => '#8A8F98', 'ord_col' => 60],
            ['nom_col' => 'AZUL MARINO', 'fam_col' => 'AZUL', 'hex_col' => '#1F2A44', 'hex_dom_col' => '#1F2A44', 'ord_col' => 70],
            ['nom_col' => 'CELESTE', 'fam_col' => 'AZUL', 'hex_col' => '#A9C7E8', 'hex_dom_col' => '#6F9FD8', 'ord_col' => 80],
            ['nom_col' => 'VERDE OLIVA', 'fam_col' => 'VERDE', 'hex_col' => '#66704A', 'hex_dom_col' => '#66704A', 'ord_col' => 90],
            ['nom_col' => 'MARRÓN', 'fam_col' => 'MARRON', 'hex_col' => '#6F4E37', 'hex_dom_col' => '#6F4E37', 'ord_col' => 100],
            ['nom_col' => 'BORGONA', 'fam_col' => 'ROJO', 'hex_col' => '#6F1D2C', 'hex_dom_col' => '#8A2332', 'ord_col' => 110],
            ['nom_col' => 'ROSA PALO', 'fam_col' => 'ROSA', 'hex_col' => '#D8A7B1', 'hex_dom_col' => '#D28FA0', 'ord_col' => 120],
        ];

        foreach ($colores as $color) {
            $this->upsertCanonicalMaster(
                'colores',
                'id_color',
                'nom_col',
                [
                    'nom_col' => $color['nom_col'],
                    'fam_col' => $color['fam_col'],
                    'hex_col' => $color['hex_col'],
                    'hex_dom_col' => $color['hex_dom_col'],
                    'ord_col' => $color['ord_col'],
                    'est_col' => true,
                    'fec_col' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    ['table' => 'prenda_variantes', 'column' => 'cod_colores_prv'],
                ],
            );
        }
    }
}
