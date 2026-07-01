<?php

namespace App\Services\Vendedor;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Talla;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendedorInventoryQueryService
{
    private const LOW_STOCK_LIMIT = 2;

    public function dashboard(): array
    {
        $availableProducts = $this->productCollection()
            ->filter(fn (Prenda $product) => $this->productAvailable($product) > 0)
            ->count();

        $inventoryRecords = Inventario::query()->count();
        $stockAlerts = $this->alertQuery()->count();

        return [
            'metrics' => [
                [
                    'key' => 'available_products',
                    'label' => 'Prendas disponibles',
                    'value' => $this->number($availableProducts),
                    'note' => 'Prendas con stock disponible mayor a 0.',
                    'tone' => 'success',
                    'icon' => 'shirt',
                    'href' => '/vendedor/prendas?availability=disponible',
                ],
                [
                    'key' => 'inventory_records',
                    'label' => 'Existencias consultables',
                    'value' => $this->number($inventoryRecords),
                    'note' => 'Variantes con registro de inventario.',
                    'tone' => 'info',
                    'icon' => 'warehouse',
                    'href' => '/vendedor/existencias',
                ],
                [
                    'key' => 'stock_alerts',
                    'label' => 'Alertas de stock',
                    'value' => $this->number($stockAlerts),
                    'note' => 'Regla temporal: stock disponible menor o igual a ' . self::LOW_STOCK_LIMIT . '.',
                    'tone' => $stockAlerts > 0 ? 'danger' : 'success',
                    'icon' => 'alert',
                    'href' => '/vendedor/alertas-stock',
                ],
                [
                    'key' => 'sales_today',
                    'label' => 'Ventas de hoy',
                    'value' => '0',
                    'note' => 'Pendiente para una fase posterior.',
                    'tone' => 'accent',
                    'icon' => 'receipt',
                    'href' => '/vendedor/ventas',
                ],
                [
                    'key' => 'sold_today',
                    'label' => 'Total vendido hoy',
                    'value' => 'Bs 0.00',
                    'note' => 'Calculo pendiente sin tocar ventas.',
                    'tone' => 'success',
                    'icon' => 'banknote',
                    'href' => '/vendedor/ventas',
                ],
                [
                    'key' => 'active_reservations',
                    'label' => 'Reservas activas',
                    'value' => '0',
                    'note' => 'Pendiente para una fase posterior.',
                    'tone' => 'info',
                    'icon' => 'calendar',
                    'href' => '/vendedor/reservas',
                ],
                [
                    'key' => 'pending_payments',
                    'label' => 'Pagos pendientes',
                    'value' => '0',
                    'note' => 'Revision de comprobantes pendiente.',
                    'tone' => 'warning',
                    'icon' => 'credit-card',
                    'href' => '/vendedor/pagos',
                ],
                [
                    'key' => 'pending_pickups',
                    'label' => 'Recojos pendientes',
                    'value' => '0',
                    'note' => 'Recojos y entregas quedan para otra fase.',
                    'tone' => 'neutral',
                    'icon' => 'truck',
                    'href' => '/vendedor/recojos',
                ],
                [
                    'key' => 'damaged_reports_pending',
                    'label' => 'Reportes de danos',
                    'value' => '0',
                    'note' => 'Pendiente para reportes de prendas danadas.',
                    'tone' => 'neutral',
                    'icon' => 'shield-alert',
                    'href' => '/vendedor/prendas-daniadas',
                ],
            ],
        ];
    }

    public function products(Request $request): array
    {
        $products = $this->productCollection()
            ->filter(fn (Prenda $product) => $this->productMatches($product, $request))
            ->values();

        return [
            'products' => $products->map(fn (Prenda $product) => $this->mapProductRow($product))->all(),
            'summary' => [
                ['label' => 'Prendas', 'value' => $this->number($products->count()), 'tone' => 'accent'],
                ['label' => 'Disponibles', 'value' => $this->number($products->filter(fn (Prenda $product) => $this->productAvailable($product) > 0)->count()), 'tone' => 'success'],
                ['label' => 'Sin stock', 'value' => $this->number($products->filter(fn (Prenda $product) => $this->productAvailable($product) <= 0)->count()), 'tone' => 'danger'],
                ['label' => 'Variantes visibles', 'value' => $this->number($products->sum(fn (Prenda $product) => $this->activeVariants($product)->count())), 'tone' => 'info'],
            ],
            'filters' => $this->productFilters($request),
            'options' => $this->filterOptions(),
        ];
    }

