<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarcasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $marcas = [
            'Zara',
            'Bershka',
            'Stradivarius',
            'Ralph Lauren',
            'Hugo Boss',
            'Massimo Dutti',
            'Mango',
            'Pull&Bear',
            'Reserved',
            'COS',
        ];

        foreach ($marcas as $marca) {
            DB::table('marcas')->updateOrInsert(
                ['nom_mar' => $marca],
                [
                    'des_mar' => "Marca {$marca}",
                    'est_mar' => true,
                    'fec_mar' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}