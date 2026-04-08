export type ShopCollectionKey = 'mujer' | 'hombre' | 'accesorios' | 'novedades';
export type ShopEditorialMode = 'collections' | 'editorial';
export type ShopAvailability = 'Disponible' | 'Ultimas piezas' | 'Agotado';
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
    rank: number;
    image: string;
    gallery: string[];
    description: string;
};

export type ShopCollectionConfig = {
    key: ShopCollectionKey;
    title: string;
    eyebrow: string;
    subtitle: string;
    description: string;
    heroImage: string;
    highlights: string[];
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

export type HomeHeroSlide = {
    title: string;
    subtitle: string;
    note: string;
    image: string;
};

export type HomeCollectionCard = {
    title: string;
    label: string;
    image: string;
    href: string;
};

export type HomeCategoryTile = {
    title: string;
    image: string;
    span: string;
    href: string;
};

export type HomeTrendGroup = {
    title: string;
    items: string[];
};

export type HomeTrendLook = {
    title: string;
    note: string;
    image: string;
    href: string;
};

export type EditorialFeature = {
    eyebrow: string;
    title: string;
    image: string;
    href: string;
};

export type EditorialMoment = {
    title: string;
    note: string;
    image: string;
};

export const shopRoutes = {
    home: '/',
    women: '/mujer',
    men: '/hombre',
    accessories: '/accesorios',
    newIn: '/novedades',
    collections: '/colecciones',
    editorial: '/editorial',
    product: (slug: string) => `/producto/${slug}`,
} as const;

export const shopNavigationItems = [
    { label: 'Mujer', href: shopRoutes.women },
    { label: 'Hombre', href: shopRoutes.men },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Colecciones', href: shopRoutes.collections },
    { label: 'Editorial', href: shopRoutes.editorial },
    { label: 'Accesorios', href: shopRoutes.accessories },
] as const;

export const shopCollections: Record<ShopCollectionKey, ShopCollectionConfig> = {
    mujer: {
        key: 'mujer',
        title: 'Mujer',
        eyebrow: 'Selection Mati Boutique',
        subtitle: 'Siluetas limpias, tailoring suave y piezas que dominan sin exceso.',
        description: 'Una seleccion pensada para verse editorial y sentirse facil.',
        heroImage:
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Quiet luxury', 'Soft tailoring', 'After dark'],
    },
    hombre: {
        key: 'hombre',
        title: 'Hombre',
        eyebrow: 'Mati Boutique edit',
        subtitle: 'Smart casual con estructura limpia, knit premium y capas precisas.',
        description: 'Looks urbanos con una base europea y una lectura mas actual.',
        heroImage:
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Old money', 'Clean street', 'Tailoring'],
    },
    accesorios: {
        key: 'accesorios',
        title: 'Accesorios',
        eyebrow: 'Refined accents',
        subtitle: 'Bolsos, cuero, metal suave y detalles que cierran el look.',
        description: 'La capa final para dar presencia sin perder limpieza visual.',
        heroImage:
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Leather lab', 'Soft gold', 'Minimal carry'],
    },
    novedades: {
        key: 'novedades',
        title: 'New In',
        eyebrow: 'Now landing',
        subtitle: 'Las piezas nuevas que entran con mas ritmo a la seleccion actual.',
        description: 'Nuevas entradas entre tailoring, knit y accesorios con presencia.',
        heroImage:
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['New season', 'Fresh arrivals', 'Quiet edits'],
    },
};

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
    { value: '1101+', label: 'Mas de Bs 1,100', min: 1101, max: Number.POSITIVE_INFINITY },
] as const satisfies ReadonlyArray<{
    value: PriceFilterKey;
    label: string;
    min: number;
    max: number;
}>;

