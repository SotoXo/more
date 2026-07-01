<?php

namespace App\Services\Vendedor;

use App\Models\Inventario;
use App\Models\PrendaDanioReporte;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendedorReportService
{
    private const TABLE_LIMIT = 12;
    private const LOW_STOCK_LIMIT = 2;

    public function indexPayload(Request $request): array
    {
        $filters = $this->filters($request);
        $data = $this->reportData($request, $filters);

        return [
            'filters' => $filters,
            'filterAction' => '/vendedor/reportes',
            'exportHref' => '/vendedor/reportes/exportar?' . Arr::query($this->filterQuery($filters)),
            'printHref' => '/vendedor/reportes/imprimir?' . Arr::query($this->filterQuery($filters)),
            'scopeLabel' => $this->isSupervising($request)
                ? 'Supervision global desde el panel vendedor. No incluye auditoria, backups, logs globales ni reportes financieros administrativos.'
                : 'Datos limitados a operaciones asociadas a tu usuario vendedor.',
            'summary' => $this->summaryCards($data),
            'charts' => $this->chartData($data),
            'tables' => $this->limitedTables($data),
            'notifications' => $this->notifications($request),
            'limits' => [
                'tableRows' => self::TABLE_LIMIT,
                'tableNote' => 'Las tablas muestran los registros mas recientes del periodo. El CSV incluye el periodo filtrado completo.',
            ],
        ];
    }

    public function printPayload(Request $request): array
    {
        $filters = $this->filters($request);
        $data = $this->reportData($request, $filters);

        return [
            'filters' => $filters,
            'scopeLabel' => $this->isSupervising($request)
                ? 'Supervision global en panel vendedor.'
                : 'Reporte limitado al usuario vendedor autenticado.',
            'generatedAt' => $this->dateTime(Carbon::now()),
            'summary' => $this->summaryCards($data),
            'tables' => $data,
            'backHref' => '/vendedor/reportes?' . Arr::query($this->filterQuery($filters)),
        ];
    }

    public function csvResponse(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $data = $this->reportData($request, $filters);
        $filename = 'mati-boutique-reporte-vendedor-' . Carbon::now(config('app.timezone'))->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data, $filters, $request) {
            $handle = fopen('php://output', 'w');

            $this->csvRow($handle, ['Mati Boutique - Mis reportes']);
            $this->csvRow($handle, ['Alcance', $this->isSupervising($request) ? 'Supervision global' : 'Usuario vendedor autenticado']);
            $this->csvRow($handle, ['Desde', $filters['date_from'], 'Hasta', $filters['date_to']]);
            $this->csvRow($handle, []);

            $this->writeCsvSection($handle, 'Ventas', ['Fecha', 'Venta', 'Cliente', 'Total', 'Método', 'Estado', 'Entrega'], $data['sales']);
            $this->writeCsvSection($handle, 'Reservas', ['Fecha', 'Reserva', 'Cliente', 'Total', 'Pagado', 'Saldo', 'Estado'], $data['reservations']);
            $this->writeCsvSection($handle, 'Pagos gestionados', ['Fecha', 'Tipo', 'Operación', 'Método', 'Monto', 'Estado', 'Acción'], $data['payments']);
            $this->writeCsvSection($handle, 'Recojos y entregas', ['Fecha', 'Tipo', 'Número', 'Cliente', 'Estado'], $data['pickups']);
            $this->writeCsvSection($handle, 'Prendas dañadas', ['Fecha', 'Código', 'Prenda', 'Causa', 'Tipo', 'Estado'], $data['damagedProducts']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function reportData(Request $request, array $filters): array
    {
        return [
            'sales' => $this->sales($request, $filters)->values()->all(),
            'reservations' => $this->reservations($request, $filters)->values()->all(),
            'payments' => $this->payments($request, $filters)->values()->all(),
            'pickups' => $this->pickups($request, $filters)->values()->all(),
            'damagedProducts' => $this->damagedProducts($request, $filters)->values()->all(),
            'stockAlerts' => $this->stockAlerts(),
        ];
    }

    private function sales(Request $request, array $filters): Collection
    {
        $query = Venta::query()
            ->with(['cliente', 'vendedor', 'usuarioEntrega', 'pagos'])
            ->whereBetween('fec_ven', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_vendedor_ven', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_ven')
            ->orderByDesc('id_venta')
            ->get()
            ->map(fn (Venta $sale) => [
                'id' => (int) $sale->id_venta,
                'primary' => $sale->nve_ven,
                'secondary' => $this->dateTime($sale->fec_ven),
                'date' => $this->dateTime($sale->fec_ven),
                'number' => $sale->nve_ven,
                'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente',
                'total' => $this->money((float) $sale->tot_ven),
                'totalAmount' => (float) $sale->tot_ven,
                'paymentMethod' => $this->paymentLabel($sale->mpa_ven),
                'status' => $this->saleStatusBadge($sale->est_ven),
                'delivery' => $this->logisticsLabel($sale->est_logistico_ven),
                'showHref' => '/vendedor/ventas/' . $sale->id_venta,
            ]);
    }

    private function reservations(Request $request, array $filters): Collection
    {
        $query = Reserva::query()
            ->with(['cliente', 'responsable'])
            ->whereBetween('fec_res', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_responsable_res', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_res')
            ->orderByDesc('id_reserva')
            ->get()
            ->map(fn (Reserva $reservation) => [
                'id' => (int) $reservation->id_reserva,
                'primary' => $reservation->nre_res,
                'secondary' => $this->dateTime($reservation->fec_res),
                'date' => $this->dateTime($reservation->fec_res),
                'number' => $reservation->nre_res,
                'customer' => $this->userName($reservation->cliente),
                'total' => $this->money((float) $reservation->tot_res),
                'totalAmount' => (float) $reservation->tot_res,
                'paid' => $this->money((float) $reservation->monto_pagado_res),
                'paidAmount' => (float) $reservation->monto_pagado_res,
                'balance' => $this->money((float) $reservation->saldo_res),
                'balanceAmount' => (float) $reservation->saldo_res,
                'status' => $this->reservationStatusBadge($reservation->est_res),
                'showHref' => '/vendedor/reservas/' . $reservation->id_reserva,
            ]);
    }

    private function payments(Request $request, array $filters): Collection
    {
        return collect()
            ->merge($this->salePayments($request, $filters))
            ->merge($this->reservationPayments($request, $filters))
            ->sortByDesc('sortKey')
            ->values();
    }

    private function salePayments(Request $request, array $filters): Collection
    {
        $query = VentaPago::query()
            ->with(['venta.cliente', 'venta.vendedor', 'usuarioVerifica'])
            ->whereNotNull('cod_usuario_verifica_vpg')
            ->whereBetween('fec_verificacion_vpg', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_verifica_vpg', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_verificacion_vpg')
            ->orderByDesc('id_venta_pago')
            ->get()
            ->map(fn (VentaPago $payment) => [
                'id' => 'venta-' . $payment->id_venta_pago,
                'primary' => $payment->venta?->nve_ven ?? 'Venta #' . $payment->cod_ventas_vpg,
                'secondary' => $this->dateTime($payment->fec_verificacion_vpg),
                'date' => $this->dateTime($payment->fec_verificacion_vpg),
                'type' => 'Venta',
                'operation' => $payment->venta?->nve_ven ?? 'Venta #' . $payment->cod_ventas_vpg,
                'method' => $this->paymentLabel($payment->metodo_pago_vpg),
                'amount' => $this->money((float) $payment->monto_verificado_vpg),
                'amountValue' => (float) $payment->monto_verificado_vpg,
                'status' => $this->paymentStatusBadge($payment->est_vpg),
                'action' => $this->paymentActionLabel($payment->est_vpg),
                'sortKey' => $this->sortKey($payment->fec_verificacion_vpg),
            ]);
    }

    private function reservationPayments(Request $request, array $filters): Collection
    {
        $query = ReservaPago::query()
            ->with(['reserva.cliente', 'reserva.responsable', 'usuarioVerifica'])
            ->whereNotNull('cod_usuario_verifica_rpg')
            ->whereBetween('fec_verificacion_rpg', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_verifica_rpg', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_verificacion_rpg')
            ->orderByDesc('id_reserva_pago')
            ->get()
            ->map(fn (ReservaPago $payment) => [
                'id' => 'reserva-' . $payment->id_reserva_pago,
                'primary' => $payment->reserva?->nre_res ?? 'Reserva #' . $payment->cod_reservas_rpg,
                'secondary' => $this->dateTime($payment->fec_verificacion_rpg),
                'date' => $this->dateTime($payment->fec_verificacion_rpg),
                'type' => 'Reserva',
                'operation' => $payment->reserva?->nre_res ?? 'Reserva #' . $payment->cod_reservas_rpg,
                'method' => $this->paymentLabel($payment->metodo_pago_rpg),
                'amount' => $this->money((float) $payment->monto_confirmado_rpg),
                'amountValue' => (float) $payment->monto_confirmado_rpg,
                'status' => $this->paymentStatusBadge($payment->est_rpg),
                'action' => $this->paymentActionLabel($payment->est_rpg),
                'sortKey' => $this->sortKey($payment->fec_verificacion_rpg),
            ]);
    }

    private function pickups(Request $request, array $filters): Collection
    {
        return collect()
            ->merge($this->salePickups($request, $filters))
            ->merge($this->reservationPickups($request, $filters))
            ->sortByDesc('sortKey')
            ->values();
    }

    private function salePickups(Request $request, array $filters): Collection
    {
        $query = Venta::query()
            ->with(['cliente', 'usuarioEntrega'])
            ->whereNotNull('fec_entrega_ven')
            ->whereBetween('fec_entrega_ven', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_entrega_ven', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_entrega_ven')
            ->orderByDesc('id_venta')
            ->get()
            ->map(fn (Venta $sale) => [
                'id' => 'venta-' . $sale->id_venta,
                'primary' => $sale->nve_ven,
                'secondary' => $this->dateTime($sale->fec_entrega_ven),
                'date' => $this->dateTime($sale->fec_entrega_ven),
                'type' => 'Venta',
                'number' => $sale->nve_ven,
                'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente',
                'status' => $this->logisticsStatusBadge($sale->est_logistico_ven),
                'sortKey' => $this->sortKey($sale->fec_entrega_ven),
            ]);
    }

    private function reservationPickups(Request $request, array $filters): Collection
    {
        $query = Reserva::query()
            ->with(['cliente', 'usuarioEntrega'])
            ->whereNotNull('fec_recogida_res')
            ->whereBetween('fec_recogida_res', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_entrega_res', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fec_recogida_res')
            ->orderByDesc('id_reserva')
            ->get()
            ->map(fn (Reserva $reservation) => [
                'id' => 'reserva-' . $reservation->id_reserva,
                'primary' => $reservation->nre_res,
                'secondary' => $this->dateTime($reservation->fec_recogida_res),
                'date' => $this->dateTime($reservation->fec_recogida_res),
                'type' => 'Reserva',
                'number' => $reservation->nre_res,
                'customer' => $this->userName($reservation->cliente),
                'status' => $this->logisticsStatusBadge($reservation->est_logistico_res),
                'sortKey' => $this->sortKey($reservation->fec_recogida_res),
            ]);
    }

    private function damagedProducts(Request $request, array $filters): Collection
    {
        $query = PrendaDanioReporte::query()
            ->whereBetween('fecha_reporte_pdr', [$filters['from'], $filters['to']]);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_reporta_pdr', (int) $request->user()->id);
        }

        return $query
            ->orderByDesc('fecha_reporte_pdr')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PrendaDanioReporte $report) => [
                'id' => (int) $report->id,
                'primary' => $report->codigo_pdr,
                'secondary' => $this->dateTime($report->fecha_reporte_pdr),
                'date' => $this->dateTime($report->fecha_reporte_pdr),
                'code' => $report->codigo_pdr,
                'name' => $report->nombre_prenda_pdr,
                'cause' => $this->damageCauseLabel($report->causa_pdr),
                'damageType' => $this->damageTypeLabel($report->tipo_danio_pdr),
                'status' => $this->damageStatusBadge($report->estado_pdr),
                'showHref' => '/vendedor/prendas-daniadas/' . $report->id,
            ]);
    }

    private function stockAlerts(): array
    {
        $count = Inventario::query()->where('cdi_inv', '<=', self::LOW_STOCK_LIMIT)->count();

        return [
            'count' => (int) $count,
            'href' => '/vendedor/alertas-stock',
        ];
    }

    private function summaryCards(array $data): array
    {
        $payments = collect($data['payments']);
        $confirmedPayments = $payments->where('status.label', 'Confirmado');
        $rejectedPayments = $payments->where('status.label', 'Rechazado');
        $activeReservations = collect($data['reservations'])
            ->filter(fn (array $reservation) => in_array($reservation['status']['key'] ?? null, ['por_procesar', 'confirmada', 'lista_recojo'], true));

        return [
            ['label' => 'Ventas realizadas', 'value' => $this->number(count($data['sales'])), 'note' => 'Ventas asociadas al periodo.', 'tone' => 'accent'],
            ['label' => 'Total vendido', 'value' => $this->money((float) collect($data['sales'])->sum('totalAmount')), 'note' => 'Suma de ventas del alcance actual.', 'tone' => 'success'],
            ['label' => 'Reservas registradas', 'value' => $this->number(count($data['reservations'])), 'note' => 'Reservas creadas en el periodo.', 'tone' => 'info'],
            ['label' => 'Reservas activas', 'value' => $this->number($activeReservations->count()), 'note' => 'Por procesar, confirmadas o listas.', 'tone' => 'success'],
            ['label' => 'Pagos confirmados', 'value' => $this->number($confirmedPayments->count()), 'note' => 'Pagos gestionados con resultado confirmado.', 'tone' => 'success'],
            ['label' => 'Comprobantes rechazados', 'value' => $this->number($rejectedPayments->count()), 'note' => 'Pagos gestionados con rechazo.', 'tone' => $rejectedPayments->count() > 0 ? 'danger' : 'neutral'],
            ['label' => 'Recojos realizados', 'value' => $this->number(count($data['pickups'])), 'note' => 'Entregas o recojos cerrados en el periodo.', 'tone' => 'warning'],
            ['label' => 'Reportes de daño', 'value' => $this->number(count($data['damagedProducts'])), 'note' => 'Reportes creados en el período.', 'tone' => 'accent'],
            ['label' => 'Alertas de stock', 'value' => $this->number($data['stockAlerts']['count']), 'note' => 'Alertas consultables por stock disponible bajo.', 'tone' => $data['stockAlerts']['count'] > 0 ? 'warning' : 'success', 'href' => $data['stockAlerts']['href']],
        ];
    }

    private function chartData(array $data): array
    {
        return [
            'salesByDay' => collect($data['sales'])
                ->groupBy(fn (array $sale) => mb_substr($sale['date'], 0, 10))
                ->map(fn (Collection $rows, string $label) => ['label' => $label, 'value' => $rows->count(), 'amount' => (float) $rows->sum('totalAmount')])
                ->values()
                ->all(),
            'reservationsByState' => collect($data['reservations'])
                ->groupBy(fn (array $reservation) => $reservation['status']['label'])
                ->map(fn (Collection $rows, string $label) => ['label' => $label, 'value' => $rows->count()])
                ->values()
                ->all(),
            'paymentsByMethod' => collect($data['payments'])
                ->groupBy('method')
                ->map(fn (Collection $rows, string $label) => ['label' => $label, 'value' => $rows->count(), 'amount' => (float) $rows->sum('amountValue')])
                ->values()
                ->all(),
            'pickupsByState' => collect($data['pickups'])
                ->groupBy(fn (array $pickup) => $pickup['status']['label'])
                ->map(fn (Collection $rows, string $label) => ['label' => $label, 'value' => $rows->count()])
                ->values()
                ->all(),
        ];
    }

    private function limitedTables(array $data): array
    {
        return [
            'sales' => array_slice($data['sales'], 0, self::TABLE_LIMIT),
            'reservations' => array_slice($data['reservations'], 0, self::TABLE_LIMIT),
            'payments' => array_slice($data['payments'], 0, self::TABLE_LIMIT),
            'pickups' => array_slice($data['pickups'], 0, self::TABLE_LIMIT),
            'damagedProducts' => array_slice($data['damagedProducts'], 0, self::TABLE_LIMIT),
        ];
    }

    private function notifications(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $notifications = $user
            ->notifications()
            ->latest()
            ->limit(8)
            ->get();

        return [
            'unreadCount' => $user->unreadNotifications()->count(),
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification) => $this->notificationRow($notification))
                ->values()
                ->all(),
            'endpoints' => [
                'index' => '/notificaciones',
                'markAll' => '/notificaciones/leer-todas',
                'markRead' => '/notificaciones/__ID__/leer',
            ],
        ];
    }

    private function notificationRow(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? 'Notificacion',
            'message' => $data['message'] ?? '',
            'type' => $data['type'] ?? null,
            'url' => $data['url'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }

    private function filters(Request $request): array
    {
        $today = Carbon::today(config('app.timezone'))->endOfDay();
        $defaultFrom = Carbon::today(config('app.timezone'))->subDays(29)->startOfDay();
        $from = $this->dateBoundary($request->string('date_from')->toString(), 'start') ?? $defaultFrom;
        $to = $this->dateBoundary($request->string('date_to')->toString(), 'end') ?? $today;

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'from' => $from,
            'to' => $to,
        ];
    }

    private function filterQuery(array $filters): array
    {
        return Arr::only($filters, ['date_from', 'date_to']);
    }

    private function writeCsvSection($handle, string $title, array $headers, array $rows): void
    {
        $this->csvRow($handle, [$title]);
        $this->csvRow($handle, $headers);

        foreach ($rows as $row) {
            $this->csvRow($handle, match ($title) {
                'Ventas' => [$row['date'], $row['number'], $row['customer'], $row['total'], $row['paymentMethod'], $row['status']['label'], $row['delivery']],
                'Reservas' => [$row['date'], $row['number'], $row['customer'], $row['total'], $row['paid'], $row['balance'], $row['status']['label']],
                'Pagos gestionados' => [$row['date'], $row['type'], $row['operation'], $row['method'], $row['amount'], $row['status']['label'], $row['action']],
                'Recojos y entregas' => [$row['date'], $row['type'], $row['number'], $row['customer'], $row['status']['label']],
                'Prendas danadas' => [$row['date'], $row['code'], $row['name'], $row['cause'], $row['damageType'], $row['status']['label']],
                default => [],
            });
        }

        $this->csvRow($handle, []);
    }

    private function csvRow($handle, array $values): void
    {
        fputcsv($handle, $values);
    }

    private function isSupervising(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isSuperadmin') && $user->isSuperadmin();
    }

    private function dateBoundary(string $value, string $boundary): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $boundary === 'end' ? $date->endOfDay() : $date->startOfDay();
    }

    private function sortKey(mixed $date): int
    {
        if (! $date) {
            return 0;
        }

        return ($date instanceof Carbon ? $date : Carbon::parse($date))->timestamp;
    }

    private function dateTime(mixed $value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
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

    private function money(float $amount): string
    {
        return 'Bs ' . number_format($amount, 2, '.', ',');
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    private function paymentLabel(?string $method): string
    {
        return match ($method) {
            'efectivo' => 'Efectivo',
            'qr' => 'QR',
            'transferencia', 'transferencia_bancaria' => 'Transferencia bancaria',
            'reserva' => 'Reserva',
            default => $this->display($method, 'Sin método'),
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
        return [
            'key' => $status,
            'label' => match ($status) {
                'por_procesar' => 'En revisión',
                'confirmada' => 'Confirmada',
                'lista_recojo' => 'Lista para recojo',
                'completada', 'recogida' => 'Completada',
                'cancelada', 'rechazada', 'vencida' => ucfirst((string) $status),
                default => $this->display($status, 'Sin estado'),
            },
            'tone' => match ($status) {
                'confirmada', 'lista_recojo', 'completada', 'recogida' => 'success',
                'por_procesar' => 'warning',
                'cancelada', 'rechazada', 'vencida' => 'danger',
                default => 'neutral',
            },
        ];
    }

    private function paymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Confirmado', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revisión', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function logisticsStatusBadge(?string $status): array
    {
        return [
            'label' => $this->logisticsLabel($status),
            'tone' => match ($status) {
                'entregada', 'recogida' => 'success',
                'lista_recojo' => 'info',
                'cancelada', 'vencida' => 'danger',
                default => 'neutral',
            },
        ];
    }

    private function damageStatusBadge(?string $status): array
    {
        return [
            'label' => match ($status) {
                'reportado' => 'Reportado',
                'en_revision' => 'En revisión',
                'confirmado' => 'Confirmado',
                'descartado' => 'Descartado',
                'reparado' => 'Reparado',
                'baja_definitiva' => 'Baja definitiva',
                'resuelto' => 'Resuelto',
                default => $this->display($status, 'Sin estado'),
            },
            'tone' => match ($status) {
                'reportado' => 'accent',
                'en_revision' => 'info',
                'confirmado' => 'warning',
                'reparado', 'resuelto' => 'success',
                'descartado', 'baja_definitiva' => 'danger',
                default => 'neutral',
            },
        ];
    }

    private function logisticsLabel(?string $status): string
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
            default => $this->display($status, 'Sin estado'),
        };
    }

    private function paymentActionLabel(?string $status): string
    {
        return match ($status) {
            'confirmado' => 'Aprobado o confirmado',
            'rechazado' => 'Rechazado',
            'pendiente_revision' => 'Pendiente',
            default => $this->display($status, 'Registrado'),
        };
    }

    private function damageCauseLabel(?string $cause): string
    {
        return match ($cause) {
            'dano_en_tienda' => 'Daño en tienda',
            'dano_en_transporte' => 'Daño en transporte',
            'dano_por_manipulacion' => 'Daño por manipulación',
            'defecto_de_fabrica' => 'Defecto de fábrica',
            'devolucion_danada' => 'Devolución dañada',
            'desgaste' => 'Desgaste',
            'mala_conservacion' => 'Mala conservación',
            'otro' => 'Otro',
            default => $this->display($cause, 'Sin causa'),
        };
    }

    private function damageTypeLabel(?string $type): string
    {
        return match ($type) {
            'mancha' => 'Mancha',
            'roto' => 'Roto',
            'cierre_danado' => 'Cierre dañado',
            'boton_faltante' => 'Botón faltante',
            'costura_abierta' => 'Costura abierta',
            'decoloracion' => 'Decoloración',
            'talla_mal_etiquetada' => 'Talla mal etiquetada',
            'defecto_fabrica' => 'Defecto de fábrica',
            'otro' => 'Otro',
            default => $this->display($type, 'Sin tipo'),
        };
    }

    private function display(mixed $value, string $fallback): string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? $fallback : $text;
    }
}
