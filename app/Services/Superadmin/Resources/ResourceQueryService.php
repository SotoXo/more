<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Favorito;
use App\Models\ImagenPrenda;
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

class ResourceQueryService
{
    public function __construct(
        private readonly ResourceDefinitionService $definitions,
        private readonly AdminFormattingService $formatting,
    ) {}

    public function baseQuery(array $definition): ?Builder
    {
        return match ($definition['kind']) {
            'taxonomy' => tap(($definition['modelClass'])::query(), function ($query) use ($definition) {
                if (!empty($definition['countRelation'])) {
                    $query->withCount($definition['countRelation']);
                }
            }),
            'users' => User::query()->with('rol'),
            'products' => Prenda::query()
                ->with(['marca', 'categoria', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes'])
                ->withCount(['variantes', 'imagenes', 'favoritos']),
            'variants' => PrendaVariante::query()
                ->with(['prenda', 'color', 'talla', 'inventario', 'imagenes'])
                ->withCount('imagenes'),
            'images' => ImagenPrenda::query()->with(['prenda', 'variante.prenda']),
            'inventory', 'alerts' => Inventario::query()->with(['variante.prenda', 'variante.color', 'variante.talla']),
            'sales' => Venta::query()
                ->with(['cliente', 'vendedor', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla'])
                ->withCount('detalles'),
            'reservations' => Reserva::query()
                ->with(['cliente', 'responsable', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla'])
                ->withCount('detalles'),
            'favorites' => Favorito::query()->with(['usuario', 'prenda.marca', 'prenda.categoria', 'prenda.temporada']),
            default => null,
        };
    }

    public function indexRecords(array $definition, Request $request): Collection
    {
        if ($definition['kind'] === 'adjustments') {
            return $this->adjustmentTemplates();
        }

        $query = $this->baseQuery($definition);
        if (!$query) {
            return collect();
        }

        $records = match ($definition['kind']) {
            'taxonomy' => $this->definitions->definitionKey($definition) === 'catalogo.temporadas'
                ? $query->orderBy('ord_tem')->orderBy('nom_tem')->get()
                : ($this->definitions->definitionKey($definition) === 'catalogo.colores'
                    ? $query->orderBy('ord_col')->orderBy('nom_col')->get()
                    : $query->orderBy($definition['nameField'])->get()),
            'users' => $query->orderByDesc('fec_usu')->orderByDesc('id')->get(),
            'products' => $query->orderByDesc('fec_pre')->orderByDesc('id_prenda')->get(),
            'variants' => $query->orderByDesc('fec_prv')->orderByDesc('id_prenda_variante')->get(),
            'images' => $query->orderBy('cod_prendas_igp')->orderBy('ord_igp')->orderByDesc('id_imagen_prenda')->get(),
            'inventory' => $query->orderByDesc('id_inventario')->get(),
            'alerts' => $query->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->get(),
            'sales' => $query->orderByDesc('fec_ven')->orderByDesc('id_venta')->get(),
            'reservations' => $query->orderByDesc('fec_res')->orderByDesc('id_reserva')->get(),
            'favorites' => $query->orderByDesc('fec_fav')->orderByDesc('id_favorito')->get(),
            default => collect(),
        };

        return $this->sortIndexRecords(
            $this->filterIndexRecords($records, $definition, $request),
            $definition,
            $request,
        )->values();
    }

    private function filterIndexRecords(Collection $records, array $definition, Request $request): Collection
    {
        $key = $this->definitions->definitionKey($definition);

        return $records->filter(function ($record) use ($key, $request) {
            return match ($key) {
                'seguridad.usuarios' => $this->userMatchesFilters($record, $request),
                'catalogo.prendas' => $this->productMatchesFilters($record, $request),
                'catalogo.variantes' => $this->variantMatchesFilters($record, $request),
                'inventario.stock', 'inventario.alertas' => $this->inventoryMatchesFilters($record, $request),
                'comercial.ventas' => $this->saleMatchesFilters($record, $request),
                'comercial.reservas' => $this->reservationMatchesFilters($record, $request),
                default => true,
            };
        });
    }

    private function sortIndexRecords(Collection $records, array $definition, Request $request): Collection
    {
        $sortBy = $request->string('sort_by')->toString();
        $direction = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $key = $this->definitions->definitionKey($definition);

        if ($sortBy === '') {
            return $records;
        }

        $resolver = match ($key) {
            'seguridad.usuarios' => fn ($record) => match ($sortBy) {
                'name' => $this->formatting->userName($record),
                'date' => $record->fec_usu?->timestamp ?? 0,
                'role' => mb_strtolower((string) ($record->rol?->nom_rol ?? ''), 'UTF-8'),
                'status' => $record->est_usu ? 1 : 0,
                default => $this->formatting->userName($record),
            },
            'catalogo.prendas' => fn ($record) => match ($sortBy) {
                'name' => $record->nom_pre ?? '',
                'date' => $record->fec_pre?->timestamp ?? 0,
                'price' => (float) $record->prb_pre,
                'stock' => $this->productAvailable($record),
                'status' => $record->est_pre ? 1 : 0,
                default => $record->nom_pre ?? '',
            },
            'catalogo.variantes' => fn ($record) => match ($sortBy) {
                'name' => $record->sku_prv ?: ($record->prenda?->nom_pre ?? ''),
                'date' => $record->fec_prv?->timestamp ?? 0,
                'price' => (float) $record->pve_prv,
                'stock' => $this->formatting->available($record->inventario),
                'status' => $record->est_prv ? 1 : 0,
                default => $record->sku_prv ?: ($record->prenda?->nom_pre ?? ''),
            },
            'inventario.stock', 'inventario.alertas' => fn ($record) => match ($sortBy) {
                'name' => trim((string) ($record->variante?->prenda?->nom_pre . ' ' . ($record->variante?->sku_prv ?? ''))),
                'stock' => $this->formatting->available($record),
                'status' => $this->stockStateScore($this->formatting->available($record)),
                default => trim((string) ($record->variante?->prenda?->nom_pre . ' ' . ($record->variante?->sku_prv ?? ''))),
            },
            'comercial.ventas' => fn ($record) => match ($sortBy) {
                'name' => $record->nve_ven ?: $this->formatting->userName($record->cliente),
                'date' => $record->fec_ven?->timestamp ?? 0,
                'price' => (float) $record->tot_ven,
                'status' => (string) ($record->est_ven ?? ''),
                default => $record->nve_ven ?: $this->formatting->userName($record->cliente),
            },
            'comercial.reservas' => fn ($record) => match ($sortBy) {
                'name' => $record->nre_res ?: $this->formatting->userName($record->cliente),
                'date' => $record->fec_res?->timestamp ?? 0,
                'status' => (string) ($record->est_res ?? ''),
                default => $record->nre_res ?: $this->formatting->userName($record->cliente),
            },
            default => null,
        };

        if (!$resolver) {
            return $records;
        }

        return $records->sortBy($resolver, SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc');
    }

    private function userMatchesFilters($record, Request $request): bool
    {
        $activeSegment = $request->string('role_slug')->toString();
        if ($activeSegment !== '' && mb_strtolower((string) ($record->rol?->nom_rol ?? ''), 'UTF-8') !== $activeSegment) {
            return false;
        }

        if (!$this->matchesOption($request->input('role'), $record->cod_roles_usu)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_usu)) {
            return false;
        }

        $search = trim($request->string('search')->toString());
        if ($search === '') {
            return true;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $this->formatting->userName($record),
            $record->email,
            $record->rol?->nom_rol,
        ]))), 'UTF-8');

        return str_contains($haystack, mb_strtolower($search, 'UTF-8'));
    }

    private function productMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->gen_pre)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_pre)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->prb_pre)) {
            return false;
        }

        if (!$this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_pre)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->productAvailable($record));
    }

    private function variantMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->prenda?->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->prenda?->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->prenda?->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->prenda?->gen_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('color'), $record->cod_colores_prv)) {
            return false;
        }

        if (!$this->matchesOption($request->input('size'), $record->cod_tallas_prv)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_prv)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->pve_prv)) {
            return false;
        }

        if (!$this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_prv)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->formatting->available($record->inventario));
    }

    private function inventoryMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->variante?->prenda?->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->variante?->prenda?->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->variante?->prenda?->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->variante?->prenda?->gen_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('color'), $record->variante?->cod_colores_prv)) {
            return false;
        }

        if (!$this->matchesOption($request->input('size'), $record->variante?->cod_tallas_prv)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->formatting->available($record));
    }

    private function saleMatchesFilters($record, Request $request): bool
    {
        $details = collect($record->detalles ?? []);

        if (!$this->matchesRelatedOption($request->input('category'), $details->pluck('variante.prenda.cod_categorias_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('brand'), $details->pluck('variante.prenda.cod_marcas_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('color'), $details->pluck('variante.cod_colores_prv'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('size'), $details->pluck('variante.cod_tallas_prv'))) {
            return false;
        }

        if (!$this->matchesText($request->input('status'), $record->est_ven)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->tot_ven)) {
            return false;
        }

        return $this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_ven);
    }

    private function reservationMatchesFilters($record, Request $request): bool
    {
        $details = collect($record->detalles ?? []);

        if (!$this->matchesRelatedOption($request->input('category'), $details->pluck('variante.prenda.cod_categorias_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('brand'), $details->pluck('variante.prenda.cod_marcas_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('color'), $details->pluck('variante.cod_colores_prv'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('size'), $details->pluck('variante.cod_tallas_prv'))) {
            return false;
        }

        if (!$this->matchesText($request->input('status'), $record->est_res)) {
            return false;
        }

        return $this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_res);
    }

    private function matchesOption($selected, $actual): bool
    {
        return blank($selected) || (string) $selected === (string) $actual;
    }

    private function matchesRelatedOption($selected, Collection $values): bool
    {
        return blank($selected) || $values->filter()->contains(fn ($value) => (string) $value === (string) $selected);
    }

    private function matchesText($selected, $actual): bool
    {
        return blank($selected) || mb_strtolower((string) $selected, 'UTF-8') === mb_strtolower((string) $actual, 'UTF-8');
    }

    private function matchesBooleanOption($selected, ?bool $actual): bool
    {
        return blank($selected) || ((string) $selected === '1' ? (bool) $actual : !$actual);
    }

    private function matchesRange($min, $max, float $value): bool
    {
        if (filled($min) && $value < (float) $min) {
            return false;
        }

        if (filled($max) && $value > (float) $max) {
            return false;
        }

        return true;
    }

    private function matchesDateRange($from, $to, $value): bool
    {
        if (!$value) {
            return blank($from) && blank($to);
        }

        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

        if (filled($from) && $date->lt(Carbon::parse($from)->startOfDay())) {
            return false;
        }

        if (filled($to) && $date->gt(Carbon::parse($to)->endOfDay())) {
            return false;
        }

        return true;
    }

    private function matchesStockFilter($selected, int $available): bool
    {
        if (blank($selected)) {
            return true;
        }

        return $this->stockState($available) === $selected;
    }

    private function stockState(int $available): string
    {
        return $available <= 0 ? 'agotado' : ($available <= 2 ? 'bajo' : 'disponible');
    }

    private function stockStateScore(int $available): int
    {
        return match ($this->stockState($available)) {
            'agotado' => 0,
            'bajo' => 1,
            default => 2,
        };
    }

    public function productAvailable($product): int
    {
        return collect($product->variantes ?? [])
            ->sum(fn ($variant) => $this->formatting->available($variant->inventario));
    }

    public function resolveRecord(array $definition, string $record): mixed
    {
        if ($definition['kind'] === 'adjustments') {
            return $this->adjustmentTemplates()->firstWhere('id', (int) $record)
                ?? abort(404);
        }

        $query = $this->baseQuery($definition);
        abort_if(!$query, 404);

        $model = new $definition['modelClass']();
        $keyName = $model->getKeyName();

        return $query->where($keyName, $record)->firstOrFail();
    }

    public function adjustmentTemplates(): Collection
    {
        $lowStock = Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->count();
        $ventas = Venta::query()->count();
        $reservas = Reserva::query()->count();

        return collect([
            [
                'id' => 1,
                'primary' => 'Rebalancear stock sensible',
                'secondary' => 'Inventario con margen corto',
                'status' => ['label' => 'Prioritario', 'tone' => 'warning'],
                'cells' => [
                    'area' => 'Inventario',
                    'focus' => $lowStock . ' registros a revisar',
                    'cut' => 'Corte del día',
                    'updated' => 'Hoy',
                ],
                'summaryMetrics' => [
                    ['label' => 'Existencias críticas', 'value' => (string) $lowStock, 'note' => 'Registros por revisar.', 'tone' => 'warning'],
                    ['label' => 'Ventas', 'value' => (string) $ventas, 'note' => 'Ventas registradas.', 'tone' => 'success'],
                    ['label' => 'Reservas', 'value' => (string) $reservas, 'note' => 'Reservas activas.', 'tone' => 'accent'],
                    ['label' => 'Corte', 'value' => 'Hoy', 'note' => 'Fecha del ajuste.', 'tone' => 'neutral'],
                ],
                'formSections' => [
                    [
                        'title' => 'Ajuste sugerido',
                        'description' => 'Motivo y criterio del ajuste.',
                        'fields' => [
                            ['name' => 'titulo', 'label' => 'Título', 'value' => 'Rebalancear existencias sensibles'],
                            ['name' => 'criterio', 'label' => 'Criterio', 'value' => $lowStock . ' registros con margen corto', 'type' => 'textarea'],
                            ['name' => 'estado', 'label' => 'Seguimiento', 'value' => '1', 'type' => 'toggle'],
                        ],
                    ],
                ],
                'detailSections' => [
                    [
                        'title' => 'Resumen del ajuste',
                        'fields' => [
                            ['label' => 'Área', 'value' => 'Inventario', 'tone' => 'accent'],
                            ['label' => 'Enfoque', 'value' => $lowStock . ' registros con margen corto', 'tone' => 'warning'],
                            ['label' => 'Corte', 'value' => 'Hoy', 'tone' => 'neutral'],
                            ['label' => 'Pulso comercial', 'value' => $ventas . ' ventas / ' . $reservas . ' reservas', 'tone' => 'success'],
                        ],
                    ],
                ],
                'timeline' => [
                    ['title' => 'Fecha', 'note' => 'Fecha del ajuste.', 'date' => 'Hoy', 'tone' => 'accent'],
                    ['title' => 'Cruce comercial', 'note' => 'Ventas y reservas relacionadas.', 'date' => $ventas . ' / ' . $reservas, 'tone' => 'success'],
                    ['title' => 'Prioridad', 'note' => 'Nivel actual.', 'date' => 'Alta', 'tone' => 'warning'],
                ],
            ],
            [
                'id' => 2,
                'primary' => 'Conciliar reservas activas',
                'secondary' => 'Seguimiento entre apartados y margen disponible',
                'status' => ['label' => 'Seguimiento', 'tone' => 'accent'],
                'cells' => [
                    'area' => 'Comercial',
                    'focus' => $reservas . ' reservas en observación',
                    'cut' => 'Semana actual',
                    'updated' => 'Ahora',
                ],
                'summaryMetrics' => [
                    ['label' => 'Reservas', 'value' => (string) $reservas, 'note' => 'Reservas activas.', 'tone' => 'accent'],
                    ['label' => 'Existencias críticas', 'value' => (string) $lowStock, 'note' => 'Cruce con inventario.', 'tone' => 'warning'],
                    ['label' => 'Ventas', 'value' => (string) $ventas, 'note' => 'Ventas del periodo.', 'tone' => 'success'],
                    ['label' => 'Corte', 'value' => 'Semana', 'note' => 'Periodo de revisión.', 'tone' => 'neutral'],
                ],
                'formSections' => [
                    [
                        'title' => 'Conciliación operativa',
                        'description' => 'Motivo y criterio de revisión.',
                        'fields' => [
                            ['name' => 'titulo', 'label' => 'Título', 'value' => 'Conciliar reservas activas'],
                            ['name' => 'criterio', 'label' => 'Criterio', 'value' => $reservas . ' reservas visibles en la semana actual', 'type' => 'textarea'],
                            ['name' => 'estado', 'label' => 'Seguimiento', 'value' => '1', 'type' => 'toggle'],
                        ],
                    ],
                ],
                'detailSections' => [
                    [
                        'title' => 'Resumen del ajuste',
                        'fields' => [
                            ['label' => 'Área', 'value' => 'Comercial', 'tone' => 'accent'],
                            ['label' => 'Enfoque', 'value' => $reservas . ' reservas visibles', 'tone' => 'warning'],
                            ['label' => 'Ventana', 'value' => 'Semana actual', 'tone' => 'neutral'],
                            ['label' => 'Cruce', 'value' => $lowStock . ' registros con margen corto', 'tone' => 'success'],
                        ],
                    ],
                ],
                'timeline' => [
                    ['title' => 'Periodo', 'note' => 'Ventana de revisión.', 'date' => 'Semana', 'tone' => 'accent'],
                    ['title' => 'Existencias críticas', 'note' => 'Registros relacionados.', 'date' => (string) $lowStock, 'tone' => 'warning'],
                    ['title' => 'Estado', 'note' => 'Seguimiento actual.', 'date' => 'Activo', 'tone' => 'success'],
                ],
            ],
        ]);
    }

    public function latestDate(Builder $query, string $field): string
    {
        $value = (clone $query)->orderByDesc($field)->value($field);

        return $this->formatting->formatDate($value);
    }
}
