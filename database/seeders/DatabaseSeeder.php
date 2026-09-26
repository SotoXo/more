<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            BaseCatalogSeeder::class,
            PrendaAttributeCatalogSeeder::class,
        ]);

        if (filter_var(env('SEED_DEMO_CATALOG', false), FILTER_VALIDATE_BOOL)) {
            $this->call([
                DemoCatalogSeeder::class,
            ]);
        }
    }
}
