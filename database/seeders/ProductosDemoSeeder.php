<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Database\Seeders\Concerns\InteractsWithCatalogMasters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductosDemoSeeder extends Seeder
{
    use InteractsWithCatalogMasters;

    public function run(): void
    {
        $now = Carbon::now();

        $categorias = DB::table('categorias')->select('id_categoria', 'nom_cat')->get();
        $marcas = DB::table('marcas')->select('id_marca', 'nom_mar')->get();
        $colores = DB::table('colores')->select('id_color', 'nom_col')->get();
        $tallas = DB::table('tallas')->select('id_talla', 'nom_tal')->get();
        $temporadas = DB::table('temporadas')->select('id_temporada', 'nom_tem')->get();

        $usuario = DB::table('users')
            ->where('email', 'superadmin@more.com')
            ->value('id');

        if (!$usuario) {
            return;
        }

        foreach ($this->demoProducts() as $product) {
            $productId = $this->upsertDemoProduct(
                [
                    'nom_pre' => $product['nom_pre'],
                    'des_pre' => $product['des_pre'] ?? null,
                    'cod_categorias_pre' => $this->masterId($categorias, 'nom_cat', 'id_categoria', $product['categoria']),
                    'cod_marcas_pre' => $this->masterId($marcas, 'nom_mar', 'id_marca', $product['marca']),
                    'cod_temporadas_pre' => $this->masterId($temporadas, 'nom_tem', 'id_temporada', $product['temporada']),
                    'gen_pre' => $product['genero'],
                    'prb_pre' => $product['precio'],
                    'est_pre' => true,
                    'cod_usuario_registra_pre' => $usuario,
                    'fec_pre' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $this->resetDemoChildren($productId);
            $this->seedDemoImages($productId, $product['image_slug'], $now);
            $this->seedDemoVariants($productId, $product, $colores, $tallas, $now);
        }
    }

    private function upsertDemoProduct(array $payload): int
    {
        $matches = DB::table('prendas')
            ->select('id_prenda', 'nom_pre')
            ->get()
            ->filter(fn ($row) => $this->normalizeCatalogSeedKey((string) $row->nom_pre) === $this->normalizeCatalogSeedKey((string) $payload['nom_pre']))
            ->sortBy('id_prenda')
            ->values();

        $insertPayload = $payload;
        $updatePayload = array_diff_key($payload, ['created_at' => true]);

        if ($matches->isEmpty()) {
            return (int) DB::table('prendas')->insertGetId($insertPayload, 'id_prenda');
        }

        $productId = (int) $matches->first()->id_prenda;

        DB::table('prendas')
            ->where('id_prenda', $productId)
            ->update($updatePayload);

        return $productId;
    }

    private function resetDemoChildren(int $productId): void
    {
        $variantIds = DB::table('prenda_variantes')
            ->where('cod_prendas_prv', $productId)
            ->pluck('id_prenda_variante');

        if ($variantIds->isNotEmpty()) {
            DB::table('inventarios')
                ->whereIn('cod_prenda_variantes_inv', $variantIds)
                ->delete();

            DB::table('imagenes_prenda')
                ->whereIn('cod_prenda_variantes_igp', $variantIds)
                ->delete();

            DB::table('prenda_variantes')
                ->whereIn('id_prenda_variante', $variantIds)
                ->delete();
        }

        DB::table('imagenes_prenda')
            ->where('cod_prendas_igp', $productId)
            ->delete();
    }

    private function seedDemoImages(int $productId, string $imageSlug, Carbon $now): void
    {
        $basePath = 'productos/demo/' . $imageSlug;

        DB::table('imagenes_prenda')->insert([
            [
                'cod_prendas_igp' => $productId,
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $basePath . '/principal.jpg',
                'url_igp' => '/storage/' . $basePath . '/principal.jpg',
                'pri_igp' => true,
                'ord_igp' => 0,
                'fue_igp' => 'DEMO_LOCAL',
                'fec_igp' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'cod_prendas_igp' => $productId,
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $basePath . '/detalle.jpg',
                'url_igp' => '/storage/' . $basePath . '/detalle.jpg',
                'pri_igp' => false,
                'ord_igp' => 1,
                'fue_igp' => 'DEMO_LOCAL',
                'fec_igp' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function seedDemoVariants(
        int $productId,
        array $product,
        Collection $colores,
        Collection $tallas,
        Carbon $now,
    ): void {
        $baseCode = $this->buildInternalProductCode($product['categoria'], $productId);
        $variantCount = count($product['variants']);

        foreach ($product['variants'] as $index => $variant) {
            $price = (float) ($variant['price'] ?? $product['precio']);
            $cost = (float) ($variant['cost'] ?? round($price * 0.58, 2));

            $variantId = DB::table('prenda_variantes')->insertGetId([
                'cod_prendas_prv' => $productId,
                'cod_colores_prv' => $this->masterId($colores, 'nom_col', 'id_color', $variant['color']),
                'cod_tallas_prv' => $this->masterId($tallas, 'nom_tal', 'id_talla', $variant['talla']),
                'sku_prv' => $variantCount > 1
                    ? $baseCode . '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)
                    : $baseCode,
                'cob_prv' => null,
                'pve_prv' => $price,
                'cos_prv' => $cost,
                'est_prv' => true,
                'fec_prv' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_prenda_variante');

            DB::table('inventarios')->insert([
                'cod_prenda_variantes_inv' => $variantId,
                'cdi_inv' => (int) $variant['stock'],
                'cre_inv' => 0,
                'ubi_inv' => $variant['location'] ?? 'DEPOSITO',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function masterId(Collection $rows, string $nameColumn, string $idColumn, string $canonicalName): int
    {
        $match = $rows->first(
            fn ($row) => $this->normalizeCatalogSeedKey((string) $row->{$nameColumn}) === $this->normalizeCatalogSeedKey($canonicalName),
        );

        if (!$match) {
            throw new \RuntimeException("No se encontro {$canonicalName} en {$nameColumn}.");
        }

        return (int) $match->{$idColumn};
    }

    private function buildInternalProductCode(string $categoryName, int $productId): string
    {
        $prefix = Str::of(Str::ascii($categoryName))
            ->upper()
            ->replaceMatches('/[^A-Z]/', '')
            ->substr(0, 3)
            ->padRight(3, 'X');

        return $prefix . '-' . str_pad((string) $productId, 5, '0', STR_PAD_LEFT);
    }

    private function demoProducts(): array
    {
        return [
            [
                'nom_pre' => 'ABRIGO CRUZADO DE LANA',
                'categoria' => 'ABRIGOS',
                'marca' => 'MASSIMO DUTTI',
                'temporada' => 'OTOÑO-INVIERNO',
                'genero' => 'mujer',
                'precio' => 780.00,
                'image_slug' => 'abrigo-cruzado-de-lana',
                'variants' => [
                    ['color' => 'CAMEL', 'talla' => 'S', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'CAMEL', 'talla' => 'M', 'stock' => 5, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'BLAZER ESTRUCTURADO CLASICO',
                'categoria' => 'BLAZERS',
                'marca' => 'ZARA',
                'temporada' => 'CAPSULE',
                'genero' => 'mujer',
                'precio' => 459.90,
                'image_slug' => 'blazer-estructurado-clasico',
                'variants' => [
                    ['color' => 'NEGRO', 'talla' => 'S', 'stock' => 6, 'location' => 'TIENDA'],
                    ['color' => 'BEIGE', 'talla' => 'M', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CAMISA OVERSIZE DE ALGODON',
                'categoria' => 'CAMISAS',
                'marca' => 'RALPH LAUREN',
                'temporada' => 'PRIMAVERA-VERANO',
                'genero' => 'mujer',
                'precio' => 329.90,
                'image_slug' => 'camisa-oversize-de-algodon',
                'variants' => [
                    ['color' => 'BLANCO', 'talla' => 'S', 'stock' => 7, 'location' => 'TIENDA'],
                    ['color' => 'BLANCO', 'talla' => 'M', 'stock' => 6, 'location' => 'TIENDA'],
                    ['color' => 'CELESTE', 'talla' => 'L', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'BLUSA SATINADA CUELLO LAZO',
                'categoria' => 'BLUSAS',
                'marca' => 'MANGO',
                'temporada' => 'RESORT',
                'genero' => 'mujer',
                'precio' => 289.90,
                'image_slug' => 'blusa-satinada-cuello-lazo',
                'variants' => [
                    ['color' => 'ROSA PALO', 'talla' => 'S', 'stock' => 5, 'location' => 'TIENDA'],
                    ['color' => 'CREMA', 'talla' => 'M', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'VESTIDO MIDI SATINADO',
                'categoria' => 'VESTIDOS',
                'marca' => 'STRADIVARIUS',
                'temporada' => 'RESORT',
                'genero' => 'mujer',
                'precio' => 399.90,
                'image_slug' => 'vestido-midi-satinado',
                'variants' => [
                    ['color' => 'BORGONA', 'talla' => 'S', 'stock' => 3, 'location' => 'TIENDA'],
                    ['color' => 'NEGRO', 'talla' => 'M', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'FALDA PLISADA FLUIDA',
                'categoria' => 'FALDAS',
                'marca' => 'RESERVED',
                'temporada' => 'CAPSULE',
                'genero' => 'mujer',
                'precio' => 259.90,
                'image_slug' => 'falda-plisada-fluida',
                'variants' => [
                    ['color' => 'GRIS', 'talla' => 'S', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'NEGRO', 'talla' => 'M', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'PANTALON SASTRERO WIDE LEG',
                'categoria' => 'PANTALONES',
                'marca' => 'MASSIMO DUTTI',
                'temporada' => 'CAPSULE',
                'genero' => 'mujer',
                'precio' => 389.90,
                'image_slug' => 'pantalon-sastrero-wide-leg',
                'variants' => [
                    ['color' => 'BEIGE', 'talla' => '36', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'BEIGE', 'talla' => '38', 'stock' => 5, 'location' => 'TIENDA'],
                    ['color' => 'NEGRO', 'talla' => '40', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'POLO DE PUNTO FINO',
                'categoria' => 'POLOS Y KNITWEAR',
                'marca' => 'COS',
                'temporada' => 'PERMANENTE',
                'genero' => 'hombre',
                'precio' => 319.90,
                'image_slug' => 'polo-de-punto-fino',
                'variants' => [
                    ['color' => 'AZUL MARINO', 'talla' => 'M', 'stock' => 5, 'location' => 'TIENDA'],
                    ['color' => 'CREMA', 'talla' => 'L', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CONJUNTO SASTRERO TONAL',
                'categoria' => 'CONJUNTOS',
                'marca' => 'HUGO BOSS',
                'temporada' => 'CAPSULE',
                'genero' => 'hombre',
                'precio' => 699.90,
                'image_slug' => 'conjunto-sastrero-tonal',
                'variants' => [
                    ['color' => 'AZUL MARINO', 'talla' => 'M', 'stock' => 2, 'location' => 'TIENDA'],
                    ['color' => 'GRIS', 'talla' => 'L', 'stock' => 2, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CINTURON DE CUERO LISO',
                'categoria' => 'ACCESORIOS',
                'marca' => 'RALPH LAUREN',
                'temporada' => 'PERMANENTE',
                'genero' => 'unisex',
                'precio' => 189.90,
                'image_slug' => 'cinturon-de-cuero-liso',
                'variants' => [
                    ['color' => 'NEGRO', 'talla' => 'UNITALLA', 'stock' => 8, 'location' => 'TIENDA'],
                    ['color' => 'MARRÓN', 'talla' => 'UNITALLA', 'stock' => 6, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CARTERA ESTRUCTURADA MINI',
                'categoria' => 'ACCESORIOS',
                'marca' => 'MANGO',
                'temporada' => 'CAPSULE',
                'genero' => 'mujer',
                'precio' => 359.90,
                'image_slug' => 'cartera-estructurada-mini',
                'variants' => [
                    ['color' => 'NEGRO', 'talla' => 'UNITALLA', 'stock' => 5, 'location' => 'TIENDA'],
                    ['color' => 'BEIGE', 'talla' => 'UNITALLA', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'BLAZER DE LINO SUAVE',
                'categoria' => 'BLAZERS',
                'marca' => 'COS',
                'temporada' => 'PRIMAVERA-VERANO',
                'genero' => 'hombre',
                'precio' => 549.90,
                'image_slug' => 'blazer-de-lino-suave',
                'variants' => [
                    ['color' => 'CREMA', 'talla' => 'M', 'stock' => 3, 'location' => 'TIENDA'],
                    ['color' => 'BEIGE', 'talla' => 'L', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CAMISA DE LINO RELAJADA',
                'categoria' => 'CAMISAS',
                'marca' => 'ZARA',
                'temporada' => 'PRIMAVERA-VERANO',
                'genero' => 'hombre',
                'precio' => 279.90,
                'image_slug' => 'camisa-de-lino-relajada',
                'variants' => [
                    ['color' => 'BLANCO', 'talla' => 'M', 'stock' => 6, 'location' => 'TIENDA'],
                    ['color' => 'CELESTE', 'talla' => 'L', 'stock' => 5, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'POLO KNIT CUELLO ABIERTO',
                'categoria' => 'POLOS Y KNITWEAR',
                'marca' => 'RESERVED',
                'temporada' => 'RESORT',
                'genero' => 'hombre',
                'precio' => 309.90,
                'image_slug' => 'polo-knit-cuello-abierto',
                'variants' => [
                    ['color' => 'VERDE OLIVA', 'talla' => 'M', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'AZUL MARINO', 'talla' => 'L', 'stock' => 4, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'PANTALON RECTO DE GABARDINA',
                'categoria' => 'PANTALONES',
                'marca' => 'HUGO BOSS',
                'temporada' => 'OTOÑO-INVIERNO',
                'genero' => 'hombre',
                'precio' => 489.90,
                'image_slug' => 'pantalon-recto-de-gabardina',
                'variants' => [
                    ['color' => 'GRIS', 'talla' => '40', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'GRIS', 'talla' => '42', 'stock' => 4, 'location' => 'DEPOSITO'],
                    ['color' => 'NEGRO', 'talla' => '44', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'BLUSA VUELO CREPE',
                'categoria' => 'BLUSAS',
                'marca' => 'BERSHKA',
                'temporada' => 'PRIMAVERA-VERANO',
                'genero' => 'mujer',
                'precio' => 219.90,
                'image_slug' => 'blusa-vuelo-crepe',
                'variants' => [
                    ['color' => 'BLANCO', 'talla' => 'S', 'stock' => 6, 'location' => 'TIENDA'],
                    ['color' => 'ROSA PALO', 'talla' => 'M', 'stock' => 5, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'VESTIDO KNIT CANALE',
                'categoria' => 'VESTIDOS',
                'marca' => 'COS',
                'temporada' => 'OTOÑO-INVIERNO',
                'genero' => 'mujer',
                'precio' => 429.90,
                'image_slug' => 'vestido-knit-canale',
                'variants' => [
                    ['color' => 'NEGRO', 'talla' => 'S', 'stock' => 3, 'location' => 'TIENDA'],
                    ['color' => 'BORGONA', 'talla' => 'M', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'ABRIGO CORTO ESPIGA',
                'categoria' => 'ABRIGOS',
                'marca' => 'HUGO BOSS',
                'temporada' => 'OTOÑO-INVIERNO',
                'genero' => 'hombre',
                'precio' => 829.90,
                'image_slug' => 'abrigo-corto-espiga',
                'variants' => [
                    ['color' => 'GRIS', 'talla' => 'M', 'stock' => 2, 'location' => 'TIENDA'],
                    ['color' => 'CAMEL', 'talla' => 'L', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'FALDA SATIN BIAS',
                'categoria' => 'FALDAS',
                'marca' => 'ZARA',
                'temporada' => 'RESORT',
                'genero' => 'mujer',
                'precio' => 279.90,
                'image_slug' => 'falda-satin-bias',
                'variants' => [
                    ['color' => 'CREMA', 'talla' => 'S', 'stock' => 4, 'location' => 'TIENDA'],
                    ['color' => 'BORGONA', 'talla' => 'M', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
            [
                'nom_pre' => 'CONJUNTO DE CHAQUETA Y PANTALON',
                'categoria' => 'CONJUNTOS',
                'marca' => 'PULL&BEAR',
                'temporada' => 'CAPSULE',
                'genero' => 'mujer',
                'precio' => 529.90,
                'image_slug' => 'conjunto-de-chaqueta-y-pantalon',
                'variants' => [
                    ['color' => 'BEIGE', 'talla' => 'S', 'stock' => 3, 'location' => 'TIENDA'],
                    ['color' => 'NEGRO', 'talla' => 'M', 'stock' => 3, 'location' => 'DEPOSITO'],
                ],
            ],
        ];
    }
}
