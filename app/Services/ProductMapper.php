<?php

namespace App\Services;

use App\Models\Favorito;
use App\Models\Prenda;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ProductMapper
{
    /**
     * Map a single Prenda model to the ShopProduct shape expected by the frontend.
     * The prenda MUST be loaded with: marca, categoria, temporada, imagenes, variantes.color, variantes.talla, variantes.inventario
     */
    public static function toShopProduct(Prenda $prenda): array
    {
        $variantesActivas = $prenda->variantes
            ->filter(fn($v) => $v->est_prv);

        $stockDisponible = fn($v) => max(0, (int) ($v->inventario?->cdi_inv ?? 0));

        $precioMin = $variantesActivas->min('pve_prv') ?? (float) $prenda->prb_pre;

        $stockTotal = $variantesActivas->sum($stockDisponible);

        if ($stockTotal === 0) {
            $availability = 'Agotado';
        } elseif ($stockTotal <= 3) {
            $availability = 'Últimas piezas';
        } else {
            $availability = 'Disponible';
        }

        $sizes = $variantesActivas
            ->filter(fn($v) => $v->talla !== null && $stockDisponible($v) > 0)
            ->sortBy(fn($v) => $v->talla?->ord_tal ?? 0)
            ->map(fn($v) => $v->talla->nom_tal)
            ->unique()
            ->values()
            ->toArray();

        $variantesDisponibles = $variantesActivas
            ->filter(fn($v) => $stockDisponible($v) > 0);

        $colors = $variantesDisponibles
            ->filter(fn($v) => $v->color !== null)
            ->map(fn($v) => [
                'name' => $v->color->nom_col,
                'family' => $v->color->fam_col,
                'hex' => $v->color->hex_col,
            ])
            ->unique(fn($color) => $color['name'].'|'.($color['family'] ?? '').'|'.($color['hex'] ?? ''))
            ->values()
            ->toArray();

        $locations = $variantesDisponibles
            ->map(fn($v) => trim((string) ($v->inventario?->ubi_inv ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $firstVariant = $variantesDisponibles->first() ?? $variantesActivas->first();
        $firstColor = $firstVariant?->color?->nom_col ?? '';

        $imagenesOrdenadas = $prenda->imagenes->sortBy([
            ['pri_igp', 'desc'],
            ['ord_igp', 'asc'],
        ]);
        $imagenPrincipal = $imagenesOrdenadas->first()?->url_igp ?? '';
        $gallery = $imagenesOrdenadas->pluck('url_igp')->filter()->values()->toArray();

        if (empty($gallery) && $imagenPrincipal !== '') {
            $gallery = [$imagenPrincipal];
        }

        $categoryName = trim((string) ($prenda->categoria?->nom_cat ?? ''));
        $department = strcasecmp($categoryName, 'Accesorios') === 0
            ? 'accesorios'
            : match (strtolower($prenda->gen_pre ?? '')) {
                'hombre' => 'hombre',
                'mujer' => 'mujer',
                'unisex' => 'unisex',
                default => 'accesorios',
            };

        $isNew = $prenda->fec_pre
            ? $prenda->fec_pre->isAfter(now()->subDays(45))
            : ($prenda->created_at?->isAfter(now()->subDays(45)) ?? false);

        $brandName = trim((string) ($prenda->marca?->nom_mar ?? ''));
        $seasonName = trim((string) ($prenda->temporada?->nom_tem ?? ''));
        $seasonCode = trim((string) ($prenda->temporada?->cod_tem ?? ''));
        $gender = strtolower(trim((string) ($prenda->gen_pre ?? '')));
        $isPermanent = self::isPermanentSeason($seasonCode, $seasonName);
        $isCurrentSeason = self::isCurrentSeason($prenda);
        $line = collect([$brandName, $seasonName])->filter()->join(' / ');

        return [
            'id' => $prenda->id_prenda,
            'slug' => (string) $prenda->id_prenda,
            'name' => $prenda->nom_pre,
            'price' => (float) $precioMin,
            'line' => $line,
            'brand' => $brandName,
            'department' => $department,
            'gender' => $gender,
            'isUnisex' => $gender === 'unisex',
            'category' => $categoryName,
            'style' => $seasonName,
            'seasonCode' => $seasonCode,
            'seasonName' => $seasonName,
            'isCurrentSeason' => $isCurrentSeason,
            'isPermanent' => $isPermanent,
            'color' => $firstColor,
            'colors' => $colors,
            'locations' => $locations,
            'totalStock' => (int) $stockTotal,
            'availability' => $availability,
            'sizes' => $sizes,
            'isNew' => $isNew,
            'isFavorite' => false,
            'rank' => 0,
            'image' => $imagenPrincipal,
            'gallery' => $gallery,
            'description' => self::resolveDescription($prenda),
        ];
    }

    /**
     * Map a collection of Prenda models to ShopProduct array preserving query order.
     */
    public static function toShopProductArray(Collection|SupportCollection $prendas): array
    {
        return $prendas
            ->values()
            ->map(function ($prenda, $index) {
                $product = self::toShopProduct($prenda);
                $product['rank'] = $index + 1;

                return $product;
            })
            ->values()
            ->toArray();
    }

    public static function attachFavoriteState(array $products, ?Authenticatable $user): array
    {
        if (!$user || empty($products)) {
            return array_map(function (array $product) {
                $product['isFavorite'] = $product['isFavorite'] ?? false;

                return $product;
            }, $products);
        }

        $favoriteIds = Favorito::where('cod_usuarios_fav', $user->getAuthIdentifier())
            ->whereIn('cod_prendas_fav', array_column($products, 'id'))
            ->pluck('cod_prendas_fav')
            ->all();

        $favoriteLookup = array_flip($favoriteIds);

        return array_map(function (array $product) use ($favoriteLookup) {
            $product['isFavorite'] = isset($favoriteLookup[$product['id']]);

            return $product;
        }, $products);
    }

    /**
     * Eager load all relations needed for mapping.
     */
    public static function withRelations($query)
    {
        return $query->with([
            'marca',
            'categoria',
            'temporada',
            'imagenes',
            'variantes' => fn($q) => $q->where('est_prv', true),
            'variantes.color',
            'variantes.talla',
            'variantes.inventario',
        ]);
    }

    /**
     * Build filter options from a collection of mapped products.
     */
    public static function filterOptions(array $products): array
    {
        $categories = collect($products)->pluck('category')->unique()->filter()->sort()->values()->toArray();
        $sizes = collect($products)->flatMap(fn($p) => $p['sizes'])->unique()->sort()->values()->toArray();
        $colors = collect($products)
            ->flatMap(function ($p) {
                $mappedColors = collect($p['colors'] ?? [])
                    ->map(fn($color) => $color['family'] ?? $color['name'] ?? null)
                    ->filter();

                return $mappedColors->isNotEmpty()
                    ? $mappedColors
                    : [($p['color'] ?? '')];
            })
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->toArray();
        $styles = collect($products)->pluck('style')->unique()->filter()->sort()->values()->toArray();
        $brands = collect($products)->pluck('brand')->unique()->filter()->sort()->values()->toArray();
        $locations = collect($products)->flatMap(fn($p) => $p['locations'] ?? [])->unique()->filter()->sort()->values()->toArray();

        return compact('categories', 'sizes', 'colors', 'styles', 'brands', 'locations');
    }

    private static function isCurrentSeason(Prenda $prenda): bool
    {
        $season = $prenda->temporada;

        if (! $season || ! $season->est_tem || ! $season->fec_ini_tem || ! $season->fec_fin_tem) {
            return false;
        }

        $today = now()->toDateString();

        return $season->fec_ini_tem->toDateString() <= $today
            && $season->fec_fin_tem->toDateString() >= $today;
    }

    private static function isPermanentSeason(string $seasonCode, string $seasonName): bool
    {
        return strtolower($seasonCode) === 'per'
            || str_contains(strtolower($seasonName), 'permanente');
    }

    private static function resolveDescription(Prenda $prenda): string
    {
        $description = trim((string) ($prenda->des_pre ?? ''));

        if (
            $description !== '' &&
            ! preg_match('/\bdemo\b|cat[aá]logo inicial/i', $description)
        ) {
            return $description;
        }

        $category = trim((string) ($prenda->categoria?->nom_cat ?? ''));
        $season = trim((string) ($prenda->temporada?->nom_tem ?? ''));
        $brand = trim((string) ($prenda->marca?->nom_mar ?? ''));

        $segments = array_filter([
            $category !== '' ? "Pieza de {$category}" : null,
            $season !== '' ? "en {$season}" : null,
            $brand !== '' ? "firmada por {$brand}" : null,
        ]);

        if (empty($segments)) {
            return '';
        }

        return ucfirst(implode(' ', $segments)).'.';
    }
}
