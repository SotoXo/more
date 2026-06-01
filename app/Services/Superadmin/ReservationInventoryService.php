<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;
use App\Models\PrendaVariante;
use Illuminate\Validation\ValidationException;

class ReservationInventoryService
{
    public function reserve(PrendaVariante $variant, int $quantity): Inventario
    {
        $inventory = $this->lockedInventory($variant);

        if ((int) $inventory->cdi_inv < $quantity) {
            $variant->loadMissing('prenda');

            throw ValidationException::withMessages([
                'reservation_items' => 'Ya no hay stock disponible para ' . ($variant->prenda?->nom_pre ?? 'esta prenda') . '.',
            ]);
        }

        $inventory->update([
            'cdi_inv' => ((int) $inventory->cdi_inv) - $quantity,
            'cre_inv' => ((int) $inventory->cre_inv) + $quantity,
        ]);

        return $inventory;
    }

    public function release(PrendaVariante $variant, int $quantity): Inventario
    {
        $inventory = $this->lockedInventory($variant);
        $reserved = (int) $inventory->cre_inv;

        if ($reserved < $quantity) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No hay stock reservado suficiente para liberar la reserva.',
            ]);
        }

        $inventory->update([
            'cdi_inv' => ((int) $inventory->cdi_inv) + $quantity,
            'cre_inv' => $reserved - $quantity,
        ]);

        return $inventory;
    }

    public function complete(PrendaVariante $variant, int $quantity): Inventario
    {
        $inventory = $this->lockedInventory($variant);
        $reserved = (int) $inventory->cre_inv;

        if ($reserved < $quantity) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No hay stock reservado suficiente para completar la reserva.',
            ]);
        }

        $inventory->update([
            'cre_inv' => $reserved - $quantity,
        ]);

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
