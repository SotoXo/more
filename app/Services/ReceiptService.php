<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Support\Carbon;

class ReceiptService
{
    public function reservation(Reserva $reservation): array
    {
        $reservation->loadMissing([
            'cliente',
            'responsable',
            'usuarioVerificaPago',
            'pagos.usuarioVerifica',
            'detalles.variante.prenda',
            'detalles.variante.color',
            'detalles.variante.talla',
        ]);

        $latestConfirmedPayment = $reservation->pagos
            ->where('est_rpg', 'confirmado')
            ->sortByDesc('fec_verificacion_rpg')
            ->sortByDesc('id_reserva_pago')
            ->first();

        $issuedAt = $reservation->fec_verificacion_pago_res
            ?? $latestConfirmedPayment?->fec_verificacion_rpg
            ?? Carbon::now();

        return [
            'kind' => 'reservation',
            'title' => 'RECIBO DE RESERVA',
            'brand' => 'Mati Boutique',
            'numberLabel' => 'Numero de reserva',
            'number' => $reservation->nre_res ?: 'RES-' . $reservation->id_reserva,
            'referenceLabel' => null,
            'reference' => null,
            'issuedAt' => $issuedAt,
            'customer' => $this->userName($reservation->cliente),
            'responsibleLabel' => 'Responsable que verifico',
            'responsible' => $this->userName($reservation->usuarioVerificaPago ?: $latestConfirmedPayment?->usuarioVerifica ?: $reservation->responsable),
            'typeLabel' => null,
            'type' => null,
            'productsTitle' => 'Productos reservados',
            'items' => $reservation->detalles->map(fn ($detail) => [
                'name' => $detail->variante?->prenda?->nom_pre ?: 'Prenda sin nombre',
                'sku' => $detail->variante?->sku_prv ?: 'Sin SKU',
                'color' => $detail->variante?->color?->nom_col ?: 'Sin color',
                'size' => $detail->variante?->talla?->nom_tal ?: 'Sin talla',
                'quantity' => (int) $detail->can_dre,
                'unitPrice' => (float) $detail->pun_dre,
                'subtotal' => (float) $detail->sub_dre,
            ])->values()->all(),
            'totals' => [
                ['label' => 'Total de la reserva', 'value' => (float) $reservation->tot_res],
                ['label' => 'Monto pagado confirmado', 'value' => (float) $reservation->monto_pagado_res],
                ['label' => 'Saldo pendiente', 'value' => max(0, (float) $reservation->tot_res - (float) $reservation->monto_pagado_res)],
            ],
            'details' => [
                ['label' => 'Estado de pago', 'value' => $this->reservationPaymentLabel($reservation->est_pago_res)],
                ['label' => 'Fecha disponible de recojo', 'value' => $this->date($reservation->fec_disponible_recojo_res)],
                ['label' => 'Fecha limite de recojo', 'value' => $this->date($reservation->fec_limite_recojo_res)],
                ['label' => 'Punto de recojo', 'value' => $reservation->punto_recojo_res ?: 'Recojo en tienda Mati Boutique'],
                ['label' => 'Observacion', 'value' => $reservation->obs_pago_res ?: $latestConfirmedPayment?->obs_rpg ?: $reservation->obs_res ?: 'Sin observacion'],
            ],
            'legalText' => 'Este recibo confirma la reserva de las prendas detalladas, sujeta a las condiciones de recojo establecidas por Mati Boutique.',
        ];
    }

