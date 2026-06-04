<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;
use App\Models\PrendaVariante;
use App\Models\Venta;
use App\Services\InventoryMovementService;
use Illuminate\Validation\ValidationException;

class SaleInventoryService
{
    public function __construct(
        private readonly InventoryMovementService $movements,
    ) {
    }

    public function reserveForSale(
        PrendaVariante $variant,
        int $quantity,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): Inventario
    {
        $inventory = Inventario::query()
            ->where('cod_prenda_variantes_inv', $variant->id_prenda_variante)
            ->lockForUpdate()
            ->first();

        if (!$inventory) {
            throw ValidationException::withMessages([
                'sale_items' => 'La variante seleccionada no tiene inventario registrado.',
            ]);
        }

        if ((int) $inventory->cdi_inv < $quantity) {
            throw ValidationException::withMessages([
                'sale_items' => 'No hay stock suficiente para esta variante.',
            ]);
        }

        $cdiAntes = (int) $inventory->cdi_inv;
        $creAntes = (int) $inventory->cre_inv;
        $ubicacionAntes = $inventory->ubi_inv;

        $inventory->update([
            'cdi_inv' => $cdiAntes - $quantity,
        ]);

        $this->movements->venta(
            $inventory,
            $quantity,
            $cdiAntes,
            (int) $inventory->cdi_inv,
            $creAntes,
            (int) $inventory->cre_inv,
            $referenciaTipo,
            $referenciaId,
            $descripcion ?? 'Venta registrada: stock disponible descontado.',
            $metadata + [
                'variante_id' => $variant->id_prenda_variante,
                'sku' => $variant->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ],
        );

        return $inventory;
    }

    public function separateForOnlineReview(
        PrendaVariante $variant,
        int $quantity,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): Inventario
    {
        $inventory = Inventario::query()
            ->where('cod_prenda_variantes_inv', $variant->id_prenda_variante)
            ->lockForUpdate()
            ->first();

        if (!$inventory) {
            throw ValidationException::withMessages([
                'sale_items' => 'La variante seleccionada no tiene inventario registrado.',
            ]);
        }

        if ((int) $inventory->cdi_inv < $quantity) {
            throw ValidationException::withMessages([
                'sale_items' => 'No hay stock disponible suficiente.',
            ]);
        }

        $cdiAntes = (int) $inventory->cdi_inv;
        $creAntes = (int) $inventory->cre_inv;
        $ubicacionAntes = $inventory->ubi_inv;

        $inventory->update([
            'cdi_inv' => $cdiAntes - $quantity,
            'cre_inv' => $creAntes + $quantity,
        ]);

        $this->movements->ventaEnRevision(
            $inventory,
            $quantity,
            $cdiAntes,
            (int) $inventory->cdi_inv,
            $creAntes,
            (int) $inventory->cre_inv,
            $referenciaTipo,
            $referenciaId,
            $descripcion ?? 'Venta online en revision: stock separado.',
            $metadata + [
                'variante_id' => $variant->id_prenda_variante,
                'sku' => $variant->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ],
        );

        return $inventory;
    }

    public function confirmSale(Venta $sale): void
    {
        $sale->loadMissing('detalles.variante');

        if ($sale->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'sale_action' => 'La venta no tiene prendas para descontar inventario.',
            ]);
        }

        foreach ($sale->detalles as $detail) {
            if (!$detail->variante) {
                throw ValidationException::withMessages([
                    'sale_action' => 'Una prenda vendida no tiene variante asociada.',
                ]);
            }

            $this->reserveForSale(
                $detail->variante,
                (int) $detail->can_dve,
                'venta',
                (int) $sale->id_venta,
                'Venta confirmada: stock disponible descontado.',
                [
                    'venta_id' => $sale->id_venta,
                    'numero_venta' => $sale->nve_ven,
                    'tipo_venta' => $sale->tiv_ven,
                ],
            );
        }
    }

    public function confirmSeparatedSale(Venta $sale): void
    {
        $this->moveSeparatedStock($sale, false);
    }

    public function releaseSeparatedSale(Venta $sale): void
    {
        $this->moveSeparatedStock($sale, true);
    }

    private function moveSeparatedStock(Venta $sale, bool $returnToAvailable): void
    {
        $sale->loadMissing('detalles.variante');

        if ($sale->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'sale_action' => 'La venta no tiene prendas para mover inventario.',
            ]);
        }

        foreach ($sale->detalles as $detail) {
            if (!$detail->variante) {
                throw ValidationException::withMessages([
                    'sale_action' => 'Una prenda vendida no tiene variante asociada.',
                ]);
            }

            $inventory = Inventario::query()
                ->where('cod_prenda_variantes_inv', $detail->variante->id_prenda_variante)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw ValidationException::withMessages([
                    'sale_action' => 'Una prenda vendida no tiene inventario registrado.',
                ]);
            }

            $quantity = (int) $detail->can_dve;
            $reserved = (int) $inventory->cre_inv;

            if ($reserved < $quantity) {
                throw ValidationException::withMessages([
                    'sale_action' => 'El stock reservado no es suficiente para completar esta acción.',
                ]);
            }

            $cdiAntes = (int) $inventory->cdi_inv;
            $creAntes = (int) $inventory->cre_inv;
            $ubicacionAntes = $inventory->ubi_inv;
            $cdiDespues = $returnToAvailable ? $cdiAntes + $quantity : $cdiAntes;
            $creDespues = $reserved - $quantity;

            $inventory->update([
                'cdi_inv' => $cdiDespues,
                'cre_inv' => $reserved - $quantity,
            ]);

            $metadata = [
                'venta_id' => $sale->id_venta,
                'numero_venta' => $sale->nve_ven,
                'tipo_venta' => $sale->tiv_ven,
                'estado_venta' => $sale->est_ven,
                'variante_id' => $detail->variante->id_prenda_variante,
                'sku' => $detail->variante->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ];

            if ($returnToAvailable) {
                $this->movements->cancelacionVenta(
                    $inventory,
                    $quantity,
                    $cdiAntes,
                    (int) $inventory->cdi_inv,
                    $creAntes,
                    (int) $inventory->cre_inv,
                    'venta',
                    (int) $sale->id_venta,
                    'Venta online cancelada o incompleta: stock reservado liberado.',
                    $metadata,
                );
            } else {
                $this->movements->venta(
                    $inventory,
                    $quantity,
                    $cdiAntes,
                    (int) $inventory->cdi_inv,
                    $creAntes,
                    (int) $inventory->cre_inv,
                    'venta',
                    (int) $sale->id_venta,
                    'Venta online confirmada: stock comprometido consumido.',
                    $metadata,
                );
            }
        }
    }
}
