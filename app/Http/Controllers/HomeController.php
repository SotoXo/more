<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Prenda;
use App\Services\ProductMapper;
use App\Services\StorefrontCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(private StorefrontCatalogService $catalogService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isSuperadmin')) {
            $user->loadMissing('rol');

            if ($user->isSuperadmin()) {
                return redirect()->route('superadmin.dashboard');
            }

            if (method_exists($user, 'isVendedor') && $user->isVendedor()) {
                return redirect()->route('vendedor.dashboard');
            }
        }

        $novedadesPrendas = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
        );
        $this->catalogService->applySeasonOrdering($novedadesPrendas);
        $novedadesPrendas = $novedadesPrendas->limit(8)->get();

        $novedades = ProductMapper::toShopProductArray($novedadesPrendas);
        $novedades = ProductMapper::attachFavoriteState($novedades, auth()->user());

        $seleccionPrendas = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
        );
        $this->catalogService->applySeasonOrdering($seleccionPrendas);
        $seleccionPrendas = $seleccionPrendas->skip(8)->limit(8)->get();

        $seleccion = ProductMapper::toShopProductArray($seleccionPrendas);
        $seleccion = ProductMapper::attachFavoriteState($seleccion, auth()->user());

        $categorias = Categoria::where('est_cat', true)
            ->whereNull('cod_categoria_padre_cat')
            ->orderBy('nom_cat')
            ->get(['id_categoria', 'nom_cat', 'des_cat'])
            ->map(fn($c) => [
                'id'    => $c->id_categoria,
                'name'  => $c->nom_cat,
                'desc'  => $c->des_cat,
                'href'  => '/catalogo?categoria=' . urlencode($c->nom_cat),
            ])
            ->values()
            ->toArray();

        $marcas = Marca::where('est_mar', true)
            ->orderBy('nom_mar')
            ->get(['id_marca', 'nom_mar', 'des_mar'])
            ->map(fn($m) => [
                'id'   => $m->id_marca,
                'name' => $m->nom_mar,
                'href' => '/catalogo?marca=' . urlencode($m->nom_mar),
            ])
            ->values()
            ->toArray();

        return Inertia::render('Home', [
            'novedades' => $novedades,
            'seleccion' => $seleccion,
            'categorias' => $categorias,
            'marcas'     => $marcas,
        ]);
    }
}
