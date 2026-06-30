<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Vendedor\VendedorDamagedProductService;
use App\Services\Vendedor\VendedorInventoryQueryService;
use App\Services\Vendedor\VendedorPaymentReviewService;
use App\Services\Vendedor\VendedorPickupService;
use App\Services\Vendedor\VendedorReservationService;
use App\Services\Vendedor\VendedorSalesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorDashboardController extends Controller
{
    public function index(
        Request $request,
        VendedorInventoryQueryService $queries,
        VendedorSalesService $sales,
        VendedorReservationService $reservations,
        VendedorPaymentReviewService $payments,
        VendedorPickupService $pickups,
        VendedorDamagedProductService $damagedProducts,
    )
    {
        $dashboard = $queries->dashboard();
        $salesMetrics = $sales->dashboardMetrics($request);
        $reservationMetrics = $reservations->dashboardMetrics($request);
        $paymentMetrics = $payments->dashboardMetrics($request);
        $pickupMetrics = $pickups->dashboardMetrics($request);
        $damagedProductMetrics = $damagedProducts->dashboardMetrics($request);
        $metrics = array_replace($salesMetrics, $reservationMetrics, $paymentMetrics, $pickupMetrics, $damagedProductMetrics);

        $dashboard['metrics'] = collect($dashboard['metrics'])
            ->map(function (array $metric) use ($metrics) {
                if (! array_key_exists($metric['key'], $metrics)) {
                    return $metric;
                }

                return array_replace($metric, $metrics[$metric['key']]);
            })
            ->values()
            ->all();

        return Inertia::render('Vendedor/Dashboard', [
            'dashboard' => $dashboard,
        ]);
    }
}
