<?php

namespace App\Services\Vendedor;

use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\Superadmin\ReservationService;
use App\Services\Superadmin\SaleService;
use App\Services\SystemLogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendedorPaymentReviewService
{
    private const REVIEW_STATUSES = ['pendiente_revision', 'confirmado', 'rechazado'];
    private const CLIENT_REVIEW_MARKER = 'comprobante de cliente';

    public function __construct(
        private readonly SaleService $sales,
        private readonly ReservationService $reservations,
        private readonly SystemLogService $logs,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $status = $this->requestedStatus($request);
        $rows = $this->reviewRows($status)
            ->filter(fn (array $row) => $this->matchesFilters($row, $request))
            ->sortByDesc('sortKey')
            ->values();

        return [
            'payments' => $rows
                ->map(fn (array $row) => Arr::except($row, ['searchText', 'sortKey', 'methodKey', 'statusKey']))
                ->values()
                ->all(),
            'summary' => $this->summary($rows),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'type' => $request->string('type')->toString(),
                'method' => $request->string('method')->toString(),
                'status' => $status,
            ],
            'options' => [
                'types' => [
                    ['value' => 'venta', 'label' => 'Compra online'],
                    ['value' => 'reserva', 'label' => 'Reserva'],
                ],
                'methods' => $this->paymentMethodOptions(),
                'statuses' => $this->statusOptions(),
            ],
            'tabs' => $this->tabs($request, $status),
            'scopeLabel' => 'Comprobantes subidos por clientes para compras online y reservas. Los pagos registrados por vendedor quedan fuera de esta revisión.',
        ];
    }

    public function showPayload(Request $request, string $paymentKey): array
    {
        $review = $this->resolvePayment($paymentKey);

        if ($review['type'] === 'venta') {
            return $this->saleShowPayload($review['payment']);
        }

        return $this->reservationShowPayload($review['payment']);
    }

    public function approve(Request $request, string $paymentKey): void
    {
        $review = $this->resolvePayment($paymentKey);
        $userId = (int) $request->user()->id;
        $role = $this->reviewerRole($request->user());

        if ($review['type'] === 'venta') {
            /** @var VentaPago $payment */
            $payment = $review['payment'];
            $this->ensurePendingSalePayment($payment);

            $amount = round((float) $payment->monto_declarado_vpg, 2);
            $this->sales->confirmOnlineSale(
                $payment->venta,
                $amount,
                $userId,
                'Comprobante de cliente aprobado desde panel vendedor.',
            );

            $payment = VentaPago::query()->with('venta')->findOrFail($payment->id_venta_pago);
            $this->logReviewed($role, $payment, 'confirmado', $amount);
            $this->logApproved($role, $payment, 'confirmado', $amount);

            return;
        }

        /** @var ReservaPago $payment */
        $payment = $review['payment'];
        $this->ensurePendingReservationPayment($payment);

        $amount = round((float) $payment->monto_declarado_rpg, 2);
        $this->reservations->verifyLatestPendingPayment(
            $payment->reserva,
            $amount,
            'Comprobante de cliente aprobado desde panel vendedor.',
            $userId,
        );

        $payment = ReservaPago::query()->with('reserva')->findOrFail($payment->id_reserva_pago);
        $this->logReviewed($role, $payment, 'confirmado', $amount);
        $this->logApproved($role, $payment, 'confirmado', $amount);
    }

    public function reject(Request $request, string $paymentKey, string $reason): void
    {
        $review = $this->resolvePayment($paymentKey);
        $userId = (int) $request->user()->id;
        $role = $this->reviewerRole($request->user());
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Ingresa el motivo del rechazo.',
            ]);
        }

        if ($review['type'] === 'venta') {
            /** @var VentaPago $payment */
            $payment = $review['payment'];
            $this->ensurePendingSalePayment($payment);

            $this->sales->markOnlineSaleIncomplete(
                $payment->venta,
                0,
                $userId,
                'Comprobante de cliente rechazado desde panel vendedor: ' . $reason,
            );

            $payment = VentaPago::query()->with('venta')->findOrFail($payment->id_venta_pago);
            $this->logReviewed($role, $payment, 'rechazado', 0);
            $this->logRejected($role, $payment, $reason);

            return;
        }

        /** @var ReservaPago $payment */
        $payment = $review['payment'];
        $this->ensurePendingReservationPayment($payment);

        $this->reservations->rejectLatestPendingPayment(
            $payment->reserva,
            'Comprobante de cliente rechazado desde panel vendedor: ' . $reason,
            $userId,
        );

        $payment = ReservaPago::query()->with('reserva')->findOrFail($payment->id_reserva_pago);
        $this->logReviewed($role, $payment, 'rechazado', 0);
        $this->logRejected($role, $payment, $reason);
    }

    public function dashboardMetrics(Request $request): array
    {
        $pending = $this->pendingClientVoucherCount();

        return [
            'pending_payments' => [
                'value' => $this->number($pending),
                'note' => $pending > 0
                    ? 'Comprobantes de clientes pendientes de revisión.'
                    : 'Sin comprobantes de clientes pendientes.',
                'tone' => $pending > 0 ? 'warning' : 'success',
            ],
        ];
    }

    private function reviewRows(?string $status): Collection
    {
        $statuses = $status ? [$status] : self::REVIEW_STATUSES;

        return collect()
            ->merge($this->salePayments($statuses)->map(fn (VentaPago $payment) => $this->mapSalePaymentRow($payment)))
            ->merge($this->reservationPayments($statuses)->map(fn (ReservaPago $payment) => $this->mapReservationPaymentRow($payment)));
    }

    private function salePayments(array $statuses): EloquentCollection
    {
        return VentaPago::query()
            ->with(['venta.cliente', 'venta.vendedor', 'usuarioVerifica'])
            ->whereIn('est_vpg', $statuses)
            ->where(fn ($query) => $query
                ->whereNotNull('comprobante_ruta_vpg')
                ->orWhereNotNull('comprobante_url_vpg'))
            ->whereHas('venta', fn ($query) => $query->where('tiv_ven', 'online'))
            ->orderByDesc('fec_vpg')
            ->orderByDesc('id_venta_pago')
            ->get();
    }

    private function reservationPayments(array $statuses): EloquentCollection
    {
        return ReservaPago::query()
            ->with(['reserva.cliente', 'reserva.responsable', 'usuarioVerifica'])
            ->whereIn('est_rpg', $statuses)
            ->where(fn ($query) => $query
                ->whereNotNull('comprobante_ruta_rpg')
                ->orWhereNotNull('comprobante_url_rpg'))
            ->whereHas('reserva')
            ->orderByDesc('fec_rpg')
            ->orderByDesc('id_reserva_pago')
            ->get()
            ->filter(fn (ReservaPago $payment) => $this->isReservationReviewCandidate($payment));
    }

    private function pendingClientVoucherCount(): int
    {
        $sales = VentaPago::query()
            ->where('est_vpg', 'pendiente_revision')
            ->where(fn ($query) => $query
                ->whereNotNull('comprobante_ruta_vpg')
                ->orWhereNotNull('comprobante_url_vpg'))
            ->whereHas('venta', fn ($query) => $query->where('tiv_ven', 'online'))
            ->count();

        $reservations = ReservaPago::query()
            ->where('est_rpg', 'pendiente_revision')
            ->where(fn ($query) => $query
                ->whereNotNull('comprobante_ruta_rpg')
                ->orWhereNotNull('comprobante_url_rpg'))
            ->whereHas('reserva')
            ->count();

        return (int) $sales + (int) $reservations;
    }

    private function mapSalePaymentRow(VentaPago $payment): array
    {
        $sale = $payment->venta;
        $date = $this->paymentDate($payment->fec_vpg, $sale?->fec_ven);
        $customer = $sale?->cliente ? $this->userName($sale->cliente) : 'Cliente sin nombre';
        $number = $sale?->nve_ven ?? 'Venta #' . $payment->cod_ventas_vpg;
        $methodKey = $this->normalizePaymentMethod((string) $payment->metodo_pago_vpg);

        return [
            'id' => 'venta-' . $payment->id_venta_pago,
            'primary' => $number,
            'secondary' => 'Compra online - ' . $this->dateTime($date),
            'date' => $this->dateTime($date),
            'type' => 'venta',
            'typeLabel' => 'Compra online',
            'number' => $number,
            'customer' => $customer,
            'paymentMethod' => $this->paymentLabel($methodKey),
            'amount' => $this->money((float) $payment->monto_declarado_vpg),
            'status' => $this->paymentStatusBadge($payment->est_vpg),
            'origin' => 'Cliente online',
            'showHref' => '/vendedor/pagos/venta-' . $payment->id_venta_pago,
            'searchText' => $this->searchText([$number, $customer, $methodKey, $payment->est_vpg, 'venta', 'compra online']),
            'sortKey' => $this->sortKey($date),
            'methodKey' => $methodKey,
            'statusKey' => (string) $payment->est_vpg,
        ];
    }

    private function mapReservationPaymentRow(ReservaPago $payment): array
    {
        $reservation = $payment->reserva;
        $date = $this->paymentDate($payment->fec_rpg, $reservation?->fec_res);
        $customer = $reservation?->cliente ? $this->userName($reservation->cliente) : 'Cliente sin nombre';
        $number = $reservation?->nre_res ?? 'Reserva #' . $payment->cod_reservas_rpg;
        $methodKey = $this->normalizePaymentMethod((string) $payment->metodo_pago_rpg);

        return [
            'id' => 'reserva-' . $payment->id_reserva_pago,
            'primary' => $number,
            'secondary' => 'Reserva - ' . $this->dateTime($date),
            'date' => $this->dateTime($date),
            'type' => 'reserva',
            'typeLabel' => 'Reserva',
            'number' => $number,
            'customer' => $customer,
            'paymentMethod' => $this->paymentLabel($methodKey),
            'amount' => $this->money((float) $payment->monto_declarado_rpg),
            'status' => $this->paymentStatusBadge($payment->est_rpg),
            'origin' => 'Cliente reserva',
            'showHref' => '/vendedor/pagos/reserva-' . $payment->id_reserva_pago,
            'searchText' => $this->searchText([$number, $customer, $methodKey, $payment->est_rpg, 'reserva']),
            'sortKey' => $this->sortKey($date),
            'methodKey' => $methodKey,
            'statusKey' => (string) $payment->est_rpg,
        ];
    }

    private function saleShowPayload(VentaPago $payment): array
    {
        $payment->loadMissing(['venta.cliente', 'venta.vendedor', 'venta.pagos.usuarioVerifica', 'usuarioVerifica']);
        $sale = $payment->venta;
        abort_unless($sale, 404);

        $verified = (float) ($payment->monto_verificado_vpg ?? 0);
        $total = (float) $sale->tot_ven;
        $canReview = $payment->est_vpg === 'pendiente_revision' && $sale->est_ven === 'en_revision';

        return [
            'payment' => [
                'id' => 'venta-' . $payment->id_venta_pago,
                'type' => 'venta',
                'typeLabel' => 'Compra online',
                'number' => $sale->nve_ven,
                'date' => $this->dateTime($payment->fec_vpg),
                'uploadedAt' => $this->dateTime($payment->fec_vpg),
                'reviewedAt' => $this->dateTime($payment->fec_verificacion_vpg),
                'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Cliente sin nombre',
                'verifier' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
                'method' => $this->paymentLabel($payment->metodo_pago_vpg),
                'declaredAmount' => $this->money((float) $payment->monto_declarado_vpg),
                'verifiedAmount' => $this->money($verified),
                'expectedAmount' => $this->money($total),
                'paidAmount' => $this->money($verified),
                'balanceAmount' => $this->money(max(0, $total - $verified)),
                'status' => $this->paymentStatusBadge($payment->est_vpg),
                'voucherUrl' => $this->voucherUrl($payment->comprobante_url_vpg, $payment->comprobante_ruta_vpg),
                'note' => $this->display($payment->obs_vpg, 'Sin observación'),
                'origin' => 'Cliente online',
                'canReview' => $canReview,
                'approveAction' => '/vendedor/pagos/venta-' . $payment->id_venta_pago . '/aprobar',
                'rejectAction' => '/vendedor/pagos/venta-' . $payment->id_venta_pago . '/rechazar',
                'inventoryNotice' => 'La revisión no escribe inventario desde esta pantalla. La aprobación delega en el servicio central de ventas online, que consume el stock ya separado sin duplicar movimientos.',
            ],
            'operation' => [
                'id' => (int) $sale->id_venta,
                'type' => 'venta',
                'number' => $sale->nve_ven,
                'total' => $this->money($total),
                'paid' => $this->money($verified),
                'balance' => $this->money(max(0, $total - $verified)),
                'status' => $this->saleStatusBadge($sale->est_ven),
                'paymentStatus' => $this->paymentStatusBadge($payment->est_vpg),
                'logisticsStatus' => $this->logisticsStatusLabel($sale->est_logistico_ven),
                'href' => '/superadmin/comercial/ventas/' . $sale->id_venta,
            ],
            'history' => $sale->pagos
                ->sortByDesc('id_venta_pago')
                ->map(fn (VentaPago $row) => $this->mapSalePaymentHistory($row))
                ->values()
                ->all(),
        ];
    }

    private function reservationShowPayload(ReservaPago $payment): array
    {
        $payment->loadMissing(['reserva.cliente', 'reserva.responsable', 'reserva.pagos.usuarioVerifica', 'usuarioVerifica']);
        $reservation = $payment->reserva;
        abort_unless($reservation, 404);

        $canReview = $payment->est_rpg === 'pendiente_revision'
            && in_array((string) $reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true);

        return [
            'payment' => [
                'id' => 'reserva-' . $payment->id_reserva_pago,
                'type' => 'reserva',
                'typeLabel' => 'Reserva',
                'number' => $reservation->nre_res,
                'date' => $this->dateTime($payment->fec_rpg),
                'uploadedAt' => $this->dateTime($payment->fec_rpg),
                'reviewedAt' => $this->dateTime($payment->fec_verificacion_rpg),
                'customer' => $reservation->cliente ? $this->userName($reservation->cliente) : 'Cliente sin nombre',
                'verifier' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
                'method' => $this->paymentLabel($payment->metodo_pago_rpg),
                'declaredAmount' => $this->money((float) $payment->monto_declarado_rpg),
                'verifiedAmount' => $this->money((float) $payment->monto_confirmado_rpg),
                'expectedAmount' => $this->money((float) $reservation->tot_res),
                'paidAmount' => $this->money((float) $reservation->monto_pagado_res),
                'balanceAmount' => $this->money((float) $reservation->saldo_res),
                'status' => $this->paymentStatusBadge($payment->est_rpg),
                'voucherUrl' => $this->voucherUrl($payment->comprobante_url_rpg, $payment->comprobante_ruta_rpg),
                'note' => $this->display($payment->obs_rpg, 'Sin observación'),
                'origin' => 'Cliente reserva',
                'canReview' => $canReview,
                'approveAction' => '/vendedor/pagos/reserva-' . $payment->id_reserva_pago . '/aprobar',
                'rejectAction' => '/vendedor/pagos/reserva-' . $payment->id_reserva_pago . '/rechazar',
                'inventoryNotice' => 'La revisión no escribe inventario desde esta pantalla. El rechazo de una reserva delega en el servicio central vigente, que libera el stock separado cuando corresponde.',
            ],
            'operation' => [
                'id' => (int) $reservation->id_reserva,
                'type' => 'reserva',
                'number' => $reservation->nre_res,
                'total' => $this->money((float) $reservation->tot_res),
                'paid' => $this->money((float) $reservation->monto_pagado_res),
                'balance' => $this->money((float) $reservation->saldo_res),
                'status' => $this->reservationStatusBadge($reservation->est_res),
                'paymentStatus' => $this->reservationPaymentStatusBadge($reservation->est_pago_res),
                'logisticsStatus' => $this->logisticsStatusLabel($reservation->est_logistico_res),
                'href' => '/vendedor/reservas/' . $reservation->id_reserva,
            ],
            'history' => $reservation->pagos
                ->sortByDesc('id_reserva_pago')
                ->map(fn (ReservaPago $row) => $this->mapReservationPaymentHistory($row))
                ->values()
                ->all(),
        ];
    }

    private function mapSalePaymentHistory(VentaPago $payment): array
    {
        return [
            'id' => 'venta-' . $payment->id_venta_pago,
            'date' => $this->dateTime($payment->fec_vpg),
            'method' => $this->paymentLabel($payment->metodo_pago_vpg),
            'declared' => $this->money((float) $payment->monto_declarado_vpg),
            'confirmed' => $this->money((float) $payment->monto_verificado_vpg),
            'status' => $this->paymentStatusBadge($payment->est_vpg),
            'verifiedAt' => $this->dateTime($payment->fec_verificacion_vpg),
            'verifiedBy' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            'note' => $this->display($payment->obs_vpg, 'Sin observación'),
        ];
    }

    private function mapReservationPaymentHistory(ReservaPago $payment): array
    {
        return [
            'id' => 'reserva-' . $payment->id_reserva_pago,
            'date' => $this->dateTime($payment->fec_rpg),
            'method' => $this->paymentLabel($payment->metodo_pago_rpg),
            'declared' => $this->money((float) $payment->monto_declarado_rpg),
            'confirmed' => $this->money((float) $payment->monto_confirmado_rpg),
            'status' => $this->paymentStatusBadge($payment->est_rpg),
            'verifiedAt' => $this->dateTime($payment->fec_verificacion_rpg),
            'verifiedBy' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            'note' => $this->display($payment->obs_rpg, 'Sin observación'),
        ];
    }

    private function resolvePayment(string $paymentKey): array
    {
        if (! preg_match('/^(venta|reserva)-([0-9]+)$/', $paymentKey, $matches)) {
            abort(404);
        }

        $id = (int) $matches[2];

        if ($matches[1] === 'venta') {
            $payment = VentaPago::query()
                ->with(['venta.cliente', 'venta.vendedor', 'venta.pagos.usuarioVerifica', 'usuarioVerifica'])
                ->findOrFail($id);

            abort_unless($this->isSaleReviewCandidate($payment), 404);

            return ['type' => 'venta', 'payment' => $payment];
        }

        $payment = ReservaPago::query()
            ->with(['reserva.cliente', 'reserva.responsable', 'reserva.pagos.usuarioVerifica', 'usuarioVerifica'])
            ->findOrFail($id);

        abort_unless($this->isReservationReviewCandidate($payment), 404);

        return ['type' => 'reserva', 'payment' => $payment];
    }

    private function isSaleReviewCandidate(VentaPago $payment): bool
    {
        return $this->hasVoucher($payment->comprobante_ruta_vpg, $payment->comprobante_url_vpg)
            && $payment->venta !== null
            && (string) $payment->venta->tiv_ven === 'online'
            && in_array((string) $payment->est_vpg, self::REVIEW_STATUSES, true);
    }

    private function isReservationReviewCandidate(ReservaPago $payment): bool
    {
        if (! $this->hasVoucher($payment->comprobante_ruta_rpg, $payment->comprobante_url_rpg) || $payment->reserva === null) {
            return false;
        }

        if ((string) $payment->est_rpg === 'pendiente_revision') {
            return true;
        }

        $observation = Str::lower(Str::ascii((string) $payment->obs_rpg));

        return in_array((string) $payment->est_rpg, ['confirmado', 'rechazado'], true)
            && str_contains($observation, self::CLIENT_REVIEW_MARKER);
    }

    private function ensurePendingSalePayment(VentaPago $payment): void
    {
        if ($payment->est_vpg !== 'pendiente_revision') {
            throw ValidationException::withMessages([
                'payment_action' => 'Este comprobante ya fue revisado.',
            ]);
        }

        if ($payment->venta?->est_ven !== 'en_revision') {
            throw ValidationException::withMessages([
                'payment_action' => 'La compra online ya no está en revisión.',
            ]);
        }

        $latestPending = $payment->venta
            ->pagos()
            ->where('est_vpg', 'pendiente_revision')
            ->orderByDesc('id_venta_pago')
            ->value('id_venta_pago');

        if ((int) $latestPending !== (int) $payment->id_venta_pago) {
            throw ValidationException::withMessages([
                'payment_action' => 'Este comprobante ya no es el pendiente activo de la compra.',
            ]);
        }
    }

    private function ensurePendingReservationPayment(ReservaPago $payment): void
    {
        if ($payment->est_rpg !== 'pendiente_revision') {
            throw ValidationException::withMessages([
                'payment_action' => 'Este comprobante ya fue revisado.',
            ]);
        }

        if (! in_array((string) $payment->reserva?->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'payment_action' => 'La reserva ya no admite revisión de pagos.',
            ]);
        }

        $latestPending = $payment->reserva
            ->pagos()
            ->where('est_rpg', 'pendiente_revision')
            ->orderByDesc('fec_rpg')
            ->orderByDesc('id_reserva_pago')
            ->value('id_reserva_pago');

        if ((int) $latestPending !== (int) $payment->id_reserva_pago) {
            throw ValidationException::withMessages([
                'payment_action' => 'Este comprobante ya no es el pendiente activo de la reserva.',
            ]);
        }
    }

    private function logReviewed(string $role, VentaPago|ReservaPago $payment, string $status, float $amount): void
    {
        $this->logs->info(
            'pagos',
            'pago_cliente_revisado_por_' . $role,
            'Comprobante de cliente revisado por ' . $role . '.',
            $payment,
            $this->paymentMetadata($payment) + [
                'estado_resultado' => $status,
                'monto_revisado' => $amount,
            ],
        );
    }

    private function logApproved(string $role, VentaPago|ReservaPago $payment, string $status, float $amount): void
    {
        $this->logs->info(
            'pagos',
            'pago_cliente_aprobado_por_' . $role,
            'Comprobante de cliente aprobado por ' . $role . '.',
            $payment,
            $this->paymentMetadata($payment) + [
                'estado_resultado' => $status,
                'monto_confirmado' => $amount,
            ],
        );
    }

    private function logRejected(string $role, VentaPago|ReservaPago $payment, string $reason): void
    {
        $this->logs->warning(
            'pagos',
            'pago_cliente_rechazado_por_' . $role,
            'Comprobante de cliente rechazado por ' . $role . '.',
            $payment,
            $this->paymentMetadata($payment) + [
                'motivo' => $reason,
            ],
        );
    }

    private function paymentMetadata(VentaPago|ReservaPago $payment): array
    {
        if ($payment instanceof VentaPago) {
            return [
                'tipo' => 'venta',
                'pago_id' => $payment->id_venta_pago,
                'venta_id' => $payment->cod_ventas_vpg,
                'numero_operacion' => $payment->venta?->nve_ven,
                'cliente_id' => $payment->venta?->cod_usuario_cliente_ven,
                'estado_pago' => $payment->est_vpg,
                'metodo_pago' => $payment->metodo_pago_vpg,
                'monto_declarado' => (float) $payment->monto_declarado_vpg,
            ];
        }

        return [
            'tipo' => 'reserva',
            'pago_id' => $payment->id_reserva_pago,
            'reserva_id' => $payment->cod_reservas_rpg,
            'numero_operacion' => $payment->reserva?->nre_res,
            'cliente_id' => $payment->reserva?->cod_usuario_cliente_res,
            'estado_pago' => $payment->est_rpg,
            'metodo_pago' => $payment->metodo_pago_rpg,
            'monto_declarado' => (float) $payment->monto_declarado_rpg,
        ];
    }

    private function matchesFilters(array $row, Request $request): bool
    {
        if (filled($request->input('type')) && (string) $request->input('type') !== $row['type']) {
            return false;
        }

        if (filled($request->input('method')) && $this->normalizePaymentMethod((string) $request->input('method')) !== $row['methodKey']) {
            return false;
        }

        if (filled($request->input('date_from')) && $row['sortKey'] < Carbon::parse($request->input('date_from'))->startOfDay()->timestamp) {
            return false;
        }

        if (filled($request->input('date_to')) && $row['sortKey'] > Carbon::parse($request->input('date_to'))->endOfDay()->timestamp) {
            return false;
        }

        $search = Str::lower(trim($request->string('search')->toString()));

        return $search === '' || str_contains($row['searchText'], $search);
    }

    private function summary(Collection $rows): array
    {
        $pending = $rows->where('statusKey', 'pendiente_revision')->count();
        $approved = $rows->where('statusKey', 'confirmado')->count();
        $rejected = $rows->where('statusKey', 'rechazado')->count();

        return [
            ['label' => 'Comprobantes', 'value' => $this->number($rows->count()), 'tone' => 'accent'],
            ['label' => 'Pendientes', 'value' => $this->number($pending), 'tone' => $pending > 0 ? 'warning' : 'success'],
            ['label' => 'Aprobados', 'value' => $this->number($approved), 'tone' => 'success'],
            ['label' => 'Rechazados', 'value' => $this->number($rejected), 'tone' => $rejected > 0 ? 'danger' : 'neutral'],
        ];
    }

    private function tabs(Request $request, ?string $activeStatus): array
    {
        $counts = $this->reviewRows(null)->countBy('statusKey');

        return collect([
            ['label' => 'Pendientes', 'status' => 'pendiente_revision'],
            ['label' => 'Aprobados', 'status' => 'confirmado'],
            ['label' => 'Rechazados', 'status' => 'rechazado'],
        ])->map(fn (array $tab) => [
            'label' => $tab['label'],
            'href' => $this->tabHref($request, $tab['status']),
            'active' => $activeStatus === $tab['status'],
            'count' => $this->number((int) ($counts[$tab['status']] ?? 0)),
        ])->all();
    }

    private function tabHref(Request $request, string $status): string
    {
        $query = $request->query();
        $query['status'] = $status;

        return '/vendedor/pagos' . (count($query) > 0 ? '?' . Arr::query($query) : '');
    }

    private function requestedStatus(Request $request): ?string
    {
        $value = $request->has('status')
            ? trim($request->string('status')->toString())
            : 'pendiente_revision';

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'pendiente', 'pendientes', 'pendiente_revision' => 'pendiente_revision',
            'aprobado', 'aprobados', 'confirmado' => 'confirmado',
            'rechazado', 'rechazados' => 'rechazado',
            default => in_array($value, self::REVIEW_STATUSES, true) ? $value : 'pendiente_revision',
        };
    }

    private function statusOptions(): array
    {
        return [
            ['value' => 'pendiente_revision', 'label' => 'Pendiente revisión'],
            ['value' => 'confirmado', 'label' => 'Aprobado'],
            ['value' => 'rechazado', 'label' => 'Rechazado'],
        ];
    }

    private function paymentMethodOptions(): array
    {
        return [
            ['value' => 'qr', 'label' => 'QR'],
            ['value' => 'transferencia_bancaria', 'label' => 'Transferencia bancaria'],
            ['value' => 'efectivo', 'label' => 'Efectivo'],
        ];
    }

    private function hasVoucher(?string $path, ?string $url): bool
    {
        return filled($path) || filled($url);
    }

    private function voucherUrl(?string $url, ?string $path): ?string
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return $url;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function paymentDate(mixed $primary, mixed $fallback): ?Carbon
    {
        $value = $primary ?: $fallback;

        if (! $value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function sortKey(?Carbon $date): int
    {
        return $date?->timestamp ?? 0;
    }

    private function searchText(array $values): string
    {
        return Str::lower(trim(implode(' ', array_filter($values, fn ($value) => filled($value)))));
    }

    private function reviewerRole(?User $user): string
    {
        return $user && method_exists($user, 'isSuperadmin') && $user->isSuperadmin()
            ? 'superadmin'
            : 'vendedor';
    }

    private function normalizePaymentMethod(string $method): string
    {
        return $method === 'transferencia' ? 'transferencia_bancaria' : $method;
    }

    private function paymentLabel(?string $method): string
    {
        return match ($this->normalizePaymentMethod((string) $method)) {
            'efectivo' => 'Efectivo',
            'qr' => 'QR',
            'transferencia_bancaria' => 'Transferencia bancaria',
            default => $this->display($method, 'Sin método'),
        };
    }

    private function paymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Aprobado', 'tone' => 'success'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            'pendiente_revision' => ['label' => 'Pendiente revisión', 'tone' => 'warning'],
            default => ['label' => $this->display($status, 'Sin pago'), 'tone' => 'neutral'],
        };
    }

    private function saleStatusBadge(?string $status): array
    {
        return match ($status) {
            'registrada' => ['label' => 'Registrada', 'tone' => 'success'],
            'en_revision' => ['label' => 'En revisión', 'tone' => 'warning'],
            'cancelada', 'incompleta' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationStatusBadge(?string $status): array
    {
        return match ($status) {
            'por_procesar' => ['label' => 'En revisión', 'tone' => 'warning'],
            'confirmada' => ['label' => 'Confirmada', 'tone' => 'success'],
            'lista_recojo' => ['label' => 'Lista para recojo', 'tone' => 'info'],
            'completada', 'recogida' => ['label' => 'Completada', 'tone' => 'success'],
            'cancelada', 'rechazada', 'vencida' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationPaymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'anticipo_confirmado' => ['label' => 'Adelanto confirmado', 'tone' => 'success'],
            'pagado_total' => ['label' => 'Pagado completamente', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revisión', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin pago'), 'tone' => 'neutral'],
        };
    }

    private function logisticsStatusLabel(?string $status): string
    {
        return match ($status) {
            'pendiente_revision' => 'Pendiente revisión',
            'pendiente_preparacion' => 'Pendiente preparación',
            'pendiente_traslado' => 'Pendiente traslado',
            'lista_recojo' => 'Lista para recojo',
            'entregada' => 'Entregada',
            'recogida' => 'Recogida',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
            default => $this->display($status, 'Sin estado logistico'),
        };
    }

    private function userName(mixed $user): string
    {
        if (! $user) {
            return 'Sin usuario';
        }

        if (method_exists($user, 'nombreCompleto')) {
            return $user->nombreCompleto();
        }

        return trim(implode(' ', array_filter([
            $user->nom_usu ?? null,
            $user->app_usu ?? null,
            $user->apm_usu ?? null,
        ]))) ?: ($user->email ?? 'Sin usuario');
    }

    private function display(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text === '' ? $fallback : $text;
    }

    private function dateTime($value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
    }

    private function money(float $amount): string
    {
        return 'Bs ' . number_format($amount, 2, '.', ',');
    }

    private function number(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
