<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaComplementariaSeeder extends Seeder
{
    public function run(): void
    {
        // Only existing category names participate. Each direction is independent.
        $rules = [
            'POLOS Y KNITWEAR' => ['PANTALONES', 'ABRIGOS', 'BLAZERS', 'ACCESORIOS', 'FALDAS'],
            'POLERA' => ['PANTALONES', 'ABRIGOS', 'ACCESORIOS'],
            'CAMISAS' => ['PANTALONES', 'BLAZERS', 'ABRIGOS', 'ACCESORIOS'],
            'BLUSAS' => ['PANTALONES', 'FALDAS', 'BLAZERS', 'ACCESORIOS'],
            'PANTALONES' => ['POLOS Y KNITWEAR', 'CAMISAS', 'BLUSAS', 'ABRIGOS', 'BLAZERS', 'POLERA'],
            'ABRIGOS' => ['POLOS Y KNITWEAR', 'CAMISAS', 'PANTALONES', 'BLUSAS', 'POLERA'],
            'BLAZERS' => ['CAMISAS', 'BLUSAS', 'PANTALONES', 'POLOS Y KNITWEAR'],
            'VESTIDOS' => ['BLAZERS', 'ABRIGOS', 'ACCESORIOS'],
            'FALDAS' => ['BLUSAS', 'POLOS Y KNITWEAR', 'CAMISAS', 'BLAZERS'],
            'ACCESORIOS' => ['VESTIDOS', 'POLOS Y KNITWEAR', 'CAMISAS', 'PANTALONES'],
            'CONJUNTOS' => ['ACCESORIOS', 'ABRIGOS'],
        ];

        $categories = Categoria::query()->get(['id_categoria', 'nom_cat'])->keyBy('nom_cat');
        $now = now();

        foreach ($rules as $originName => $destinations) {
            $origin = $categories->get($originName);
            if (! $origin) continue;

            foreach ($destinations as $index => $destinationName) {
                $destination = $categories->get($destinationName);
                if (! $destination || $origin->id_categoria === $destination->id_categoria) continue;

                DB::table('categoria_complementarias')->insertOrIgnore([
                    'cod_categoria_origen_cc' => $origin->id_categoria,
                    'cod_categoria_destino_cc' => $destination->id_categoria,
                    'pri_cc' => $index + 1,
                    'est_cc' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
