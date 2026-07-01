<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\PrendaDanioReporte;
use App\Services\Vendedor\VendedorDamagedProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorDamagedProductController extends Controller
{
    public function index(Request $request, VendedorDamagedProductService $damagedProducts)
    {
        return Inertia::render('Vendedor/DamagedProducts/Index', $damagedProducts->indexPayload($request));
    }

    public function create(VendedorDamagedProductService $damagedProducts)
    {
        return Inertia::render('Vendedor/DamagedProducts/Create', $damagedProducts->createPayload());
    }

    public function store(Request $request, VendedorDamagedProductService $damagedProducts): RedirectResponse
    {
        $report = $damagedProducts->store($request);

        return redirect()
            ->route('vendedor.prendas-daniadas.show', $report)
            ->with('success', 'Reporte de prenda dañada creado. Queda pendiente de revisión administrativa.');
    }

    public function show(Request $request, PrendaDanioReporte $reporte, VendedorDamagedProductService $damagedProducts)
    {
        return Inertia::render('Vendedor/DamagedProducts/Show', $damagedProducts->showPayload($request, $reporte));
    }
}
