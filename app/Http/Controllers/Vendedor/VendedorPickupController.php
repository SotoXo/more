<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Venta;
use App\Services\Vendedor\VendedorPickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorPickupController extends Controller
{
    public function index(Request $request, VendedorPickupService $pickups)
    {
        return Inertia::render('Vendedor/Pickups/Index', $pickups->indexPayload($request));
    }

    public function sale(Request $request, Venta $venta, VendedorPickupService $pickups)
    {
        return Inertia::render('Vendedor/Pickups/SaleShow', $pickups->saleShowPayload($request, $venta));
    }

    public function deliverSale(Request $request, Venta $venta, VendedorPickupService $pickups): RedirectResponse
    {
        $pickups->deliverSale($request, $venta);

        return redirect()
            ->route('vendedor.recojos.ventas.show', $venta)
            ->with('success', 'Venta marcada como entregada.');
    }

    public function reservation(Request $request, Reserva $reserva, VendedorPickupService $pickups)
    {
        return Inertia::render('Vendedor/Pickups/ReservationShow', $pickups->reservationShowPayload($request, $reserva));
    }

    public function deliverReservation(Request $request, Reserva $reserva, VendedorPickupService $pickups): RedirectResponse
    {
        $pickups->deliverReservation($request, $reserva);

        return redirect()
            ->route('vendedor.recojos.reservas.show', $reserva)
            ->with('success', 'Reserva marcada como recogida.');
    }
}