    public function sale(Venta $sale, ?Reserva $reservation = null): array
    {
        $sale->loadMissing([
            'cliente',
            'vendedor',
            'pagos.usuarioVerifica',
            'detalles.variante.prenda',
            'detalles.variante.color',
            'detalles.variante.talla',
        ]);

        $latestPayment = $sale->pagos
            ->sortByDesc('fec_verificacion_vpg')
            ->sortByDesc('id_venta_pago')
            ->first();

        $reservation ??= $this->reservationForSale($sale);
        $issuedAt = $latestPayment?->fec_verificacion_vpg ?? $sale->fec_ven ?? Carbon::now();

        return [
            'kind' => 'sale',
            'title' => 'RECIBO DE VENTA',
            'brand' => 'Mati Boutique',
            'numberLabel' => 'Numero de venta',
            'number' => $sale->nve_ven ?: 'VEN-' . $sale->id_venta,
            'referenceLabel' => $reservation ? 'Referencia de reserva' : null,
            'reference' => $reservation?->nre_res,
            'issuedAt' => $issuedAt,
            'customer' => $this->userName($sale->cliente),
            'responsibleLabel' => ($sale->tiv_ven === 'online') ? 'Encargado que verifico' : 'Vendedor/responsable',
            'responsible' => $this->userName($latestPayment?->usuarioVerifica ?: $sale->vendedor),
            'typeLabel' => 'Tipo de venta',
            'type' => $sale->tiv_ven === 'online' ? 'Online' : ($sale->tiv_ven === 'reserva' ? 'Desde reserva' : ucfirst((string) $sale->tiv_ven)),
            'productsTitle' => 'Productos vendidos',
            'items' => $sale->detalles->map(fn ($detail) => [
                'name' => $detail->variante?->prenda?->nom_pre ?: 'Prenda sin nombre',
                'sku' => $detail->variante?->sku_prv ?: 'Sin SKU',
                'color' => $detail->variante?->color?->nom_col ?: 'Sin color',
                'size' => $detail->variante?->talla?->nom_tal ?: 'Sin talla',
                'quantity' => (int) $detail->can_dve,
                'unitPrice' => (float) $detail->pun_dve,
                'subtotal' => (float) $detail->sub_dve,
            ])->values()->all(),
            'totals' => [
                ['label' => 'Subtotal', 'value' => (float) $sale->sub_ven],
                ['label' => 'Descuento', 'value' => (float) $sale->des_ven],
                ['label' => 'Total pagado', 'value' => (float) $sale->tot_ven],
            ],
            'details' => [
                ['label' => 'Metodo de pago', 'value' => $this->paymentMethodLabel($latestPayment?->metodo_pago_vpg ?: $sale->mpa_ven)],
                ['label' => 'Estado', 'value' => 'Venta confirmada'],
                ['label' => 'Observacion', 'value' => $latestPayment?->obs_vpg ?: 'Sin observacion'],
            ],
            'legalText' => 'Este recibo confirma la venta y entrega de las prendas detalladas.',
        ];
    }

    public function canIssueReservationReceipt(Reserva $reservation): bool
    {
        return in_array($reservation->est_pago_res, ['anticipo_confirmado', 'pagado_total'], true)
            || $reservation->pagos()->where('est_rpg', 'confirmado')->exists();
    }

    public function canIssueSaleReceipt(Venta $sale): bool
    {
        return $sale->est_ven === 'registrada';
    }

    public function saleForReservation(Reserva $reservation): ?Venta
    {
        if (!$reservation->nre_res) {
            return null;
        }

        $reservationNumber = preg_replace('/[^A-Za-z0-9-]/', '', (string) $reservation->nre_res);
        $saleNumber = $reservationNumber ? substr('VEN-RES-' . $reservationNumber, 0, 50) : null;

        return $saleNumber
            ? Venta::query()->where('nve_ven', $saleNumber)->first()
            : null;
    }

    private function reservationForSale(Venta $sale): ?Reserva
    {
        if (!str_starts_with((string) $sale->nve_ven, 'VEN-RES-')) {
            return null;
        }

        $reservationNumber = substr((string) $sale->nve_ven, strlen('VEN-RES-'));

        return $reservationNumber
            ? Reserva::query()->where('nre_res', $reservationNumber)->first()
            : null;
    }

    private function userName($user): string
    {
        if (!$user) {
            return 'Sin registrar';
        }

        return trim(implode(' ', array_filter([$user->nom_usu, $user->app_usu, $user->apm_usu]))) ?: ($user->email ?? 'Sin registrar');
    }

    private function reservationPaymentLabel(?string $status): string
    {
        return match ($status) {
            'anticipo_confirmado' => 'Adelanto confirmado',
            'pagado_total' => 'Pagado completamente',
            'rechazado' => 'Pago rechazado',
            default => 'Pendiente de revision',
        };
    }

    private function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'qr' => 'QR',
            'transferencia_bancaria' => 'Transferencia bancaria',
            'efectivo' => 'Efectivo',
            'reserva' => 'Reserva',
            default => $method ? mb_convert_case(str_replace('_', ' ', $method), MB_CASE_TITLE, 'UTF-8') : 'Sin metodo',
        };
    }

    private function date($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }
}
