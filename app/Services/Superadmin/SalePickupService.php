<?php

namespace App\Services\Superadmin;

use App\Models\User;
use App\Models\Venta;
use App\Services\CommercialNotificationService;
use App\Services\SystemLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalePickupService
{
    public function __construct(
        private readonly CommercialNotificationService $notifications,
        private readonly SystemLogService $logs,
    ) {
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

            $this->logs->info(
                'logistica',
                'venta_lista_recojo',
                'Venta marcada lista para recojo: ' . $sale->nve_ven . '.',
                $sale,
                $this->salePickupMetadata($sale),
            );

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

            $this->logs->info(
                'logistica',
                'venta_entregada',
                'Venta entregada: ' . $sale->nve_ven . '.',
                $sale,
                $this->salePickupMetadata($sale) + [
                    'usuario_entrega_id' => $user->id,
                ],
            );

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

    private function salePickupMetadata(Venta $sale): array
    {
        return [
            'venta_id' => $sale->id_venta,
            'numero_venta' => $sale->nve_ven,
            'cliente_id' => $sale->cod_usuario_cliente_ven,
            'estado_logistico' => $sale->est_logistico_ven,
            'punto_recojo' => $sale->punto_recojo_ven,
        ];
    }
}