export const products: ShopProduct[] = [
    {
        id: 1,
        slug: 'blazer-structured-classic',
        name: 'Blazer Structured Classic',
        price: 790,
        line: 'Tailored / Atelier Europa',
        brand: 'Atelier Europa',
        department: 'mujer',
        category: 'Blazers',
        style: 'Tailoring',
        color: 'Ivory',
        availability: 'Disponible',
        sizes: ['XS', 'S', 'M', 'L'],
        isNew: true,
        rank: 1,
        image: 'https://images.pexels.com/photos/7760003/pexels-photo-7760003.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/7760003/pexels-photo-7760003.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Blazer de hombro limpio y caida firme para una silueta precisa.',
    },
    {
        id: 2,
        slug: 'wide-leg-tailored-pant',
        name: 'Wide-Leg Tailored Pant',
        price: 420,
        line: 'Smart casual / Studio 04',
        brand: 'Studio 04',
        department: 'mujer',
        category: 'Trousers',
        style: 'Smart Casual',
        color: 'Stone',
        availability: 'Ultimas piezas',
        sizes: ['S', 'M', 'L'],
        isNew: true,
        rank: 2,
        image: 'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Pierna amplia y cintura alta para equilibrar looks pulidos y relajados.',
    },
    {
        id: 3,
        slug: 'camisa-crisp-oversized',
        name: 'Camisa Crisp Oversized',
        price: 285,
        line: 'Minimal / White Room',
        brand: 'White Room',
        department: 'mujer',
        category: 'Shirts',
        style: 'Minimal',
        color: 'White',
        availability: 'Disponible',
        sizes: ['S', 'M', 'L'],
        isNew: true,
        rank: 3,
        image: 'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Camisa amplia con cuello limpio para capas ligeras y proporciones editoriales.',
    },
    {
        id: 4,
        slug: 'trench-minimal',
        name: 'Trench Minimal',
        price: 960,
        line: 'Outerwear / Continental Edit',
        brand: 'Continental Edit',
        department: 'mujer',
        category: 'Outerwear',
        style: 'Quiet Luxury',
        color: 'Camel',
        availability: 'Disponible',
        sizes: ['S', 'M', 'L'],
        isNew: false,
        rank: 4,
        image: 'https://images.pexels.com/photos/5119928/pexels-photo-5119928.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/5119928/pexels-photo-5119928.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Trench de linea depurada con presencia suave y movimiento largo.',
    },
    {
        id: 5,
        slug: 'falda-satin-midi',
        name: 'Falda Satin Midi',
        price: 370,
        line: 'Evening soft / Satin Atelier',
        brand: 'Satin Atelier',
        department: 'mujer',
        category: 'Skirts',
        style: 'After Dark',
        color: 'Black Cherry',
        availability: 'Ultimas piezas',
        sizes: ['S', 'M', 'L'],
        isNew: false,
        rank: 5,
        image: 'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Satin limpio con brillo controlado para noches y capas sobrias.',
    },
    {
        id: 6,
        slug: 'knit-set-soft-ivory',
        name: 'Knit Set Soft Ivory',
        price: 520,
        line: 'Soft neutrals / Maison Edit',
        brand: 'Maison Edit',
        department: 'mujer',
        category: 'Knitwear',
        style: 'Soft Neutrals',
        color: 'Ivory',
        availability: 'Disponible',
        sizes: ['S', 'M'],
        isNew: true,
        rank: 6,
        image: 'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Conjunto knit de tono marfil con tacto suave y lectura boutique.',
    },
    {
        id: 7,
        slug: 'blazer-night-line',
        name: 'Blazer Night Line',
        price: 840,
        line: 'Evening tailoring / Noir Atelier',
        brand: 'Noir Atelier',
        department: 'mujer',
        category: 'Blazers',
        style: 'After Dark',
        color: 'Black',
        availability: 'Disponible',
        sizes: ['XS', 'S', 'M', 'L'],
        isNew: false,
        rank: 7,
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Sastreria oscura con brillo bajo y una silueta pensada para la noche.',
    },
    {
        id: 8,
        slug: 'coat-city-line',
        name: 'Coat City Line',
        price: 1180,
        line: 'Mati Boutique selection',
        brand: 'Mati Select',
        department: 'hombre',
        category: 'Outerwear',
        style: 'Old Money',
        color: 'Deep Navy',
        availability: 'Disponible',
        sizes: ['M', 'L', 'XL'],
        isNew: false,
        rank: 1,
        image: 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Abrigo de linea larga, hombro suave y un tono oscuro con mucha presencia.',
    },
    {
        id: 9,
        slug: 'shirt-white-edit',
        name: 'Shirt White Edit',
        price: 290,
        line: 'Minimal capsule',
        brand: 'Minimal Capsule',
        department: 'hombre',
        category: 'Shirts',
        style: 'Clean Street',
        color: 'White',
        availability: 'Disponible',
        sizes: ['S', 'M', 'L', 'XL'],
        isNew: true,
        rank: 2,
        image: 'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/7682241/pexels-photo-7682241.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/4937226/pexels-photo-4937226.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Camisa blanca con estructura limpia para capas casuales y looks pulidos.',
    },
    {
        id: 10,
        slug: 'tailored-trousers-signature',
        name: 'Tailored Trousers',
        price: 460,
        line: 'Quiet luxury',
        brand: 'Quiet Line',
        department: 'hombre',
        category: 'Trousers',
        style: 'Tailoring',
        color: 'Charcoal',
        availability: 'Disponible',
        sizes: ['S', 'M', 'L', 'XL'],
        isNew: false,
        rank: 3,
        image: 'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Pantalon sastre de caida recta para una base limpia y sobria.',
    },
    {
        id: 11,
        slug: 'knit-polo-relaxed',
        name: 'Knit Polo Relaxed',
        price: 345,
        line: 'Casual premium / Club House',
        brand: 'Club House',
        department: 'hombre',
        category: 'Knitwear',
        style: 'Smart Casual',
        color: 'Deep Navy',
        availability: 'Disponible',
        sizes: ['M', 'L', 'XL'],
        isNew: true,
        rank: 4,
        image: 'https://images.pexels.com/photos/4937226/pexels-photo-4937226.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/4937226/pexels-photo-4937226.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Polo knit de trama suave para un smart casual relajado y pulido.',
    },
    {
        id: 12,
        slug: 'overshirt-street-clean',
        name: 'Overshirt Street Clean',
        price: 380,
        line: 'Street clean / Studio Nord',
        brand: 'Studio Nord',
        department: 'hombre',
        category: 'Overshirts',
        style: 'Clean Street',
        color: 'Graphite',
        availability: 'Disponible',
        sizes: ['M', 'L', 'XL'],
        isNew: true,
        rank: 5,
        image: 'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/4937226/pexels-photo-4937226.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Overshirt de linea limpia para sumar estructura sin peso visual.',
    },
    {
        id: 13,
        slug: 'suede-bomber-soft',
        name: 'Suede Bomber Soft',
        price: 890,
        line: 'Minimal luxury / Studio Nord',
        brand: 'Studio Nord',
        department: 'hombre',
        category: 'Outerwear',
        style: 'Minimal Luxury',
        color: 'Taupe',
        availability: 'Ultimas piezas',
        sizes: ['M', 'L', 'XL'],
        isNew: false,
        rank: 6,
        image: 'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Bomber de tacto suave en tono taupe para una lectura premium discreta.',
    },
    {
        id: 14,
        slug: 'tote-structured-ivory',
        name: 'Tote Structured Ivory',
        price: 410,
        line: 'Quiet luxury / Atelier Carry',
        brand: 'Atelier Carry',
        department: 'accesorios',
        category: 'Bags',
        style: 'Quiet Luxury',
        color: 'Ivory',
        availability: 'Disponible',
        sizes: ['One Size'],
        isNew: true,
        rank: 1,
        image: 'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/5119928/pexels-photo-5119928.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Bolso de silueta estructurada para cerrar looks claros y muy pulidos.',
    },
    {
        id: 15,
        slug: 'leather-belt-contour',
        name: 'Leather Belt Contour',
        price: 180,
        line: 'Tailoring finish / Leather Lab',
        brand: 'Leather Lab',
        department: 'accesorios',
        category: 'Belts',
        style: 'Tailoring',
        color: 'Espresso',
        availability: 'Disponible',
        sizes: ['75', '85', '95'],
        isNew: false,
        rank: 2,
        image: 'https://images.pexels.com/photos/6046228/pexels-photo-6046228.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/6046228/pexels-photo-6046228.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36611867/pexels-photo-36611867.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Cinturon de cuero oscuro para dar definicion a looks sastre y casuales.',
    },
    {
        id: 16,
        slug: 'chain-earrings-soft-gold',
        name: 'Chain Earrings Soft Gold',
        price: 120,
        line: 'After dark / Atelier Metal',
        brand: 'Atelier Metal',
        department: 'accesorios',
        category: 'Jewelry',
        style: 'After Dark',
        color: 'Soft Gold',
        availability: 'Disponible',
        sizes: ['One Size'],
        isNew: true,
        rank: 3,
        image: 'https://images.pexels.com/photos/942878/pexels-photo-942878.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/942878/pexels-photo-942878.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Aros de brillo bajo para sumar metal suave sin romper la limpieza del look.',
    },
    {
        id: 17,
        slug: 'scarf-soft-stone',
        name: 'Scarf Soft Stone',
        price: 160,
        line: 'Soft neutrals / Northern Loom',
        brand: 'Northern Loom',
        department: 'accesorios',
        category: 'Scarves',
        style: 'Soft Neutrals',
        color: 'Stone',
        availability: 'Disponible',
        sizes: ['One Size'],
        isNew: false,
        rank: 4,
        image: 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Panuelo ligero en tonos piedra para sumar textura y tono a capas suaves.',
    },
    {
        id: 18,
        slug: 'loafer-city-classic',
        name: 'Loafer City Classic',
        price: 540,
        line: 'Old money / Leather Lab',
        brand: 'Leather Lab',
        department: 'accesorios',
        category: 'Footwear',
        style: 'Old Money',
        color: 'Espresso',
        availability: 'Ultimas piezas',
        sizes: ['39', '40', '41', '42', '43'],
        isNew: false,
        rank: 5,
        image: 'https://images.pexels.com/photos/1124465/pexels-photo-1124465.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/1124465/pexels-photo-1124465.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6046228/pexels-photo-6046228.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Loafer de cuero oscuro para completar outfits con aire clasico y preciso.',
    },
    {
        id: 19,
        slug: 'mini-bag-midnight',
        name: 'Mini Bag Midnight',
        price: 360,
        line: 'After dark / Midnight Line',
        brand: 'Midnight Line',
        department: 'accesorios',
        category: 'Bags',
        style: 'After Dark',
        color: 'Black',
        availability: 'Disponible',
        sizes: ['One Size'],
        isNew: true,
        rank: 6,
        image: 'https://images.pexels.com/photos/5709661/pexels-photo-5709661.jpeg?auto=compress&cs=tinysrgb&w=1200',
        gallery: [
            'https://images.pexels.com/photos/5709661/pexels-photo-5709661.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1400',
            'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1400',
        ],
        description: 'Bolso compacto en negro profundo para un cierre mas nocturno y elegante.',
    },
];

