export type ShopCollectionKey =
    | 'catalogo'
    | 'mujer'
    | 'hombre'
    | 'accesorios'
    | 'novedades';

export type ShopEditorialMode = 'collections' | 'editorial';
export type ShopAvailability = 'Disponible' | 'Últimas piezas' | 'Agotado';
export type ShopSortKey =
    | 'recommended'
    | 'newest'
    | 'price-asc'
    | 'price-desc';
export type PriceFilterKey =
    | 'all'
    | '0-350'
    | '351-700'
    | '701-1100'
    | '1101+';

export type ShopProduct = {
    id: number;
    slug: string;
    name: string;
    price: number;
    line: string;
    brand: string;
    department: 'mujer' | 'hombre' | 'accesorios' | 'unisex';
    gender?: string;
    isUnisex?: boolean;
    category: string;
    style: string;
    seasonCode?: string;
    seasonName?: string;
    isCurrentSeason?: boolean;
    isPermanent?: boolean;
    color: string;
    availability: ShopAvailability;
    sizes: string[];
    colors?: Array<{
        name: string;
        family?: string | null;
        hex: string | null;
    }>;
    locations?: string[];
    totalStock?: number;
    isNew: boolean;
    isFavorite?: boolean;
    rank: number;
    image: string;
    gallery: string[];
    description: string;
};

export type ShopVariant = {
    id: number;
    sku: string;
    color: string;
    color_hex: string | null;
    palette_type?: 'dominante' | 'equilibrada';
    palette?: Array<{ name: string; hex: string | null }>;
    talla: string;
    precio: number;
    stock: number;
};

export type ShopFilterState = {
    categories: string[];
    sizes: string[];
    colors: string[];
    styles: string[];
    brands: string[];
    locations: string[];
    price: PriceFilterKey;
    availability: 'all' | ShopAvailability;
    hideSoldOut: boolean;
};

export type ShopFilterOptions = {
    categories: string[];
    sizes: string[];
    colors: string[];
    styles: string[];
    brands: string[];
    locations: string[];
};

export type HomeCategoryLink = {
    id: number;
    name: string;
    desc?: string | null;
    href: string;
};

export type HomeBrandLink = {
    id: number;
    name: string;
    href: string;
};

export type ShopCuratedLink = {
    label: string;
    href: string;
    meta?: string;
};

export type ShopCuratedFeature = {
    eyebrow: string;
    title: string;
    description: string;
    image: string;
    href: string;
};

export type ShopCuratedMoment = {
    note: string;
    title: string;
    image: string;
    href: string;
};

export type ShopCuratedRailContent = {
    eyebrow: string;
    title: string;
    description: string;
};

export type ShopPagination = {
    total: number;
    currentPage: number;
    lastPage: number;
    previousPageUrl: string | null;
    nextPageUrl: string | null;
};
