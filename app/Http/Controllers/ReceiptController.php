<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Venta;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptService $receipts)
    {
    }

    public function adminReservation(Request $request, Reserva $reserva)
    {
        abort_unless($this->receipts->canIssueReservationReceipt($reserva), 404);

        return view('receipts.show', [
            'receipt' => $this->receipts->reservation($reserva),
            'returnUrl' => route('superadmin.resources.show', ['section' => 'comercial', 'resource' => 'reservas', 'record' => $reserva->id_reserva]),
        ]);
    }

    public function adminSale(Request $request, Venta $venta)
    {
        abort_unless($this->receipts->canIssueSaleReceipt($venta), 404);

        return view('receipts.show', [
            'receipt' => $this->receipts->sale($venta),
            'returnUrl' => route('superadmin.resources.show', ['section' => 'comercial', 'resource' => 'ventas', 'record' => $venta->id_venta]),
        ]);
    }

    public function customerReservation(Request $request, Reserva $reserva)
    {
        abort_unless((int) $reserva->cod_usuario_cliente_res === (int) $request->user()->id, 403);
        abort_unless($this->receipts->canIssueReservationReceipt($reserva), 404);

        return view('receipts.show', [
            'receipt' => $this->receipts->reservation($reserva),
            'returnUrl' => route('cuenta.reservas'),
        ]);
    }

    public function customerSale(Request $request, Venta $venta)
    {
        abort_unless((int) $venta->cod_usuario_cliente_ven === (int) $request->user()->id, 403);
        abort_unless($this->receipts->canIssueSaleReceipt($venta), 404);

        return view('receipts.show', [
            'receipt' => $this->receipts->sale($venta),
            'returnUrl' => route('cuenta.pedidos'),
        ]);
    }
}
