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
    department: 'mujer' | 'hombre' | 'accesorios';
    category: string;
    style: string;
    color: string;
    availability: ShopAvailability;
    sizes: string[];
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
    price: PriceFilterKey;
    availability: 'all' | ShopAvailability;
};

export type ShopFilterOptions = {
    categories: string[];
    sizes: string[];
    colors: string[];
    styles: string[];
    brands: string[];
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
