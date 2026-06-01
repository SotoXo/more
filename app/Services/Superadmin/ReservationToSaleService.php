<?php

namespace App\Services\Superadmin;

use App\Models\DetalleVenta;
use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReservationToSaleService
{
    public function __construct(
        private readonly SaleNumberService $numbers,
    ) {
    }

    public function convert(Reserva $reservation, int $sellerId, bool $allowCompleted = false): Venta
    {
        if ($reservation->est_res === 'completada' && !$allowCompleted) {
            throw ValidationException::withMessages([
                'reservation_action' => 'La reserva ya fue recogida. No se puede crear otra venta.',
            ]);
        }

        if ($this->saleForReservation($reservation)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'Esta reserva ya tiene una venta registrada.',
            ]);
        }

        if ((float) $reservation->saldo_res > 0) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No se puede marcar como recogido si aun existe saldo pendiente.',
            ]);
        }

        $reservation->loadMissing('detalles');

        if ($reservation->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'reservation_action' => 'La reserva no tiene prendas para convertir en venta.',
            ]);
        }

        $sale = Venta::query()->create([
            'nve_ven' => $this->saleNumber($reservation),
            'cod_usuario_cliente_ven' => $reservation->cod_usuario_cliente_res,
            'cod_usuario_vendedor_ven' => $sellerId > 0 ? $sellerId : $reservation->cod_usuario_responsable_res,
            'tiv_ven' => 'reserva',
            'sub_ven' => (float) $reservation->sub_res,
            'des_ven' => (float) $reservation->des_res,
            'tot_ven' => (float) $reservation->tot_res,
            'mpa_ven' => $this->mainPaymentMethod($reservation),
            'est_ven' => 'registrada',
            'est_logistico_ven' => 'entregada',
            'fec_disponible_recojo_ven' => $reservation->fec_disponible_recojo_res,
            'fec_limite_recojo_ven' => $reservation->fec_limite_recojo_res,
            'punto_recojo_ven' => $reservation->punto_recojo_res,
            'origen_stock_ven' => $reservation->origen_stock_res,
            'fec_entrega_ven' => Carbon::now(),
            'cod_usuario_entrega_ven' => $sellerId > 0 ? $sellerId : $reservation->cod_usuario_responsable_res,
            'fec_ven' => Carbon::now(),
        ]);

        foreach ($reservation->detalles as $detail) {
            DetalleVenta::query()->create([
                'cod_ventas_dve' => $sale->id_venta,
                'cod_prenda_variantes_dve' => $detail->cod_prenda_variantes_dre,
                'can_dve' => (int) $detail->can_dre,
                'pun_dve' => (float) $detail->pun_dre,
                'sub_dve' => (float) $detail->sub_dre,
                'fec_dve' => Carbon::now(),
            ]);
        }

        return $sale;
    }

    private function saleNumber(Reserva $reservation): string
    {
        $candidate = $this->saleNumberCandidate($reservation);

        if ($candidate) {
            if (Venta::query()->where('nve_ven', $candidate)->exists()) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'Esta reserva ya tiene una venta registrada.',
                ]);
            }

            return $candidate;
        }

        return $this->numbers->next();
    }

    public function saleForReservation(Reserva $reservation): ?Venta
    {
        $candidate = $this->saleNumberCandidate($reservation);

        return $candidate
            ? Venta::query()->withCount('detalles')->where('nve_ven', $candidate)->first()
            : null;
    }

    public function saleNumberCandidate(Reserva $reservation): ?string
    {
        $reservationNumber = preg_replace('/[^A-Za-z0-9-]/', '', (string) $reservation->nre_res);

        return $reservationNumber ? substr('VEN-RES-' . $reservationNumber, 0, 50) : null;
    }

    private function mainPaymentMethod(Reserva $reservation): string
    {
        $method = $reservation->pagos()
            ->where('est_rpg', 'confirmado')
            ->orderByDesc('fec_verificacion_rpg')
            ->orderByDesc('id_reserva_pago')
            ->value('metodo_pago_rpg');

        return $method ?: 'reserva';
    }
}
