<?php

namespace App\Services\Superadmin;

use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Favorito;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportQueryService
{
    private const LOW_STOCK_LIMIT = 3;

    private const RESERVATION_STATES = [
        'por_procesar' => ['label' => 'Por procesar', 'tone' => 'warning'],
        'confirmada' => ['label' => 'Confirmada', 'tone' => 'info'],
        'lista_recojo' => ['label' => 'Lista para recojo', 'tone' => 'accent'],
        'completada' => ['label' => 'Completada', 'tone' => 'success'],
        'cancelada' => ['label' => 'Cancelada', 'tone' => 'danger'],
        'vencida' => ['label' => 'Vencida', 'tone' => 'danger'],
        'rechazada' => ['label' => 'Rechazada', 'tone' => 'danger'],
    ];

    private const ACTIVE_RESERVATION_STATES = ['por_procesar', 'confirmada', 'lista_recojo'];

    public function payload(Request $request): array
    {
        return [
            'metrics' => $this->summaryMetrics($request),
            'reports' => [
                $this->salesReport($request),
                $this->reservationsReport($request),
                $this->paymentsReport($request),
                $this->inventoryReport(),
                $this->customersReport($request),
                $this->catalogReport(),
            ],
            'filterControls' => $this->filterControls($request),
            'sortOptions' => [],
            'sortBy' => '',
            'sortDirection' => 'desc',
            'appliedFilters' => $this->appliedFilters($request),
            'emptyState' => [
                'title' => 'Sin datos operativos',
                'description' => 'No hay informacion suficiente para construir reportes con los criterios actuales.',
                'actionLabel' => 'Limpiar filtros',
                'actionHref' => '/superadmin/reportes',
            ],
        ];
    }

    public function exportDataset(string $type, Request $request): array
    {
        return match ($this->normalizeExportType($type)) {
            'ventas' => $this->salesExportDataset($request),
            'reservas' => $this->reservationsExportDataset($request),
            'pagos' => $this->paymentsExportDataset($request),
            'inventario' => $this->inventoryExportDataset($request),
            'clientes' => $this->customersExportDataset($request),
            default => throw new \InvalidArgumentException('Tipo de reporte no soportado.'),
        };
    }

    public function supportedExportTypes(): array
    {
        return ['ventas', 'reservas', 'pagos', 'inventario', 'clientes'];
    }

    public function filtersMetadata(Request $request): array
    {
        $keys = [
            'period',
            'date_from',
            'date_to',
            'seller',
            'payment_method',
            'sale_status',
            'reservation_status',
            'customer',
            'responsible',
            'section',
        ];

        return collect($keys)
            ->mapWithKeys(fn (string $key) => [$key => $this->filterMetadataValue($request->input($key))])
            ->filter(fn ($value) => $value !== null)
            ->all();
    }

    private function normalizeExportType(string $type): string
    {
        $type = trim(mb_strtolower(str_replace('.csv', '', $type), 'UTF-8'));

        return match ($type) {
            'ventas', 'sales' => 'ventas',
            'reservas', 'reservations' => 'reservas',
            'pagos', 'payments' => 'pagos',
            'inventario', 'inventory' => 'inventario',
            'clientes', 'customers' => 'clientes',
            default => $type,
        };
    }

    private function filterMetadataValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $values = collect($value)
                ->map(fn ($item) => $this->filterMetadataValue($item))
                ->filter(fn ($item) => $item !== null)
                ->values()
                ->all();

            return $values === [] ? null : $values;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function salesExportDataset(Request $request): array
    {
        $sales = $this->salesQuery($request)
            ->with(['cliente', 'vendedor', 'detalles.variante.prenda'])
            ->orderByDesc('fec_ven')
            ->orderByDesc('id_venta')
            ->get();
        $confirmedTotal = (float) $sales->where('est_ven', 'registrada')->sum('tot_ven');

        return $this->exportDatasetPayload($request, 'ventas', 'Reporte de ventas', [
            ['key' => 'number', 'label' => 'Nro. venta'],
            ['key' => 'customer', 'label' => 'Cliente'],
            ['key' => 'seller', 'label' => 'Vendedor'],
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'status', 'label' => 'Estado'],
            ['key' => 'logistics_status', 'label' => 'Estado logistico'],
            ['key' => 'payment_method', 'label' => 'Método de pago'],
            ['key' => 'sold_products', 'label' => 'Productos vendidos'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'date', 'label' => 'Fecha'],
        ], $sales->map(fn (Venta $sale) => [
            'number' => $sale->nve_ven ?: 'Venta #' . $sale->id_venta,
            'customer' => $this->userName($sale->cliente),
            'seller' => $this->userName($sale->vendedor),
            'type' => $sale->tiv_ven === 'reserva' ? 'Desde reserva' : $this->humanize((string) $sale->tiv_ven),
            'status' => $this->humanize((string) $sale->est_ven),
            'logistics_status' => $this->humanize((string) $sale->est_logistico_ven),
            'payment_method' => $this->humanize((string) $sale->mpa_ven),
            'sold_products' => $this->saleProductsLabel($sale),
            'total' => $this->money((float) $sale->tot_ven),
            'date' => $this->date($sale->fec_ven),
        ])->values()->all(), [
            ['label' => 'Ventas confirmadas', 'value' => (string) $sales->where('est_ven', 'registrada')->count()],
            ['label' => 'Ventas en revision', 'value' => (string) $sales->where('est_ven', 'en_revision')->count()],
            ['label' => 'Canceladas o incompletas', 'value' => (string) $sales->whereIn('est_ven', ['cancelada', 'incompleta'])->count()],
            ['label' => 'Total confirmado', 'value' => $this->money($confirmedTotal)],
        ]);
    }

    private function reservationsExportDataset(Request $request): array
    {
        $reservations = $this->reservationsQuery($request)
            ->with(['cliente', 'responsable'])
            ->orderByDesc('fec_res')
            ->orderByDesc('id_reserva')
            ->get();
        $stateCounts = $this->reservationStateCounts($reservations);

        return $this->exportDatasetPayload($request, 'reservas', 'Reporte de reservas', [
            ['key' => 'number', 'label' => 'Nro. reserva'],
            ['key' => 'customer', 'label' => 'Cliente'],
            ['key' => 'responsible', 'label' => 'Responsable'],
            ['key' => 'reservation_status', 'label' => 'Estado reserva'],
            ['key' => 'payment_status', 'label' => 'Estado pago'],
            ['key' => 'logistics_status', 'label' => 'Estado logistico'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'paid_amount', 'label' => 'Monto pagado'],
            ['key' => 'balance', 'label' => 'Saldo'],
            ['key' => 'date', 'label' => 'Fecha'],
        ], $reservations->map(fn (Reserva $reservation) => [
            'number' => $reservation->nre_res ?: 'Reserva #' . $reservation->id_reserva,
            'customer' => $this->userName($reservation->cliente),
            'responsible' => $this->userName($reservation->responsable),
            'reservation_status' => $this->reservationStatusLabel($this->reservationDisplayStatus($reservation)),
            'payment_status' => $this->humanize((string) $reservation->est_pago_res),
            'logistics_status' => $this->humanize((string) $reservation->est_logistico_res),
            'total' => $this->money((float) $reservation->tot_res),
            'paid_amount' => $this->money((float) $reservation->monto_pagado_res),
            'balance' => $this->money((float) $reservation->saldo_res),
            'date' => $this->date($reservation->fec_res),
        ])->values()->all(), collect(self::RESERVATION_STATES)
            ->map(fn (array $meta, string $state) => ['label' => $meta['label'], 'value' => (string) ($stateCounts[$state] ?? 0)])
            ->values()
            ->all());
    }

    private function paymentsExportDataset(Request $request): array
    {
        $payments = $this->paymentCollections($request);
        $allPayments = $payments['sales']->concat($payments['reservations'])->sortByDesc('sort_date')->values();

        return $this->exportDatasetPayload($request, 'pagos', 'Reporte de pagos', [
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'operation_code', 'label' => 'Codigo operacion'],
            ['key' => 'customer', 'label' => 'Cliente'],
            ['key' => 'method', 'label' => 'Metodo'],
            ['key' => 'status', 'label' => 'Estado'],
            ['key' => 'declared_amount', 'label' => 'Monto declarado'],
            ['key' => 'verified_amount', 'label' => 'Monto verificado/confirmado'],
            ['key' => 'date', 'label' => 'Fecha'],
        ], $allPayments->map(fn (array $payment) => [
            'type' => $payment['type'],
            'operation_code' => $payment['reference'],
            'customer' => $payment['customer'],
            'method' => $this->humanize((string) $payment['method']),
            'status' => $this->humanize((string) $payment['status']),
            'declared_amount' => $this->money((float) $payment['declared_amount']),
            'verified_amount' => $this->money((float) $payment['verified_amount']),
            'date' => $this->date($payment['date']),
        ])->values()->all(), [
            ['label' => 'Pendientes', 'value' => (string) $this->paymentStatusCount($payments, 'pendiente_revision')],
            ['label' => 'Confirmados', 'value' => (string) $this->paymentStatusCount($payments, 'confirmado')],
            ['label' => 'Rechazados', 'value' => (string) $this->paymentStatusCount($payments, 'rechazado')],
            ['label' => 'Monto declarado', 'value' => $this->money((float) $allPayments->sum('declared_amount'))],
            ['label' => 'Monto verificado', 'value' => $this->money((float) $allPayments->sum('verified_amount'))],
        ]);
    }

    private function inventoryExportDataset(Request $request): array
    {
        $variants = PrendaVariante::query()
            ->with(['prenda', 'color', 'talla', 'inventario'])
            ->orderBy('sku_prv')
            ->get();
        $available = (int) $variants->sum(fn (PrendaVariante $variant) => (int) ($variant->inventario?->cdi_inv ?? 0));
        $reserved = (int) $variants->sum(fn (PrendaVariante $variant) => (int) ($variant->inventario?->cre_inv ?? 0));
        $lowStock = $variants->filter(fn (PrendaVariante $variant) => $variant->inventario && $variant->inventario->cdi_inv > 0 && $variant->inventario->cdi_inv <= self::LOW_STOCK_LIMIT)->count();
        $withoutStock = $variants->filter(fn (PrendaVariante $variant) => $variant->inventario && $variant->inventario->cdi_inv <= 0)->count();
        $withoutInventory = $variants->filter(fn (PrendaVariante $variant) => $variant->inventario === null)->count();

        return $this->exportDatasetPayload($request, 'inventario', 'Reporte de inventario', [
            ['key' => 'product', 'label' => 'Prenda'],
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'color', 'label' => 'Color'],
            ['key' => 'size', 'label' => 'Talla'],
            ['key' => 'available_stock', 'label' => 'Stock disponible'],
            ['key' => 'reserved_stock', 'label' => 'Stock comprometido'],
            ['key' => 'stock_status', 'label' => 'Estado stock'],
        ], $variants->map(fn (PrendaVariante $variant) => [
            'product' => $variant->prenda?->nom_pre ?? 'Sin prenda',
            'sku' => $variant->sku_prv ?: 'Sin SKU',
            'color' => $variant->color?->nom_col ?? 'Sin color',
            'size' => $variant->talla?->nom_tal ?? 'Sin talla',
            'available_stock' => (string) ($variant->inventario?->cdi_inv ?? 0),
            'reserved_stock' => (string) ($variant->inventario?->cre_inv ?? 0),
            'stock_status' => $variant->inventario ? $this->stockStatus((int) $variant->inventario->cdi_inv)['label'] : 'Sin inventario',
        ])->values()->all(), [
            ['label' => 'Stock disponible', 'value' => (string) $available],
            ['label' => 'Stock comprometido', 'value' => (string) $reserved],
            ['label' => 'Bajo stock', 'value' => (string) $lowStock],
            ['label' => 'Sin stock', 'value' => (string) $withoutStock],
            ['label' => 'Variantes sin inventario', 'value' => (string) $withoutInventory],
        ]);
    }

    private function customersExportDataset(Request $request): array
    {
        $customers = $this->frequentCustomersForExport($request);

        return $this->exportDatasetPayload($request, 'clientes', 'Reporte de clientes frecuentes', [
            ['key' => 'customer', 'label' => 'Cliente'],
            ['key' => 'purchases', 'label' => 'Compras'],
            ['key' => 'reservations', 'label' => 'Reservas'],
            ['key' => 'purchased_total', 'label' => 'Total comprado'],
            ['key' => 'reserved_total', 'label' => 'Total reservado'],
            ['key' => 'last_activity', 'label' => 'Ultima actividad'],
        ], $customers->map(fn (array $customer) => [
            'customer' => $customer['label'],
            'purchases' => (string) $customer['sales_count'],
            'reservations' => (string) $customer['reservations_count'],
            'purchased_total' => $this->money((float) $customer['sales_total']),
            'reserved_total' => $this->money((float) $customer['reservations_total']),
            'last_activity' => $this->date($customer['last_activity']),
        ])->values()->all(), [
            ['label' => 'Clientes frecuentes', 'value' => (string) $customers->count()],
            ['label' => 'Compras asociadas', 'value' => (string) $customers->sum('sales_count')],
            ['label' => 'Reservas asociadas', 'value' => (string) $customers->sum('reservations_count')],
            ['label' => 'Total comprado confirmado', 'value' => $this->money((float) $customers->sum('sales_total'))],
            ['label' => 'Total reservado', 'value' => $this->money((float) $customers->sum('reservations_total'))],
        ]);
    }

    private function exportDatasetPayload(Request $request, string $type, string $title, array $columns, array $rows, array $summary): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'filename' => 'mati-boutique-' . $type . '-' . Carbon::now()->format('Ymd-His') . '.csv',
            'dateRangeLabel' => $this->dateRangeLabel($request),
            'appliedFilters' => $this->appliedFilters($request),
            'summary' => $summary,
            'columns' => $columns,
            'rows' => $rows,
            'footer' => 'Reporte generado por el sistema de gestion comercial de Mati Boutique.',
        ];
    }

    private function summaryMetrics(Request $request): array
    {
        $sales = $this->salesQuery($request)->get(['id_venta', 'tot_ven', 'est_ven', 'tiv_ven']);
        $reservations = $this->reservationsQuery($request)->get(['id_reserva', 'est_res', 'ven_res', 'fec_limite_recojo_res']);
        $payments = $this->paymentCollections($request);
        $available = (int) Inventario::query()->sum('cdi_inv');
        $reserved = (int) Inventario::query()->sum('cre_inv');
        $pendingPayments = $this->paymentStatusCount($payments, 'pendiente_revision');
        $pendingReviews = $sales->where('est_ven', 'en_revision')->count()
            + $reservations->where('est_res', 'por_procesar')->count()
            + $pendingPayments;

        $confirmedSalesTotal = (float) $sales->where('est_ven', 'registrada')->sum('tot_ven');

        return [
            ['label' => 'Ventas confirmadas', 'value' => (string) $sales->where('est_ven', 'registrada')->count(), 'note' => 'Ventas registradas dentro del filtro actual.', 'tone' => 'success'],
            ['label' => 'Total vendido', 'value' => $this->money($confirmedSalesTotal), 'note' => 'Suma confirmada de ventas registradas en el periodo.', 'tone' => $confirmedSalesTotal > 0 ? 'accent' : 'neutral'],
            ['label' => 'Pendientes de revision', 'value' => (string) $pendingReviews, 'note' => 'Ventas, reservas y pagos que requieren revision.', 'tone' => $pendingReviews > 0 ? 'warning' : 'success'],
            ['label' => 'Stock disponible', 'value' => (string) $available, 'note' => $reserved . ' unidades comprometidas en inventario.', 'tone' => $available > 0 ? 'success' : 'danger'],
        ];
    }

    private function salesReport(Request $request): array
    {
        $sales = $this->salesQuery($request)->with(['vendedor', 'cliente'])->get();
        $details = $this->saleDetails($sales->pluck('id_venta'));
        $cancelledOrIncomplete = $sales->whereIn('est_ven', ['cancelada', 'incompleta'])->count();
        $confirmedSalesTotal = (float) $sales->where('est_ven', 'registrada')->sum('tot_ven');

        return [
            'key' => 'sales',
            'title' => 'Ventas',
            'description' => 'Resumen operativo de ventas confirmadas, en revision, online, canceladas, incompletas y generadas desde reservas.',
            'metrics' => [
                ['label' => 'Confirmadas', 'value' => (string) $sales->where('est_ven', 'registrada')->count(), 'note' => 'Ventas con estado registrada.', 'tone' => 'success'],
                ['label' => 'En revision', 'value' => (string) $sales->where('est_ven', 'en_revision')->count(), 'note' => 'Compras/comprobantes pendientes.', 'tone' => 'warning'],
                ['label' => 'Canceladas o incompletas', 'value' => (string) $cancelledOrIncomplete, 'note' => 'Ventas canceladas o con monto incompleto.', 'tone' => $cancelledOrIncomplete > 0 ? 'danger' : 'success'],
                ['label' => 'Ventas online', 'value' => (string) $sales->where('tiv_ven', 'online')->count(), 'note' => 'Ventas originadas desde compra online.', 'tone' => 'info'],
                ['label' => 'Desde reservas', 'value' => (string) $sales->where('tiv_ven', 'reserva')->count(), 'note' => 'Ventas creadas al cerrar reservas.', 'tone' => 'accent'],
                ['label' => 'Total vendido', 'value' => $this->money($confirmedSalesTotal), 'note' => 'Suma confirmada de ventas registradas.', 'tone' => $confirmedSalesTotal > 0 ? 'accent' : 'neutral'],
            ],
            'charts' => [
                [
                    'title' => 'Estados de ventas',
                    'description' => 'Confirmadas, en revision, canceladas e incompletas.',
                    'type' => 'donut',
                    'data' => $this->salesStatusChart($sales),
                    'emptyMessage' => 'No hay ventas para graficar con estos filtros.',
                ],
                [
                    'title' => 'Origen de ventas',
                    'description' => 'Online, fisicas, desde reservas y otros origenes.',
                    'type' => 'donut',
                    'data' => $this->salesOriginChart($sales),
                    'emptyMessage' => 'No hay origenes de venta para mostrar.',
                ],
                [
                    'title' => 'Tendencia de ventas',
                    'description' => 'Cantidad de ventas registradas por fecha.',
                    'type' => 'area',
                    'data' => $this->salesTrend($sales),
                    'emptyMessage' => 'No hay ventas suficientes para la tendencia del periodo.',
                ],
                [
                    'title' => 'Productos mas vendidos',
                    'description' => 'Unidades vendidas por prenda.',
                    'type' => 'bar',
                    'data' => $this->rankingToChart($this->topSaleProducts($details)),
                    'emptyMessage' => 'No hay productos vendidos en este filtro.',
                ],
            ],
            'rankings' => [
                ['title' => 'Productos mas vendidos', 'items' => $this->topSaleProducts($details)],
                ['title' => 'Ventas por vendedor', 'items' => $this->salesBySeller($sales)],
                ['title' => 'Ventas por método de pago', 'items' => $this->salesByPayment($sales)],
            ],
            'tables' => [[
                'title' => 'Ultimas ventas del filtro',
                'description' => 'Lectura rapida para confirmar fecha, vendedor, origen, estado y total.',
                'columns' => [
                    ['key' => 'date', 'label' => 'Fecha'],
                    ['key' => 'seller', 'label' => 'Vendedor'],
                    ['key' => 'type', 'label' => 'Origen'],
                    ['key' => 'items', 'label' => 'Prendas', 'align' => 'center'],
                    ['key' => 'payment', 'label' => 'Metodo'],
                    ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
                ],
                'rows' => $sales
                    ->sortByDesc(fn (Venta $sale) => $sale->fec_ven?->timestamp ?? 0)
                    ->take(10)
                    ->values()
                    ->map(fn (Venta $sale) => [
                        'id' => 'sale-' . $sale->id_venta,
                        'primary' => $sale->nve_ven ?: 'Venta #' . $sale->id_venta,
                        'secondary' => $this->userName($sale->cliente) ?: 'Cliente no registrado',
                        'status' => $this->statusBadge((string) $sale->est_ven),
                        'cells' => [
                            'date' => $this->date($sale->fec_ven),
                            'seller' => $this->userName($sale->vendedor),
                            'type' => $sale->tiv_ven === 'reserva' ? 'Desde reserva' : $this->humanize((string) $sale->tiv_ven),
                            'items' => (string) $details->where('cod_ventas_dve', $sale->id_venta)->sum('can_dve'),
                            'payment' => $this->humanize((string) $sale->mpa_ven),
                            'total' => $this->money((float) $sale->tot_ven),
                        ],
                        'showHref' => '/superadmin/comercial/ventas/' . $sale->id_venta,
                    ])
                    ->all(),
            ]],
        ];
    }

    private function reservationsReport(Request $request): array
    {
        $reservations = $this->reservationsQuery($request)->with(['cliente', 'responsable'])->get();
        $details = $this->reservationDetails($reservations->pluck('id_reserva'));
        $stateCounts = $this->reservationStateCounts($reservations);

        return [
            'key' => 'reservations',
            'title' => 'Reservas',
            'description' => 'Seguimiento de reservas por estado actual: por procesar, confirmadas, listas para recojo, completadas, canceladas, vencidas y rechazadas.',
            'metrics' => [
                ['label' => 'Por procesar', 'value' => (string) ($stateCounts['por_procesar'] ?? 0), 'note' => 'Reservas pendientes de revision.', 'tone' => 'warning'],
                ['label' => 'Confirmadas', 'value' => (string) ($stateCounts['confirmada'] ?? 0), 'note' => 'Reservas aprobadas y vigentes.', 'tone' => 'info'],
                ['label' => 'Listas para recojo', 'value' => (string) ($stateCounts['lista_recojo'] ?? 0), 'note' => 'Reservas listas para entregar.', 'tone' => 'accent'],
                ['label' => 'Completadas', 'value' => (string) ($stateCounts['completada'] ?? 0), 'note' => 'Reservas cerradas como recogidas.', 'tone' => 'success'],
                ['label' => 'Canceladas', 'value' => (string) ($stateCounts['cancelada'] ?? 0), 'note' => 'Reservas anuladas administrativamente.', 'tone' => 'danger'],
                ['label' => 'Vencidas/Rechazadas', 'value' => (string) (($stateCounts['vencida'] ?? 0) + ($stateCounts['rechazada'] ?? 0)), 'note' => 'Reservas fuera de flujo o rechazadas.', 'tone' => (($stateCounts['vencida'] ?? 0) + ($stateCounts['rechazada'] ?? 0)) > 0 ? 'danger' : 'success'],
            ],
            'charts' => [
                [
                    'title' => 'Reservas por estado',
                    'description' => 'Distribucion de estados actuales del flujo de reservas.',
                    'type' => 'donut',
                    'data' => $this->reservationStateChart($stateCounts),
                    'emptyMessage' => 'No hay reservas para graficar con estos filtros.',
                ],
                [
                    'title' => 'Productos mas reservados',
                    'description' => 'Unidades reservadas por prenda.',
                    'type' => 'bar',
                    'data' => $this->rankingToChart($this->topReservedProducts($details)),
                    'emptyMessage' => 'No hay productos reservados en este filtro.',
                ],
            ],
            'rankings' => [
                ['title' => 'Prendas mas reservadas', 'items' => $this->topReservedProducts($details)],
            ],
            'tables' => [[
                'title' => 'Reservas recientes',
                'description' => 'Incluye estado vencido visual cuando una reserva vigente supera su fecha límite.',
                'columns' => [
                    ['key' => 'customer', 'label' => 'Cliente'],
                    ['key' => 'responsible', 'label' => 'Responsable'],
                    ['key' => 'expires', 'label' => 'Vence'],
                    ['key' => 'state', 'label' => 'Estado'],
                ],
                'rows' => $reservations
                    ->sortByDesc(fn (Reserva $reservation) => $reservation->fec_res?->timestamp ?? 0)
                    ->take(10)
                    ->values()
                    ->map(fn (Reserva $reservation) => [
                        'id' => 'reservation-' . $reservation->id_reserva,
                        'primary' => $reservation->nre_res ?: 'Reserva #' . $reservation->id_reserva,
                        'secondary' => $this->date($reservation->fec_res),
                        'status' => $this->reservationBadge($reservation),
                        'cells' => [
                            'customer' => $this->userName($reservation->cliente),
                            'responsible' => $this->userName($reservation->responsable),
                            'expires' => $this->date($reservation->fec_limite_recojo_res ?: $reservation->ven_res),
                            'state' => $this->reservationStatusLabel($this->reservationDisplayStatus($reservation)),
                        ],
                        'showHref' => '/superadmin/comercial/reservas/' . $reservation->id_reserva,
                    ])
                    ->all(),
            ]],
        ];
    }

    private function paymentsReport(Request $request): array
    {
        $payments = $this->paymentCollections($request);
        $salePayments = $payments['sales'];
        $reservationPayments = $payments['reservations'];
        $allPayments = $salePayments->concat($reservationPayments);

        return [
            'key' => 'payments',
            'title' => 'Pagos',
            'description' => 'Revision operativa de pagos de ventas y reservas por estado, metodo y montos declarados o verificados.',
            'metrics' => [
                ['label' => 'Pendientes', 'value' => (string) $this->paymentStatusCount($payments, 'pendiente_revision'), 'note' => 'Comprobantes pendientes de revision.', 'tone' => 'warning'],
                ['label' => 'Confirmados', 'value' => (string) $this->paymentStatusCount($payments, 'confirmado'), 'note' => 'Pagos aprobados.', 'tone' => 'success'],
                ['label' => 'Rechazados', 'value' => (string) $this->paymentStatusCount($payments, 'rechazado'), 'note' => 'Pagos observados o rechazados.', 'tone' => $this->paymentStatusCount($payments, 'rechazado') > 0 ? 'danger' : 'success'],
                ['label' => 'Monto declarado', 'value' => $this->money((float) $allPayments->sum('declared_amount')), 'note' => 'Suma declarada por clientes o vendedores.', 'tone' => 'accent'],
                ['label' => 'Monto verificado', 'value' => $this->money((float) $allPayments->sum('verified_amount')), 'note' => 'Suma confirmada por revision administrativa.', 'tone' => 'success'],
            ],
            'charts' => [
                [
                    'title' => 'Pagos por estado',
                    'description' => 'Pagos pendientes, confirmados y rechazados.',
                    'type' => 'donut',
                    'data' => $this->paymentStatusChart($payments),
                    'emptyMessage' => 'No hay pagos para graficar con estos filtros.',
                ],
                [
                    'title' => 'Pagos por metodo',
                    'description' => 'QR, transferencia, efectivo u otros metodos registrados.',
                    'type' => 'bar',
                    'data' => $this->paymentMethodChart($allPayments),
                    'emptyMessage' => 'No hay metodos de pago para mostrar.',
                ],
            ],
            'rankings' => [
                ['title' => 'Pagos por metodo', 'items' => $this->paymentMethodsRanking($allPayments)],
            ],
            'tables' => [[
                'title' => 'Pagos recientes',
                'description' => 'Pagos de ventas y reservas, con montos declarados y verificados.',
                'columns' => [
                    ['key' => 'type', 'label' => 'Tipo'],
                    ['key' => 'method', 'label' => 'Metodo'],
                    ['key' => 'declared', 'label' => 'Declarado', 'align' => 'right'],
                    ['key' => 'verified', 'label' => 'Verificado', 'align' => 'right'],
                    ['key' => 'date', 'label' => 'Fecha'],
                ],
                'rows' => $allPayments
                    ->sortByDesc('sort_date')
                    ->take(10)
                    ->values()
                    ->map(fn (array $payment) => [
                        'id' => $payment['id'],
                        'primary' => $payment['reference'],
                        'secondary' => $payment['customer'],
                        'status' => $this->paymentBadge($payment['status']),
                        'cells' => [
                            'type' => $payment['type'],
                            'method' => $this->humanize($payment['method']),
                            'declared' => $this->money((float) $payment['declared_amount']),
                            'verified' => $this->money((float) $payment['verified_amount']),
                            'date' => $this->date($payment['date']),
                        ],
                        'showHref' => $payment['href'],
                    ])
                    ->all(),
            ]],
        ];
    }

    private function inventoryReport(): array
    {
        $inventories = Inventario::query()
            ->with(['variante.prenda.categoria', 'variante.prenda.marca', 'variante.color', 'variante.talla'])
            ->get();
        $withoutStock = $inventories->where('cdi_inv', 0)->count();
        $lowStock = $inventories->filter(fn (Inventario $inventory) => $inventory->cdi_inv > 0 && $inventory->cdi_inv <= self::LOW_STOCK_LIMIT)->count();
        $variantsWithoutInventory = PrendaVariante::query()->doesntHave('inventario')->count();
        $recentMovements = InventarioMovimiento::query()
            ->where('created_at', '>=', Carbon::now(config('app.timezone'))->subDays(7))
            ->count();

        return [
            'key' => 'inventory',
            'title' => 'Inventario',
            'description' => 'Estado operativo de stock disponible, comprometido, bajo stock y variantes pendientes de inventario.',
            'metrics' => [
                ['label' => 'Stock disponible', 'value' => (string) $inventories->sum('cdi_inv'), 'note' => 'Suma de inventarios.cdi_inv.', 'tone' => 'success'],
                ['label' => 'Stock comprometido', 'value' => (string) $inventories->sum('cre_inv'), 'note' => 'Suma de inventarios.cre_inv.', 'tone' => 'warning'],
                ['label' => 'Sin stock', 'value' => (string) $withoutStock, 'note' => 'Inventarios con disponible en cero.', 'tone' => $withoutStock > 0 ? 'danger' : 'success'],
                ['label' => 'Bajo stock', 'value' => (string) $lowStock, 'note' => 'Disponible entre 1 y ' . self::LOW_STOCK_LIMIT . ' unidades.', 'tone' => $lowStock > 0 ? 'warning' : 'success'],
                ['label' => 'Sin inventario', 'value' => (string) $variantsWithoutInventory, 'note' => 'Variantes creadas sin registro en inventarios.', 'tone' => $variantsWithoutInventory > 0 ? 'warning' : 'success'],
                ['label' => 'Movimientos recientes', 'value' => (string) $recentMovements, 'note' => 'Kardex registrado en los ultimos 7 dias.', 'tone' => 'info', 'href' => '/superadmin/inventario/movimientos'],
            ],
            'charts' => [
                [
                    'title' => 'Stock operativo',
                    'description' => 'Disponible, comprometido, bajo stock y sin stock.',
                    'type' => 'donut',
                    'data' => [
                        ['label' => 'Disponible', 'value' => (int) $inventories->sum('cdi_inv'), 'tone' => 'success'],
                        ['label' => 'Comprometido', 'value' => (int) $inventories->sum('cre_inv'), 'tone' => 'warning'],
                        ['label' => 'Bajo stock', 'value' => (int) $lowStock, 'tone' => 'danger'],
                        ['label' => 'Sin stock', 'value' => (int) $withoutStock, 'tone' => 'neutral'],
                    ],
                    'emptyMessage' => 'No hay datos de inventario para graficar.',
                ],
                [
                    'title' => 'Stock por categoria',
                    'description' => 'Unidades disponibles agrupadas por categoria.',
                    'type' => 'bar',
                    'data' => $this->rankingToChart($this->inventoryRanking('Stock por categoria', $inventories, fn (Inventario $inventory) => $inventory->variante?->prenda?->categoria?->nom_cat ?? 'Sin categoria')['items']),
                    'emptyMessage' => 'No hay stock disponible por categoria.',
                ],
            ],
            'rankings' => [
                $this->inventoryRanking('Stock por categoria', $inventories, fn (Inventario $inventory) => $inventory->variante?->prenda?->categoria?->nom_cat ?? 'Sin categoria'),
                $this->inventoryRanking('Stock por marca', $inventories, fn (Inventario $inventory) => $inventory->variante?->prenda?->marca?->nom_mar ?? 'Sin marca'),
                $this->inventoryRanking('Stock por ubicacion', $inventories, fn (Inventario $inventory) => filled($inventory->ubi_inv) ? (string) $inventory->ubi_inv : 'Sin ubicacion'),
            ],
            'tables' => [[
                'title' => 'Variantes con alerta de stock',
                'description' => 'Registros sin stock o con margen corto para reposicion administrativa.',
                'columns' => [
                    ['key' => 'variant', 'label' => 'Variante'],
                    ['key' => 'available', 'label' => 'Disponible', 'align' => 'right'],
                    ['key' => 'reserved', 'label' => 'Comprometido', 'align' => 'right'],
                    ['key' => 'location', 'label' => 'Ubicacion'],
                ],
                'rows' => $inventories
                    ->filter(fn (Inventario $inventory) => $inventory->cdi_inv <= self::LOW_STOCK_LIMIT)
                    ->sortBy('cdi_inv')
                    ->take(10)
                    ->values()
                    ->map(fn (Inventario $inventory) => [
                        'id' => 'inventory-alert-' . $inventory->id_inventario,
                        'primary' => $inventory->variante?->prenda?->nom_pre ?? 'Variante #' . $inventory->cod_prenda_variantes_inv,
                        'secondary' => $this->variantLabel($inventory->variante),
                        'status' => $this->stockStatus((int) $inventory->cdi_inv),
                        'cells' => [
                            'variant' => $inventory->variante?->sku_prv ?: 'Sin SKU',
                            'available' => (string) $inventory->cdi_inv,
                            'reserved' => (string) $inventory->cre_inv,
                            'location' => $inventory->ubi_inv ?: 'Sin ubicacion',
                        ],
                        'showHref' => '/superadmin/inventario/stock/' . $inventory->id_inventario,
                    ])
                    ->all(),
            ]],
        ];
    }

    private function customersReport(Request $request): array
    {
        $items = $this->frequentCustomers($request);

        return [
            'key' => 'customers',
            'title' => 'Clientes',
            'description' => 'Clientes frecuentes calculados por compras y reservas asociadas dentro del filtro actual.',
            'metrics' => [
                ['label' => 'Clientes frecuentes', 'value' => (string) $items->count(), 'note' => 'Clientes con compras o reservas en el filtro.', 'tone' => 'accent'],
                ['label' => 'Compras asociadas', 'value' => (string) $items->sum('sales_count'), 'note' => 'Ventas con cliente registrado.', 'tone' => 'success'],
                ['label' => 'Reservas asociadas', 'value' => (string) $items->sum('reservations_count'), 'note' => 'Reservas con cliente registrado.', 'tone' => 'info'],
                ['label' => 'Movimiento total', 'value' => (string) $items->sum('operations_count'), 'note' => 'Compras mas reservas.', 'tone' => 'warning'],
            ],
            'charts' => [
                [
                    'title' => 'Clientes frecuentes',
                    'description' => 'Compras y reservas combinadas por cliente.',
                    'type' => 'bar',
                    'data' => $items
                        ->take(8)
                        ->map(fn (array $item) => [
                            'label' => $item['label'],
                            'value' => (int) $item['operations_count'],
                            'tone' => 'accent',
                            'href' => '/superadmin/seguridad/usuarios/' . $item['id'],
                        ])
                        ->values()
                        ->all(),
                    'emptyMessage' => 'No hay clientes con compras o reservas en este filtro.',
                ],
            ],
            'rankings' => [[
                'title' => 'Clientes frecuentes',
                'items' => $items
                    ->take(8)
                    ->map(fn (array $item) => [
                        'label' => $item['label'],
                        'value' => (string) $item['operations_count'],
                        'note' => $item['sales_count'] . ' compra(s), ' . $item['reservations_count'] . ' reserva(s)',
                        'tone' => 'accent',
                    ])
                    ->values()
                    ->all(),
            ]],
            'tables' => [[
                'title' => 'Clientes con mayor movimiento',
                'description' => 'Ranking combinado de compras y reservas.',
                'columns' => [
                    ['key' => 'sales', 'label' => 'Compras', 'align' => 'right'],
                    ['key' => 'reservations', 'label' => 'Reservas', 'align' => 'right'],
                    ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
                ],
                'rows' => $items
                    ->take(10)
                    ->map(fn (array $item) => [
                        'id' => 'customer-' . $item['id'],
                        'primary' => $item['label'],
                        'secondary' => $item['email'],
                        'status' => ['label' => $item['operations_count'] . ' movimiento(s)', 'tone' => 'accent'],
                        'cells' => [
                            'sales' => (string) $item['sales_count'],
                            'reservations' => (string) $item['reservations_count'],
                            'total' => (string) $item['operations_count'],
                        ],
                        'showHref' => '/superadmin/seguridad/usuarios/' . $item['id'],
                    ])
                    ->values()
                    ->all(),
            ]],
        ];
    }

    private function catalogReport(): array
    {
        $garments = Prenda::query()->with(['categoria', 'marca', 'temporada'])->withCount(['favoritos'])->get();

        return [
            'key' => 'catalog',
            'title' => 'Catálogo',
            'description' => 'Distribucion del catálogo y senales de interes de clientes desde favoritos.',
            'metrics' => [
                ['label' => 'Prendas', 'value' => (string) $garments->count(), 'note' => 'Total de prendas registradas.', 'tone' => 'accent'],
                ['label' => 'Favoritos', 'value' => (string) Favorito::query()->count(), 'note' => 'Productos guardados por clientes.', 'tone' => 'info'],
            ],
            'charts' => [
                [
                    'title' => 'Favoritos por prenda',
                    'description' => 'Prendas guardadas con mayor frecuencia.',
                    'type' => 'bar',
                    'data' => $this->rankingToChart($this->favoriteGarments($garments)),
                    'emptyMessage' => 'No hay favoritos registrados para graficar.',
                ],
            ],
            'rankings' => [
                $this->garmentRanking('Prendas por categoria', $garments, fn (Prenda $garment) => $garment->categoria?->nom_cat ?? 'Sin categoria'),
                $this->garmentRanking('Prendas por marca', $garments, fn (Prenda $garment) => $garment->marca?->nom_mar ?? 'Sin marca'),
                $this->garmentRanking('Prendas por temporada', $garments, fn (Prenda $garment) => $garment->temporada?->nom_tem ?? 'Sin temporada'),
                ['title' => 'Favoritos por prenda', 'items' => $this->favoriteGarments($garments)],
            ],
            'tables' => [],
        ];
    }

    private function salesQuery(Request $request): Builder
    {
        $query = Venta::query()
            ->when(filled($request->input('seller')), fn (Builder $query) => $query->where('cod_usuario_vendedor_ven', $request->input('seller')))
            ->when(filled($request->input('customer')), fn (Builder $query) => $query->where('cod_usuario_cliente_ven', $request->input('customer')))
            ->when(filled($request->input('payment_method')), fn (Builder $query) => $query->where('mpa_ven', $request->input('payment_method')))
            ->when(filled($request->input('sale_status')), fn (Builder $query) => $query->where('est_ven', $request->input('sale_status')));

        return $this->applyDateFilter($query, $request, 'fec_ven');
    }

    private function reservationsQuery(Request $request): Builder
    {
        $query = Reserva::query()
            ->when(filled($request->input('reservation_status')), fn (Builder $query) => $this->applyReservationStatusFilter($query, (string) $request->input('reservation_status')))
            ->when(filled($request->input('customer')), fn (Builder $query) => $query->where('cod_usuario_cliente_res', $request->input('customer')))
            ->when(filled($request->input('responsible')), fn (Builder $query) => $query->where('cod_usuario_responsable_res', $request->input('responsible')));

        return $this->applyDateFilter($query, $request, 'fec_res');
    }

    private function applyReservationStatusFilter(Builder $query, string $status): Builder
    {
        if ($status === 'vencida') {
            return $query->where(function (Builder $query) {
                $query->where('est_res', 'vencida')
                    ->orWhere(function (Builder $query) {
                        $query->whereIn('est_res', self::ACTIVE_RESERVATION_STATES)
                            ->where(function (Builder $query) {
                                $query->whereDate('fec_limite_recojo_res', '<', Carbon::today())
                                    ->orWhere(function (Builder $query) {
                                        $query->whereNull('fec_limite_recojo_res')
                                            ->whereDate('ven_res', '<', Carbon::today());
                                    });
                            });
                    });
            });
        }

        if (in_array($status, self::ACTIVE_RESERVATION_STATES, true)) {
            return $query->where('est_res', $status)
                ->where(function (Builder $query) {
                    $query->whereNull('fec_limite_recojo_res')
                        ->orWhereDate('fec_limite_recojo_res', '>=', Carbon::today());
                })
                ->where(function (Builder $query) {
                    $query->whereNotNull('fec_limite_recojo_res')
                        ->orWhereNull('ven_res')
                        ->orWhereDate('ven_res', '>=', Carbon::today());
                });
        }

        return $query->where('est_res', $status);
    }

    private function paymentCollections(Request $request): array
    {
        $salePayments = $this->applyDateFilter(
            VentaPago::query()
                ->with(['venta.cliente'])
                ->when(filled($request->input('payment_method')), fn (Builder $query) => $query->where('metodo_pago_vpg', $request->input('payment_method')))
                ->when(filled($request->input('seller')), fn (Builder $query) => $query->whereHas('venta', fn (Builder $query) => $query->where('cod_usuario_vendedor_ven', $request->input('seller'))))
                ->when(filled($request->input('customer')), fn (Builder $query) => $query->whereHas('venta', fn (Builder $query) => $query->where('cod_usuario_cliente_ven', $request->input('customer'))))
                ->when(filled($request->input('sale_status')), fn (Builder $query) => $query->whereHas('venta', fn (Builder $query) => $query->where('est_ven', $request->input('sale_status')))),
            $request,
            'fec_vpg',
        )
            ->get()
            ->map(fn (VentaPago $payment) => [
                'id' => 'venta-pago-' . $payment->id_venta_pago,
                'reference' => $payment->venta?->nve_ven ?: 'Venta #' . $payment->cod_ventas_vpg,
                'customer' => $this->userName($payment->venta?->cliente),
                'type' => 'Venta',
                'method' => (string) $payment->metodo_pago_vpg,
                'status' => (string) $payment->est_vpg,
                'declared_amount' => (float) $payment->monto_declarado_vpg,
                'verified_amount' => (float) $payment->monto_verificado_vpg,
                'date' => $payment->fec_vpg ?? $payment->created_at,
                'sort_date' => ($payment->fec_vpg ?? $payment->created_at)?->timestamp ?? 0,
                'href' => '/superadmin/comercial/ventas/' . $payment->cod_ventas_vpg,
            ]);

        $reservationPayments = $this->applyDateFilter(
            ReservaPago::query()
                ->with(['reserva.cliente'])
                ->when(filled($request->input('payment_method')), fn (Builder $query) => $query->where('metodo_pago_rpg', $request->input('payment_method')))
                ->when(filled($request->input('customer')), fn (Builder $query) => $query->whereHas('reserva', fn (Builder $query) => $query->where('cod_usuario_cliente_res', $request->input('customer'))))
                ->when(filled($request->input('responsible')), fn (Builder $query) => $query->whereHas('reserva', fn (Builder $query) => $query->where('cod_usuario_responsable_res', $request->input('responsible'))))
                ->when(filled($request->input('reservation_status')), fn (Builder $query) => $query->whereHas('reserva', fn (Builder $query) => $this->applyReservationStatusFilter($query, (string) $request->input('reservation_status')))),
            $request,
            'fec_rpg',
        )
            ->get()
            ->map(fn (ReservaPago $payment) => [
                'id' => 'reserva-pago-' . $payment->id_reserva_pago,
                'reference' => $payment->reserva?->nre_res ?: 'Reserva #' . $payment->cod_reservas_rpg,
                'customer' => $this->userName($payment->reserva?->cliente),
                'type' => 'Reserva',
                'method' => (string) $payment->metodo_pago_rpg,
                'status' => (string) $payment->est_rpg,
                'declared_amount' => (float) $payment->monto_declarado_rpg,
                'verified_amount' => (float) $payment->monto_confirmado_rpg,
                'date' => $payment->fec_rpg ?? $payment->created_at,
                'sort_date' => ($payment->fec_rpg ?? $payment->created_at)?->timestamp ?? 0,
                'href' => '/superadmin/comercial/reservas/' . $payment->cod_reservas_rpg,
            ]);

        return ['sales' => $salePayments, 'reservations' => $reservationPayments];
    }

    private function applyDateFilter(Builder $query, Request $request, string $column): Builder
    {
        [$from, $to] = $this->dateRange($request);

        return $query
            ->when($from, fn (Builder $query) => $query->whereDate($column, '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate($column, '<=', $to->toDateString()));
    }

    private function dateRange(Request $request): array
    {
        $period = $request->string('period')->toString();
        $today = Carbon::today();

        return match ($period) {
            'hoy' => [$today->copy(), $today->copy()],
            'ultimos_7_dias' => [$today->copy()->subDays(6), $today->copy()],
            'mes_actual' => [$today->copy()->startOfMonth(), $today->copy()],
            default => [
                filled($request->input('date_from')) ? Carbon::parse($request->input('date_from')) : null,
                filled($request->input('date_to')) ? Carbon::parse($request->input('date_to')) : null,
            ],
        };
    }

    private function dateRangeLabel(Request $request): string
    {
        [$from, $to] = $this->dateRange($request);
        $period = $request->string('period')->toString();

        $periodLabel = match ($period) {
            'hoy' => 'Hoy',
            'ultimos_7_dias' => 'Ultimos 7 dias',
            'mes_actual' => 'Mes actual',
            'personalizado' => 'Rango personalizado',
            default => 'Rango de fechas',
        };

        if ($from && $to) {
            return $periodLabel . ': ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y');
        }

        if ($from) {
            return 'Desde ' . $from->format('d/m/Y');
        }

        if ($to) {
            return 'Hasta ' . $to->format('d/m/Y');
        }

        return 'Sin rango de fechas aplicado';
    }

    private function saleDetails(Collection $saleIds): Collection
    {
        return $saleIds->isEmpty()
            ? collect()
            : DetalleVenta::query()->with('variante.prenda')->whereIn('cod_ventas_dve', $saleIds)->get();
    }

    private function saleProductsLabel(Venta $sale): string
    {
        $sale->loadMissing('detalles.variante.prenda');

        $products = $sale->detalles
            ->map(function (DetalleVenta $detail) {
                $name = $detail->variante?->prenda?->nom_pre ?? 'Prenda sin nombre';

                return $name . ' x' . (int) $detail->can_dve;
            })
            ->filter()
            ->values();

        return $products->isEmpty() ? 'Sin detalle' : $products->implode(' | ');
    }

    private function reservationDetails(Collection $reservationIds): Collection
    {
        return $reservationIds->isEmpty()
            ? collect()
            : DetalleReserva::query()->with('variante.prenda')->whereIn('cod_reservas_dre', $reservationIds)->get();
    }

    private function inventoryRanking(string $title, Collection $inventories, callable $labelResolver): array
    {
        return [
            'title' => $title,
            'items' => $inventories
                ->groupBy($labelResolver)
                ->map(fn (Collection $group, string $label) => ['label' => $label, 'value' => (string) $group->sum('cdi_inv'), 'note' => $group->sum('cre_inv') . ' comprometido(s)', 'tone' => 'accent'])
                ->sortByDesc(fn (array $item) => (int) $item['value'])
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    private function garmentRanking(string $title, Collection $garments, callable $labelResolver): array
    {
        return [
            'title' => $title,
            'items' => $garments
                ->groupBy($labelResolver)
                ->map(fn (Collection $group, string $label) => ['label' => $label, 'value' => (string) $group->count(), 'note' => 'prenda(s)', 'tone' => 'neutral'])
                ->sortByDesc(fn (array $item) => (int) $item['value'])
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    private function topSaleProducts(Collection $details): array
    {
        return $details
            ->groupBy(fn (DetalleVenta $detail) => $detail->variante?->prenda?->nom_pre ?? 'Sin prenda')
            ->map(fn (Collection $group, string $label) => ['label' => $label, 'value' => (string) $group->sum('can_dve'), 'note' => $this->money((float) $group->sum('sub_dve')), 'tone' => 'accent'])
            ->sortByDesc(fn (array $item) => (int) $item['value'])
            ->take(8)
            ->values()
            ->all();
    }

    private function topReservedProducts(Collection $details): array
    {
        return $details
            ->groupBy(fn (DetalleReserva $detail) => $detail->variante?->prenda?->nom_pre ?? 'Sin prenda')
            ->map(fn (Collection $group, string $label) => ['label' => $label, 'value' => (string) $group->sum('can_dre'), 'note' => $group->count() . ' linea(s)', 'tone' => 'accent'])
            ->sortByDesc(fn (array $item) => (int) $item['value'])
            ->take(8)
            ->values()
            ->all();
    }

    private function salesBySeller(Collection $sales): array
    {
        return $sales
            ->groupBy(fn (Venta $sale) => $this->userName($sale->vendedor))
            ->map(fn (Collection $group, string $label) => ['label' => $label, 'amount' => (float) $group->sum('tot_ven'), 'note' => $group->count() . ' venta(s)', 'tone' => 'info'])
            ->sortByDesc('amount')
            ->take(8)
            ->map(fn (array $item) => ['label' => $item['label'], 'value' => $this->money((float) $item['amount']), 'note' => $item['note'], 'tone' => $item['tone']])
            ->values()
            ->all();
    }

    private function salesByPayment(Collection $sales): array
    {
        return $sales
            ->groupBy(fn (Venta $sale) => $sale->mpa_ven ?: 'Sin metodo')
            ->map(fn (Collection $group, string $label) => ['label' => $this->humanize($label), 'amount' => (float) $group->sum('tot_ven'), 'note' => $group->count() . ' venta(s)', 'tone' => 'success'])
            ->sortByDesc('amount')
            ->map(fn (array $item) => ['label' => $item['label'], 'value' => $this->money((float) $item['amount']), 'note' => $item['note'], 'tone' => $item['tone']])
            ->values()
            ->all();
    }

    private function favoriteGarments(Collection $garments): array
    {
        return $garments
            ->sortByDesc('favoritos_count')
            ->take(8)
            ->map(fn (Prenda $garment) => ['label' => $garment->nom_pre, 'value' => (string) $garment->favoritos_count, 'note' => $garment->marca?->nom_mar ?? 'Sin marca', 'tone' => $garment->favoritos_count > 0 ? 'success' : 'neutral'])
            ->values()
            ->all();
    }

    private function frequentCustomers(Request $request): Collection
    {
        $sales = $this->salesQuery($request)
            ->with('cliente')
            ->whereNotNull('cod_usuario_cliente_ven')
            ->get();
        $reservations = $this->reservationsQuery($request)
            ->with('cliente')
            ->whereNotNull('cod_usuario_cliente_res')
            ->get();

        $customerIds = $sales->pluck('cod_usuario_cliente_ven')
            ->merge($reservations->pluck('cod_usuario_cliente_res'))
            ->filter()
            ->unique()
            ->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $customerIds)->get()->keyBy('id');

        return $customerIds
            ->map(function ($customerId) use ($sales, $reservations, $users) {
                $user = $users->get((int) $customerId);
                $salesCount = $sales->where('cod_usuario_cliente_ven', (int) $customerId)->count();
                $reservationsCount = $reservations->where('cod_usuario_cliente_res', (int) $customerId)->count();

                return [
                    'id' => (int) $customerId,
                    'label' => $this->userName($user),
                    'email' => (string) ($user?->email ?? 'Sin correo'),
                    'sales_count' => $salesCount,
                    'reservations_count' => $reservationsCount,
                    'operations_count' => $salesCount + $reservationsCount,
                ];
            })
            ->sortByDesc('operations_count')
            ->values();
    }

    private function frequentCustomersForExport(Request $request): Collection
    {
        $sales = $this->salesQuery($request)
            ->with('cliente')
            ->whereNotNull('cod_usuario_cliente_ven')
            ->get();
        $reservations = $this->reservationsQuery($request)
            ->with('cliente')
            ->whereNotNull('cod_usuario_cliente_res')
            ->get();

        $customerIds = $sales->pluck('cod_usuario_cliente_ven')
            ->merge($reservations->pluck('cod_usuario_cliente_res'))
            ->filter()
            ->unique()
            ->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $customerIds)->get()->keyBy('id');

        return $customerIds
            ->map(function ($customerId) use ($sales, $reservations, $users) {
                $user = $users->get((int) $customerId);
                $customerSales = $sales->where('cod_usuario_cliente_ven', (int) $customerId);
                $customerReservations = $reservations->where('cod_usuario_cliente_res', (int) $customerId);
                $lastSale = $customerSales->max(fn (Venta $sale) => $sale->fec_ven?->timestamp ?? 0);
                $lastReservation = $customerReservations->max(fn (Reserva $reservation) => $reservation->fec_res?->timestamp ?? 0);
                $lastActivity = max((int) $lastSale, (int) $lastReservation);

                return [
                    'id' => (int) $customerId,
                    'label' => $this->userName($user),
                    'sales_count' => $customerSales->count(),
                    'reservations_count' => $customerReservations->count(),
                    'sales_total' => (float) $customerSales->where('est_ven', 'registrada')->sum('tot_ven'),
                    'reservations_total' => (float) $customerReservations
                        ->whereIn('est_res', ['por_procesar', 'confirmada', 'lista_recojo', 'completada'])
                        ->sum('tot_res'),
                    'last_activity' => $lastActivity > 0 ? Carbon::createFromTimestamp($lastActivity) : null,
                    'operations_count' => $customerSales->count() + $customerReservations->count(),
                ];
            })
            ->sortByDesc('operations_count')
            ->values();
    }

    private function reservationStateCounts(Collection $reservations): array
    {
        $counts = collect(array_keys(self::RESERVATION_STATES))->mapWithKeys(fn (string $state) => [$state => 0])->all();

        foreach ($reservations as $reservation) {
            $state = $this->reservationDisplayStatus($reservation);
            $counts[$state] = ($counts[$state] ?? 0) + 1;
        }

        return $counts;
    }

    private function reservationDisplayStatus(Reserva $reservation): string
    {
        if ($this->reservationIsExpired($reservation)) {
            return 'vencida';
        }

        $state = trim((string) $reservation->est_res);

        return $state !== '' ? $state : 'por_procesar';
    }

    private function reservationIsExpired(Reserva $reservation): bool
    {
        if (!in_array((string) $reservation->est_res, self::ACTIVE_RESERVATION_STATES, true)) {
            return false;
        }

        $limit = $reservation->fec_limite_recojo_res ?: $reservation->ven_res;

        return $limit && $limit->lt(Carbon::now());
    }

    private function salesStatusChart(Collection $sales): array
    {
        return [
            ['label' => 'Confirmadas', 'value' => $sales->where('est_ven', 'registrada')->count(), 'tone' => 'success'],
            ['label' => 'En revision', 'value' => $sales->where('est_ven', 'en_revision')->count(), 'tone' => 'warning'],
            ['label' => 'Canceladas', 'value' => $sales->where('est_ven', 'cancelada')->count(), 'tone' => 'danger'],
            ['label' => 'Incompletas', 'value' => $sales->where('est_ven', 'incompleta')->count(), 'tone' => 'neutral'],
        ];
    }

    private function salesOriginChart(Collection $sales): array
    {
        return $sales
            ->groupBy(fn (Venta $sale) => $sale->tiv_ven ?: 'sin_origen')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label === 'reserva' ? 'Desde reservas' : $this->humanize($label),
                'value' => $group->count(),
                'tone' => match ($label) {
                    'online' => 'info',
                    'reserva' => 'accent',
                    'fisica' => 'success',
                    default => 'neutral',
                },
            ])
            ->values()
            ->all();
    }

    private function salesTrend(Collection $sales): array
    {
        return $sales
            ->filter(fn (Venta $sale) => $sale->fec_ven !== null)
            ->groupBy(fn (Venta $sale) => $sale->fec_ven->format('Y-m-d'))
            ->sortKeys()
            ->map(fn (Collection $group, string $date) => [
                'label' => Carbon::parse($date)->format('d M'),
                'value' => $group->count(),
                'amount' => (float) $group->sum('tot_ven'),
                'tone' => 'accent',
            ])
            ->values()
            ->all();
    }

    private function reservationStateChart(array $stateCounts): array
    {
        return collect(self::RESERVATION_STATES)
            ->map(fn (array $meta, string $state) => [
                'label' => $meta['label'],
                'value' => (int) ($stateCounts[$state] ?? 0),
                'tone' => $meta['tone'],
            ])
            ->values()
            ->all();
    }

    private function paymentStatusChart(array $payments): array
    {
        return [
            ['label' => 'Pendientes', 'value' => $this->paymentStatusCount($payments, 'pendiente_revision'), 'tone' => 'warning'],
            ['label' => 'Confirmados', 'value' => $this->paymentStatusCount($payments, 'confirmado'), 'tone' => 'success'],
            ['label' => 'Rechazados', 'value' => $this->paymentStatusCount($payments, 'rechazado'), 'tone' => 'danger'],
        ];
    }

    private function paymentMethodChart(Collection $payments): array
    {
        return $payments
            ->groupBy(fn (array $payment) => $payment['method'] ?: 'sin_metodo')
            ->map(fn (Collection $group, string $method) => [
                'label' => $this->humanize($method),
                'value' => $group->count(),
                'tone' => match ($method) {
                    'qr' => 'info',
                    'transferencia_bancaria' => 'accent',
                    'efectivo' => 'success',
                    default => 'neutral',
                },
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function paymentMethodsRanking(Collection $payments): array
    {
        return $payments
            ->groupBy(fn (array $payment) => $payment['method'] ?: 'sin_metodo')
            ->map(fn (Collection $group, string $method) => [
                'label' => $this->humanize($method),
                'value' => (string) $group->count(),
                'note' => $this->money((float) $group->sum('declared_amount')) . ' declarado',
                'tone' => 'accent',
            ])
            ->sortByDesc(fn (array $item) => (int) $item['value'])
            ->values()
            ->all();
    }

    private function paymentStatusCount(array $payments, string $status): int
    {
        return $payments['sales']->where('status', $status)->count()
            + $payments['reservations']->where('status', $status)->count();
    }

    private function rankingToChart(array $items): array
    {
        return collect($items)
            ->map(fn (array $item) => [
                'label' => $item['label'],
                'value' => (int) preg_replace('/[^0-9]/', '', (string) $item['value']),
                'tone' => $item['tone'] ?? 'accent',
            ])
            ->values()
            ->all();
    }

    private function filterControls(Request $request): array
    {
        return [
            $this->selectFilter('period', 'Periodo', $request, [
                ['label' => 'Hoy', 'value' => 'hoy'],
                ['label' => 'Ultimos 7 dias', 'value' => 'ultimos_7_dias'],
                ['label' => 'Mes actual', 'value' => 'mes_actual'],
                ['label' => 'Rango personalizado', 'value' => 'personalizado'],
            ]),
            $this->dateFilter('date_from', 'Fecha inicio', $request),
            $this->dateFilter('date_to', 'Fecha fin', $request),
            $this->selectFilter('seller', 'Vendedor', $request, $this->userOptions('cod_usuario_vendedor_ven', Venta::class)),
            $this->selectFilter('payment_method', 'Método de pago', $request, $this->paymentMethodOptions()),
            $this->selectFilter('sale_status', 'Estado de venta', $request, $this->distinctOptions(Venta::class, 'est_ven')),
            $this->selectFilter('reservation_status', 'Estado de reserva', $request, $this->reservationStatusOptions()),
            $this->selectFilter('customer', 'Cliente', $request, $this->userOptions('cod_usuario_cliente_res', Reserva::class)),
            $this->selectFilter('responsible', 'Responsable', $request, $this->userOptions('cod_usuario_responsable_res', Reserva::class)),
        ];
    }

    private function appliedFilters(Request $request): array
    {
        return collect($this->filterControls($request))
            ->map(function (array $control) {
                $value = trim((string) ($control['value'] ?? ''));

                if ($value === '') {
                    return null;
                }

                $label = ($control['type'] ?? 'text') === 'select'
                    ? (collect($control['options'] ?? [])->firstWhere('value', $value)['label'] ?? $value)
                    : $value;

                return ['label' => $control['label'], 'value' => $label];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function userOptions(string $column, string $modelClass): array
    {
        $ids = $modelClass::query()->whereNotNull($column)->distinct()->pluck($column);

        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('nom_usu')
            ->get()
            ->map(fn (User $user) => ['label' => $this->userName($user), 'value' => (string) $user->id])
            ->values()
            ->all();
    }

    private function distinctOptions(string $modelClass, string $column): array
    {
        return $modelClass::query()
            ->whereNotNull($column)
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => ['label' => $this->humanize((string) $value), 'value' => (string) $value])
            ->values()
            ->all();
    }

    private function paymentMethodOptions(): array
    {
        return collect([
            ...Venta::query()->whereNotNull('mpa_ven')->distinct()->pluck('mpa_ven')->all(),
            ...VentaPago::query()->whereNotNull('metodo_pago_vpg')->distinct()->pluck('metodo_pago_vpg')->all(),
            ...ReservaPago::query()->whereNotNull('metodo_pago_rpg')->distinct()->pluck('metodo_pago_rpg')->all(),
        ])
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->map(fn ($value) => ['label' => $this->humanize((string) $value), 'value' => (string) $value])
            ->values()
            ->all();
    }

    private function reservationStatusOptions(): array
    {
        $existing = $this->distinctOptions(Reserva::class, 'est_res');
        $required = collect(self::RESERVATION_STATES)
            ->map(fn (array $meta, string $state) => ['label' => $meta['label'], 'value' => $state])
            ->values();

        return $required
            ->concat($existing)
            ->unique('value')
            ->values()
            ->all();
    }

    private function selectFilter(string $key, string $label, Request $request, array $options): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'select', 'value' => $request->string($key)->toString(), 'options' => $options];
    }

    private function dateFilter(string $key, string $label, Request $request): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'date', 'value' => $request->string($key)->toString()];
    }

    private function stockStatus(int $available): array
    {
        if ($available <= 0) {
            return ['label' => 'Sin stock', 'tone' => 'danger'];
        }

        if ($available <= self::LOW_STOCK_LIMIT) {
            return ['label' => 'Bajo stock', 'tone' => 'warning'];
        }

        return ['label' => 'Disponible', 'tone' => 'success'];
    }

    private function statusBadge(string $status): array
    {
        return match ($status) {
            'anulada', 'cancelada', 'rechazada' => ['label' => $this->humanize($status), 'tone' => 'danger'],
            'incompleta', 'en_revision', 'por_procesar' => ['label' => $this->humanize($status), 'tone' => 'warning'],
            'registrada', 'completada', 'confirmada' => ['label' => $this->humanize($status), 'tone' => 'success'],
            'lista_recojo' => ['label' => 'Lista para recojo', 'tone' => 'accent'],
            'vencida' => ['label' => 'Vencida', 'tone' => 'danger'],
            default => ['label' => $this->humanize($status ?: 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationBadge(Reserva $reservation): array
    {
        return $this->statusBadge($this->reservationDisplayStatus($reservation));
    }

    private function paymentBadge(string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Confirmado', 'tone' => 'success'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            'pendiente_revision' => ['label' => 'Pendiente revision', 'tone' => 'warning'],
            default => ['label' => $this->humanize($status), 'tone' => 'neutral'],
        };
    }

    private function reservationStatusLabel(string $status): string
    {
        return self::RESERVATION_STATES[$status]['label'] ?? $this->humanize($status);
    }

    private function variantLabel(?PrendaVariante $variant): string
    {
        if (!$variant) {
            return 'Variante sin relacion';
        }

        return trim(implode(' / ', array_filter([$variant->color?->nom_col, $variant->talla?->nom_tal, $variant->sku_prv]))) ?: 'Sin detalle de variante';
    }

    private function userName(?User $user): string
    {
        return $user?->nombreCompleto() ?: 'Sin usuario';
    }

    private function date($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }

    private function humanize(string $value): string
    {
        $value = trim(str_replace(['_', '-'], ' ', $value));

        return $value === '' ? 'Sin dato' : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
