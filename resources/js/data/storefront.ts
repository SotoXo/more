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
        title: 'Novedades de temporada',
        subtitle: 'Prendas seleccionadas para renovar tu guardarropa con elegancia y facilidad.',
        note: 'Explora lo nuevo y encuentra piezas listas para comprar.',
        image: 'https://images.pexels.com/photos/985635/pexels-photo-985635.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
    {
        title: 'Selección Mati Boutique',
        subtitle: 'Categorías, marcas y prendas reunidas para comprar de forma simple y cuidada.',
        note: 'Recorre la tienda por novedades, categorías o catálogo completo.',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
    {
        title: 'Detalles para elegir mejor',
        subtitle: 'Consulta color, talla, disponibilidad y precio antes de reservar tu prenda.',
        note: 'Compra con información clara y atención boutique.',
        image: 'https://images.pexels.com/photos/6455010/pexels-photo-6455010.jpeg?auto=compress&cs=tinysrgb&w=1600',
    },
];

export const homeCollectionCards: HomeCollectionCard[] = [
    {
        title: 'Explorar catálogo',
        image: 'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg?auto=compress&cs=tinysrgb&w=1200',
        label: 'Todas las prendas',
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
        eyebrow: 'Todas las prendas',
        subtitle: 'Explora prendas, marcas y categorías disponibles en MORE.',
        description: 'Filtra por talla, color, precio o marca para encontrar la prenda que buscas.',
        heroImage:
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Todas las prendas', 'Marcas disponibles', 'Filtros de compra'],
    },
    mujer: {
        title: 'Mujer',
        eyebrow: 'Moda mujer',
        subtitle: 'Prendas femeninas seleccionadas para looks elegantes y actuales.',
        description: 'Encuentra opciones para vestir de día, de noche o para renovar básicos clave.',
        heroImage:
            'https://images.pexels.com/photos/4879302/pexels-photo-4879302.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Prendas femeninas', 'Looks actuales', 'Tallas disponibles'],
    },
    hombre: {
        title: 'Hombre',
        eyebrow: 'Moda hombre',
        subtitle: 'Prendas masculinas con líneas limpias y estilo actual.',
        description: 'Explora opciones para looks casuales, urbanos y de temporada.',
        heroImage:
            'https://images.pexels.com/photos/36207069/pexels-photo-36207069.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Prendas masculinas', 'Estilo urbano', 'Disponibilidad actual'],
    },
    accesorios: {
        title: 'Accesorios',
        eyebrow: 'Complementos',
        subtitle: 'Accesorios para completar tu look con detalle y estilo.',
        description: 'Revisa bolsos, piezas de cuero y complementos disponibles en la tienda.',
        heroImage:
            'https://images.pexels.com/photos/5059874/pexels-photo-5059874.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Bolsos', 'Complementos', 'Piezas disponibles'],
    },
    novedades: {
        title: 'Novedades',
        eyebrow: 'Recién llegadas',
        subtitle: 'Las prendas recién incorporadas al catálogo de MORE.',
        description: 'Revisa las últimas llegadas y reserva tus favoritas antes de que se agoten.',
        heroImage:
            'https://images.pexels.com/photos/977585/pexels-photo-977585.jpeg?auto=compress&cs=tinysrgb&w=1600',
        highlights: ['Llegadas recientes', 'Prendas nuevas', 'Reserva disponible'],
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
        title: 'Comprar por marca',
        note: 'Selección por marca',
        image: 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.catalog,
    },
    {
        title: 'Ver novedades',
        note: 'Últimas llegadas',
        image: 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.newIn,
    },
    {
        title: 'Explorar catálogo',
        note: 'Todas las prendas',
        image: 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400',
        href: shopRoutes.catalog,
    },
];
