<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TallasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $tallas = [
            ['nom_tal' => 'XS', 'ord_tal' => 1],
            ['nom_tal' => 'S',  'ord_tal' => 2],
            ['nom_tal' => 'M',  'ord_tal' => 3],
            ['nom_tal' => 'L',  'ord_tal' => 4],
            ['nom_tal' => 'XL', 'ord_tal' => 5],
            ['nom_tal' => '38', 'ord_tal' => 6],
            ['nom_tal' => '40', 'ord_tal' => 7],
        ];

        foreach ($tallas as $talla) {
            DB::table('tallas')->updateOrInsert(
                ['nom_tal' => $talla['nom_tal']],
                [
                    'ord_tal' => $talla['ord_tal'],
                    'est_tal' => true,
                    'fec_tal' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}