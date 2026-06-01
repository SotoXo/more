<?php

namespace App\Services;

use App\Models\Prenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StorefrontCatalogService
{
    private const WOMEN_GENDERS = ['mujer', 'unisex'];
    private const MEN_GENDERS = ['hombre', 'unisex'];

    public function catalog(Request $request): Collection
    {
        return $this->getProducts($request);
    }

    public function women(Request $request): Collection
    {
        return $this->getProducts($request, 'mujer');
    }

    public function men(Request $request): Collection
    {
        return $this->getProducts($request, 'hombre');
    }

    public function accessories(Request $request): Collection
    {
        return $this->getProducts($request, null, function (Builder $query) {
            $query->whereHas('categoria', fn($category) => $category->where('nom_cat', 'Accesorios'));
        });
    }

    public function newIn(Request $request): Collection
    {
        return $this->getProducts($request);
    }

    public function initialFilters(Request $request): array
    {
        return [
            'categories' => $this->queryValues($request, 'categoria'),
            'sizes' => [],
            'colors' => [],
            'styles' => $this->queryValues($request, ['temporada', 'edicion']),
            'brands' => $this->queryValues($request, 'marca'),
            'locations' => $this->queryValues($request, ['ubicacion', 'sucursal']),
            'price' => 'all',
            'availability' => 'all',
        ];
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

    private function getProducts(Request $request, ?string $collectionGender = null, ?callable $scope = null): Collection
    {
        $query = $this->baseQuery();

        if ($scope !== null) {
            $scope($query);
        }

        $explicitGender = $this->firstQueryValue($request, 'genero');

        if ($explicitGender !== null) {
            $this->applyGenderFilter($query, $explicitGender);
        } elseif ($collectionGender !== null) {
            $this->applyGenderFilter($query, $collectionGender);
        }

        if ($collectionGender !== null) {
            $query->whereHas('categoria', fn($category) => $category->where('nom_cat', '!=', 'Accesorios'));
        }

        $this->applySeasonFilter($query, $this->queryValues($request, ['temporada', 'edicion']));
        $this->applySeasonOrdering($query);

        return $query->get();
    }

    private function baseQuery(): Builder
    {
        return ProductMapper::withRelations(
            Prenda::query()->where('est_pre', true)
        );
    }

    private function applyGenderFilter(Builder $query, string $gender): void
    {
        match (strtolower(trim($gender))) {
            'mujer' => $query->whereIn('gen_pre', self::WOMEN_GENDERS),
            'hombre' => $query->whereIn('gen_pre', self::MEN_GENDERS),
            'unisex' => $query->where('gen_pre', 'unisex'),
            default => null,
        };
    }

    private function applySeasonFilter(Builder $query, array $selectedSeasons): void
    {
        if (empty($selectedSeasons)) {
            return;
        }

        $permanentSelections = collect($selectedSeasons)
            ->filter(fn($value) => $this->isPermanentSeasonValue($value))
            ->values();
        $seasonSelections = collect($selectedSeasons)
            ->reject(fn($value) => $this->isPermanentSeasonValue($value))
            ->values();

        $query->whereHas('temporada', function (Builder $seasonQuery) use ($permanentSelections, $seasonSelections) {
            $seasonQuery->where(function (Builder $filter) use ($permanentSelections, $seasonSelections) {
                if ($seasonSelections->isNotEmpty()) {
                    $filter
                        ->whereIn('nom_tem', $seasonSelections->all())
                        ->orWhereIn('cod_tem', $seasonSelections->all())
                        ->orWhere(fn(Builder $permanent) => $this->wherePermanentSeason($permanent));

                    return;
                }

                if ($permanentSelections->isNotEmpty()) {
                    $this->wherePermanentSeason($filter);
                }
            });
        });
    }

    private function wherePermanentSeason(Builder $query): void
    {
        $query
            ->whereRaw("LOWER(COALESCE(cod_tem, '')) = 'per'")
            ->orWhereRaw("LOWER(COALESCE(nom_tem, '')) LIKE '%permanente%'");
    }

    private function isPermanentSeasonValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return $normalized === 'per' || str_contains($normalized, 'permanente');
    }

    private function firstQueryValue(Request $request, string $key): ?string
    {
        $values = $this->queryValues($request, $key);

        return $values[0] ?? null;
    }

    private function queryValues(Request $request, string|array $keys): array
    {
        $values = [];

        foreach ((array) $keys as $key) {
            $raw = $request->query($key);

            if ($raw === null || $raw === '') {
                continue;
            }

            $values = array_merge($values, is_array($raw) ? $raw : [$raw]);
        }

        return collect($values)
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }
}