    public function productDetail(Prenda $product): array
    {
        $product->loadMissing([
            'categoria',
            'marca',
            'temporada',
            'imagenes',
            'variantes.color',
            'variantes.talla',
            'variantes.inventario',
            'variantes.imagenes',
        ]);

        $variants = $this->activeVariants($product);
        $gallery = $this->productGallery($product);

        return [
            'product' => [
                'id' => (int) $product->id_prenda,
                'name' => (string) $product->nom_pre,
                'description' => trim((string) ($product->des_pre ?? '')) ?: 'Sin descripción registrada.',
                'image' => $gallery[0] ?? null,
                'gallery' => $gallery,
                'category' => $product->categoria?->nom_cat ?? 'Sin categoria',
                'brand' => $product->marca?->nom_mar ?? 'Sin marca',
                'season' => $product->temporada?->nom_tem ?? 'Sin temporada',
                'gender' => $this->display($product->gen_pre, 'Sin genero'),
                'price' => $this->money((float) $product->prb_pre),
                'status' => $product->est_pre ? ['label' => 'Activa', 'tone' => 'success'] : ['label' => 'Inactiva', 'tone' => 'neutral'],
                'available' => $this->number($this->productAvailable($product)),
                'committed' => $this->number($this->productCommitted($product)),
                'colors' => $this->variantNames($variants, 'color.nom_col'),
                'sizes' => $this->variantNames($variants, 'talla.nom_tal'),
                'locations' => $this->variantLocations($variants),
            ],
            'variants' => $variants->map(fn (PrendaVariante $variant) => $this->mapVariantDetail($variant))->values()->all(),
        ];
    }

    public function stock(Request $request): array
    {
        $records = $this->inventoryCollection()
            ->filter(fn (Inventario $inventory) => $this->inventoryMatches($inventory, $request))
            ->values();

        return $this->inventoryPayload($records, $request);
    }

    public function stockAlerts(Request $request): array
    {
        $records = $this->inventoryCollection()
            ->filter(fn (Inventario $inventory) => (int) $inventory->cdi_inv <= self::LOW_STOCK_LIMIT)
            ->filter(fn (Inventario $inventory) => $this->inventoryMatches($inventory, $request))
            ->sortBy(fn (Inventario $inventory) => str_pad((string) max(0, (int) $inventory->cdi_inv), 4, '0', STR_PAD_LEFT) . '-' . ($inventory->variante?->prenda?->nom_pre ?? ''))
            ->values();

        $payload = $this->inventoryPayload($records, $request);
        $payload['alerts'] = [
            'limit' => self::LOW_STOCK_LIMIT,
            'withoutStock' => $records->filter(fn (Inventario $inventory) => (int) $inventory->cdi_inv <= 0)->count(),
            'critical' => $records->filter(fn (Inventario $inventory) => (int) $inventory->cdi_inv === 1)->count(),
            'low' => $records->filter(fn (Inventario $inventory) => (int) $inventory->cdi_inv === 2)->count(),
        ];

        return $payload;
    }

    private function inventoryPayload(Collection $records, Request $request): array
    {
        $productLabel = null;
        $productId = $request->input('product');

        if (filled($productId)) {
            $productLabel = Prenda::query()
                ->where('id_prenda', $productId)
                ->value('nom_pre');
        }

        return [
            'items' => $records->map(fn (Inventario $inventory) => $this->mapInventoryRow($inventory))->all(),
            'summary' => [
                ['label' => 'Registros', 'value' => $this->number($records->count()), 'tone' => 'accent'],
                ['label' => 'Stock disponible', 'value' => $this->number((int) $records->sum('cdi_inv')), 'tone' => 'success'],
                ['label' => 'Stock comprometido', 'value' => $this->number((int) $records->sum('cre_inv')), 'tone' => 'warning'],
                ['label' => 'Sin stock', 'value' => $this->number($records->filter(fn (Inventario $inventory) => (int) $inventory->cdi_inv <= 0)->count()), 'tone' => 'danger'],
            ],
            'filters' => $this->inventoryFilters($request, $productLabel),
            'options' => $this->filterOptions(),
        ];
    }

