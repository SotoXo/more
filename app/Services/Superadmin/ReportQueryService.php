<?php

namespace App\Services\Superadmin;

use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Favorito;
use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportQueryService
{
    private const LOW_STOCK_LIMIT = 3;

    public function payload(Request $request): array
    {
        return [
            'metrics' => $this->summaryMetrics($request),
            'reports' => [
                $this->inventoryReport(),
                $this->salesReport($request),
                $this->reservationsReport($request),
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

    private function summaryMetrics(Request $request): array
    {
        $available = (int) Inventario::query()->sum('cdi_inv');
        $reserved = (int) Inventario::query()->sum('cre_inv');
        $salesTotal = (float) $this->salesQuery($request)->sum('tot_ven');
        $activeReservations = $this->reservationsQuery($request)->where('est_res', 'activa')->count();

        return [
            ['label' => 'Stock disponible', 'value' => (string) $available, 'note' => 'Unidades disponibles en inventario.', 'tone' => $available > 0 ? 'success' : 'danger'],
            ['label' => 'Stock reservado', 'value' => (string) $reserved, 'note' => 'Unidades separadas por reservas.', 'tone' => $reserved > 0 ? 'warning' : 'neutral'],
            ['label' => 'Total vendido', 'value' => $this->money($salesTotal), 'note' => 'Ventas dentro del filtro actual.', 'tone' => $salesTotal > 0 ? 'accent' : 'neutral'],
            ['label' => 'Reservas activas', 'value' => (string) $activeReservations, 'note' => 'Reservas vigentes dentro del filtro.', 'tone' => $activeReservations > 0 ? 'info' : 'neutral'],
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

        return [
            'key' => 'inventory',
            'title' => 'Inventario',
            'description' => 'Estado operativo de existencias disponibles, reservadas y variantes pendientes de inventario.',
            'metrics' => [
                ['label' => 'Disponible total', 'value' => (string) $inventories->sum('cdi_inv'), 'note' => 'Suma de inventarios.cdi_inv.', 'tone' => 'success'],
                ['label' => 'Reservado total', 'value' => (string) $inventories->sum('cre_inv'), 'note' => 'Suma de inventarios.cre_inv.', 'tone' => 'warning'],
                ['label' => 'Sin stock', 'value' => (string) $withoutStock, 'note' => 'Inventarios con disponible en cero.', 'tone' => $withoutStock > 0 ? 'danger' : 'success'],
                ['label' => 'Bajo stock', 'value' => (string) $lowStock, 'note' => 'Disponible entre 1 y ' . self::LOW_STOCK_LIMIT . ' unidades.', 'tone' => $lowStock > 0 ? 'warning' : 'success'],
                ['label' => 'Sin inventario', 'value' => (string) $variantsWithoutInventory, 'note' => 'Variantes creadas sin registro en inventarios.', 'tone' => $variantsWithoutInventory > 0 ? 'warning' : 'success'],
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
                    ['key' => 'reserved', 'label' => 'Reservado', 'align' => 'right'],
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
                        'showHref' => '/superadmin/inventario/existencias/' . $inventory->id_inventario,
                    ])
                    ->all(),
            ]],
        ];
    }

    private function salesReport(Request $request): array
    {
        $sales = $this->salesQuery($request)->with(['vendedor', 'cliente'])->get();
        $details = $this->saleDetails($sales->pluck('id_venta'));

        return [
            'key' => 'sales',
            'title' => 'Ventas',
            'description' => 'Resumen de ventas registradas, metodos de pago, vendedores e items mas vendidos.',
            'metrics' => [
                ['label' => 'Ventas', 'value' => (string) $sales->count(), 'note' => 'Cantidad de ventas filtradas.', 'tone' => $sales->count() > 0 ? 'accent' : 'neutral'],
                ['label' => 'Total vendido', 'value' => $this->money((float) $sales->sum('tot_ven')), 'note' => 'Suma de ventas.tot_ven.', 'tone' => $sales->sum('tot_ven') > 0 ? 'success' : 'neutral'],
                ['label' => 'Descuentos', 'value' => $this->money((float) $sales->sum('des_ven')), 'note' => 'Descuento general aplicado.', 'tone' => $sales->sum('des_ven') > 0 ? 'warning' : 'neutral'],
            ],
            'rankings' => [
                ['title' => 'Productos mas vendidos', 'items' => $this->topSaleProducts($details)],
                ['title' => 'Ventas por vendedor', 'items' => $this->salesBySeller($sales)],
                ['title' => 'Ventas por metodo de pago', 'items' => $this->salesByPayment($sales)],
            ],
            'tables' => [[
                'title' => 'Ultimas ventas del filtro',
                'description' => 'Lectura rapida para confirmar fecha, vendedor, metodo y total.',
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
                        'status' => $sale->tiv_ven === 'reserva' ? ['label' => 'Desde reserva', 'tone' => 'accent'] : $this->statusBadge((string) $sale->est_ven),
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
        $expired = $reservations->filter(fn (Reserva $reservation) => $reservation->est_res === 'activa' && $reservation->ven_res && $reservation->ven_res->lt(Carbon::today()))->count();

        return [
            'key' => 'reservations',
            'title' => 'Reservas',
            'description' => 'Seguimiento de reservas activas, vencidas, canceladas, completadas y prendas mas separadas.',
            'metrics' => [
                ['label' => 'Activas', 'value' => (string) $reservations->where('est_res', 'activa')->count(), 'note' => 'Reservas vigentes o pendientes de cierre.', 'tone' => 'info'],
                ['label' => 'Vencidas', 'value' => (string) $expired, 'note' => 'Activas con fecha de vencimiento anterior a hoy.', 'tone' => $expired > 0 ? 'danger' : 'success'],
                ['label' => 'Canceladas', 'value' => (string) $reservations->where('est_res', 'cancelada')->count(), 'note' => 'Reservas anuladas administrativamente.', 'tone' => 'warning'],
                ['label' => 'Completadas', 'value' => (string) $reservations->where('est_res', 'completada')->count(), 'note' => 'Reservas cerradas como entregadas.', 'tone' => 'success'],
            ],
            'rankings' => [
                ['title' => 'Prendas mas reservadas', 'items' => $this->topReservedProducts($details)],
            ],
            'tables' => [[
                'title' => 'Reservas recientes',
                'description' => 'Incluye badge visual para reservas vencidas sin cambiar estado automaticamente.',
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
                            'expires' => $this->date($reservation->ven_res),
                            'state' => $this->humanize((string) $reservation->est_res),
                        ],
                        'showHref' => '/superadmin/reservas/' . $reservation->id_reserva,
                    ])
                    ->all(),
            ]],
        ];
    }

    private function catalogReport(): array
    {
        $garments = Prenda::query()->with(['categoria', 'marca', 'temporada'])->withCount(['favoritos'])->get();

        return [
            'key' => 'catalog',
            'title' => 'Catalogo',
            'description' => 'Distribucion del catalogo y senales de interes de clientes desde favoritos.',
            'metrics' => [
                ['label' => 'Prendas', 'value' => (string) $garments->count(), 'note' => 'Total de prendas registradas.', 'tone' => 'accent'],
                ['label' => 'Favoritos', 'value' => (string) Favorito::query()->count(), 'note' => 'Productos guardados por clientes.', 'tone' => 'info'],
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
        return Venta::query()
            ->when(filled($request->input('date_from')), fn (Builder $query) => $query->whereDate('fec_ven', '>=', $request->input('date_from')))
            ->when(filled($request->input('date_to')), fn (Builder $query) => $query->whereDate('fec_ven', '<=', $request->input('date_to')))
            ->when(filled($request->input('seller')), fn (Builder $query) => $query->where('cod_usuario_vendedor_ven', $request->input('seller')))
            ->when(filled($request->input('payment_method')), fn (Builder $query) => $query->where('mpa_ven', $request->input('payment_method')))
            ->when(filled($request->input('sale_status')), fn (Builder $query) => $query->where('est_ven', $request->input('sale_status')));
    }

    private function reservationsQuery(Request $request): Builder
    {
        return Reserva::query()
            ->when(filled($request->input('date_from')), fn (Builder $query) => $query->whereDate('fec_res', '>=', $request->input('date_from')))
            ->when(filled($request->input('date_to')), fn (Builder $query) => $query->whereDate('fec_res', '<=', $request->input('date_to')))
            ->when(filled($request->input('reservation_status')), fn (Builder $query) => $query->where('est_res', $request->input('reservation_status')))
            ->when(filled($request->input('customer')), fn (Builder $query) => $query->where('cod_usuario_cliente_res', $request->input('customer')))
            ->when(filled($request->input('responsible')), fn (Builder $query) => $query->where('cod_usuario_responsable_res', $request->input('responsible')));
    }

    private function saleDetails(Collection $saleIds): Collection
    {
        return $saleIds->isEmpty()
            ? collect()
            : DetalleVenta::query()->with('variante.prenda')->whereIn('cod_ventas_dve', $saleIds)->get();
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
                ->map(fn (Collection $group, string $label) => ['label' => $label, 'value' => (string) $group->sum('cdi_inv'), 'note' => $group->sum('cre_inv') . ' reservado(s)', 'tone' => 'accent'])
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

    private function favoriteGarments(Collection $garments): array
    {
        return $garments
            ->sortByDesc('favoritos_count')
            ->take(8)
            ->map(fn (Prenda $garment) => ['label' => $garment->nom_pre, 'value' => (string) $garment->favoritos_count, 'note' => $garment->marca?->nom_mar ?? 'Sin marca', 'tone' => $garment->favoritos_count > 0 ? 'success' : 'neutral'])
            ->values()
            ->all();
    }

    private function filterControls(Request $request): array
    {
        return [
            $this->dateFilter('date_from', 'Fecha inicio', $request),
            $this->dateFilter('date_to', 'Fecha fin', $request),
            $this->selectFilter('seller', 'Vendedor', $request, $this->userOptions('cod_usuario_vendedor_ven', Venta::class)),
            $this->selectFilter('payment_method', 'Metodo de pago', $request, $this->distinctOptions(Venta::class, 'mpa_ven')),
            $this->selectFilter('sale_status', 'Estado de venta', $request, $this->distinctOptions(Venta::class, 'est_ven')),
            $this->selectFilter('reservation_status', 'Estado de reserva', $request, $this->distinctOptions(Reserva::class, 'est_res')),
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
            'anulada', 'cancelada' => ['label' => $this->humanize($status), 'tone' => 'danger'],
            'registrada', 'completada' => ['label' => $this->humanize($status), 'tone' => 'success'],
            default => ['label' => $this->humanize($status ?: 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationBadge(Reserva $reservation): array
    {
        if ($reservation->est_res === 'activa' && $reservation->ven_res && $reservation->ven_res->lt(Carbon::today())) {
            return ['label' => 'Vencida', 'tone' => 'danger'];
        }

        return $this->statusBadge((string) $reservation->est_res);
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
