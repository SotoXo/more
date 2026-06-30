<?php

namespace App\Services\Vendedor;

use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\Superadmin\ReservationService;
use App\Services\Superadmin\SalePickupService;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendedorPickupService
{
    private const PAYMENT_BLOCK_MESSAGE = 'No se puede entregar porque el pago no esta confirmado al 100%.';
    private const READY_LOGISTICS = ['lista_recojo'];
    private const ACTIVE_RESERVATION_STATES = ['confirmada', 'lista_recojo'];

    public function __construct(
        private readonly SalePickupService $salePickup,
        private readonly ReservationService $reservations,
        private readonly SystemLogService $logs,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $tab = $this->requestedTab($request);
        $allRows = $this->pickupRows();
        $rows = $allRows
            ->filter(fn (array $row) => $this->rowBelongsToTab($row, $tab))
            ->filter(fn (array $row) => $this->matchesFilters($row, $request))
            ->sortBy('sortKey')
            ->values();

        return [
            'pickups' => $rows
                ->map(fn (array $row) => Arr::except($row, ['sortKey', 'searchText', 'paymentStatusKey', 'logisticsStatusKey', 'isReady', 'isBlocked']))
                ->values()
                ->all(),
            'summary' => $this->summary($allRows),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
                'payment_status' => $request->string('payment_status')->toString(),
                'logistics_status' => $request->string('logistics_status')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'tab' => $tab,
            ],
            'options' => [
                'types' => [
                    ['value' => 'venta', 'label' => 'Venta'],
                    ['value' => 'reserva', 'label' => 'Reserva'],
                ],
                'paymentStatuses' => [
                    ['value' => 'pagado_total', 'label' => 'Pago 100%'],
                    ['value' => 'pendiente_revision', 'label' => 'Pendiente revision'],
                    ['value' => 'incompleto', 'label' => 'Pago incompleto'],
                    ['value' => 'sin_pago', 'label' => 'Sin pago'],
                ],
                'logisticsStatuses' => [
                    ['value' => 'lista_recojo', 'label' => 'Lista para recojo'],
                    ['value' => 'pendiente_preparacion', 'label' => 'Pendiente preparacion'],
                    ['value' => 'pendiente_traslado', 'label' => 'Pendiente traslado'],
                    ['value' => 'pendiente_revision', 'label' => 'Pendiente revision'],
                ],
            ],
            'tabs' => $this->tabs($request, $tab, $allRows),
            'scopeLabel' => 'Bandeja operativa de entregas fisicas y recojos. Solo se habilita entrega cuando el pago confirmado cubre el 100%.',
        ];
    }

    public function saleShowPayload(Request $request, Venta $sale): array
    {
        $sale = $this->saleQuery()
            ->whereKey($sale->id_venta)
            ->firstOrFail();

        $payment = $this->salePaymentSummary($sale);
        $deliverability = $this->saleDeliverability($sale, $payment);

        return [
            'sale' => [
                'id' => (int) $sale->id_venta,
                'number' => $sale->nve_ven,
                'date' => $this->dateTime($sale->fec_ven),
                'seller' => $this->userName($sale->vendedor),
                'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente',
                'type' => $this->saleTypeLabel($sale->tiv_ven),
                'total' => $this->money((float) $sale->tot_ven),
                'paid' => $this->money($payment['confirmed']),
                'balance' => $this->money($payment['balance']),
                'paymentPercent' => $payment['percentageLabel'],
                'paymentStatus' => $this->paymentStatusBadge($payment['status']),
                'saleStatus' => $this->saleStatusBadge($sale->est_ven),
                'logisticsStatus' => $this->logisticsStatusLabel($sale->est_logistico_ven),
                'pickupPoint' => $this->display($sale->punto_recojo_ven, 'Recojo en tienda Mati Boutique'),
                'stockOrigin' => $this->display($sale->origen_stock_ven, 'TIENDA'),
                'availableAt' => $this->date($sale->fec_disponible_recojo_ven),
                'limitAt' => $this->date($sale->fec_limite_recojo_ven),
                'deliveredAt' => $this->dateTime($sale->fec_entrega_ven),
                'canDeliver' => $deliverability['canDeliver'],
                'blockReason' => $deliverability['reason'],
                'deliverAction' => '/vendedor/recojos/ventas/' . $sale->id_venta . '/entregar',
            ],
            'items' => $sale->detalles
                ->map(fn (DetalleVenta $detail) => $this->mapSaleItem($detail))
                ->values()
                ->all(),
            'payments' => $sale->pagos
                ->sortByDesc('id_venta_pago')
                ->map(fn (VentaPago $payment) => $this->mapSalePayment($payment))
                ->values()
                ->all(),
            'rules' => [
                'paymentBlockMessage' => self::PAYMENT_BLOCK_MESSAGE,
                'stockNotice' => 'Entregar una venta no modifica cdi_inv ni cre_inv. El stock ya fue descontado o confirmado en el flujo comercial previo.',
            ],
        ];
    }

    public function reservationShowPayload(Request $request, Reserva $reservation): array
    {
        $reservation = $this->reservationQuery()
            ->whereKey($reservation->id_reserva)
            ->firstOrFail();

        $payment = $this->reservationPaymentSummary($reservation);
        $deliverability = $this->reservationDeliverability($reservation, $payment);

        return [
            'reservation' => [
                'id' => (int) $reservation->id_reserva,
                'number' => $reservation->nre_res,
                'date' => $this->dateTime($reservation->fec_res),
                'responsible' => $this->userName($reservation->responsable),
                'customer' => $this->userName($reservation->cliente),
                'total' => $this->money((float) $reservation->tot_res),
                'paid' => $this->money($payment['confirmed']),
                'balance' => $this->money($payment['balance']),
                'paymentPercent' => $payment['percentageLabel'],
                'paymentStatus' => $this->paymentStatusBadge($payment['status']),
                'status' => $this->reservationStatusBadge($reservation->est_res),
                'logisticsStatus' => $this->logisticsStatusLabel($reservation->est_logistico_res),
                'expiresAt' => $this->date($reservation->ven_res),
                'availableAt' => $this->date($reservation->fec_disponible_recojo_res),
                'limitAt' => $this->date($reservation->fec_limite_recojo_res),
                'pickupPoint' => $this->display($reservation->punto_recojo_res, 'Recojo en tienda Mati Boutique'),
                'stockOrigin' => $this->display($reservation->origen_stock_res, 'TIENDA'),
                'pickedUpAt' => $this->dateTime($reservation->fec_recogida_res),
                'canDeliver' => $deliverability['canDeliver'],
                'blockReason' => $deliverability['reason'],
                'deliverAction' => '/vendedor/recojos/reservas/' . $reservation->id_reserva . '/entregar',
            ],
            'items' => $reservation->detalles
                ->map(fn (DetalleReserva $detail) => $this->mapReservationItem($detail))
                ->values()
                ->all(),
            'payments' => $reservation->pagos
                ->sortByDesc('id_reserva_pago')
                ->map(fn (ReservaPago $payment) => $this->mapReservationPayment($payment))
                ->values()
                ->all(),
            'rules' => [
                'paymentBlockMessage' => self::PAYMENT_BLOCK_MESSAGE,
                'stockNotice' => 'Recoger una reserva no descuenta cdi_inv otra vez. El flujo central consume el stock comprometido en cre_inv y registra conversion_reserva_venta.',
            ],
        ];
    }

    public function deliverSale(Request $request, Venta $sale): void
    {
        $user = $request->user();

        DB::transaction(function () use ($sale, $user) {
            $lockedSale = $this->saleQuery()
                ->lockForUpdate()
                ->findOrFail($sale->id_venta);

            $previousLogistics = (string) $lockedSale->est_logistico_ven;
            $payment = $this->salePaymentSummary($lockedSale);
            $deliverability = $this->saleDeliverability($lockedSale, $payment);

            if (! $payment['paidFull']) {
                $this->logPaymentBlock('venta', $lockedSale, $user, $payment);
                throw ValidationException::withMessages([
                    'pickup_action' => self::PAYMENT_BLOCK_MESSAGE,
                ]);
            }

            if (! $deliverability['canDeliver']) {
                throw ValidationException::withMessages([
                    'pickup_action' => $deliverability['reason'] ?: 'Esta venta no puede entregarse.',
                ]);
            }

            $this->salePickup->markDelivered($lockedSale, $user);
            $lockedSale->refresh();

            $this->logs->info(
                'logistica',
                'venta_entregada_por_vendedor',
                'Venta entregada por vendedor: ' . $lockedSale->nve_ven . '.',
                $lockedSale,
                [
                    'tipo_operacion' => 'venta',
                    'venta_id' => $lockedSale->id_venta,
                    'numero_venta' => $lockedSale->nve_ven,
                    'usuario_vendedor_id' => $user?->id,
                    'estado_anterior' => $previousLogistics,
                    'estado_nuevo' => $lockedSale->est_logistico_ven,
                    'total' => (float) $lockedSale->tot_ven,
                    'monto_confirmado' => $payment['confirmed'],
                ],
            );
        });
    }

    public function deliverReservation(Request $request, Reserva $reservation): void
    {
        $user = $request->user();
        $reservation = $this->reservationQuery()->findOrFail($reservation->id_reserva);
        $previousStatus = (string) $reservation->est_res;
        $previousLogistics = (string) $reservation->est_logistico_res;
        $payment = $this->reservationPaymentSummary($reservation);
        $deliverability = $this->reservationDeliverability($reservation, $payment);

        if (! $payment['paidFull']) {
            $this->logPaymentBlock('reserva', $reservation, $user, $payment);
            throw ValidationException::withMessages([
                'pickup_action' => self::PAYMENT_BLOCK_MESSAGE,
            ]);
        }

        if (! $deliverability['canDeliver']) {
            throw ValidationException::withMessages([
                'pickup_action' => $deliverability['reason'] ?: 'Esta reserva no puede marcarse como recogida.',
            ]);
        }

        $saleBefore = $this->reservations->saleForReservation($reservation);

        $this->reservations->complete(
            $reservation,
            (int) $user->id,
            null,
            null,
            'Reserva recogida desde panel vendedor.',
            null,
        );

        $reservation->refresh();
        $saleAfter = $this->reservations->saleForReservation($reservation);

        $this->logs->info(
            'logistica',
            'reserva_recogida_por_vendedor',
            'Reserva recogida por vendedor: ' . $reservation->nre_res . '.',
            $reservation,
            [
                'tipo_operacion' => 'reserva',
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'usuario_vendedor_id' => $user?->id,
                'estado_anterior' => $previousStatus,
                'estado_nuevo' => $reservation->est_res,
                'estado_logistico_anterior' => $previousLogistics,
                'estado_logistico_nuevo' => $reservation->est_logistico_res,
                'total' => (float) $reservation->tot_res,
                'monto_confirmado' => $payment['confirmed'],
                'venta_id' => $saleAfter?->id_venta,
                'numero_venta' => $saleAfter?->nve_ven,
            ],
        );

        if (! $saleBefore && $saleAfter) {
            $this->logs->info(
                'ventas',
                'reserva_convertida_a_venta_desde_recojo',
                'Reserva convertida a venta desde recojo vendedor: ' . $reservation->nre_res . '.',
                $saleAfter,
                [
                    'reserva_id' => $reservation->id_reserva,
                    'numero_reserva' => $reservation->nre_res,
                    'venta_id' => $saleAfter->id_venta,
                    'numero_venta' => $saleAfter->nve_ven,
                    'usuario_vendedor_id' => $user?->id,
                    'total' => (float) $saleAfter->tot_ven,
                ],
            );
        }
    }

    public function dashboardMetrics(Request $request): array
    {
        $rows = $this->pickupRows();
        $ready = $rows->where('isReady', true)->count();
        $blocked = $rows->where('isBlocked', true)->count();

        return [
            'pending_pickups' => [
                'value' => $this->number($ready),
                'note' => $blocked > 0
                    ? $this->number($blocked) . ' operaciones bloqueadas por pago pendiente.'
                    : 'Operaciones pagadas al 100% listas para entregar.',
                'tone' => $ready > 0 ? 'warning' : 'success',
            ],
        ];
    }

    private function pickupRows(): Collection
    {
        return collect()
            ->merge($this->saleQuery()->get()->map(fn (Venta $sale) => $this->mapSaleRow($sale)))
            ->merge($this->reservationQuery()->get()->map(fn (Reserva $reservation) => $this->mapReservationRow($reservation)));
    }

    private function saleQuery()
    {
        return Venta::query()
            ->with([
                'cliente',
                'vendedor',
                'pagos.usuarioVerifica',
                'detalles.variante.prenda.imagenes',
                'detalles.variante.color',
                'detalles.variante.talla',
                'detalles.variante.inventario',
            ])
            ->whereIn('est_ven', ['registrada', 'en_revision'])
            ->whereNotIn('est_logistico_ven', ['entregada', 'cancelada'])
            ->orderBy('fec_limite_recojo_ven')
            ->orderByDesc('id_venta');
    }

    private function reservationQuery()
    {
        return Reserva::query()
            ->with([
                'cliente',
                'responsable',
                'usuarioEntrega',
                'pagos.usuarioVerifica',
                'detalles.variante.prenda.imagenes',
                'detalles.variante.color',
                'detalles.variante.talla',
                'detalles.variante.inventario',
            ])
            ->whereIn('est_res', ['por_procesar', 'confirmada', 'lista_recojo'])
            ->whereNotIn('est_logistico_res', ['recogida', 'cancelada', 'vencida'])
            ->orderBy('fec_limite_recojo_res')
            ->orderByDesc('id_reserva');
    }

    private function mapSaleRow(Venta $sale): array
    {
        $payment = $this->salePaymentSummary($sale);
        $deliverability = $this->saleDeliverability($sale, $payment);
        $date = $sale->fec_limite_recojo_ven ?: $sale->fec_disponible_recojo_ven ?: $sale->fec_ven;
        $customer = $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente';

        return [
            'id' => 'venta-' . $sale->id_venta,
            'primary' => $sale->nve_ven,
            'secondary' => 'Venta - ' . $customer,
            'type' => 'venta',
            'typeLabel' => 'Venta',
            'number' => $sale->nve_ven,
            'customer' => $customer,
            'date' => $this->dateTime($sale->fec_ven),
            'limitAt' => $this->date($sale->fec_limite_recojo_ven),
            'total' => $this->money((float) $sale->tot_ven),
            'paid' => $this->money($payment['confirmed']),
            'balance' => $this->money($payment['balance']),
            'paymentStatus' => $this->paymentStatusBadge($payment['status']),
            'logisticsStatus' => $this->logisticsStatusLabel($sale->est_logistico_ven),
            'location' => $this->display($sale->punto_recojo_ven, 'Recojo en tienda Mati Boutique'),
            'status' => $deliverability['canDeliver']
                ? ['label' => 'Lista', 'tone' => 'success']
                : ($payment['paidFull'] ? ['label' => 'No lista', 'tone' => 'info'] : ['label' => 'Bloqueada', 'tone' => 'warning']),
            'showHref' => '/vendedor/recojos/ventas/' . $sale->id_venta,
            'isReady' => $deliverability['canDeliver'],
            'isBlocked' => ! $payment['paidFull'],
            'paymentStatusKey' => $payment['status'],
            'logisticsStatusKey' => (string) $sale->est_logistico_ven,
            'sortKey' => $this->sortKey($date),
            'searchText' => $this->searchText([$sale->nve_ven, $customer, $sale->est_logistico_ven, $payment['status'], 'venta']),
        ];
    }

    private function mapReservationRow(Reserva $reservation): array
    {
        $payment = $this->reservationPaymentSummary($reservation);
        $deliverability = $this->reservationDeliverability($reservation, $payment);
        $date = $reservation->fec_limite_recojo_res ?: $reservation->fec_disponible_recojo_res ?: $reservation->fec_res;
        $customer = $this->userName($reservation->cliente);

        return [
            'id' => 'reserva-' . $reservation->id_reserva,
            'primary' => $reservation->nre_res,
            'secondary' => 'Reserva - ' . $customer,
            'type' => 'reserva',
            'typeLabel' => 'Reserva',
            'number' => $reservation->nre_res,
            'customer' => $customer,
            'date' => $this->dateTime($reservation->fec_res),
            'limitAt' => $this->date($reservation->fec_limite_recojo_res),
            'total' => $this->money((float) $reservation->tot_res),
            'paid' => $this->money($payment['confirmed']),
            'balance' => $this->money($payment['balance']),
            'paymentStatus' => $this->paymentStatusBadge($payment['status']),
            'logisticsStatus' => $this->logisticsStatusLabel($reservation->est_logistico_res),
            'location' => $this->display($reservation->punto_recojo_res, 'Recojo en tienda Mati Boutique'),
            'status' => $deliverability['canDeliver']
                ? ['label' => 'Lista', 'tone' => 'success']
                : ($payment['paidFull'] ? ['label' => 'No lista', 'tone' => 'info'] : ['label' => 'Bloqueada', 'tone' => 'warning']),
            'showHref' => '/vendedor/recojos/reservas/' . $reservation->id_reserva,
            'isReady' => $deliverability['canDeliver'],
            'isBlocked' => ! $payment['paidFull'],
            'paymentStatusKey' => $payment['status'],
            'logisticsStatusKey' => (string) $reservation->est_logistico_res,
            'sortKey' => $this->sortKey($date),
            'searchText' => $this->searchText([$reservation->nre_res, $customer, $reservation->est_logistico_res, $payment['status'], 'reserva']),
        ];
    }

    private function rowBelongsToTab(array $row, string $tab): bool
    {
        return match ($tab) {
            'ventas' => $row['type'] === 'venta' && $row['isReady'],
            'reservas' => $row['type'] === 'reserva' && $row['isReady'],
            'bloqueadas' => $row['isBlocked'],
            default => $row['isReady'],
        };
    }

    private function matchesFilters(array $row, Request $request): bool
    {
        if (filled($request->input('type')) && (string) $request->input('type') !== $row['type']) {
            return false;
        }

        if (filled($request->input('payment_status')) && (string) $request->input('payment_status') !== $row['paymentStatusKey']) {
            return false;
        }

        if (filled($request->input('logistics_status')) && (string) $request->input('logistics_status') !== $row['logisticsStatusKey']) {
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
        $readySales = $rows->where('type', 'venta')->where('isReady', true)->count();
        $readyReservations = $rows->where('type', 'reserva')->where('isReady', true)->count();
        $blocked = $rows->where('isBlocked', true)->count();

        return [
            ['label' => 'Ventas listas', 'value' => $this->number($readySales), 'tone' => $readySales > 0 ? 'success' : 'neutral'],
            ['label' => 'Reservas listas', 'value' => $this->number($readyReservations), 'tone' => $readyReservations > 0 ? 'success' : 'neutral'],
            ['label' => 'Bloqueadas por pago', 'value' => $this->number($blocked), 'tone' => $blocked > 0 ? 'warning' : 'success'],
            ['label' => 'Listas totales', 'value' => $this->number($readySales + $readyReservations), 'tone' => 'accent'],
        ];
    }

    private function tabs(Request $request, string $active, Collection $rows): array
    {
        $tabs = [
            ['key' => 'ventas', 'label' => 'Ventas listas', 'count' => $rows->where('type', 'venta')->where('isReady', true)->count()],
            ['key' => 'reservas', 'label' => 'Reservas listas', 'count' => $rows->where('type', 'reserva')->where('isReady', true)->count()],
            ['key' => 'bloqueadas', 'label' => 'Bloqueadas por pago', 'count' => $rows->where('isBlocked', true)->count()],
        ];

        return collect($tabs)
            ->map(fn (array $tab) => [
                'label' => $tab['label'],
                'href' => $this->tabHref($request, $tab['key']),
                'active' => $active === $tab['key'],
                'count' => $this->number((int) $tab['count']),
            ])
            ->all();
    }

    private function tabHref(Request $request, string $tab): string
    {
        $query = $request->query();
        $query['tab'] = $tab;

        return '/vendedor/recojos' . (count($query) > 0 ? '?' . Arr::query($query) : '');
    }

    private function requestedTab(Request $request): string
    {
        return match ($request->string('tab')->toString()) {
            'reservas' => 'reservas',
            'bloqueadas' => 'bloqueadas',
            default => 'ventas',
        };
    }

    private function salePaymentSummary(Venta $sale): array
    {
        $confirmed = (float) $sale->pagos
            ->where('est_vpg', 'confirmado')
            ->sum(fn (VentaPago $payment) => (float) $payment->monto_verificado_vpg);

        $pending = $sale->pagos->contains(fn (VentaPago $payment) => $payment->est_vpg === 'pendiente_revision');

        return $this->paymentSummary((float) $sale->tot_ven, $confirmed, $pending);
    }

    private function reservationPaymentSummary(Reserva $reservation): array
    {
        $confirmed = (float) $reservation->pagos
            ->where('est_rpg', 'confirmado')
            ->sum(fn (ReservaPago $payment) => (float) $payment->monto_confirmado_rpg);

        $pending = $reservation->pagos->contains(fn (ReservaPago $payment) => $payment->est_rpg === 'pendiente_revision');

        return $this->paymentSummary((float) $reservation->tot_res, $confirmed, $pending);
    }

    private function paymentSummary(float $total, float $confirmed, bool $hasPending): array
    {
        $total = round($total, 2);
        $confirmed = min(round($confirmed, 2), $total);
        $balance = max(0, round($total - $confirmed, 2));
        $paidFull = $total > 0 && $confirmed + 0.0001 >= $total;
        $percentage = $total > 0 ? min(100, round(($confirmed / $total) * 100, 2)) : 0;

        $status = match (true) {
            $paidFull => 'pagado_total',
            $hasPending => 'pendiente_revision',
            $confirmed > 0 => 'incompleto',
            default => 'sin_pago',
        };

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'balance' => $balance,
            'paidFull' => $paidFull,
            'percentage' => $percentage,
            'percentageLabel' => number_format($percentage, 2, '.', ',') . '%',
            'status' => $status,
        ];
    }

    private function saleDeliverability(Venta $sale, array $payment): array
    {
        if (! $payment['paidFull']) {
            return ['canDeliver' => false, 'reason' => self::PAYMENT_BLOCK_MESSAGE];
        }

        if ($sale->est_ven !== 'registrada') {
            return ['canDeliver' => false, 'reason' => 'Solo se pueden entregar ventas confirmadas.'];
        }

        if ($sale->est_logistico_ven === 'entregada') {
            return ['canDeliver' => false, 'reason' => 'Esta venta ya fue entregada.'];
        }

        if (! in_array((string) $sale->est_logistico_ven, self::READY_LOGISTICS, true)) {
            return ['canDeliver' => false, 'reason' => 'La venta todavia no esta lista para recojo.'];
        }

        return ['canDeliver' => true, 'reason' => null];
    }

    private function reservationDeliverability(Reserva $reservation, array $payment): array
    {
        if (! $payment['paidFull']) {
            return ['canDeliver' => false, 'reason' => self::PAYMENT_BLOCK_MESSAGE];
        }

        if ($reservation->est_res === 'completada' || $reservation->est_logistico_res === 'recogida') {
            return ['canDeliver' => false, 'reason' => 'Esta reserva ya fue recogida.'];
        }

        if (in_array((string) $reservation->est_res, ['cancelada', 'rechazada', 'vencida'], true)) {
            return ['canDeliver' => false, 'reason' => 'Esta reserva ya no puede entregarse.'];
        }

        if (! in_array((string) $reservation->est_res, self::ACTIVE_RESERVATION_STATES, true)) {
            return ['canDeliver' => false, 'reason' => 'Solo se pueden recoger reservas confirmadas.'];
        }

        if (! in_array((string) $reservation->est_logistico_res, self::READY_LOGISTICS, true)) {
            return ['canDeliver' => false, 'reason' => 'La reserva todavia no esta lista para recojo.'];
        }

        if ($reservation->fec_limite_recojo_res && $reservation->fec_limite_recojo_res->lt(Carbon::now())) {
            return ['canDeliver' => false, 'reason' => 'La reserva esta vencida y debe revisarse antes de entregar.'];
        }

        if (! $this->reservationHasCommittedStock($reservation)) {
            return ['canDeliver' => false, 'reason' => 'No existe stock comprometido suficiente en cre_inv para completar la reserva.'];
        }

        return ['canDeliver' => true, 'reason' => null];
    }

    private function reservationHasCommittedStock(Reserva $reservation): bool
    {
        $reservation->loadMissing('detalles.variante.inventario');

        foreach ($reservation->detalles as $detail) {
            $inventory = $detail->variante?->inventario;

            if (! $inventory || (int) $inventory->cre_inv < (int) $detail->can_dre) {
                return false;
            }
        }

        return $reservation->detalles->isNotEmpty();
    }

    private function logPaymentBlock(string $type, Venta|Reserva $operation, ?User $user, array $payment): void
    {
        $this->logs->warning(
            'logistica',
            'entrega_bloqueada_por_pago_pendiente',
            self::PAYMENT_BLOCK_MESSAGE,
            $operation,
            [
                'tipo_operacion' => $type,
                'operacion_id' => $operation->getKey(),
                'numero_operacion' => $type === 'venta' ? $operation->nve_ven : $operation->nre_res,
                'usuario_vendedor_id' => $user?->id,
                'total' => $payment['total'],
                'monto_confirmado' => $payment['confirmed'],
                'saldo' => $payment['balance'],
            ],
        );
    }

    private function mapSaleItem(DetalleVenta $detail): array
    {
        $variant = $detail->variante;
        $product = $variant?->prenda;

        return [
            'id' => (int) $detail->id_detalle_venta,
            'primary' => $product?->nom_pre ?? 'Prenda sin nombre',
            'secondary' => $variant?->sku_prv ?: 'Sin SKU',
            'image' => $product ? $this->primaryImage($product) : null,
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?? 'Sin color',
            'size' => $variant?->talla?->nom_tal ?? 'Sin talla',
            'quantity' => $this->number((int) $detail->can_dve),
            'location' => $variant?->inventario?->ubi_inv ?: 'Sin ubicacion',
        ];
    }

    private function mapReservationItem(DetalleReserva $detail): array
    {
        $variant = $detail->variante;
        $product = $variant?->prenda;
        $committed = (int) ($variant?->inventario?->cre_inv ?? 0);
        $quantity = (int) $detail->can_dre;

        return [
            'id' => (int) $detail->id_detalle_reserva,
            'primary' => $product?->nom_pre ?? 'Prenda sin nombre',
            'secondary' => $variant?->sku_prv ?: 'Sin SKU',
            'image' => $product ? $this->primaryImage($product) : null,
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?? 'Sin color',
            'size' => $variant?->talla?->nom_tal ?? 'Sin talla',
            'quantity' => $this->number($quantity),
            'location' => $variant?->inventario?->ubi_inv ?: 'Sin ubicacion',
            'committed' => $this->number($committed),
            'committedStatus' => $committed >= $quantity
                ? ['label' => 'cre_inv suficiente', 'tone' => 'success']
                : ['label' => 'cre_inv insuficiente', 'tone' => 'danger'],
        ];
    }

    private function mapSalePayment(VentaPago $payment): array
    {
        return [
            'id' => (int) $payment->id_venta_pago,
            'date' => $this->dateTime($payment->fec_vpg),
            'method' => $this->paymentMethodLabel($payment->metodo_pago_vpg),
            'declared' => $this->money((float) $payment->monto_declarado_vpg),
            'confirmed' => $this->money((float) $payment->monto_verificado_vpg),
            'status' => $this->paymentRowStatusBadge($payment->est_vpg),
            'verifiedBy' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            'voucherUrl' => $this->voucherUrl($payment->comprobante_url_vpg, $payment->comprobante_ruta_vpg),
        ];
    }

    private function mapReservationPayment(ReservaPago $payment): array
    {
        return [
            'id' => (int) $payment->id_reserva_pago,
            'date' => $this->dateTime($payment->fec_rpg),
            'method' => $this->paymentMethodLabel($payment->metodo_pago_rpg),
            'declared' => $this->money((float) $payment->monto_declarado_rpg),
            'confirmed' => $this->money((float) $payment->monto_confirmado_rpg),
            'status' => $this->paymentRowStatusBadge($payment->est_rpg),
            'verifiedBy' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            'voucherUrl' => $this->voucherUrl($payment->comprobante_url_rpg, $payment->comprobante_ruta_rpg),
        ];
    }

    private function paymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'pagado_total' => ['label' => 'Pago 100%', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revision', 'tone' => 'warning'],
            'incompleto' => ['label' => 'Incompleto', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => 'Sin pago', 'tone' => 'neutral'],
        };
    }

    private function paymentRowStatusBadge(?string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Confirmado', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revision', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function saleStatusBadge(?string $status): array
    {
        return match ($status) {
            'registrada' => ['label' => 'Registrada', 'tone' => 'success'],
            'en_revision' => ['label' => 'En revision', 'tone' => 'warning'],
            'cancelada', 'incompleta' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationStatusBadge(?string $status): array
    {
        return match ($status) {
            'por_procesar' => ['label' => 'En revision', 'tone' => 'warning'],
            'confirmada' => ['label' => 'Confirmada', 'tone' => 'success'],
            'lista_recojo' => ['label' => 'Lista para recojo', 'tone' => 'info'],
            'completada', 'recogida' => ['label' => 'Completada', 'tone' => 'success'],
            'cancelada', 'rechazada', 'vencida' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function logisticsStatusLabel(?string $status): string
    {
        return match ($status) {
            'pendiente_revision' => 'Pendiente revision',
            'pendiente_preparacion' => 'Pendiente preparacion',
            'pendiente_traslado' => 'Pendiente traslado',
            'lista_recojo' => 'Lista para recojo',
            'entregada' => 'Entregada',
            'recogida' => 'Recogida',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
            default => $this->display($status, 'Sin estado logistico'),
        };
    }

    private function saleTypeLabel(?string $type): string
    {
        return match ($type) {
            'online' => 'Compra online',
            'fisica' => 'Venta directa',
            'reserva' => 'Venta por reserva',
            default => $this->display($type, 'Venta'),
        };
    }

    private function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'efectivo' => 'Efectivo',
            'qr' => 'QR',
            'transferencia', 'transferencia_bancaria' => 'Transferencia bancaria',
            'reserva' => 'Reserva',
            default => $this->display($method, 'Sin metodo'),
        };
    }

    private function primaryImage($product): ?string
    {
        $image = collect($product->imagenes ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->first();

        return $this->imageUrl($image?->url_igp, $image?->rut_igp);
    }

    private function voucherUrl(?string $url, ?string $path): ?string
    {
        return $this->imageUrl($url, $path);
    }

    private function imageUrl(?string $url, ?string $path): ?string
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

    private function sortKey(mixed $date): int
    {
        if (! $date) {
            return 0;
        }

        return ($date instanceof Carbon ? $date : Carbon::parse($date))->timestamp;
    }

    private function searchText(array $values): string
    {
        return Str::lower(trim(implode(' ', array_filter($values, fn ($value) => filled($value)))));
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

    private function date($value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y');
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
