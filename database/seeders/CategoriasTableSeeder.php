<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoriasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categorias = [
            ['nom_cat' => 'Blazers', 'des_cat' => 'Blazers y sacos estructurados'],
            ['nom_cat' => 'Camisas', 'des_cat' => 'Camisas formales y casuales'],
            ['nom_cat' => 'Pantalones', 'des_cat' => 'Pantalones de vestir y casuales'],
            ['nom_cat' => 'Vestidos', 'des_cat' => 'Vestidos de distintas líneas'],
            ['nom_cat' => 'Abrigos', 'des_cat' => 'Abrigos, trench y outerwear'],
            ['nom_cat' => 'Faldas', 'des_cat' => 'Faldas midi, mini y satinadas'],
            ['nom_cat' => 'Polos y Knitwear', 'des_cat' => 'Polos, sweaters y knitwear'],
            ['nom_cat' => 'Accesorios', 'des_cat' => 'Accesorios de moda'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->updateOrInsert(
                ['nom_cat' => $categoria['nom_cat']],
                [
                    'des_cat' => $categoria['des_cat'],
                    'cod_categoria_padre_cat' => null,
                    'est_cat' => true,
                    'fec_cat' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}