<?php

namespace App\Services\Superadmin;

use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Services\SystemLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReservationPaymentService
{
    public function __construct(private readonly SystemLogService $logs)
    {
    }

    public function registerPayment(Reserva $reservation, UploadedFile $voucher, float $declaredAmount, ?string $method): ReservaPago
    {
        if ($declaredAmount <= 0) {
            throw ValidationException::withMessages([
                'monto_declarado_rpg' => 'El monto declarado debe ser mayor a 0.',
            ]);
        }

        try {
            $path = $voucher->store('reservas/comprobantes', 'public');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'comprobante_rpg' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
            ]);
        }

        if (!$path) {
            throw ValidationException::withMessages([
                'comprobante_rpg' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
            ]);
        }

        $payment = ReservaPago::query()->create([
            'cod_reservas_rpg' => $reservation->id_reserva,
            'monto_declarado_rpg' => $declaredAmount,
            'monto_confirmado_rpg' => 0,
            'porcentaje_rpg' => $this->percentage($declaredAmount, (float) $reservation->tot_res),
            'metodo_pago_rpg' => $method ?: null,
            'comprobante_ruta_rpg' => $path,
            'comprobante_url_rpg' => Storage::disk('public')->url($path),
            'est_rpg' => 'pendiente_revision',
            'fec_rpg' => Carbon::now(),
        ]);

        $this->logs->info(
            'pagos',
            'comprobante_subido',
            'Comprobante subido para reserva: ' . $reservation->nre_res . '.',
            $payment,
            [
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'monto_declarado' => $declaredAmount,
                'metodo_pago' => $method,
                'estado' => $payment->est_rpg,
            ],
        );

        return $payment;
    }

    public function verifyPayment(Reserva $reservation, ReservaPago $payment, float $confirmedAmount, ?string $observation, int $userId): array
    {
        if ($confirmedAmount <= 0) {
            throw ValidationException::withMessages([
                'monto_confirmado_rpg' => 'El monto confirmado debe ser mayor a 0.',
            ]);
        }

        if (!in_array($reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No se puede verificar el pago de una reserva cancelada, vencida o completada.',
            ]);
        }

        if ((int) $payment->cod_reservas_rpg !== (int) $reservation->id_reserva) {
            throw ValidationException::withMessages([
                'reservation_action' => 'El pago no pertenece a esta reserva.',
            ]);
        }

        $total = (float) $reservation->tot_res;
        $minimum = round($total * 0.5, 2);
        $alreadyPaid = (float) $reservation->monto_pagado_res;

        if (($alreadyPaid + $confirmedAmount) > round($total, 2)) {
            throw ValidationException::withMessages([
                'monto_confirmado_rpg' => 'El monto confirmado no puede ser mayor al saldo pendiente.',
            ]);
        }

        if ($confirmedAmount < $minimum && $alreadyPaid <= 0) {
            throw ValidationException::withMessages([
                'monto_confirmado_rpg' => 'El monto confirmado no alcanza el anticipo minimo del 50%.',
            ]);
        }

        $payment->update($this->verificationPayload($reservation, $confirmedAmount, 'confirmado', $observation, $userId));

        $confirmedTotal = (float) $reservation->pagos()
            ->where('est_rpg', 'confirmado')
            ->sum('monto_confirmado_rpg');
        $paid = min($confirmedTotal, $total);
        $balance = max(0, $total - $paid);

        $reservation->update([
            'monto_pagado_res' => $paid,
            'saldo_res' => $balance,
            'est_pago_res' => $balance <= 0 ? 'pagado_total' : 'anticipo_confirmado',
            'est_res' => 'confirmada',
            'cod_usuario_verifica_pago_res' => $userId,
            'fec_verificacion_pago_res' => Carbon::now(),
            'obs_pago_res' => $observation,
        ]);

        $this->logs->info(
            'pagos',
            'pago_confirmado',
            'Pago confirmado para reserva: ' . $reservation->nre_res . '.',
            $payment,
            [
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'monto_confirmado' => $confirmedAmount,
                'monto_pagado' => $paid,
                'saldo' => $balance,
                'metodo_pago' => $payment->metodo_pago_rpg,
                'estado' => 'confirmado',
            ],
        );

        return ['accepted' => true, 'message' => $balance <= 0 ? 'Pago total confirmado.' : 'Anticipo confirmado.'];
    }

    public function registerConfirmedPickupPayment(Reserva $reservation, float $amount, string $method, ?string $observation, int $userId, ?UploadedFile $voucher = null): ReservaPago
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'monto_pago_final' => 'El monto recibido debe ser mayor a 0.',
            ]);
        }

        $path = null;
        $url = null;

        if ($method !== 'efectivo' && $voucher) {
            try {
                $path = $voucher->store('reservas/comprobantes', 'public');
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'comprobante_pago_final' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
                ]);
            }

            if (!$path) {
                throw ValidationException::withMessages([
                    'comprobante_pago_final' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
                ]);
            }

            $url = Storage::disk('public')->url($path);
        }

        $payment = ReservaPago::query()->create([
            'cod_reservas_rpg' => $reservation->id_reserva,
            'monto_declarado_rpg' => $amount,
            'monto_confirmado_rpg' => $amount,
            'porcentaje_rpg' => $this->percentage($amount, (float) $reservation->tot_res),
            'metodo_pago_rpg' => $method,
            'comprobante_ruta_rpg' => $path,
            'comprobante_url_rpg' => $url,
            'est_rpg' => 'confirmado',
            'cod_usuario_verifica_rpg' => $userId,
            'fec_verificacion_rpg' => Carbon::now(),
            'obs_rpg' => $observation,
            'fec_rpg' => Carbon::now(),
        ]);

        $paid = min((float) $reservation->tot_res, (float) $reservation->monto_pagado_res + $amount);

        $reservation->update([
            'monto_pagado_res' => $paid,
            'saldo_res' => max(0, (float) $reservation->tot_res - $paid),
            'est_pago_res' => $paid >= (float) $reservation->tot_res ? 'pagado_total' : 'anticipo_confirmado',
            'cod_usuario_verifica_pago_res' => $userId,
            'fec_verificacion_pago_res' => Carbon::now(),
            'obs_pago_res' => $observation ?: $reservation->obs_pago_res,
        ]);

        $this->logs->info(
            'pagos',
            'pago_confirmado',
            'Pago final confirmado para reserva: ' . $reservation->nre_res . '.',
            $payment,
            [
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'monto_confirmado' => $amount,
                'monto_pagado' => $paid,
                'saldo' => max(0, (float) $reservation->tot_res - $paid),
                'metodo_pago' => $method,
                'estado' => 'confirmado',
            ],
        );

        return $payment;
    }

    public function rejectPayment(Reserva $reservation, ReservaPago $payment, ?string $observation, int $userId): void
    {
        if (!in_array($reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No se puede rechazar el pago de una reserva cancelada, vencida o recogida.',
            ]);
        }

        if (blank($observation)) {
            throw ValidationException::withMessages([
                'obs_rpg' => 'La observacion es obligatoria al rechazar un comprobante.',
            ]);
        }

        $payment->update($this->verificationPayload($reservation, 0, 'rechazado', $observation, $userId));
        $reservation->update([
            'est_pago_res' => 'rechazado',
            'est_res' => 'rechazada',
            'cod_usuario_verifica_pago_res' => $userId,
            'fec_verificacion_pago_res' => Carbon::now(),
            'obs_pago_res' => $observation,
        ]);

        $this->logs->warning(
            'pagos',
            'pago_rechazado',
            'Pago rechazado para reserva: ' . $reservation->nre_res . '.',
            $payment,
            [
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'metodo_pago' => $payment->metodo_pago_rpg,
                'estado' => 'rechazado',
            ],
        );
    }

    private function verificationPayload(Reserva $reservation, float $confirmedAmount, string $status, ?string $observation, int $userId): array
    {
        return [
            'monto_confirmado_rpg' => $confirmedAmount,
            'porcentaje_rpg' => $this->percentage($confirmedAmount, (float) $reservation->tot_res),
            'est_rpg' => $status,
            'cod_usuario_verifica_rpg' => $userId,
            'fec_verificacion_rpg' => Carbon::now(),
            'obs_rpg' => $observation,
        ];
    }

    private function percentage(float $amount, float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($amount / $total) * 100, 2);
    }
}
