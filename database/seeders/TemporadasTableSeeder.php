<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TemporadasTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $temporadas = [
            ['nom_tem' => 'Primavera-Verano', 'des_tem' => 'Colecciones ligeras y frescas'],
            ['nom_tem' => 'Otono-Invierno', 'des_tem' => 'Colecciones de abrigo y layering'],
            ['nom_tem' => 'Resort', 'des_tem' => 'Línea de transición y viaje'],
            ['nom_tem' => 'Capsule', 'des_tem' => 'Selección atemporal y curada'],
        ];

        foreach ($temporadas as $temporada) {
            DB::table('temporadas')->updateOrInsert(
                ['nom_tem' => $temporada['nom_tem']],
                [
                    'des_tem' => $temporada['des_tem'],
                    'est_tem' => true,
                    'fec_tem' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}