    private function productCollection(): Collection
    {
        return Prenda::query()
            ->with([
                'categoria',
                'marca',
                'temporada',
                'imagenes',
                'variantes.color',
                'variantes.talla',
                'variantes.inventario',
            ])
            ->orderBy('nom_pre')
            ->get();
    }

    private function inventoryCollection(): Collection
    {
        return Inventario::query()
            ->with([
                'variante.prenda.categoria',
                'variante.prenda.marca',
                'variante.prenda.temporada',
                'variante.prenda.imagenes',
                'variante.color',
                'variante.talla',
            ])
            ->orderBy('ubi_inv')
            ->orderByDesc('cdi_inv')
            ->get();
    }

    private function alertQuery()
    {
        return Inventario::query()->where('cdi_inv', '<=', self::LOW_STOCK_LIMIT);
    }

    private function productMatches(Prenda $product, Request $request): bool
    {
        if (! $this->matchesOption($request->input('category'), $product->cod_categorias_pre)) {
            return false;
        }

        if (! $this->matchesOption($request->input('brand'), $product->cod_marcas_pre)) {
            return false;
        }

        if (! $this->matchesStatus($request->input('status'), $product->est_pre)) {
            return false;
        }

        $variants = $this->activeVariants($product);

        if (filled($request->input('color')) && ! $variants->contains(fn (PrendaVariante $variant) => $this->matchesOption($request->input('color'), $variant->cod_colores_prv))) {
            return false;
        }

        if (filled($request->input('size')) && ! $variants->contains(fn (PrendaVariante $variant) => $this->matchesOption($request->input('size'), $variant->cod_tallas_prv))) {
            return false;
        }

        if (filled($request->input('location')) && ! $this->variantLocations($variants)->contains(fn (string $location) => $this->sameText($location, (string) $request->input('location')))) {
            return false;
        }

        if (! $this->matchesAvailability($request->input('availability'), $this->productAvailable($product))) {
            return false;
        }

        $search = trim($request->string('search')->toString());

        return $search === '' || $this->containsSearch($this->productHaystack($product), $search);
    }

    private function inventoryMatches(Inventario $inventory, Request $request): bool
    {
        $variant = $inventory->variante;
        $product = $variant?->prenda;

        if (! $variant || ! $product) {
            return false;
        }

        if (! $this->matchesOption($request->input('product'), $product->id_prenda)) {
            return false;
        }

        if (! $this->matchesOption($request->input('color'), $variant->cod_colores_prv)) {
            return false;
        }

        if (! $this->matchesOption($request->input('size'), $variant->cod_tallas_prv)) {
            return false;
        }

        if (filled($request->input('location')) && ! $this->sameText((string) $inventory->ubi_inv, (string) $request->input('location'))) {
            return false;
        }

        if (! $this->matchesInventoryStatus($request->input('status'), $product->est_pre && $variant->est_prv)) {
            return false;
        }

        if (! $this->matchesStockFilter($request->input('stock'), (int) $inventory->cdi_inv, (int) $inventory->cre_inv)) {
            return false;
        }

        $search = trim($request->string('search')->toString());

        return $search === '' || $this->containsSearch($this->inventoryHaystack($inventory), $search);
    }

