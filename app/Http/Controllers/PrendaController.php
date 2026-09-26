<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Prenda;
use App\Services\ComplementaryProductService;
use App\Services\ProductMapper;
use Inertia\Inertia;

class PrendaController extends Controller
{
    public function __construct(private ComplementaryProductService $complements)
    {
    }

    public function show(string $id)
    {
        $prenda = ProductMapper::withRelations(Prenda::where('est_pre', true))
            ->findOrFail($id);

        $prenda->loadMissing(['patrones', 'texturas', 'materiales', 'variantes.colores']);
        $product = ProductMapper::toShopProduct($prenda);
        $details = [
            'patterns' => $prenda->patrones->pluck('nom_pat')->values()->all(),
            'textures' => $prenda->texturas->pluck('nom_tex')->values()->all(),
            'materials' => $prenda->materiales->sortBy('pivot.ord_pmt')->map(fn ($material) => [
                'name' => $material->nom_mat,
                'percentage' => $material->pivot->porcentaje_pmt,
            ])->values()->all(),
        ];

        // Build detailed variants for the product page
        $variants = $prenda->variantes
            ->filter(fn($v) => $v->est_prv)
            ->map(fn($v) => [
                'id'        => $v->id_prenda_variante,
                'sku'       => $v->sku_prv,
                'color'     => $v->color?->nom_col ?? '',
                'color_hex' => $v->color?->hex_col ?? null,
                'palette_type' => $v->tip_pal_prv ?? 'dominante',
                'palette' => $v->colores->sortBy('pivot.ord_pvc')->map(fn ($color) => ['name' => $color->nom_col, 'hex' => $color->hex_col])->values()->all(),
                'talla'     => $v->talla?->nom_tal ?? '',
                'precio'    => (float) $v->pve_prv,
                'stock'     => max(0, (int) ($v->inventario?->cdi_inv ?? 0)),
            ])
            ->values()
            ->toArray();

        $complementaryProducts = ProductMapper::toShopProductArray(
            $this->complements->recommend($prenda)->pluck('product')
        );

        $isFavorite = false;
        if ($user = auth()->user()) {
            $isFavorite = Favorito::where('cod_usuarios_fav', $user->id)
                ->where('cod_prendas_fav', $prenda->id_prenda)
                ->exists();

            $complementaryProducts = ProductMapper::attachFavoriteState($complementaryProducts, $user);
        }

        $product['isFavorite'] = $isFavorite;

        return Inertia::render('Shop/Product', [
            'product'        => $product,
            'variants'       => $variants,
            'details'        => $details,
            'complementaryProducts' => $complementaryProducts,
            'isFavorite'     => $isFavorite,
        ]);
    }
}
