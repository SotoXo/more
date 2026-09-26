<?php

namespace App\Services;

use App\Models\Prenda;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorefrontCatalogService
{
    private const WOMEN_GENDERS = ['mujer', 'unisex'];
    private const MEN_GENDERS = ['hombre', 'unisex'];
    private const SORTS = ['recommended', 'newest', 'price-asc', 'price-desc'];
    private const PRICES = [
        '0-350' => [0, 350],
        '351-700' => [351, 700],
        '701-1100' => [701, 1100],
        '1101+' => [1101, null],
    ];

    public function catalog(Request $request): LengthAwarePaginator { return $this->getProducts($request, 'catalogo'); }
    public function women(Request $request): LengthAwarePaginator { return $this->getProducts($request, 'mujer'); }
    public function men(Request $request): LengthAwarePaginator { return $this->getProducts($request, 'hombre'); }
    public function accessories(Request $request): LengthAwarePaginator { return $this->getProducts($request, 'accesorios'); }
    public function newIn(Request $request): LengthAwarePaginator { return $this->getProducts($request, 'novedades'); }

    public function initialFilters(Request $request): array
    {
        $price = (string) $request->query('precio', 'all');
        $availability = (string) $request->query('disponibilidad', 'all');

        return [
            'categories' => $this->queryValues($request, 'categoria'),
            'sizes' => $this->queryValues($request, 'talla'),
            'colors' => $this->queryValues($request, 'color'),
            'styles' => $this->queryValues($request, ['temporada', 'edicion']),
            'brands' => $this->queryValues($request, 'marca'),
            'locations' => $this->queryValues($request, ['ubicacion', 'sucursal']),
            'price' => isset(self::PRICES[$price]) ? $price : 'all',
            'availability' => in_array($availability, ['all', 'Disponible', 'Últimas piezas', 'Agotado'], true) ? $availability : 'all',
            'hideSoldOut' => $availability !== 'Agotado' && $request->query('mostrar_agotados') !== '1',
        ];
    }

    public function initialSort(Request $request, string $collection): string
    {
        $sort = (string) $request->query('orden', $collection === 'novedades' ? 'newest' : 'recommended');

        return in_array($sort, self::SORTS, true) ? $sort : ($collection === 'novedades' ? 'newest' : 'recommended');
    }

    public function filterOptions(Request $request, string $collection): array
    {
        $filters = $this->initialFilters($request);
        $base = $this->scopedQuery($request, $collection, false);
        if ($filters['hideSoldOut']) {
            $this->whereAvailable($base, []);
        }

        $values = fn (Builder $query, string $column) => $query
            ->distinct()->orderBy($column)->pluck($column)->filter()->values()->all();

        $categories = $values((clone $base)->join('categorias as facet_category', 'facet_category.id_categoria', '=', 'prendas.cod_categorias_pre'), 'facet_category.nom_cat');
        $styles = $values((clone $base)->join('temporadas as facet_season', 'facet_season.id_temporada', '=', 'prendas.cod_temporadas_pre'), 'facet_season.nom_tem');
        $brands = $values((clone $base)->join('marcas as facet_brand', 'facet_brand.id_marca', '=', 'prendas.cod_marcas_pre'), 'facet_brand.nom_mar');

        $variants = (clone $base)
            ->join('prenda_variantes as facet_variant', function ($join) {
                $join->on('facet_variant.cod_prendas_prv', '=', 'prendas.id_prenda')->where('facet_variant.est_prv', true);
            })
            ->join('inventarios as facet_inventory', 'facet_inventory.cod_prenda_variantes_inv', '=', 'facet_variant.id_prenda_variante');
        if ($filters['hideSoldOut']) {
            $variants->where('facet_inventory.cdi_inv', '>', 0);
        }

        $sizes = $values((clone $variants)->join('tallas as facet_size', 'facet_size.id_talla', '=', 'facet_variant.cod_tallas_prv'), 'facet_size.nom_tal');
        $colors = $values((clone $variants)->join('colores as facet_color', 'facet_color.id_color', '=', 'facet_variant.cod_colores_prv'), 'facet_color.fam_col');
        $locations = $values(clone $variants, 'facet_inventory.ubi_inv');

        return compact('categories', 'sizes', 'colors', 'styles', 'brands', 'locations');
    }

    public function availableProducts(Builder $query): Builder
    {
        return $this->whereAvailable($query, []);
    }

    public function applySeasonOrdering(Builder $query): Builder
    {
        $today = CarbonImmutable::today()->toDateString();

        return $query
            ->leftJoin('temporadas as temporada_orden', 'temporada_orden.id_temporada', '=', 'prendas.cod_temporadas_pre')
            ->select('prendas.*')
            ->orderByRaw(
                "
                CASE
                    WHEN temporada_orden.id_temporada IS NULL THEN 5
                    WHEN temporada_orden.est_tem IS TRUE
                        AND temporada_orden.fec_ini_tem IS NOT NULL
                        AND temporada_orden.fec_fin_tem IS NOT NULL
                        AND temporada_orden.fec_ini_tem <= ?
                        AND temporada_orden.fec_fin_tem >= ? THEN 1
                    WHEN LOWER(COALESCE(temporada_orden.cod_tem, '')) = 'per'
                        OR LOWER(COALESCE(temporada_orden.nom_tem, '')) LIKE '%permanente%' THEN 2
                    WHEN temporada_orden.fec_ini_tem IS NOT NULL
                        AND temporada_orden.fec_ini_tem > ? THEN 3
                    WHEN temporada_orden.fec_fin_tem IS NOT NULL
                        AND temporada_orden.fec_fin_tem < ? THEN 4
                    ELSE 5
                END
                ",
                [$today, $today, $today, $today]
            )
            ->orderByRaw('COALESCE(temporada_orden.ord_tem, 999999) asc')
            ->orderByDesc('prendas.fec_pre')
            ->orderByDesc('prendas.updated_at')
            ->orderByDesc('prendas.created_at')
            ->orderByDesc('prendas.id_prenda');
    }

    private function getProducts(Request $request, string $collection): LengthAwarePaginator
    {
        $filters = $this->initialFilters($request);
        $query = $this->scopedQuery($request, $collection, true);
        $variantFilters = $this->variantFilters($filters);
        $hasVariantFilter = $variantFilters['sizes'] || $variantFilters['colors'] || $variantFilters['locations'];

        if ($hasVariantFilter) {
            $query->whereHas('variantes', fn (Builder $variant) => $this->applyVariantFilters(
                $variant->where('est_prv', true), $variantFilters
            ));
        }

        $stockConstraint = fn (Builder $variant) => $this->applyVariantFilters(
            $variant->where('est_prv', true)
                ->whereHas('inventario', fn (Builder $inventory) => $inventory->where('cdi_inv', '>', 0)),
            $variantFilters
        );

        if ($filters['hideSoldOut']) {
            $query->whereHas('variantes', $stockConstraint);
        }
        if ($filters['availability'] === 'Agotado') {
            $query->whereDoesntHave('variantes', $stockConstraint);
        } elseif (in_array($filters['availability'], ['Disponible', 'Últimas piezas'], true)) {
            [$stockSql, $stockBindings] = $this->stockSumSql($variantFilters);
            $operator = $filters['availability'] === 'Disponible' ? '> 3' : 'BETWEEN 1 AND 3';
            $query->whereRaw("({$stockSql}) {$operator}", $stockBindings);
        }

        [$priceSql, $priceBindings] = $this->minimumPriceSql($variantFilters);
        if (isset(self::PRICES[$filters['price']])) {
            [$min, $max] = self::PRICES[$filters['price']];
            $query->whereRaw("COALESCE(({$priceSql}), prendas.prb_pre) >= ?", [...$priceBindings, $min]);
            if ($max !== null) {
                $query->whereRaw("COALESCE(({$priceSql}), prendas.prb_pre) <= ?", [...$priceBindings, $max]);
            }
        }

        $sort = $this->initialSort($request, $collection);
        $available = $this->matchingVariantSubquery($variantFilters, true)
            ->where('filter_inventory.cdi_inv', '>', 0)->selectRaw('1')->limit(1);
        // The availability tier precedes commercial order in SQL, including LIMIT/OFFSET.
        $query->orderByRaw(
            'CASE WHEN EXISTS (' . $available->toSql() . ') THEN 0 ELSE 1 END',
            $available->getBindings()
        );

        if ($sort === 'recommended') {
            $this->applySeasonOrdering($query);
        } elseif ($sort === 'newest') {
            $query->select('prendas.*')
                ->orderByDesc('prendas.fec_pre')->orderByDesc('prendas.created_at')->orderByDesc('prendas.id_prenda');
        } else {
            $query->select('prendas.*')
                ->orderByRaw("COALESCE(({$priceSql}), prendas.prb_pre) " . ($sort === 'price-asc' ? 'ASC' : 'DESC'), $priceBindings)
                ->orderByDesc('prendas.id_prenda');
        }

        $query = ProductMapper::withRelations($query);
        if ($hasVariantFilter) {
            $query->with(['variantes' => fn ($variant) => $this->applyVariantFilters(
                $variant->where('est_prv', true), $variantFilters
            )]);
        }

        return $query->paginate(12)->withQueryString();
    }

    private function scopedQuery(Request $request, string $collection, bool $applyFilters): Builder
    {
        $query = Prenda::query()->where('prendas.est_pre', true);
        $gender = $this->firstQueryValue($request, 'genero')
            ?? match ($collection) { 'mujer' => 'mujer', 'hombre' => 'hombre', default => null };

        if ($gender !== null) {
            $this->applyGenderFilter($query, $gender);
        }
        if (in_array($collection, ['mujer', 'hombre'], true)) {
            $query->whereHas('categoria', fn (Builder $category) => $category->where('nom_cat', '!=', 'Accesorios'));
        } elseif ($collection === 'accesorios') {
            $query->whereHas('categoria', fn (Builder $category) => $category->where('nom_cat', 'Accesorios'));
        }

        if ($applyFilters) {
            $filters = $this->initialFilters($request);
            if ($filters['categories']) {
                $query->whereHas('categoria', fn (Builder $category) => $category->whereIn('nom_cat', $filters['categories']));
            }
            if ($filters['brands']) {
                $query->whereHas('marca', fn (Builder $brand) => $brand->whereIn('nom_mar', $filters['brands']));
            }
            $this->applySeasonFilter($query, $filters['styles']);
        }

        return $query;
    }

    private function whereAvailable(Builder $query, array $variantFilters): Builder
    {
        return $query->whereHas('variantes', fn (Builder $variant) => $this->applyVariantFilters(
            $variant->where('est_prv', true)
                ->whereHas('inventario', fn (Builder $inventory) => $inventory->where('cdi_inv', '>', 0)),
            $variantFilters
        ));
    }

    private function variantFilters(array $filters): array
    {
        return [
            'sizes' => $filters['sizes'],
            'colors' => $filters['colors'],
            'locations' => $filters['locations'],
        ];
    }

    private function applyVariantFilters($variant, array $filters)
    {
        if ($filters['sizes'] ?? []) {
            $variant->whereHas('talla', fn (Builder $size) => $size->whereIn('nom_tal', $filters['sizes']));
        }
        if ($filters['colors'] ?? []) {
            $variant->whereHas('color', fn (Builder $color) => $color
                ->where(fn (Builder $match) => $match
                    ->whereIn('fam_col', $filters['colors'])
                    ->orWhereIn('nom_col', $filters['colors'])));
        }
        if ($filters['locations'] ?? []) {
            $variant->whereHas('inventario', fn (Builder $inventory) => $inventory->whereIn('ubi_inv', $filters['locations']));
        }

        return $variant;
    }

    private function matchingVariantSubquery(array $filters, bool $withInventory): QueryBuilder
    {
        $query = DB::table('prenda_variantes as filter_variant')
            ->whereColumn('filter_variant.cod_prendas_prv', 'prendas.id_prenda')
            ->where('filter_variant.est_prv', true);

        if ($withInventory || ($filters['locations'] ?? [])) {
            $query->join('inventarios as filter_inventory', 'filter_inventory.cod_prenda_variantes_inv', '=', 'filter_variant.id_prenda_variante');
        }
        if ($filters['sizes'] ?? []) {
            $query->join('tallas as filter_size', 'filter_size.id_talla', '=', 'filter_variant.cod_tallas_prv')
                ->whereIn('filter_size.nom_tal', $filters['sizes']);
        }
        if ($filters['colors'] ?? []) {
            $query->join('colores as filter_color', 'filter_color.id_color', '=', 'filter_variant.cod_colores_prv')
                ->where(fn (QueryBuilder $match) => $match
                    ->whereIn('filter_color.fam_col', $filters['colors'])
                    ->orWhereIn('filter_color.nom_col', $filters['colors']));
        }
        if ($filters['locations'] ?? []) {
            $query->whereIn('filter_inventory.ubi_inv', $filters['locations']);
        }

        return $query;
    }

    private function minimumPriceSql(array $filters): array
    {
        $query = $this->matchingVariantSubquery($filters, false)->selectRaw('MIN(filter_variant.pve_prv)');

        return [$query->toSql(), $query->getBindings()];
    }

    private function stockSumSql(array $filters): array
    {
        $query = $this->matchingVariantSubquery($filters, true)
            ->selectRaw('COALESCE(SUM(GREATEST(filter_inventory.cdi_inv, 0)), 0)');

        return [$query->toSql(), $query->getBindings()];
    }

    private function applyGenderFilter(Builder $query, string $gender): void
    {
        match (strtolower(trim($gender))) {
            'mujer' => $query->whereIn('prendas.gen_pre', self::WOMEN_GENDERS),
            'hombre' => $query->whereIn('prendas.gen_pre', self::MEN_GENDERS),
            'unisex' => $query->where('prendas.gen_pre', 'unisex'),
            default => null,
        };
    }

    private function applySeasonFilter(Builder $query, array $selectedSeasons): void
    {
        if (! $selectedSeasons) return;

        $permanent = collect($selectedSeasons)->contains(fn ($value) => $this->isPermanentSeasonValue($value));
        $ordinary = collect($selectedSeasons)->reject(fn ($value) => $this->isPermanentSeasonValue($value))->values()->all();

        $query->whereHas('temporada', function (Builder $season) use ($permanent, $ordinary) {
            $season->where(function (Builder $match) use ($permanent, $ordinary) {
                if ($ordinary) {
                    $match->whereIn('nom_tem', $ordinary)->orWhereIn('cod_tem', $ordinary);
                }
                if ($permanent) {
                    $method = $ordinary ? 'orWhere' : 'where';
                    $match->{$method}(fn (Builder $permanentQuery) => $this->wherePermanentSeason($permanentQuery));
                }
            });
        });
    }

    private function wherePermanentSeason(Builder $query): void
    {
        $query->whereRaw("LOWER(COALESCE(cod_tem, '')) = 'per'")
            ->orWhereRaw("LOWER(COALESCE(nom_tem, '')) LIKE '%permanente%'");
    }

    private function isPermanentSeasonValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return $normalized === 'per' || str_contains($normalized, 'permanente');
    }

    private function firstQueryValue(Request $request, string $key): ?string
    {
        return $this->queryValues($request, $key)[0] ?? null;
    }

    private function queryValues(Request $request, string|array $keys): array
    {
        $values = [];
        foreach ((array) $keys as $key) {
            $raw = $request->query($key);
            if ($raw === null || $raw === '') continue;
            $values = array_merge($values, is_array($raw) ? $raw : [$raw]);
        }

        return collect($values)->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))->unique()->values()->all();
    }
}