    private function mapProductRow(Prenda $product): array
    {
        $variants = $this->activeVariants($product);
        $available = $this->productAvailable($product);

        return [
            'id' => (int) $product->id_prenda,
            'primary' => (string) $product->nom_pre,
            'secondary' => trim(implode(' / ', array_filter([
                $product->categoria?->nom_cat,
                $product->marca?->nom_mar,
            ]))),
            'image' => $this->primaryImage($product),
            'category' => $product->categoria?->nom_cat ?? 'Sin categoria',
            'brand' => $product->marca?->nom_mar ?? 'Sin marca',
            'season' => $product->temporada?->nom_tem ?? 'Sin temporada',
            'gender' => $this->display($product->gen_pre, 'Sin genero'),
            'price' => $this->money((float) $product->prb_pre),
            'status' => $product->est_pre ? ['label' => 'Activa', 'tone' => 'success'] : ['label' => 'Inactiva', 'tone' => 'neutral'],
            'availability' => $this->availabilityBadge($available),
            'available' => $this->number($available),
            'variantsCount' => $this->number($variants->count()),
            'availableVariants' => $this->number($variants->filter(fn (PrendaVariante $variant) => (int) ($variant->inventario?->cdi_inv ?? 0) > 0)->count()),
            'colors' => $this->variantNames($variants, 'color.nom_col')->join(', ') ?: 'Sin color',
            'sizes' => $this->variantNames($variants, 'talla.nom_tal')->join(', ') ?: 'Sin talla',
            'showHref' => '/vendedor/prendas/' . $product->id_prenda,
            'stockHref' => '/vendedor/existencias?product=' . $product->id_prenda,
        ];
    }

    private function mapInventoryRow(Inventario $inventory): array
    {
        $variant = $inventory->variante;
        $product = $variant?->prenda;
        $available = (int) $inventory->cdi_inv;

        return [
            'id' => (int) $inventory->id_inventario,
            'primary' => $product?->nom_pre ?? 'Prenda sin nombre',
            'secondary' => $variant?->sku_prv ?: 'Sin SKU',
            'image' => $product ? $this->primaryImage($product) : null,
            'productId' => $product?->id_prenda,
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?? 'Sin color',
            'size' => $variant?->talla?->nom_tal ?? 'Sin talla',
            'price' => $this->money((float) ($variant?->pve_prv ?? $product?->prb_pre ?? 0)),
            'available' => $this->number($available),
            'committed' => $this->number((int) $inventory->cre_inv),
            'location' => $this->display($inventory->ubi_inv, 'Sin ubicación'),
            'status' => ($product?->est_pre && $variant?->est_prv)
                ? ['label' => 'Activa', 'tone' => 'success']
                : ['label' => 'Inactiva', 'tone' => 'neutral'],
            'stockLevel' => $this->stockLevelBadge($available),
            'showHref' => $product ? '/vendedor/prendas/' . $product->id_prenda : null,
        ];
    }

    private function mapVariantDetail(PrendaVariante $variant): array
    {
        $inventory = $variant->inventario;
        $available = (int) ($inventory?->cdi_inv ?? 0);

        return [
            'id' => (int) $variant->id_prenda_variante,
            'primary' => $variant->sku_prv ?: 'Sin SKU',
            'secondary' => trim(implode(' / ', array_filter([
                $variant->color?->nom_col,
                $variant->talla?->nom_tal,
            ]))),
            'image' => $this->variantImage($variant),
            'sku' => $variant->sku_prv ?: 'Sin SKU',
            'color' => $variant->color?->nom_col ?? 'Sin color',
            'size' => $variant->talla?->nom_tal ?? 'Sin talla',
            'price' => $this->money((float) $variant->pve_prv),
            'available' => $this->number($available),
            'committed' => $this->number((int) ($inventory?->cre_inv ?? 0)),
            'location' => $this->display($inventory?->ubi_inv, 'Sin ubicación'),
            'status' => $variant->est_prv ? ['label' => 'Activa', 'tone' => 'success'] : ['label' => 'Inactiva', 'tone' => 'neutral'],
            'stockLevel' => $this->stockLevelBadge($available),
        ];
    }

    private function activeVariants(Prenda $product): Collection
    {
        return collect($product->variantes ?? [])
            ->filter(fn (PrendaVariante $variant) => (bool) $variant->est_prv)
            ->values();
    }

