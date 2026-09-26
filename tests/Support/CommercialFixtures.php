<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

trait CommercialFixtures
{
    protected function fixtures(): array
    {
        Notification::fake();
        Storage::fake('public');
        $customer = User::factory()->create();
        $category = DB::table('categorias')->insertGetId(['nom_cat' => 'QA_TEST_ROPA', 'est_cat' => true], 'id_categoria');
        $color = DB::table('colores')->insertGetId(['nom_col' => 'QA_TEST_NEGRO', 'est_col' => true], 'id_color');
        $size = DB::table('tallas')->insertGetId(['nom_tal' => 'QA_TEST_L', 'est_tal' => true], 'id_talla');
        $product = DB::table('prendas')->insertGetId(['nom_pre' => 'QA_TEST_POLO', 'cod_usuario_registra_pre' => $customer->id, 'cod_categorias_pre' => $category, 'gen_pre' => 'unisex', 'prb_pre' => 150, 'est_pre' => true], 'id_prenda');
        $variant = DB::table('prenda_variantes')->insertGetId(['cod_prendas_prv' => $product, 'cod_colores_prv' => $color, 'cod_tallas_prv' => $size, 'sku_prv' => 'QA_TEST_SKU', 'pve_prv' => 150, 'cos_prv' => 50, 'est_prv' => true], 'id_prenda_variante');
        DB::table('inventarios')->insert(['cod_prenda_variantes_inv' => $variant, 'cdi_inv' => 3, 'cre_inv' => 0, 'ubi_inv' => 'TIENDA']);

        return [$customer, $variant];
    }

}
