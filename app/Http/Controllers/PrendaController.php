<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Prenda;
use App\Services\ProductMapper;
use Inertia\Inertia;

class PrendaController extends Controller
{
    public function show(string $id)
    {
        $prenda = ProductMapper::withRelations(Prenda::where('est_pre', true))
            ->findOrFail($id);

        $product = ProductMapper::toShopProduct($prenda);

        // Build detailed variants for the product page
        $variants = $prenda->variantes
            ->filter(fn($v) => $v->est_prv)
            ->map(fn($v) => [
                'id'        => $v->id_prenda_variante,
                'sku'       => $v->sku_prv,
                'color'     => $v->color?->nom_col ?? '',
                'color_hex' => $v->color?->hex_col ?? null,
                'talla'     => $v->talla?->nom_tal ?? '',
                'precio'    => (float) $v->pve_prv,
                'stock'     => max(0, ($v->inventario?->cdi_inv ?? 0) - ($v->inventario?->cre_inv ?? 0)),
            ])
            ->values()
            ->toArray();

        // Related products (same category, different product)
        $relacionadas = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
                ->where('cod_categorias_pre', $prenda->cod_categorias_pre)
                ->where('id_prenda', '!=', $prenda->id_prenda)
        )
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $relatedProducts = ProductMapper::toShopProductArray($relacionadas);

        $completaTuLook = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
                ->where('id_prenda', '!=', $prenda->id_prenda)
                ->where(function ($query) use ($prenda) {
                    if (strcasecmp((string) $prenda->categoria?->nom_cat, 'Accesorios') === 0) {
                        $query->whereHas('categoria', fn($q) => $q->where('nom_cat', '!=', 'Accesorios'));

                        return;
                    }

                    $query
                        ->whereHas('categoria', fn($q) => $q->where('nom_cat', 'Accesorios'))
                        ->orWhere(function ($subQuery) use ($prenda) {
                            $subQuery
                                ->where('gen_pre', $prenda->gen_pre)
                                ->where('cod_categorias_pre', '!=', $prenda->cod_categorias_pre)
                                ->whereHas('categoria', fn($q) => $q->where('nom_cat', '!=', 'Accesorios'));
                        });
                })
        )
            ->orderByDesc('fec_pre')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $completeLookProducts = ProductMapper::toShopProductArray($completaTuLook);

        $isFavorite = false;
        if ($user = auth()->user()) {
            $isFavorite = Favorito::where('cod_usuarios_fav', $user->id)
                ->where('cod_prendas_fav', $prenda->id_prenda)
                ->exists();

            $relatedProducts = ProductMapper::attachFavoriteState($relatedProducts, $user);
            $completeLookProducts = ProductMapper::attachFavoriteState($completeLookProducts, $user);
        }

        $product['isFavorite'] = $isFavorite;

        return Inertia::render('Shop/Product', [
            'product'        => $product,
            'variants'       => $variants,
            'relatedProducts' => $relatedProducts,
            'completeLookProducts' => $completeLookProducts,
            'isFavorite'     => $isFavorite,
        ]);
    }
}
