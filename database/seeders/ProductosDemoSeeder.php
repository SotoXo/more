<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $categorias = DB::table('categorias')->pluck('id_categoria', 'nom_cat');
        $marcas = DB::table('marcas')->pluck('id_marca', 'nom_mar');
        $colores = DB::table('colores')->pluck('id_color', 'nom_col');
        $tallas = DB::table('tallas')->pluck('id_talla', 'nom_tal');
        $temporadas = DB::table('temporadas')->pluck('id_temporada', 'nom_tem');
        $usuario = DB::table('users')->where('email', 'superadmin@more.com')->value('id');

        $productos = [
            ['nom' => 'Blazer Structured Classic', 'cat' => 'Blazers', 'marca' => 'Zara', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 459.90],
            ['nom' => 'Wide Leg Tailored Pant', 'cat' => 'Pantalones', 'marca' => 'Massimo Dutti', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 389.90],
            ['nom' => 'Camisa Crisp Oversized', 'cat' => 'Camisas', 'marca' => 'Zara', 'temp' => 'Primavera-Verano', 'gen' => 'mujer', 'precio' => 279.90],
            ['nom' => 'Trench Minimal', 'cat' => 'Abrigos', 'marca' => 'Mango', 'temp' => 'Otono-Invierno', 'gen' => 'mujer', 'precio' => 599.90],
            ['nom' => 'Vestido Satin Midi', 'cat' => 'Vestidos', 'marca' => 'Stradivarius', 'temp' => 'Resort', 'gen' => 'mujer', 'precio' => 349.90],
            ['nom' => 'Falda Soft Pleat', 'cat' => 'Faldas', 'marca' => 'Reserved', 'temp' => 'Primavera-Verano', 'gen' => 'mujer', 'precio' => 239.90],
            ['nom' => 'Knit Polo Relaxed', 'cat' => 'Polos y Knitwear', 'marca' => 'COS', 'temp' => 'Capsule', 'gen' => 'hombre', 'precio' => 319.90],
            ['nom' => 'Camisa Linen Edit', 'cat' => 'Camisas', 'marca' => 'Ralph Lauren', 'temp' => 'Primavera-Verano', 'gen' => 'hombre', 'precio' => 429.90],
            ['nom' => 'Tailored Trousers', 'cat' => 'Pantalones', 'marca' => 'Hugo Boss', 'temp' => 'Capsule', 'gen' => 'hombre', 'precio' => 489.90],
            ['nom' => 'Soft Knit Crew', 'cat' => 'Polos y Knitwear', 'marca' => 'Massimo Dutti', 'temp' => 'Otono-Invierno', 'gen' => 'hombre', 'precio' => 359.90],
            ['nom' => 'Weekend Hoodie Clean', 'cat' => 'Polos y Knitwear', 'marca' => 'Pull&Bear', 'temp' => 'Resort', 'gen' => 'hombre', 'precio' => 259.90],
            ['nom' => 'City Coat Line', 'cat' => 'Abrigos', 'marca' => 'Hugo Boss', 'temp' => 'Otono-Invierno', 'gen' => 'hombre', 'precio' => 649.90],
            ['nom' => 'Chelsea Boot Edit', 'cat' => 'Accesorios', 'marca' => 'Bershka', 'temp' => 'Otono-Invierno', 'gen' => 'hombre', 'precio' => 299.90],
            ['nom' => 'Leather Belt Minimal', 'cat' => 'Accesorios', 'marca' => 'Ralph Lauren', 'temp' => 'Capsule', 'gen' => 'hombre', 'precio' => 189.90],
            ['nom' => 'Structured Handbag', 'cat' => 'Accesorios', 'marca' => 'Mango', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 329.90],
            ['nom' => 'Evening Satin Top', 'cat' => 'Camisas', 'marca' => 'Stradivarius', 'temp' => 'Resort', 'gen' => 'mujer', 'precio' => 219.90],
            ['nom' => 'Quiet Luxury Blouse', 'cat' => 'Camisas', 'marca' => 'COS', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 269.90],
            ['nom' => 'Soft Wool Blazer', 'cat' => 'Blazers', 'marca' => 'Hugo Boss', 'temp' => 'Otono-Invierno', 'gen' => 'hombre', 'precio' => 579.90],
            ['nom' => 'Pleated Office Skirt', 'cat' => 'Faldas', 'marca' => 'Zara', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 259.90],
            ['nom' => 'Minimal Resort Dress', 'cat' => 'Vestidos', 'marca' => 'Mango', 'temp' => 'Resort', 'gen' => 'mujer', 'precio' => 369.90],
            ['nom' => 'Urban Layer Shirt', 'cat' => 'Camisas', 'marca' => 'Bershka', 'temp' => 'Resort', 'gen' => 'hombre', 'precio' => 229.90],
            ['nom' => 'Soft Neutral Trouser', 'cat' => 'Pantalones', 'marca' => 'Reserved', 'temp' => 'Capsule', 'gen' => 'hombre', 'precio' => 309.90],
            ['nom' => 'Classic Tote Edit', 'cat' => 'Accesorios', 'marca' => 'Massimo Dutti', 'temp' => 'Capsule', 'gen' => 'mujer', 'precio' => 379.90],
            ['nom' => 'Minimal Knit Dress', 'cat' => 'Vestidos', 'marca' => 'COS', 'temp' => 'Otono-Invierno', 'gen' => 'mujer', 'precio' => 399.90],
        ];

        $variantesPorGenero = [
            'mujer' => [
                ['color' => 'Negro', 'talla' => 'S'],
                ['color' => 'Beige', 'talla' => 'M'],
                ['color' => 'Blanco', 'talla' => 'L'],
            ],
            'hombre' => [
                ['color' => 'Azul Marino', 'talla' => 'M'],
                ['color' => 'Gris', 'talla' => 'L'],
                ['color' => 'Camel', 'talla' => 'XL'],
            ],
        ];

        foreach ($productos as $index => $producto) {
            $prendaId = DB::table('prendas')->insertGetId([
                'nom_pre' => $producto['nom'],
                'des_pre' => 'Prenda demo para catálogo inicial de MORE / Mati Boutique.',
                'cod_categorias_pre' => $categorias[$producto['cat']],
                'cod_marcas_pre' => $marcas[$producto['marca']],
                'cod_temporadas_pre' => $temporadas[$producto['temp']],
                'gen_pre' => $producto['gen'],
                'prb_pre' => $producto['precio'],
                'est_pre' => true,
                'cod_usuario_registra_pre' => $usuario,
                'fec_pre' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_prenda');

            DB::table('imagenes_prenda')->insert([
                [
                    'cod_prendas_igp' => $prendaId,
                    'cod_prenda_variantes_igp' => null,
                    'rut_igp' => "productos/demo-" . ($index + 1) . "-1.jpg",
                    'url_igp' => "/storage/productos/demo-" . ($index + 1) . "-1.jpg",
                    'pri_igp' => true,
                    'ord_igp' => 1,
                    'fue_igp' => 'seed',
                    'fec_igp' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'cod_prendas_igp' => $prendaId,
                    'cod_prenda_variantes_igp' => null,
                    'rut_igp' => "productos/demo-" . ($index + 1) . "-2.jpg",
                    'url_igp' => "/storage/productos/demo-" . ($index + 1) . "-2.jpg",
                    'pri_igp' => false,
                    'ord_igp' => 2,
                    'fue_igp' => 'seed',
                    'fec_igp' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $variantes = $variantesPorGenero[$producto['gen']];
            $cantidadVariantes = rand(2, 3);

            for ($i = 0; $i < $cantidadVariantes; $i++) {
                $var = $variantes[$i];
                $sku = 'MORE-' . str_pad((string)$prendaId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr($var['color'], 0, 3)) . '-' . $var['talla'];

                $varianteId = DB::table('prenda_variantes')->insertGetId([
                    'cod_prendas_prv' => $prendaId,
                    'cod_colores_prv' => $colores[$var['color']],
                    'cod_tallas_prv' => $tallas[$var['talla']],
                    'sku_prv' => $sku,
                    'cob_prv' => null,
                    'pve_prv' => $producto['precio'] + rand(0, 40),
                    'cos_prv' => $producto['precio'] * 0.6,
                    'est_prv' => true,
                    'fec_prv' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'id_prenda_variante');

                DB::table('inventarios')->insert([
                    'cod_prenda_variantes_inv' => $varianteId,
                    'cdi_inv' => rand(3, 15),
                    'cre_inv' => 0,
                    'ubi_inv' => 'Sucursal Central',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}