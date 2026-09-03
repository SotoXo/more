<?php

namespace App\Http\Controllers;

use App\Services\Superadmin\SaleCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompraOnlineController extends Controller
{
    public function __construct(private readonly SaleCheckoutService $checkout)
    {
    }

    public function create(Request $request): Response
    {
        $this->checkout->ensureCustomerCanCheckout($request->user());

        return Inertia::render('Checkout');
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->withErrors(['compra' => 'Debes iniciar sesion para finalizar la compra.']);
        }

        $this->checkout->ensureCustomerCanCheckout($request->user());
        $validated = $this->checkout->validateOnlineCheckout($request);
        $this->checkout->createOnlineCheckout($request, $request->user(), $validated);

        return redirect()->route('cuenta.pedidos')
            ->with('success', 'Compra enviada a revision correctamente.');
    }
}
