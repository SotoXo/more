<?php

namespace App\Services\Superadmin;

use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PickupLogisticsService
{
    private const PICKUP_POINT = 'Recojo en tienda Mati Boutique';

    public function calculate(Collection $inventories): array
    {
        $today = Carbon::today();
        $locations = $inventories
            ->map(fn ($inventory) => $this->normalizeLocation($inventory->ubi_inv ?? null))
            ->filter()
            ->unique()
            ->values();

        $origin = $locations->count() > 1 ? 'MIXTO' : ($locations->first() ?: 'TIENDA');
        $available = $this->availableDateForOrigin($origin, $today);

        return [
            'origin' => $origin,
            'status' => $this->statusForOrigin($origin),
            'available_at' => $available->copy()->startOfDay(),
            'limit_at' => $available->copy()->addDays(30)->endOfDay(),
            'pickup_point' => self::PICKUP_POINT,
        ];
    }

    public function reservationPayload(Collection $inventories): array
    {
        $logistics = $this->calculate($inventories);

        return [
            'est_logistico_res' => $logistics['status'],
            'fec_disponible_recojo_res' => $logistics['available_at'],
            'fec_limite_recojo_res' => $logistics['limit_at'],
            'punto_recojo_res' => $logistics['pickup_point'],
            'origen_stock_res' => $logistics['origin'],
            'ven_res' => $logistics['limit_at'],
        ];
    }

    public function salePayload(Collection $inventories): array
    {
        $logistics = $this->calculate($inventories);

        return [
            'est_logistico_ven' => $logistics['status'],
            'fec_disponible_recojo_ven' => $logistics['available_at'],
            'fec_limite_recojo_ven' => $logistics['limit_at'],
            'punto_recojo_ven' => $logistics['pickup_point'],
            'origen_stock_ven' => $logistics['origin'],
        ];
    }

    public function reservationModelPayload(Reserva $reservation): array
    {
        $reservation->loadMissing('detalles.variante.inventario');

        return $this->reservationPayload(
            $reservation->detalles
                ->pluck('variante.inventario')
                ->filter()
        );
    }

    public function saleModelPayload(Venta $sale): array
    {
        $sale->loadMissing('detalles.variante.inventario');

        return $this->salePayload(
            $sale->detalles
                ->pluck('variante.inventario')
                ->filter()
        );
    }

    private function normalizeLocation(?string $location): string
    {
        $normalized = mb_strtoupper(trim((string) ($location ?: 'TIENDA')), 'UTF-8');
        $normalized = str_replace('DEPÓSITO', 'DEPOSITO', $normalized);

        return in_array($normalized, ['TIENDA', 'DEPOSITO'], true) ? $normalized : 'TIENDA';
    }

    private function availableDateForOrigin(string $origin, Carbon $today): Carbon
    {
        return in_array($origin, ['DEPOSITO', 'MIXTO'], true)
            ? $today->copy()->addDays(3)
            : $today->copy();
    }

    private function statusForOrigin(string $origin): string
    {
        return match ($origin) {
            'DEPOSITO' => 'pendiente_traslado',
            'MIXTO' => 'pendiente_preparacion',
            default => 'lista_recojo',
        };
    }
}
