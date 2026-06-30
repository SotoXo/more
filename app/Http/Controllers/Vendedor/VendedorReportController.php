<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Vendedor\VendedorReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendedorReportController extends Controller
{
    public function index(Request $request, VendedorReportService $reports)
    {
        return Inertia::render('Vendedor/Reports/Index', $reports->indexPayload($request));
    }

    public function export(Request $request, VendedorReportService $reports): StreamedResponse
    {
        return $reports->csvResponse($request);
    }

    public function print(Request $request, VendedorReportService $reports)
    {
        return Inertia::render('Vendedor/Reports/Print', $reports->printPayload($request));
    }
}
