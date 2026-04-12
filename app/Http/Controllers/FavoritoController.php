<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Prenda;
use App\Services\ProductMapper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoritoController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $prendaIds = Favorito::where('cod_usuarios_fav', $userId)
            ->orderByDesc('created_at')
            ->pluck('cod_prendas_fav');

        $prendas = ProductMapper::withRelations(
            Prenda::where('est_pre', true)->whereIn('id_prenda', $prendaIds)
        )->get();

        // Preserve the favorites order
        $prendas = $prendas->sortBy(fn($p) => $prendaIds->search($p->id_prenda))->values();

        $products = ProductMapper::toShopProductArray($prendas);

        return Inertia::render('Cuenta/Favoritos', [
            'products' => $products,
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate(['prenda_id' => 'required|integer|exists:prendas,id_prenda']);

        $userId   = auth()->id();
        $prendaId = $request->prenda_id;

        $favorito = Favorito::where('cod_usuarios_fav', $userId)
            ->where('cod_prendas_fav', $prendaId)
            ->first();

        if ($favorito) {
            $favorito->delete();
            $isFavorite = false;
        } else {
            Favorito::create([
                'cod_usuarios_fav' => $userId,
                'cod_prendas_fav'  => $prendaId,
                'fec_fav'          => now(),
            ]);
            $isFavorite = true;
        }

        return response()->json(['is_favorite' => $isFavorite]);
    }
}
