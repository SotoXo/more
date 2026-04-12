<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\PrendaVariante;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarritoController extends Controller
{
    private const SESSION_KEY = 'carrito';

    public function index()
    {
        $items = $this->getCartItems();

        return Inertia::render('Carrito', [
            'items'    => $items,
            'subtotal' => collect($items)->sum(fn($i) => $i['precio'] * $i['cantidad']),
        ]);
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'variante_id' => 'required|integer|exists:prenda_variantes,id_prenda_variante',
            'cantidad'    => 'required|integer|min:1|max:10',
        ]);

        $varianteId = $request->variante_id;
        $cantidad   = $request->cantidad;

        // Check stock
        $inventario = Inventario::where('cod_prenda_variantes_inv', $varianteId)->first();
        $stock = $inventario ? max(0, $inventario->cdi_inv - $inventario->cre_inv) : 0;

        if ($stock === 0) {
            return response()->json(['error' => 'Esta variante no tiene stock disponible.'], 422);
        }

        $carrito = session(self::SESSION_KEY, []);
        $key     = (string) $varianteId;

        $cantidadActual = $carrito[$key]['cantidad'] ?? 0;
        $cantidadFinal  = min($cantidadActual + $cantidad, $stock);

        $variante = PrendaVariante::with(['prenda.marca', 'prenda.imagenes', 'color', 'talla'])
            ->findOrFail($varianteId);

        $imagen = $variante->prenda->imagenes
            ->sortByDesc('pri_igp')->sortBy('ord_igp')
            ->first()?->url_igp ?? '';

        $carrito[$key] = [
            'variante_id' => $varianteId,
            'prenda_id'   => $variante->cod_prendas_prv,
            'cantidad'    => $cantidadFinal,
            'nombre'      => $variante->prenda->nom_pre,
            'marca'       => $variante->prenda->marca?->nom_mar ?? '',
            'imagen'      => $imagen,
            'talla'       => $variante->talla?->nom_tal ?? '',
            'color'       => $variante->color?->nom_col ?? '',
            'precio'      => (float) $variante->pve_prv,
        ];

        session([self::SESSION_KEY => $carrito]);

        return response()->json([
            'message'   => 'Producto agregado al carrito.',
            'cart_count' => count($carrito),
        ]);
    }

    public function actualizar(Request $request, int $varianteId)
    {
        $request->validate(['cantidad' => 'required|integer|min:1|max:10']);

        $carrito = session(self::SESSION_KEY, []);
        $key     = (string) $varianteId;

        if (isset($carrito[$key])) {
            $carrito[$key]['cantidad'] = $request->cantidad;
            session([self::SESSION_KEY => $carrito]);
        }

        return response()->json(['success' => true]);
    }

    public function eliminar(int $varianteId)
    {
        $carrito = session(self::SESSION_KEY, []);
        unset($carrito[(string) $varianteId]);
        session([self::SESSION_KEY => $carrito]);

        return response()->json(['success' => true]);
    }

    public function vaciar()
    {
        session()->forget(self::SESSION_KEY);

        return response()->json(['success' => true]);
    }

    private function getCartItems(): array
    {
        return array_values(session(self::SESSION_KEY, []));
    }
}
