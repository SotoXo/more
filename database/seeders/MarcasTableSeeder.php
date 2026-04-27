<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;

class MarcasTableSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $marcas = [
            'ZARA',
            'BERSHKA',
            'STRADIVARIUS',
            'RALPH LAUREN',
            'HUGO BOSS',
            'MASSIMO DUTTI',
            'MANGO',
            'PULL&BEAR',
            'RESERVED',
            'COS',
        ];

        foreach ($marcas as $marca) {
            $this->upsertCanonicalMaster(
                'marcas',
                'id_marca',
                'nom_mar',
                [
                    'nom_mar' => $marca,
                    'des_mar' => null,
                    'est_mar' => true,
                    'fec_mar' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    ['table' => 'prendas', 'column' => 'cod_marcas_pre'],
                ],
            );
        }
    }
}
