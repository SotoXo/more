<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;

class CategoriasTableSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $categorias = [
            [
                'nom_cat' => 'ABRIGOS',
                'des_cat' => 'Abrigos, capas y piezas de exterior.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'BLAZERS',
                'des_cat' => 'Sastreria ligera y blazers de estructura definida.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'CAMISAS',
                'des_cat' => 'Camisas y piezas de botonadura.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'BLUSAS',
                'des_cat' => 'Blusas fluidas y piezas de caida suave.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'VESTIDOS',
                'des_cat' => 'Vestidos de linea diaria, evento y ocasion.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'FALDAS',
                'des_cat' => 'Faldas para look diario, oficina o ocasion.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'PANTALONES',
                'des_cat' => 'Pantalones sastreros, rectos y de silueta amplia.',
                'tip_tal_cat' => 'NUMERICA',
            ],
            [
                'nom_cat' => 'POLOS Y KNITWEAR',
                'des_cat' => 'Punto ligero, knitwear y polos de base.',
                'tip_tal_cat' => 'ALFA',
            ],
            [
                'nom_cat' => 'ACCESORIOS',
                'des_cat' => 'Complementos de styling y piezas de apoyo.',
                'tip_tal_cat' => 'UNITALLA',
            ],
            [
                'nom_cat' => 'CONJUNTOS',
                'des_cat' => 'Sets coordinados y propuestas armadas.',
                'tip_tal_cat' => 'ALFA',
            ],
        ];

        foreach ($categorias as $categoria) {
            $this->upsertCanonicalMaster(
                'categorias',
                'id_categoria',
                'nom_cat',
                [
                    'nom_cat' => $categoria['nom_cat'],
                    'des_cat' => $categoria['des_cat'],
                    'tip_tal_cat' => $categoria['tip_tal_cat'],
                    'cod_categoria_padre_cat' => null,
                    'est_cat' => true,
                    'fec_cat' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    ['table' => 'prendas', 'column' => 'cod_categorias_pre'],
                    ['table' => 'categorias', 'column' => 'cod_categoria_padre_cat'],
                ],
            );
        }
    }
}
