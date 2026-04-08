<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            CategoriasTableSeeder::class,
            MarcasTableSeeder::class,
            ColoresTableSeeder::class,
            TallasTableSeeder::class,
            TemporadasTableSeeder::class,
            UsersTableSeeder::class,
            ProductosDemoSeeder::class,
        ]);
    }
}