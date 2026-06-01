<?php

namespace App\Http\Controllers;

use App\Services\Superadmin\SaleCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompraOnlineController extends Controller
{
    public function __construct(private readonly SaleCheckoutService $checkout)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->withErrors(['compra' => 'Debes iniciar sesion para finalizar la compra.']);
        }

        $validated = $this->checkout->validateOnlineCheckout($request);
        $this->checkout->createOnlineCheckout($request, $request->user(), $validated);

        return redirect()->route('cuenta.pedidos')
            ->with('success', 'Compra enviada a revision correctamente.');
    }
}