    private function productAvailable(Prenda $product): int
    {
        return (int) $this->activeVariants($product)
            ->sum(fn (PrendaVariante $variant) => max(0, (int) ($variant->inventario?->cdi_inv ?? 0)));
    }

    private function productCommitted(Prenda $product): int
    {
        return (int) $this->activeVariants($product)
            ->sum(fn (PrendaVariante $variant) => max(0, (int) ($variant->inventario?->cre_inv ?? 0)));
    }

    private function variantNames(Collection $variants, string $path): Collection
    {
        return $variants
            ->map(fn (PrendaVariante $variant) => data_get($variant, $path))
            ->filter()
            ->unique()
            ->values();
    }

    private function variantLocations(Collection $variants): Collection
    {
        return $variants
            ->map(fn (PrendaVariante $variant) => trim((string) ($variant->inventario?->ubi_inv ?? '')))
            ->filter()
            ->unique()
            ->values();
    }

    private function productHaystack(Prenda $product): string
    {
        $variants = $this->activeVariants($product);

        return trim(implode(' ', array_filter([
            $product->nom_pre,
            $product->des_pre,
            $product->gen_pre,
            $product->categoria?->nom_cat,
            $product->marca?->nom_mar,
            $product->temporada?->nom_tem,
            $variants->pluck('sku_prv')->join(' '),
            $this->variantNames($variants, 'color.nom_col')->join(' '),
            $this->variantNames($variants, 'talla.nom_tal')->join(' '),
            $this->variantLocations($variants)->join(' '),
        ])));
    }

    private function inventoryHaystack(Inventario $inventory): string
    {
        $variant = $inventory->variante;
        $product = $variant?->prenda;

        return trim(implode(' ', array_filter([
            $product?->nom_pre,
            $product?->categoria?->nom_cat,
            $product?->marca?->nom_mar,
            $variant?->sku_prv,
            $variant?->color?->nom_col,
            $variant?->talla?->nom_tal,
            $inventory->ubi_inv,
        ])));
    }

    private function filterOptions(): array
    {
        return [
            'categories' => Categoria::query()
                ->where('est_cat', true)
                ->orderBy('nom_cat')
                ->get(['id_categoria', 'nom_cat'])
                ->map(fn (Categoria $category) => ['value' => (string) $category->id_categoria, 'label' => $category->nom_cat])
                ->all(),
            'brands' => Marca::query()
                ->where('est_mar', true)
                ->orderBy('nom_mar')
                ->get(['id_marca', 'nom_mar'])
                ->map(fn (Marca $brand) => ['value' => (string) $brand->id_marca, 'label' => $brand->nom_mar])
                ->all(),
            'colors' => Color::query()
                ->where('est_col', true)
                ->orderBy('ord_col')
                ->orderBy('nom_col')
                ->get(['id_color', 'nom_col'])
                ->map(fn (Color $color) => ['value' => (string) $color->id_color, 'label' => $color->nom_col])
                ->all(),
            'sizes' => Talla::query()
                ->where('est_tal', true)
                ->orderBy('ord_tal')
                ->orderBy('nom_tal')
                ->get(['id_talla', 'nom_tal'])
                ->map(fn (Talla $size) => ['value' => (string) $size->id_talla, 'label' => $size->nom_tal])
                ->all(),
            'locations' => Inventario::query()
                ->whereNotNull('ubi_inv')
                ->select('ubi_inv')
                ->distinct()
                ->orderBy('ubi_inv')
                ->pluck('ubi_inv')
                ->map(fn ($location) => trim((string) $location))
                ->filter()
                ->unique()
                ->values()
                ->map(fn (string $location) => ['value' => $location, 'label' => $location])
                ->all(),
            'availability' => [
                ['value' => 'disponible', 'label' => 'Disponible'],
                ['value' => 'bajo', 'label' => 'Bajo stock'],
                ['value' => 'agotado', 'label' => 'Sin stock'],
            ],
            'stock' => [
                ['value' => 'disponible', 'label' => 'Disponible'],
                ['value' => 'bajo', 'label' => 'Bajo stock'],
                ['value' => 'agotado', 'label' => 'Sin stock'],
                ['value' => 'comprometido', 'label' => 'Con comprometido'],
            ],
            'statuses' => [
                ['value' => 'active', 'label' => 'Activo'],
                ['value' => 'inactive', 'label' => 'Inactivo'],
            ],
        ];
    }

