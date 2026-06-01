<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;
use App\Models\PrendaVariante;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;

class SaleInventoryService
{
    public function reserveForSale(PrendaVariante $variant, int $quantity): Inventario
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

        $inventory->update([
            'cdi_inv' => ((int) $inventory->cdi_inv) - $quantity,
        ]);

        return $inventory;
    }

    public function separateForOnlineReview(PrendaVariante $variant, int $quantity): Inventario
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

        $inventory->update([
            'cdi_inv' => ((int) $inventory->cdi_inv) - $quantity,
            'cre_inv' => ((int) $inventory->cre_inv) + $quantity,
        ]);

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

            $this->reserveForSale($detail->variante, (int) $detail->can_dve);
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

            $inventory->update([
                'cdi_inv' => $returnToAvailable ? ((int) $inventory->cdi_inv) + $quantity : (int) $inventory->cdi_inv,
                'cre_inv' => $reserved - $quantity,
            ]);
        }
    }
}
