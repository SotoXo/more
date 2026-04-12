<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\ProductMapper;
use Inertia\Inertia;

class CuentaController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $pedidosCount  = Venta::where('cod_usuario_cliente_ven', $user->id)->count();
        $reservasCount = Reserva::where('cod_usuario_cliente_res', $user->id)->count();
        $favoritosCount = Favorito::where('cod_usuarios_fav', $user->id)->count();

        $ultimoPedido = Venta::with('detalles.variante.prenda.imagenes')
            ->where('cod_usuario_cliente_ven', $user->id)
            ->latest('fec_ven')
            ->first();

        return Inertia::render('Cuenta/Index', [
            'pedidosCount'   => $pedidosCount,
            'reservasCount'  => $reservasCount,
            'favoritosCount' => $favoritosCount,
            'ultimoPedido'   => $ultimoPedido ? $this->mapVenta($ultimoPedido) : null,
        ]);
    }

    public function pedidos()
    {
        $user = auth()->user();

        $ventas = Venta::with(['detalles' => fn($q) => $q->with([
            'variante.prenda.imagenes',
            'variante.prenda.marca',
            'variante.color',
            'variante.talla',
        ])])
            ->where('cod_usuario_cliente_ven', $user->id)
            ->orderByDesc('fec_ven')
            ->get();

        $pedidos = $ventas->map(fn($v) => $this->mapVenta($v))->values()->toArray();

        return Inertia::render('Cuenta/Pedidos', [
            'pedidos' => $pedidos,
        ]);
    }

    public function reservas()
    {
        $user = auth()->user();

        $reservas = Reserva::with(['detalles' => fn($q) => $q->with([
            'variante.prenda.imagenes',
            'variante.prenda.marca',
            'variante.color',
            'variante.talla',
        ])])
            ->where('cod_usuario_cliente_res', $user->id)
            ->orderByDesc('fec_res')
            ->get();

        $reservasMapped = $reservas->map(fn($r) => $this->mapReserva($r))->values()->toArray();

        return Inertia::render('Cuenta/Reservas', [
            'reservas' => $reservasMapped,
        ]);
    }

    private function mapVenta(Venta $venta): array
    {
        return [
            'id'       => $venta->id_venta,
            'numero'   => $venta->nve_ven,
            'estado'   => $venta->est_ven,
            'subtotal' => (float) $venta->sub_ven,
            'descuento' => (float) $venta->des_ven,
            'total'    => (float) $venta->tot_ven,
            'pago'     => $venta->mpa_ven,
            'fecha'    => $venta->fec_ven?->toDateString(),
            'items'    => $venta->detalles?->map(fn($d) => [
                'id'       => $d->id_detalle_venta,
                'cantidad' => $d->can_dve,
                'precio'   => (float) $d->pun_dve,
                'subtotal' => (float) $d->sub_dve,
                'nombre'   => $d->variante?->prenda?->nom_pre ?? '',
                'marca'    => $d->variante?->prenda?->marca?->nom_mar ?? '',
                'imagen'   => $d->variante?->prenda?->imagenes?->sortByDesc('pri_igp')->first()?->url_igp ?? '',
                'talla'    => $d->variante?->talla?->nom_tal ?? '',
                'color'    => $d->variante?->color?->nom_col ?? '',
                'slug'     => (string) ($d->variante?->prenda?->id_prenda ?? ''),
            ])->values()->toArray() ?? [],
        ];
    }

    private function mapReserva(Reserva $reserva): array
    {
        return [
            'id'       => $reserva->id_reserva,
            'numero'   => $reserva->nre_res,
            'estado'   => $reserva->est_res,
            'vence'    => $reserva->ven_res?->toDateString(),
            'fecha'    => $reserva->fec_res?->toDateString(),
            'obs'      => $reserva->obs_res,
            'items'    => $reserva->detalles?->map(fn($d) => [
                'id'       => $d->id_detalle_reserva,
                'cantidad' => $d->can_dre,
                'nombre'   => $d->variante?->prenda?->nom_pre ?? '',
                'marca'    => $d->variante?->prenda?->marca?->nom_mar ?? '',
                'imagen'   => $d->variante?->prenda?->imagenes?->sortByDesc('pri_igp')->first()?->url_igp ?? '',
                'talla'    => $d->variante?->talla?->nom_tal ?? '',
                'color'    => $d->variante?->color?->nom_col ?? '',
                'slug'     => (string) ($d->variante?->prenda?->id_prenda ?? ''),
            ])->values()->toArray() ?? [],
        ];
    }
}
