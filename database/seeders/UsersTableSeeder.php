<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $roles = DB::table('roles')->pluck('id_rol', 'nom_rol');

        $users = [
            [
                'nom_usu' => 'LIMBER',
                'app_usu' => 'SOTO',
                'apm_usu' => 'CAZU',
                'tel_usu' => '70000001',
                'email' => 'superadmin@more.com',
                'password' => Hash::make('password'),
                'cod_roles_usu' => $roles['superadmin'] ?? null,
            ],
            [
                'nom_usu' => 'MATI',
                'app_usu' => 'BOUTIQUE',
                'apm_usu' => 'VENDEDOR',
                'tel_usu' => '70000002',
                'email' => 'vendedor@more.com',
                'password' => Hash::make('password'),
                'cod_roles_usu' => $roles['vendedor'] ?? null,
            ],
            [
                'nom_usu' => 'CLIENTE',
                'app_usu' => 'DEMO',
                'apm_usu' => 'MORE',
                'tel_usu' => '70000003',
                'email' => 'cliente@more.com',
                'password' => Hash::make('password'),
                'cod_roles_usu' => $roles['cliente'] ?? null,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'est_usu' => true,
                    'fec_usu' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
