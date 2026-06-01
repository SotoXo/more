<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Prenda;
use App\Models\Temporada;
use App\Services\ProductMapper;
use App\Services\StorefrontCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    private const FALLBACK_IMAGES = [
        'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1600',
        'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1600',
        'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600',
        'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1600',
    ];

    public function __construct(private StorefrontCatalogService $catalogService)
    {
    }

    public function mujer(Request $request)
    {
        return $this->renderCollection('mujer', $this->catalogService->women($request), $request);
    }

    public function hombre(Request $request)
    {
        return $this->renderCollection('hombre', $this->catalogService->men($request), $request);
    }

    public function novedades(Request $request)
    {
        return $this->renderCollection('novedades', $this->catalogService->newIn($request), $request);
    }

    public function accesorios(Request $request)
    {
        return $this->renderCollection('accesorios', $this->catalogService->accessories($request), $request);
    }

    public function catalogo(Request $request)
    {
        return $this->renderCollection('catalogo', $this->catalogService->catalog($request), $request);
    }

    public function colecciones()
    {
        return $this->renderCuratedPage('collections');
    }

    public function editorial()
    {
        return $this->renderCuratedPage('editorial');
    }

    private function renderCollection(string $collectionKey, $prendas, Request $request)
    {
        $products = ProductMapper::toShopProductArray($prendas);
        $products = ProductMapper::attachFavoriteState($products, auth()->user());
        $filterOptions = ProductMapper::filterOptions($products);

        return Inertia::render('Shop/Collection', [
            'collection'    => $collectionKey,
            'products'      => $products,
            'filterOptions' => $filterOptions,
            'initialFilters' => $this->catalogService->initialFilters($request),
        ]);
    }

    private function renderCuratedPage(string $mode)
    {
        $featureCards = $mode === 'collections'
            ? $this->buildCollectionFeatureCards()
            : $this->buildEditorialFeatureCards();

        $momentCards = $mode === 'editorial'
            ? $this->buildEditorialMomentCards()
            : [];

        $navigationLinks = $mode === 'collections'
            ? $this->buildCollectionNavigationLinks()
            : $this->buildEditorialNavigationLinks();

        $railQuery = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
        );
        $this->catalogService->applySeasonOrdering($railQuery);

        if ($mode === 'editorial') {
            $railQuery->skip(3);
        }

        $railProducts = ProductMapper::toShopProductArray(
            $railQuery->limit(8)->get()
        );
        $railProducts = ProductMapper::attachFavoriteState($railProducts, auth()->user());

        $highlights = $mode === 'collections'
            ? collect($featureCards)->pluck('title')->take(4)->values()->all()
            : $this->buildEditorialHighlights();

        $railContent = $mode === 'collections'
            ? [
                'eyebrow' => 'Ahora en tienda',
                'title' => 'Piezas para empezar el recorrido',
                'description' => 'Una selección de Mati Boutique para revisar disponibilidad y reservar.',
            ]
            : [
                'eyebrow' => 'Selección Mati Boutique',
                'title' => 'Prendas para seguir el tono',
                'description' => 'Una edición de la tienda para seguir la historia desde el producto.',
            ];

        return Inertia::render('Shop/Editorial', [
            'mode' => $mode,
            'heroImage' => $featureCards[0]['image'] ?? $this->fallbackHeroImage($mode),
            'highlights' => $highlights,
            'navigationLinks' => $navigationLinks,
            'featureCards' => $featureCards,
            'momentCards' => $momentCards,
            'railProducts' => $railProducts,
            'railContent' => $railContent,
        ]);
    }

    private function buildCollectionNavigationLinks(): array
    {
        return [
            [
                'label' => 'Mujer',
                'href' => route('shop.women'),
                'meta' => $this->activeProductsCount(
                    fn($query) => $query
                        ->whereIn('gen_pre', ['mujer', 'unisex'])
                        ->whereHas('categoria', fn($category) => $category->where('nom_cat', '!=', 'Accesorios'))
                ) . ' piezas',
            ],
            [
                'label' => 'Hombre',
                'href' => route('shop.men'),
                'meta' => $this->activeProductsCount(
                    fn($query) => $query
                        ->whereIn('gen_pre', ['hombre', 'unisex'])
                        ->whereHas('categoria', fn($category) => $category->where('nom_cat', '!=', 'Accesorios'))
                ) . ' piezas',
            ],
            [
                'label' => 'Accesorios',
                'href' => route('shop.accessories'),
                'meta' => $this->activeProductsCount(
                    fn($query) => $query
                        ->whereHas('categoria', fn($category) => $category->where('nom_cat', 'Accesorios'))
                ) . ' piezas',
            ],
            [
                'label' => 'Novedades',
                'href' => route('shop.new-in'),
                'meta' => $this->activeProductsCount() . ' piezas',
            ],
        ];
    }

    private function buildEditorialNavigationLinks(): array
    {
        return Marca::where('est_mar', true)
            ->get(['id_marca', 'nom_mar'])
            ->map(function ($marca) {
                $count = $this->activeProductsCount(
                    fn($query) => $query->where('cod_marcas_pre', $marca->id_marca)
                );

                return [
                    'label' => $marca->nom_mar,
                    'href' => $this->catalogUrl(['marca' => $marca->nom_mar]),
                    'meta' => $count . ' piezas',
                    'count' => $count,
                ];
            })
            ->filter(fn($item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->take(4)
            ->values()
            ->map(fn($item) => [
                'label' => $item['label'],
                'href' => $item['href'],
                'meta' => $item['meta'],
            ])
            ->all();
    }

    private function buildCollectionFeatureCards(): array
    {
        return Categoria::where('est_cat', true)
            ->whereNull('cod_categoria_padre_cat')
            ->get(['id_categoria', 'nom_cat', 'des_cat'])
            ->map(function ($categoria, $index) {
                $count = $this->activeProductsCount(
                    fn($query) => $query->where('cod_categorias_pre', $categoria->id_categoria)
                );

                return [
                    'eyebrow' => $count . ' piezas',
                    'title' => $categoria->nom_cat,
                    'description' => $categoria->des_cat ?: 'Una entrada abierta para seguir recorriendo la boutique.',
                    'image' => $this->representativeImage(
                        Prenda::where('est_pre', true)->where('cod_categorias_pre', $categoria->id_categoria),
                        $index
                    ),
                    'href' => $this->catalogUrl(['categoria' => $categoria->nom_cat]),
                    'count' => $count,
                ];
            })
            ->filter(fn($item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->map(fn($item) => [
                'eyebrow' => $item['eyebrow'],
                'title' => $item['title'],
                'description' => $item['description'],
                'image' => $item['image'],
                'href' => $item['href'],
            ])
            ->all();
    }

    private function buildEditorialFeatureCards(): array
    {
        return Temporada::where('est_tem', true)
            ->get(['id_temporada', 'nom_tem'])
            ->map(function ($temporada, $index) {
                $count = $this->activeProductsCount(
                    fn($query) => $query->where('cod_temporadas_pre', $temporada->id_temporada)
                );

                return [
                    'eyebrow' => 'Temporada',
                    'title' => $temporada->nom_tem,
                    'description' => $count . ' piezas dentro de esta edición.',
                    'image' => $this->representativeImage(
                        Prenda::where('est_pre', true)->where('cod_temporadas_pre', $temporada->id_temporada),
                        $index
                    ),
                    'href' => $this->catalogUrl(['temporada' => $temporada->nom_tem]),
                    'count' => $count,
                ];
            })
            ->filter(fn($item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->map(fn($item) => [
                'eyebrow' => $item['eyebrow'],
                'title' => $item['title'],
                'description' => $item['description'],
                'image' => $item['image'],
                'href' => $item['href'],
            ])
            ->all();
    }

    private function buildEditorialMomentCards(): array
    {
        $products = ProductMapper::withRelations(
            Prenda::where('est_pre', true)
        );
        $this->catalogService->applySeasonOrdering($products);
        $products = $products->limit(3)->get();

        return $products->values()->map(function ($prenda, $index) {
            $meta = collect([
                $prenda->marca?->nom_mar,
                $prenda->temporada?->nom_tem,
            ])->filter()->join(' / ');

            return [
                'note' => $meta !== '' ? $meta : 'Selección Mati Boutique',
                'title' => $prenda->nom_pre,
                'image' => $this->productImage($prenda) ?: $this->fallbackImage($index),
                'href' => route('shop.product', ['id' => $prenda->id_prenda]),
            ];
        })->all();
    }

    private function buildEditorialHighlights(): array
    {
        return Temporada::where('est_tem', true)
            ->get(['id_temporada', 'nom_tem'])
            ->map(function ($temporada) {
                return [
                    'title' => $temporada->nom_tem,
                    'count' => $this->activeProductsCount(
                        fn($query) => $query->where('cod_temporadas_pre', $temporada->id_temporada)
                    ),
                ];
            })
            ->filter(fn($item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->take(4)
            ->pluck('title')
            ->values()
            ->all();
    }

    private function activeProductsCount(?callable $scope = null): int
    {
        $query = Prenda::where('est_pre', true);

        if ($scope !== null) {
            $scope($query);
        }

        return $query->count();
    }

    private function representativeImage($query, int $index): string
    {
        $productQuery = ProductMapper::withRelations($query);
        $this->catalogService->applySeasonOrdering($productQuery);
        $product = $productQuery->first();

        return $this->productImage($product) ?: $this->fallbackImage($index);
    }

    private function productImage(?Prenda $product): string
    {
        if (! $product) {
            return '';
        }

        return $product->imagenes
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->first()?->url_igp ?? '';
    }

    private function catalogUrl(array $query): string
    {
        return route('shop.catalog') . '?' . http_build_query($query);
    }

    private function fallbackHeroImage(string $mode): string
    {
        return $mode === 'collections'
            ? self::FALLBACK_IMAGES[0]
            : self::FALLBACK_IMAGES[1];
    }

    private function fallbackImage(int $index): string
    {
        return self::FALLBACK_IMAGES[$index % count(self::FALLBACK_IMAGES)];
    }
}