export const homeHeroSlides: HomeHeroSlide[] = [
    {
        title: 'Nueva temporada',
        subtitle: 'Curado por Mati Boutique.',
        image: 'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1600',
        note: 'Soft tailoring, city layers, quiet shine.',
    },
    {
        title: 'Minimal luxury',
        subtitle: 'Looks limpios para todos los dias.',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600',
        note: 'Stone, black, ivory and deep navy.',
    },
    {
        title: 'After dark edit',
        subtitle: 'Texturas suaves y contraste preciso.',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1600',
        note: 'Editorial pieces for nights in motion.',
    },
];

export const homeCollectionCards: HomeCollectionCard[] = [
    {
        title: 'Weekend tailoring',
        image: 'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1200',
        label: 'Mati Boutique edit',
        href: shopRoutes.collections,
    },
    {
        title: 'Quiet evening',
        image: 'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1200',
        label: 'New season',
        href: shopRoutes.editorial,
    },
];

export const homeCategoryTiles: HomeCategoryTile[] = [
    {
        title: 'Mujer',
        image: 'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-7 lg:col-span-5',
        href: shopRoutes.women,
    },
    {
        title: 'Hombre',
        image: 'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-5 lg:col-span-3',
        href: shopRoutes.men,
    },
    {
        title: 'Accesorios',
        image: 'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
        href: shopRoutes.accessories,
    },
    {
        title: 'Casual',
        image: 'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
        href: shopRoutes.men,
    },
    {
        title: 'Formal',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
        href: shopRoutes.women,
    },
    {
        title: 'New In',
        image: 'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-3',
        href: shopRoutes.newIn,
    },
    {
        title: 'Editorial',
        image: 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-12 lg:col-span-5',
        href: shopRoutes.editorial,
    },
];

