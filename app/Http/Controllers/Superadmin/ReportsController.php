<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\ReportQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request, ReportQueryService $reports)
    {
        return Inertia::render('Superadmin/Reports', $reports->payload($request));
    }
}