    private function productFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'category' => $request->string('category')->toString(),
            'brand' => $request->string('brand')->toString(),
            'color' => $request->string('color')->toString(),
            'size' => $request->string('size')->toString(),
            'availability' => $request->string('availability')->toString(),
            'location' => $request->string('location')->toString(),
            'status' => $request->string('status')->toString(),
        ];
    }

    private function inventoryFilters(Request $request, ?string $productLabel = null): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'product' => $request->string('product')->toString(),
            'productLabel' => $productLabel,
            'color' => $request->string('color')->toString(),
            'size' => $request->string('size')->toString(),
            'location' => $request->string('location')->toString(),
            'stock' => $request->string('stock')->toString(),
            'status' => $request->string('status')->toString(),
        ];
    }

    private function primaryImage(Prenda $product): ?string
    {
        $image = collect($product->imagenes ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->first();

        return $this->imageUrl($image?->url_igp, $image?->rut_igp);
    }

    private function variantImage(PrendaVariante $variant): ?string
    {
        $image = collect($variant->imagenes ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->first();

        return $this->imageUrl($image?->url_igp, $image?->rut_igp)
            ?? ($variant->prenda ? $this->primaryImage($variant->prenda) : null);
    }

    private function productGallery(Prenda $product): array
    {
        return collect($product->imagenes ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->map(fn ($image) => $this->imageUrl($image->url_igp, $image->rut_igp))
            ->filter()
            ->values()
            ->all();
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

        return Storage::url($path);
    }

    private function availabilityBadge(int $available): array
    {
        if ($available <= 0) {
            return ['label' => 'Sin stock', 'tone' => 'danger'];
        }

        if ($available <= self::LOW_STOCK_LIMIT) {
            return ['label' => 'Bajo stock', 'tone' => 'warning'];
        }

        return ['label' => 'Disponible', 'tone' => 'success'];
    }

    private function stockLevelBadge(int $available): array
    {
        if ($available <= 0) {
            return ['label' => 'Sin stock', 'tone' => 'danger'];
        }

        if ($available === 1) {
            return ['label' => 'Critico', 'tone' => 'danger'];
        }

        if ($available === 2) {
            return ['label' => 'Bajo', 'tone' => 'warning'];
        }

        return ['label' => 'Disponible', 'tone' => 'success'];
    }

    private function matchesAvailability(mixed $selected, int $available): bool
    {
        if (blank($selected)) {
            return true;
        }

        return ($available <= 0 ? 'agotado' : ($available <= self::LOW_STOCK_LIMIT ? 'bajo' : 'disponible')) === (string) $selected;
    }

    private function matchesStockFilter(mixed $selected, int $available, int $committed): bool
    {
        if (blank($selected)) {
            return true;
        }

        if ($selected === 'comprometido') {
            return $committed > 0;
        }

        return $this->matchesAvailability($selected, $available);
    }

    private function matchesOption(mixed $selected, mixed $actual): bool
    {
        return blank($selected) || (string) $selected === (string) $actual;
    }

    private function matchesStatus(mixed $selected, bool $active): bool
    {
        return blank($selected)
            || ((string) $selected === 'active' && $active)
            || ((string) $selected === 'inactive' && ! $active);
    }

    private function matchesInventoryStatus(mixed $selected, bool $active): bool
    {
        return $this->matchesStatus($selected, $active);
    }

    private function sameText(string $left, string $right): bool
    {
        return mb_strtolower(trim($left), 'UTF-8') === mb_strtolower(trim($right), 'UTF-8');
    }

    private function containsSearch(string $haystack, string $needle): bool
    {
        return str_contains(
            mb_strtolower($haystack, 'UTF-8'),
            mb_strtolower($needle, 'UTF-8'),
        );
    }

    private function display(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text === '' ? $fallback : $text;
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
