import type {
    PriceFilterKey,
    ShopAvailability,
    ShopFilterState,
    ShopProduct,
    ShopSortKey,
} from '@/types/shop';

export const shopRoutes = {
    home: '/',
    catalog: '/catalogo',
    women: '/mujer',
    men: '/hombre',
    accessories: '/accesorios',
    newIn: '/novedades',
    collections: '/colecciones',
    editorial: '/editorial',
    product: (id: string | number) => `/producto/${id}`,
} as const;

export const shopNavigationItems = [
    { label: 'Inicio', href: shopRoutes.home },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Mujer', href: shopRoutes.women },
    { label: 'Hombre', href: shopRoutes.men },
    { label: 'Accesorios', href: shopRoutes.accessories },
    { label: 'Catálogo', href: shopRoutes.catalog },
] as const;

export const sortOptions = [
    { value: 'recommended', label: 'Recomendados' },
    { value: 'newest', label: 'Nuevos' },
    { value: 'price-asc', label: 'Precio ascendente' },
    { value: 'price-desc', label: 'Precio descendente' },
] as const satisfies ReadonlyArray<{ value: ShopSortKey; label: string }>;

export const priceFilterOptions = [
    { value: 'all', label: 'Todos', min: 0, max: Number.POSITIVE_INFINITY },
    { value: '0-350', label: 'Hasta Bs 350', min: 0, max: 350 },
    { value: '351-700', label: 'Bs 351 a Bs 700', min: 351, max: 700 },
    { value: '701-1100', label: 'Bs 701 a Bs 1,100', min: 701, max: 1100 },
    { value: '1101+', label: 'Más de Bs 1,100', min: 1101, max: Number.POSITIVE_INFINITY },
] as const satisfies ReadonlyArray<{
    value: PriceFilterKey;
    label: string;
    min: number;
    max: number;
}>;

export function createDefaultFilters(): ShopFilterState {
    return {
        categories: [],
        sizes: [],
        colors: [],
        styles: [],
        brands: [],
        locations: [],
        price: 'all',
        availability: 'all',
    };
}

export function formatPrice(price: number) {
    return `Bs ${price.toLocaleString('es-BO')}`;
}

function includesFilter(selected: string[], current: string) {
    return selected.length === 0 || selected.includes(current);
}

function includesAnyFilter(selected: string[], current: string[]) {
    return selected.length === 0 || current.some((item) => selected.includes(item));
}

export function filterProducts(
    collectionProducts: ShopProduct[],
    filters: ShopFilterState,
) {
    const selectedPriceRange = priceFilterOptions.find(
        (option) => option.value === filters.price,
    );

    return collectionProducts.filter((product) => {
        const matchesAvailability =
            filters.availability === 'all' ||
            product.availability === filters.availability;
        const matchesPrice =
            selectedPriceRange !== undefined &&
            product.price >= selectedPriceRange.min &&
            product.price <= selectedPriceRange.max;

        return (
            includesFilter(filters.categories, product.category) &&
            includesAnyFilter(
                filters.colors,
                product.colors?.map((color) => color.name).filter(Boolean) ?? [
                    product.color,
                ],
            ) &&
            includesFilter(filters.styles, product.style) &&
            includesFilter(filters.brands, product.brand) &&
            includesAnyFilter(filters.locations, product.locations ?? []) &&
            matchesAvailability &&
            matchesPrice &&
            (filters.sizes.length === 0 ||
                product.sizes.some((size) => filters.sizes.includes(size)))
        );
    });
}

export function sortProducts(productsToSort: ShopProduct[], sortKey: ShopSortKey) {
    const ordered = [...productsToSort];

    ordered.sort((left, right) => {
        if (sortKey === 'price-asc') {
            return left.price - right.price;
        }

        if (sortKey === 'price-desc') {
            return right.price - left.price;
        }

        if (sortKey === 'newest') {
            if (left.isNew !== right.isNew) {
                return Number(right.isNew) - Number(left.isNew);
            }

            return left.rank - right.rank;
        }

        return left.rank - right.rank;
    });

    return ordered;
}

export function getAvailabilityFromStock(
    stock: number,
): ShopAvailability {
    if (stock <= 0) {
        return 'Agotado';
    }

    if (stock <= 3) {
        return 'Últimas piezas';
    }

    return 'Disponible';
}
