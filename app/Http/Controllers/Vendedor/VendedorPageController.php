<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Prenda;
use App\Services\Vendedor\VendedorInventoryQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorPageController extends Controller
{
    public function prendas(Request $request, VendedorInventoryQueryService $queries)
    {
        return Inertia::render('Vendedor/Products/Index', $queries->products($request));
    }

    public function prendaShow(Prenda $prenda, VendedorInventoryQueryService $queries)
    {
        return Inertia::render('Vendedor/Products/Show', $queries->productDetail($prenda));
    }

    public function existencias(Request $request, VendedorInventoryQueryService $queries)
    {
        return Inertia::render('Vendedor/Inventory/Stock', $queries->stock($request));
    }

    public function alertasStock(Request $request, VendedorInventoryQueryService $queries)
    {
        return Inertia::render('Vendedor/Inventory/StockAlerts', $queries->stockAlerts($request));
    }

    public function ventas()
    {
        return Inertia::render('Vendedor/Sales/Index');
    }

    public function reservas()
    {
        return Inertia::render('Vendedor/Reservations/Index');
    }

    public function pagos()
    {
        return Inertia::render('Vendedor/Payments/Index');
    }

    public function recojos()
    {
        return Inertia::render('Vendedor/Pickups/Index');
    }

    public function prendasDaniadas()
    {
        return Inertia::render('Vendedor/DamagedProducts/Index');
    }

    public function reportes()
    {
        return Inertia::render('Vendedor/Reports/Index');
    }
}
