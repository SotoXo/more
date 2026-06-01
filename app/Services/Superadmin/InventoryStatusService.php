<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;

class InventoryStatusService
{
    public function available(?Inventario $inventory): int
    {
        return max(0, (int) ($inventory?->cdi_inv ?? 0));
    }

    public function reserved(?Inventario $inventory): int
    {
        return max(0, (int) ($inventory?->cre_inv ?? 0));
    }

    public function total(?Inventario $inventory): int
    {
        return $this->available($inventory) + $this->reserved($inventory);
    }

    public function state(?Inventario $inventory): string
    {
        $available = $this->available($inventory);

        return $available === 0 ? 'agotado' : ($available <= 3 ? 'bajo' : 'disponible');
    }

    public function status(?Inventario $inventory): array
    {
        if ($this->reserved($inventory) > 0) {
            return ['label' => 'Reservado', 'tone' => 'accent'];
        }

        return match ($this->state($inventory)) {
            'agotado' => ['label' => 'Sin stock', 'tone' => 'danger'],
            'bajo' => ['label' => 'Bajo stock', 'tone' => 'warning'],
            default => ['label' => 'Disponible', 'tone' => 'success'],
        };
    }

    public function score(?Inventario $inventory): int
    {
        return match ($this->state($inventory)) {
            'agotado' => 0,
            'bajo' => 1,
            default => 2,
        };
    }
}