export const homeTrendGroups: HomeTrendGroup[] = [
    {
        title: 'Now trending',
        items: ['Old money', 'Quiet luxury', 'Tailoring', 'Clean street'],
    },
    {
        title: 'Palette',
        items: ['Ivory', 'Stone', 'Black cherry', 'Deep navy'],
    },
    {
        title: 'Mood',
        items: ['Soft neutrals', 'Minimal knit', 'After dark', 'New season'],
    },
];

export const homeTrendLooks: HomeTrendLook[] = [
    {
        title: 'Old money',
        note: 'soft layers',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1200',
        href: shopRoutes.men,
    },
    {
        title: 'Quiet luxury',
        note: 'tailored neutrals',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1200',
        href: shopRoutes.women,
    },
    {
        title: 'Clean street',
        note: 'minimal edge',
        image: 'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1200',
        href: shopRoutes.newIn,
    },
];

export const editorialFeatures: EditorialFeature[] = [
    {
        eyebrow: 'Selection Mati Boutique',
        title: 'Quiet layers for the city',
        image: 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.men,
    },
    {
        eyebrow: 'New season',
        title: 'Soft tailoring, lighter shine',
        image: 'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.women,
    },
    {
        eyebrow: 'Accessory focus',
        title: 'The pieces that finish the look',
        image: 'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.accessories,
    },
];

