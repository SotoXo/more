<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;
use App\Models\PrendaVariante;
use App\Services\InventoryMovementService;
use Illuminate\Validation\ValidationException;

class ReservationInventoryService
{
    public function __construct(
        private readonly InventoryMovementService $movements,
    ) {
    }

    public function reserve(
        PrendaVariante $variant,
        int $quantity,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): Inventario
    {
        $inventory = $this->lockedInventory($variant);

        if ((int) $inventory->cdi_inv < $quantity) {
            $variant->loadMissing('prenda');

            throw ValidationException::withMessages([
                'reservation_items' => 'Ya no hay stock disponible para ' . ($variant->prenda?->nom_pre ?? 'esta prenda') . '.',
            ]);
        }

        $cdiAntes = (int) $inventory->cdi_inv;
        $creAntes = (int) $inventory->cre_inv;
        $ubicacionAntes = $inventory->ubi_inv;

        $inventory->update([
            'cdi_inv' => $cdiAntes - $quantity,
            'cre_inv' => $creAntes + $quantity,
        ]);

        $this->movements->reserva(
            $inventory,
            $quantity,
            $cdiAntes,
            (int) $inventory->cdi_inv,
            $creAntes,
            (int) $inventory->cre_inv,
            $referenciaTipo,
            $referenciaId,
            $descripcion ?? 'Reserva registrada: stock separado.',
            $metadata + [
                'variante_id' => $variant->id_prenda_variante,
                'sku' => $variant->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ],
        );

        return $inventory;
    }

    public function release(
        PrendaVariante $variant,
        int $quantity,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): Inventario
    {
        $inventory = $this->lockedInventory($variant);
        $reserved = (int) $inventory->cre_inv;

        if ($reserved < $quantity) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No hay stock reservado suficiente para liberar la reserva.',
            ]);
        }

        $cdiAntes = (int) $inventory->cdi_inv;
        $creAntes = (int) $inventory->cre_inv;
        $ubicacionAntes = $inventory->ubi_inv;

        $inventory->update([
            'cdi_inv' => $cdiAntes + $quantity,
            'cre_inv' => $reserved - $quantity,
        ]);

        $this->movements->liberacionReserva(
            $inventory,
            $quantity,
            $cdiAntes,
            (int) $inventory->cdi_inv,
            $creAntes,
            (int) $inventory->cre_inv,
            $referenciaTipo,
            $referenciaId,
            $descripcion ?? 'Reserva liberada: stock comprometido devuelto a disponible.',
            $metadata + [
                'variante_id' => $variant->id_prenda_variante,
                'sku' => $variant->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ],
        );

        return $inventory;
    }

    public function complete(
        PrendaVariante $variant,
        int $quantity,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): Inventario
    {
        $inventory = $this->lockedInventory($variant);
        $reserved = (int) $inventory->cre_inv;

        if ($reserved < $quantity) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No hay stock reservado suficiente para completar la reserva.',
            ]);
        }

        $cdiAntes = (int) $inventory->cdi_inv;
        $creAntes = (int) $inventory->cre_inv;
        $ubicacionAntes = $inventory->ubi_inv;

        $inventory->update([
            'cre_inv' => $reserved - $quantity,
        ]);

        $this->movements->conversionReservaVenta(
            $inventory,
            $quantity,
            $cdiAntes,
            (int) $inventory->cdi_inv,
            $creAntes,
            (int) $inventory->cre_inv,
            $referenciaTipo,
            $referenciaId,
            $descripcion ?? 'Reserva completada: stock comprometido convertido en venta.',
            $metadata + [
                'variante_id' => $variant->id_prenda_variante,
                'sku' => $variant->sku_prv,
                'ubicacion_antes' => $ubicacionAntes,
                'ubicacion_despues' => $inventory->ubi_inv,
            ],
        );

        return $inventory;
    }

    private function lockedInventory(PrendaVariante $variant): Inventario
    {
        $inventory = Inventario::query()
            ->where('cod_prenda_variantes_inv', $variant->id_prenda_variante)
            ->lockForUpdate()
            ->first();

        if (!$inventory) {
            throw ValidationException::withMessages([
                'reservation_items' => 'La variante seleccionada no tiene inventario registrado.',
            ]);
        }

        return $inventory;
    }
}
