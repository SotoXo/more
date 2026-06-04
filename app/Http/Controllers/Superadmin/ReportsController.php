<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\SystemLogService;
use App\Services\Superadmin\ReportQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request, ReportQueryService $reports)
    {
        app(SystemLogService::class)->info(
            'reportes',
            'reporte_visualizado',
            'Reportes operativos visualizados.',
            null,
            [
                'seccion' => $request->input('section', 'resumen'),
                'filtros' => $request->only(['fecha_desde', 'fecha_hasta', 'estado', 'cliente', 'section']),
            ],
        );

        return Inertia::render('Superadmin/Reports', $reports->payload($request));
    }
}
