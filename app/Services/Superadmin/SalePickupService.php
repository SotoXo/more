<?php

namespace App\Services\Superadmin;

use App\Models\User;
use App\Models\Venta;
use App\Services\CommercialNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalePickupService
{
    public function __construct(private readonly CommercialNotificationService $notifications)
    {
    }

    public function markReadyForPickup(Venta $sale, User $user): void
    {
        DB::transaction(function () use ($sale) {
            $sale = Venta::query()
                ->with('cliente')
                ->lockForUpdate()
                ->findOrFail($sale->id_venta);

            $this->ensureRegisteredSale($sale);

            if (!in_array($sale->est_logistico_ven, ['pendiente_traslado', 'pendiente_preparacion'], true)) {
                throw ValidationException::withMessages([
                    'sale_action' => 'Solo se pueden marcar como listas ventas pendientes de traslado o preparación.',
                ]);
            }

            $sale->update([
                'est_logistico_ven' => 'lista_recojo',
            ]);

            DB::afterCommit(function () use ($sale) {
                $sale->refresh();
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleReadyForPickup($sale);
            });
        });
    }

    public function markDelivered(Venta $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user) {
            $sale = Venta::query()
                ->with('cliente')
                ->lockForUpdate()
                ->findOrFail($sale->id_venta);

            $this->ensureRegisteredSale($sale);

            if ($sale->est_logistico_ven === 'entregada') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Esta venta ya fue entregada.',
                ]);
            }

            if ($sale->est_logistico_ven !== 'lista_recojo') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Solo se pueden entregar ventas listas para recojo.',
                ]);
            }

            $sale->update([
                'est_logistico_ven' => 'entregada',
                'fec_entrega_ven' => Carbon::now(),
                'cod_usuario_entrega_ven' => $user->id,
            ]);

            DB::afterCommit(function () use ($sale) {
                $sale->refresh();
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleDelivered($sale);
            });
        });
    }

    private function ensureRegisteredSale(Venta $sale): void
    {
        if ($sale->est_ven !== 'registrada') {
            throw ValidationException::withMessages([
                'sale_action' => 'Solo se pueden gestionar entregas de ventas confirmadas.',
            ]);
        }
    }
}
