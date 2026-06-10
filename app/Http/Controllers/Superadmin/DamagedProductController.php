<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PrendaDanioReporte;
use App\Services\Superadmin\DamagedProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DamagedProductController extends Controller
{
    public function __construct(
        private readonly DamagedProductService $damagedProducts,
    ) {
    }

    public function index(Request $request)
    {
        return Inertia::render('Superadmin/DamagedProducts/Index', $this->damagedProducts->indexPayload($request));
    }

    public function create()
    {
        return Inertia::render('Superadmin/DamagedProducts/Create', $this->damagedProducts->createPayload());
    }

    public function store(Request $request): RedirectResponse
    {
        $report = $this->damagedProducts->createReport($request);

        return redirect()
            ->route('superadmin.damaged-products.show', $report)
            ->with('success', 'Reporte de prenda dañada registrado. La creación no movió inventario.');
    }

    public function show(PrendaDanioReporte $reporte)
    {
        return Inertia::render('Superadmin/DamagedProducts/Show', $this->damagedProducts->showPayload($reporte));
    }

    public function markInReview(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->markInReview($request, $reporte);

        return back()->with('success', 'Reporte marcado en revisión.');
    }

    public function confirm(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->confirmDamage($request, $reporte);

        return back()->with('success', 'Daño confirmado. Inventario y Kardex se actualizaron solo si correspondía.');
    }

    public function discard(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->discardReport($request, $reporte);

        return back()->with('success', 'Reporte descartado sin movimientos de inventario.');
    }

    public function repair(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->repairReport($request, $reporte);

        return back()->with('success', 'Reporte reparado. El stock disponible fue devuelto cuando correspondía.');
    }

    public function definitiveLoss(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->markDefinitiveLoss($request, $reporte);

        return back()->with('success', 'Reporte marcado como baja definitiva sin descontar stock nuevamente.');
    }

    public function resolve(Request $request, PrendaDanioReporte $reporte): RedirectResponse
    {
        $this->damagedProducts->resolveReport($request, $reporte);

        return back()->with('success', 'Reporte resuelto.');
    }
}