export const editorialMoments: EditorialMoment[] = [
    {
        title: 'Quiet tailoring',
        note: 'neutral structures',
        image: 'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1400',
    },
    {
        title: 'Soft shine',
        note: 'dark satin',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1400',
    },
    {
        title: 'New season',
        note: 'fresh arrivals',
        image: 'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1400',
    },
];

export const homeArrivalSlugs = [
    'blazer-structured-classic',
    'wide-leg-tailored-pant',
    'camisa-crisp-oversized',
    'knit-polo-relaxed',
    'trench-minimal',
    'falda-satin-midi',
] as const;

export const homeSelectionSlugs = [
    'coat-city-line',
    'shirt-white-edit',
    'falda-satin-midi',
    'knit-polo-relaxed',
    'tailored-trousers-signature',
] as const;

export function createDefaultFilters(): ShopFilterState {
    return {
        categories: [],
        sizes: [],
        colors: [],
        styles: [],
        brands: [],
        price: 'all',
        availability: 'all',
    };
}

export function formatPrice(price: number) {
    return `Bs ${price.toLocaleString('es-BO')}`;
}

export function getProductBySlug(slug: string) {
    return products.find((product) => product.slug === slug) ?? null;
}

export function getProductsBySlugs(slugs: readonly string[]) {
    return slugs
        .map((slug) => getProductBySlug(slug))
        .filter((product): product is ShopProduct => product !== null);
}

export function getCollectionProducts(collection: ShopCollectionKey) {
    if (collection === 'novedades') {
        return products.filter((product) => product.isNew);
    }

    return products.filter((product) => product.department === collection);
}

function getUniqueValues(values: string[]) {
    return [...new Set(values)].sort((left, right) => left.localeCompare(right));
}

export function getProductFilterOptions(
    collectionProducts: ShopProduct[],
): ShopFilterOptions {
    return {
        categories: getUniqueValues(collectionProducts.map((product) => product.category)),
        sizes: getUniqueValues(collectionProducts.flatMap((product) => product.sizes)),
        colors: getUniqueValues(collectionProducts.map((product) => product.color)),
        styles: getUniqueValues(collectionProducts.map((product) => product.style)),
        brands: getUniqueValues(collectionProducts.map((product) => product.brand)),
    };
}

function includesFilter(selected: string[], current: string) {
    return selected.length === 0 || selected.includes(current);
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
            includesFilter(filters.colors, product.color) &&
            includesFilter(filters.styles, product.style) &&
            includesFilter(filters.brands, product.brand) &&
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

function getRelatedScore(base: ShopProduct, candidate: ShopProduct) {
    let score = 0;

    if (base.department === candidate.department) {
        score += 4;
    }

    if (base.style === candidate.style) {
        score += 4;
    }

    if (base.color === candidate.color) {
        score += 2;
    }

    if (base.brand === candidate.brand) {
        score += 1;
    }

    if (base.category === candidate.category) {
        score += 1;
    }

    if (candidate.isNew) {
        score += 0.5;
    }

    return score;
}

export function getRelatedProducts(product: ShopProduct, limit = 4) {
    return [...products]
        .filter((candidate) => candidate.slug !== product.slug)
        .sort((left, right) => {
            const scoreDifference =
                getRelatedScore(product, right) - getRelatedScore(product, left);

            if (scoreDifference !== 0) {
                return scoreDifference;
            }

            return left.rank - right.rank;
        })
        .slice(0, limit);
}

function getLookScore(base: ShopProduct, candidate: ShopProduct) {
    let score = getRelatedScore(base, candidate);

    if (base.department !== 'accesorios' && candidate.department === 'accesorios') {
        score += 4;
    }

    if (base.department === 'accesorios' && candidate.department !== 'accesorios') {
        score += 4;
    }

    if (base.department === candidate.department && base.category !== candidate.category) {
        score += 3;
    }

    return score;
}

export function getCompleteLookProducts(product: ShopProduct, limit = 3) {
    return [...products]
        .filter((candidate) => candidate.slug !== product.slug)
        .sort((left, right) => {
            const scoreDifference =
                getLookScore(product, right) - getLookScore(product, left);

            if (scoreDifference !== 0) {
                return scoreDifference;
            }

            return left.rank - right.rank;
        })
        .slice(0, limit);
}
