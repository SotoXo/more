<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\ReportExportService;
use App\Services\Superadmin\ReportPrintService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(string $type, Request $request, ReportExportService $exports): StreamedResponse
    {
        return $exports->csvResponse($type, $request);
    }

    public function print(string $type, Request $request, ReportPrintService $prints)
    {
        return Inertia::render('Superadmin/ReportPrint', $prints->payload($type, $request));
    }
}
