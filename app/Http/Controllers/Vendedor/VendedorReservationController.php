<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\Vendedor\VendedorReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorReservationController extends Controller
{
    public function index(Request $request, VendedorReservationService $reservations)
    {
        return Inertia::render('Vendedor/Reservations/Index', $reservations->indexPayload($request));
    }

    public function create(Request $request, VendedorReservationService $reservations)
    {
        return Inertia::render('Vendedor/Reservations/Create', $reservations->createPayload($request));
    }

    public function store(Request $request, VendedorReservationService $reservations): RedirectResponse
    {
        $reservation = $reservations->store($request);

        return redirect()
            ->route('vendedor.reservas.show', $reservation)
            ->with('success', 'Reserva registrada. El pago quedó confirmado automáticamente.');
    }

    public function show(Request $request, Reserva $reserva, VendedorReservationService $reservations)
    {
        return Inertia::render('Vendedor/Reservations/Show', $reservations->showPayload($request, $reserva));
    }

    public function storePayment(Request $request, Reserva $reserva, VendedorReservationService $reservations): RedirectResponse
    {
        $reservations->storePayment($request, $reserva);

        return redirect()
            ->route('vendedor.reservas.show', $reserva)
            ->with('success', 'Pago registrado y confirmado automaticamente.');
    }

    public function cancel(Request $request, Reserva $reserva, VendedorReservationService $reservations): RedirectResponse
    {
        $reservations->cancel($request, $reserva);

        return redirect()
            ->route('vendedor.reservas.show', $reserva)
            ->with('success', 'Reserva cancelada y stock liberado.');
    }
}
