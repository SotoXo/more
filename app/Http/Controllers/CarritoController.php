<?php

namespace App\Http\Controllers;

use App\Models\PrendaVariante;
use App\Services\Superadmin\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'paymentMethods' => $this->onlinePaymentMethods(),
        ]);
    }

    public function confirmar(Request $request, ReservationService $reservations)
    {
        $this->ensureCustomerCanReserve($request);

        $items = collect($this->getCartItems());

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'reservation_items' => 'Selecciona al menos una prenda.',
            ]);
        }

        $request->merge([
            'cod_usuario_cliente_res' => $request->user()->id,
            'des_res' => 0,
            'reservation_items' => $items
                ->map(fn (array $item) => [
                    'cod_prenda_variantes_dre' => $item['variante_id'],
                    'can_dre' => $item['cantidad'],
                ])
                ->values()
                ->all(),
        ]);

        $validated = $reservations->validateCreate($request);
        $reservations->createReservation($request, $validated);

        session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('cuenta.reservas')
            ->with('success', 'Tu reserva fue enviada a revisión. Te notificaremos cuando sea confirmada.');
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'variante_id' => 'required|integer|exists:prenda_variantes,id_prenda_variante',
            'cantidad'    => 'required|integer|min:1',
        ]);

        $varianteId = $request->variante_id;
        $cantidad   = $request->cantidad;

        $variante = $this->reservableVariant($varianteId);
        $stock = max(0, (int) $variante->inventario->cdi_inv);

        if ($stock === 0 || $stock < $cantidad) {
            return response()->json(['message' => 'No hay stock suficiente para reservar esa cantidad.'], 422);
        }

        $carrito = session(self::SESSION_KEY, []);
        $key     = (string) $varianteId;

        $cantidadActual = $carrito[$key]['cantidad'] ?? 0;
        $cantidadFinal  = $cantidadActual + $cantidad;

        if ($cantidadFinal > $stock) {
            return response()->json(['message' => 'No hay stock suficiente para reservar esa cantidad.'], 422);
        }

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
            'message'   => 'Prenda agregada a la bolsa de reservas.',
            'cart_count' => $this->cartCount($carrito),
        ]);
    }

    public function actualizar(Request $request, int $varianteId)
    {
        $request->validate(['cantidad' => 'required|integer|min:1']);

        $carrito = session(self::SESSION_KEY, []);
        $key     = (string) $varianteId;

        if (isset($carrito[$key])) {
            $variante = $this->reservableVariant($varianteId);
            $stock = max(0, (int) $variante->inventario->cdi_inv);

            if ($stock < $request->integer('cantidad')) {
                return response()->json(['message' => 'No hay stock suficiente para reservar esa cantidad.'], 422);
            }

            $carrito[$key]['cantidad'] = $request->cantidad;
            session([self::SESSION_KEY => $carrito]);
        }

        return response()->json([
            'success' => true,
            'cart_count' => $this->cartCount($carrito),
        ]);
    }

    public function eliminar(int $varianteId)
    {
        $carrito = session(self::SESSION_KEY, []);
        unset($carrito[(string) $varianteId]);
        session([self::SESSION_KEY => $carrito]);

        return response()->json([
            'success' => true,
            'cart_count' => $this->cartCount($carrito),
        ]);
    }

    public function vaciar()
    {
        session()->forget(self::SESSION_KEY);

        return response()->json([
            'success' => true,
            'cart_count' => 0,
        ]);
    }

    private function getCartItems(): array
    {
        return array_values(session(self::SESSION_KEY, []));
    }

    private function cartCount(array $carrito): int
    {
        return collect($carrito)->sum(fn (array $item) => (int) ($item['cantidad'] ?? 0));
    }

    private function reservableVariant(int $variantId): PrendaVariante
    {
        $variant = PrendaVariante::query()
            ->with(['prenda.marca', 'prenda.imagenes', 'color', 'talla', 'inventario'])
            ->find($variantId);

        if (!$variant || !$variant->est_prv || !$variant->prenda?->est_pre) {
            throw ValidationException::withMessages([
                'variante_id' => 'La prenda seleccionada ya no está activa para reservas.',
            ]);
        }

        if (!$variant->inventario) {
            throw ValidationException::withMessages([
                'variante_id' => 'La variante seleccionada no tiene inventario registrado.',
            ]);
        }

        return $variant;
    }

    private function ensureCustomerCanReserve(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => 'Debes iniciar sesión para finalizar la reserva.',
            ]);
        }

        $user->loadMissing('rol');
        $role = mb_strtolower((string) ($user->rol?->nom_rol ?? ''), 'UTF-8');

        if (!$user->isActive() || !$user->rol?->isActive() || $role !== 'cliente') {
            throw ValidationException::withMessages([
                'auth' => 'Debes usar una cuenta de cliente para realizar una reserva.',
            ]);
        }
    }

    private function onlinePaymentMethods(): array
    {
        return [
            ['label' => 'QR', 'value' => 'qr'],
            ['label' => 'Transferencia bancaria', 'value' => 'transferencia_bancaria'],
        ];
    }
}
