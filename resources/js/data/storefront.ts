import { shopRoutes } from '@/lib/shop';
import type { ShopCollectionKey } from '@/types/shop';

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

export type CollectionPresentation = {
    title: string;
    eyebrow: string;
    subtitle: string;
    description: string;
    heroImage: string;
    highlights: string[];
};

export type CategoryVisual = {
    image: string;
    span: string;
};

export type BrandEditorialCard = {
    title: string;
    note: string;
    image: string;
    href: string;
};

export const homeHeroSlides: HomeHeroSlide[] = [
    {
        title: 'Nuevas siluetas',
        subtitle: 'Texturas limpias, capas suaves y piezas pensadas para una presencia serena.',
        note: 'Una entrada editorial para recorrer MORE con intención.',
        image: 'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
    {
        title: 'El ritmo del guardarropa',
        subtitle: 'Marcas, categorías y prendas se entrelazan en una selección con pulso propio.',
        note: 'Pensado para descubrir piezas, no solo para buscarlas.',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
    {
        title: 'Detalles que sostienen el look',
        subtitle: 'Color, talla y acabado se leen con claridad para comprar con calma.',
        note: 'MORE mezcla precisión boutique con una mirada contemporánea.',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
];

export const homeCollectionCards: HomeCollectionCard[] = [
    {
        title: 'Explorar catálogo',
        image: 'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1200',
        label: 'Edición completa',
        href: shopRoutes.catalog,
    },
    {
        title: 'Ver novedades',
        image: 'https://images.pexels.com/photos/6311612/pexels-photo-6311612.jpeg?auto=compress&cs=tinysrgb&w=1200',
        label: 'Llegadas recientes',
        href: shopRoutes.newIn,
    },
];

export const collectionPresentation: Record<ShopCollectionKey, CollectionPresentation> = {
    catalogo: {
        title: 'Catálogo',
        eyebrow: 'Edición completa',
        subtitle: 'Un recorrido amplio por prendas, firmas y acentos que conviven bajo una misma mirada.',
        description: 'La forma más abierta de descubrir MORE: piezas para mezclar, guardar y volver a mirar.',
        heroImage:
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Edición completa', 'Firmas seleccionadas', 'Piezas para descubrir'],
    },
    mujer: {
        title: 'Mujer',
        eyebrow: 'Edición Mati Boutique',
        subtitle: 'Sastrería fluida, textura suave y siluetas que se mueven con calma.',
        description: 'Una edición femenina pensada para construir presencia sin exceso y con mucho detalle.',
        heroImage:
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Sastrería suave', 'Líneas largas', 'Capas ligeras'],
    },
    hombre: {
        title: 'Hombre',
        eyebrow: 'Edición Mati Boutique',
        subtitle: 'Texturas sobrias, capas precisas y una edición masculina de ritmo contenido.',
        description: 'Un guardarropa de contraste limpio, pensado para moverse entre lo clásico y lo actual.',
        heroImage:
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Líneas sobrias', 'Capas precisas', 'Tono urbano'],
    },
    accesorios: {
        title: 'Accesorios',
        eyebrow: 'Acentos precisos',
        subtitle: 'Los detalles que cierran el look con una nota exacta de textura y carácter.',
        description: 'Bolsos, cuero y pequeños acentos que completan la composición con elegancia.',
        heroImage:
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Cuero suave', 'Acentos precisos', 'Ritmo final'],
    },
    novedades: {
        title: 'Novedades',
        eyebrow: 'Recién llegadas',
        subtitle: 'Las últimas entradas de la temporada, listas para renovar el ritmo del guardarropa.',
        description: 'Llegadas recientes entre sastrería suave, tejido pulido y accesorios con presencia.',
        heroImage:
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Llegadas recientes', 'Piezas clave', 'Selección viva'],
    },
};

export const homeCategoryVisuals: CategoryVisual[] = [
    {
        image: 'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-7 lg:col-span-5',
    },
    {
        image: 'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-5 lg:col-span-3',
    },
    {
        image: 'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
    },
    {
        image: 'https://images.pexels.com/photos/36510860/pexels-photo-36510860.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
    },
    {
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-4',
    },
    {
        image: 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1200',
        span: 'md:col-span-6 lg:col-span-3',
    },
];

export const homeBrandEditorialCards: BrandEditorialCard[] = [
    {
        title: 'Firmas en diálogo',
        note: 'Selección por marca',
        image: 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.catalog,
    },
    {
        title: 'Capítulos del guardarropa',
        note: 'Rutas de compra',
        image: 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.newIn,
    },
    {
        title: 'Detalle con intención',
        note: 'Mirada de producto',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.catalog,
    },
];
