<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\Marca;
use App\Models\PrendaVariante;
use App\Models\Talla;
use App\Models\Temporada;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $variants = PrendaVariante::query()
            ->with(['prenda.categoria', 'prenda.marca', 'prenda.temporada', 'color', 'talla', 'inventario'])
            ->get();

        $records = $this->sortRecords(
            $this->filterRecords($variants, $request),
            $request,
        )->values();

        return Inertia::render('Superadmin/Reports', [
            'metrics' => $this->metrics($records),
            'columns' => [
                ['key' => 'category', 'label' => 'Categoria'],
                ['key' => 'brand', 'label' => 'Marca'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'size', 'label' => 'Talla'],
                ['key' => 'price', 'label' => 'Precio', 'align' => 'right'],
                ['key' => 'stock', 'label' => 'Stock', 'align' => 'right'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'rows' => $records->map(fn ($record) => [
                'id' => $record->id_prenda_variante,
                'primary' => $record->prenda?->nom_pre ?? 'Variante #' . $record->id_prenda_variante,
                'secondary' => $record->sku_prv ?: 'Sin SKU',
                'status' => $this->stockStatus($this->available($record)),
                'cells' => [
                    'category' => $record->prenda?->categoria?->nom_cat ?? 'Sin categoria',
                    'brand' => $record->prenda?->marca?->nom_mar ?? 'Sin marca',
                    'color' => $record->color?->nom_col ?? 'Sin color',
                    'size' => $record->talla?->nom_tal ?? 'Sin talla',
                    'price' => $this->money((float) $record->pve_prv),
                    'stock' => (string) $this->available($record),
                    'date' => $this->formatDate($record->fec_prv),
                ],
                'showHref' => '/superadmin/catalogo/variantes/' . $record->id_prenda_variante,
                'editHref' => '/superadmin/catalogo/variantes/' . $record->id_prenda_variante . '/editar',
            ])->all(),
            'filterControls' => $this->filterControls($request),
            'sortOptions' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Precio', 'value' => 'price'],
                ['label' => 'Stock', 'value' => 'stock'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'sortBy' => $request->string('sort_by')->toString(),
            'sortDirection' => $request->input('sort_dir', 'desc'),
            'appliedFilters' => $this->appliedFilters($request),
            'emptyState' => [
                'title' => 'Sin coincidencias',
                'description' => 'No hay resultados con los criterios actuales.',
                'actionLabel' => 'Limpiar filtros',
                'actionHref' => '/superadmin/reportes',
            ],
        ]);
    }

    private function filterControls(Request $request): array
    {
        return [
            $this->selectFilter('category', 'Categoria', $request, $this->categoryOptions()),
            $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
            $this->selectFilter('season', 'Temporada', $request, $this->seasonOptions()),
            $this->selectFilter('gender', 'Genero', $request, $this->genderOptions()),
            $this->selectFilter('color', 'Color', $request, $this->colorOptions()),
            $this->selectFilter('size', 'Talla', $request, $this->sizeOptions()),
            $this->selectFilter('stock', 'Stock', $request, $this->stockOptions()),
            $this->numberFilter('price_min', 'Precio desde', $request),
            $this->numberFilter('price_max', 'Precio hasta', $request),
            $this->dateFilter('date_from', 'Fecha desde', $request),
            $this->dateFilter('date_to', 'Fecha hasta', $request),
        ];
    }

    private function metrics(Collection $records): array
    {
        $available = $records->sum(fn ($record) => $this->available($record));
        $critical = $records->filter(fn ($record) => $this->available($record) <= 2)->count();
        $averagePrice = $records->count() > 0
            ? $records->avg(fn ($record) => (float) $record->pve_prv)
            : 0;

        return [
            [
                'label' => 'Resultados',
                'value' => (string) $records->count(),
                'note' => 'Variantes dentro del corte actual.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Stock disponible',
                'value' => (string) $available,
                'note' => 'Saldo disponible en los resultados.',
                'tone' => $available > 0 ? 'success' : 'danger',
            ],
            [
                'label' => 'Precio promedio',
                'value' => $this->money((float) $averagePrice),
                'note' => 'Promedio de precio de venta.',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Stock critico',
                'value' => (string) $critical,
                'note' => 'Variantes con margen corto.',
                'tone' => $critical > 0 ? 'warning' : 'success',
            ],
        ];
    }

    private function filterRecords(Collection $records, Request $request): Collection
    {
        return $records->filter(function ($record) use ($request) {
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

            if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->pve_prv)) {
                return false;
            }

            if (!$this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_prv)) {
                return false;
            }

            return $this->matchesStock($request->input('stock'), $this->available($record));
        });
    }

    private function sortRecords(Collection $records, Request $request): Collection
    {
        $sortBy = $request->string('sort_by')->toString();

        if ($sortBy === '') {
            return $records;
        }

        $descending = $request->input('sort_dir', 'desc') !== 'asc';

        return $records->sortBy(fn ($record) => match ($sortBy) {
            'name' => $record->prenda?->nom_pre ?? '',
            'date' => $record->fec_prv?->timestamp ?? 0,
            'price' => (float) $record->pve_prv,
            'stock' => $this->available($record),
            'status' => $this->stockStateScore($this->available($record)),
            default => $record->prenda?->nom_pre ?? '',
        }, SORT_NATURAL | SORT_FLAG_CASE, $descending);
    }

    private function appliedFilters(Request $request): array
    {
        $controls = collect($this->filterControls($request))
            ->map(function ($control) {
                $value = trim((string) ($control['value'] ?? ''));

                if ($value === '') {
                    return null;
                }

                if (($control['type'] ?? 'text') === 'select') {
                    $label = collect($control['options'] ?? [])->firstWhere('value', $value)['label'] ?? $value;

                    return [
                        'label' => $control['label'],
                        'value' => $label,
                    ];
                }

                return [
                    'label' => $control['label'],
                    'value' => $value,
                ];
            })
            ->filter()
            ->values();

        $sortBy = $request->string('sort_by')->toString();

        if ($sortBy !== '') {
            $sortLabel = collect([
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Precio', 'value' => 'price'],
                ['label' => 'Stock', 'value' => 'stock'],
                ['label' => 'Estado', 'value' => 'status'],
            ])->firstWhere('value', $sortBy)['label'] ?? $sortBy;

            $controls->push([
                'label' => 'Orden',
                'value' => $sortLabel . ' · ' . ($request->input('sort_dir', 'desc') === 'asc' ? 'Ascendente' : 'Descendente'),
            ]);
        }

        return $controls->all();
    }

    private function categoryOptions(): array
    {
        return Categoria::query()
            ->orderBy('nom_cat')
            ->get()
            ->map(fn ($record) => ['label' => $record->nom_cat, 'value' => (string) $record->id_categoria])
            ->values()
            ->all();
    }

    private function brandOptions(): array
    {
        return Marca::query()
            ->orderBy('nom_mar')
            ->get()
            ->map(fn ($record) => ['label' => $record->nom_mar, 'value' => (string) $record->id_marca])
            ->values()
            ->all();
    }

    private function seasonOptions(): array
    {
        return Temporada::query()
            ->orderBy('nom_tem')
            ->get()
            ->map(fn ($record) => ['label' => $record->nom_tem, 'value' => (string) $record->id_temporada])
            ->values()
            ->all();
    }

    private function genderOptions(): array
    {
        return [
            ['label' => 'Mujer', 'value' => 'mujer'],
            ['label' => 'Hombre', 'value' => 'hombre'],
            ['label' => 'Unisex', 'value' => 'unisex'],
            ['label' => 'Accesorios', 'value' => 'accesorios'],
        ];
    }

    private function colorOptions(): array
    {
        return Color::query()
            ->orderBy('nom_col')
            ->get()
            ->map(fn ($record) => ['label' => $record->nom_col, 'value' => (string) $record->id_color])
            ->values()
            ->all();
    }

    private function sizeOptions(): array
    {
        return Talla::query()
            ->orderBy('ord_tal')
            ->orderBy('nom_tal')
            ->get()
            ->map(fn ($record) => ['label' => $record->nom_tal, 'value' => (string) $record->id_talla])
            ->values()
            ->all();
    }

    private function stockOptions(): array
    {
        return [
            ['label' => 'Disponible', 'value' => 'disponible'],
            ['label' => 'Bajo', 'value' => 'bajo'],
            ['label' => 'Agotado', 'value' => 'agotado'],
        ];
    }

    private function selectFilter(string $key, string $label, Request $request, array $options): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'select',
            'value' => $request->string($key)->toString(),
            'options' => $options,
        ];
    }

    private function numberFilter(string $key, string $label, Request $request): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'value' => $request->string($key)->toString(),
            'placeholder' => '0',
        ];
    }

    private function dateFilter(string $key, string $label, Request $request): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'date',
            'value' => $request->string($key)->toString(),
        ];
    }

    private function available($record): int
    {
        $inventory = $record->inventario;

        if (!$inventory) {
            return 0;
        }

        return max(0, (int) ($inventory->cdi_inv ?? 0) - (int) ($inventory->cre_inv ?? 0));
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

    private function stockStatus(int $available): array
    {
        return match ($this->stockState($available)) {
            'agotado' => ['label' => 'Agotado', 'tone' => 'danger'],
            'bajo' => ['label' => 'Bajo', 'tone' => 'warning'],
            default => ['label' => 'Disponible', 'tone' => 'success'],
        };
    }

    private function matchesOption($selected, $actual): bool
    {
        return blank($selected) || (string) $selected === (string) $actual;
    }

    private function matchesText($selected, $actual): bool
    {
        return blank($selected) || mb_strtolower((string) $selected, 'UTF-8') === mb_strtolower((string) $actual, 'UTF-8');
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

    private function matchesStock($selected, int $available): bool
    {
        return blank($selected) || $this->stockState($available) === $selected;
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d M Y');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }
}
