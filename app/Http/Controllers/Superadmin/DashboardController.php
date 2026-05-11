<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\DashboardStatsService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $stats)
    {
        return Inertia::render('Superadmin/Dashboard', [
            'dashboard' => $stats->payload(),
        ]);
    }
}
