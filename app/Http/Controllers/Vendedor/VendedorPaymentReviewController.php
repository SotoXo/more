<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Vendedor\VendedorPaymentReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendedorPaymentReviewController extends Controller
{
    public function index(Request $request, VendedorPaymentReviewService $payments)
    {
        return Inertia::render('Vendedor/Payments/Index', $payments->indexPayload($request));
    }

    public function show(Request $request, string $pago, VendedorPaymentReviewService $payments)
    {
        return Inertia::render('Vendedor/Payments/Show', $payments->showPayload($request, $pago));
    }

    public function approve(Request $request, string $pago, VendedorPaymentReviewService $payments): RedirectResponse
    {
        $payments->approve($request, $pago);

        return redirect()
            ->route('vendedor.pagos.show', $pago)
            ->with('success', 'Comprobante aprobado correctamente.');
    }

    public function reject(Request $request, string $pago, VendedorPaymentReviewService $payments): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Ingresa el motivo del rechazo.',
            'rejection_reason.min' => 'El motivo del rechazo debe tener al menos 5 caracteres.',
        ]);

        $payments->reject($request, $pago, $validated['rejection_reason']);

        return redirect()
            ->route('vendedor.pagos.show', $pago)
            ->with('success', 'Comprobante rechazado correctamente.');
    }
}
