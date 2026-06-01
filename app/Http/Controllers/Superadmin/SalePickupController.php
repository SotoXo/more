<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\Superadmin\SalePickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalePickupController extends Controller
{
    public function __construct(private readonly SalePickupService $pickup)
    {
    }

    public function markReady(Request $request, Venta $venta): RedirectResponse
    {
        $this->pickup->markReadyForPickup($venta, $request->user());

        return redirect()
            ->back()
            ->with('success', 'Venta marcada como lista para recojo.');
    }

    public function markDelivered(Request $request, Venta $venta): RedirectResponse
    {
        $this->pickup->markDelivered($venta, $request->user());

        return redirect()
            ->back()
            ->with('success', 'Venta marcada como entregada.');
    }
}
