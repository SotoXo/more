<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\Vendedor\VendedorSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorSalesController extends Controller
{
    public function index(Request $request, VendedorSalesService $sales)
    {
        return Inertia::render('Vendedor/Sales/Index', $sales->indexPayload($request));
    }

    public function create(Request $request, VendedorSalesService $sales)
    {
        return Inertia::render('Vendedor/Sales/Create', $sales->createPayload($request));
    }

    public function store(Request $request, VendedorSalesService $sales): RedirectResponse
    {
        $sale = $sales->store($request);

        return redirect()
            ->route('vendedor.ventas.show', $sale)
            ->with('success', 'Venta registrada. El pago quedó confirmado automáticamente.');
    }

    public function show(Request $request, Venta $venta, VendedorSalesService $sales)
    {
        return Inertia::render('Vendedor/Sales/Show', $sales->showPayload($request, $venta));
    }
}
