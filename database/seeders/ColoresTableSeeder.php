<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ColoresTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $colores = [
            ['nom_col' => 'Negro', 'hex_col' => '#000000'],
            ['nom_col' => 'Blanco', 'hex_col' => '#FFFFFF'],
            ['nom_col' => 'Beige', 'hex_col' => '#D8C3A5'],
            ['nom_col' => 'Camel', 'hex_col' => '#C19A6B'],
            ['nom_col' => 'Gris', 'hex_col' => '#808080'],
            ['nom_col' => 'Azul Marino', 'hex_col' => '#1F2A44'],
            ['nom_col' => 'Marron', 'hex_col' => '#6F4E37'],
            ['nom_col' => 'Verde Oliva', 'hex_col' => '#556B2F'],
            ['nom_col' => 'Borgona', 'hex_col' => '#800020'],
            ['nom_col' => 'Rosa Palo', 'hex_col' => '#D8A7B1'],
            ['nom_col' => 'Crema', 'hex_col' => '#F5F5DC'],
            ['nom_col' => 'Celeste', 'hex_col' => '#87CEEB'],
        ];

        foreach ($colores as $color) {
            DB::table('colores')->updateOrInsert(
                ['nom_col' => $color['nom_col']],
                [
                    'hex_col' => $color['hex_col'],
                    'est_col' => true,
                    'fec_col' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}