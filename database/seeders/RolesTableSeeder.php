<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('roles')->upsert([
            [
                'nom_rol' => 'superadmin',
                'des_rol' => 'Rol con acceso total al sistema',
                'est_rol' => true,
                'fec_rol' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nom_rol' => 'vendedor',
                'des_rol' => 'Rol encargado de ventas y reservas',
                'est_rol' => true,
                'fec_rol' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nom_rol' => 'cliente',
                'des_rol' => 'Rol asignado a clientes del sistema',
                'est_rol' => true,
                'fec_rol' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['nom_rol'], ['des_rol', 'est_rol', 'fec_rol', 'updated_at']);
    }
}