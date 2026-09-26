<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriasTableSeeder::class,
            CategoriaComplementariaSeeder::class,
            MarcasTableSeeder::class,
            ColoresTableSeeder::class,
            ExpandedColorCatalogSeeder::class,
            TallasTableSeeder::class,
            TemporadasTableSeeder::class,
        ]);
    }
}
