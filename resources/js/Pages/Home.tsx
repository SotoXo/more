import LandingFooter from '@/Components/landing/LandingFooter';
import LandingHeader from '@/Components/landing/LandingHeader';
import SectionHeading from '@/Components/landing/SectionHeading';
import ProductCard from '@/Components/shop/ProductCard';
import {
    homeCategoryVisuals,
    homeCollectionCards,
    homeHeroSlides,
} from '@/data/storefront';
import { formatPrice, shopRoutes } from '@/lib/shop';
import type { LandingPageProps } from '@/types/landing';
import type {
    HomeBrandLink,
    HomeCategoryLink,
    ShopProduct,
} from '@/types/shop';
import { Head, Link, usePage } from '@inertiajs/react';
import Autoplay from 'embla-carousel-autoplay';
import useEmblaCarousel from 'embla-carousel-react';
import { motion, type Variants } from 'framer-motion';
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    Play,
    ShoppingBag,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type HomeProps = {
    novedades: ShopProduct[];
    seleccion: ShopProduct[];
    categorias: HomeCategoryLink[];
    marcas: HomeBrandLink[];
};

type EditorialAccess = {
    title: string;
    label: string;
    image: string;
    href: string;
};

const reveal: Variants = {
    hidden: { opacity: 0, y: 28 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.72, ease: [0.22, 1, 0.36, 1] },
    },
};

const stagger: Variants = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.09 } },
};

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';
const primaryButtonClass =
    'more-cta-primary px-6 py-3 text-[0.72rem] uppercase tracking-[0.24em]';
const secondaryButtonClass =
    'more-cta-secondary px-6 py-3 text-[0.72rem] uppercase tracking-[0.24em]';
const editorialLinkClass =
    'more-cta-editorial text-[0.72rem] uppercase tracking-[0.24em]';
const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

function CarouselButtons({
    onPrev,
    onNext,
}: {
    onPrev: () => void;
    onNext: () => void;
}) {
    return (
        <div className="flex items-center gap-2">
            <button
                type="button"
                onClick={onPrev}
                aria-label="Anterior"
                className="more-icon-button inline-flex h-11 w-11 items-center justify-center"
            >
                <ChevronLeft size={18} />
            </button>
            <button
                type="button"
                onClick={onNext}
                aria-label="Siguiente"
                className="more-icon-button inline-flex h-11 w-11 items-center justify-center"
            >
                <ChevronRight size={18} />
            </button>
        </div>
    );
}

function ProductSpotlight({ product }: { product: ShopProduct }) {
    return (
        <Link
            href={shopRoutes.product(product.slug)}
            className="group grid min-h-[480px] overflow-hidden rounded-[1.55rem] border border-white/10 bg-[#070a0f] shadow-[0_26px_82px_rgba(0,0,0,0.36)] lg:grid-cols-[0.96fr_1.04fr]"
        >
            <div className="relative min-h-[360px] overflow-hidden lg:min-h-full">
                {product.image ? (
                    <img
                        src={product.image}
                        alt={product.name}
                        className="h-full w-full object-cover transition duration-[1000ms] group-hover:scale-[1.035]"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-[#0b0f14]">
                        <ShoppingBag size={30} className="text-stone-600" />
                    </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/78 via-black/8 to-transparent" />
                <div className="absolute left-5 top-5 rounded-full border border-[rgba(223,184,166,0.28)] bg-black/40 px-4 py-2 text-[0.64rem] uppercase tracking-[0.22em] text-stone-100 backdrop-blur">
                    Nueva llegada
                </div>
            </div>

            <div className="flex flex-col justify-between p-6 sm:p-8 lg:p-10">
                <div>
                    <p className="text-[0.7rem] uppercase tracking-[0.28em] text-[rgb(236,205,191)]">
                        {product.brand || product.category}
                    </p>
                    <h3
                        className="mt-5 max-w-lg text-5xl leading-[0.92] text-white sm:text-6xl"
                        style={serifStyle}
                    >
                        {product.name}
                    </h3>
                    <p className="mt-5 max-w-md text-sm leading-7 text-stone-200/90">
                        {product.description ||
                            'Prenda disponible en MORE. Revisa talla, color y precio antes de reservar.'}
                    </p>
                </div>

                <div className="mt-8 space-y-6">
                    <div className="grid gap-3 border-y border-white/10 py-5 sm:grid-cols-3">
                        <div>
                            <p className="text-[0.62rem] uppercase tracking-[0.22em] text-stone-500">
                                Categoría
                            </p>
                            <p className="mt-2 text-sm text-stone-100">{product.category}</p>
                        </div>
                        <div>
                            <p className="text-[0.62rem] uppercase tracking-[0.22em] text-stone-500">
                                Estilo
                            </p>
                            <p className="mt-2 text-sm text-stone-100">
                                {product.line || product.style || 'Selección MORE'}
                            </p>
                        </div>
                        <div>
                            <p className="text-[0.62rem] uppercase tracking-[0.22em] text-stone-500">
                                Precio
                            </p>
                            <p className="mt-2 text-sm font-medium text-[rgb(236,205,191)]">
                                {formatPrice(product.price)}
                            </p>
                        </div>
                    </div>

                    <span className={editorialLinkClass}>
                        Ver producto
                        <ArrowRight size={14} />
                    </span>
                </div>
            </div>
        </Link>
    );
}

function EmptyCommercialState({
    title,
    description,
    href,
    label,
}: {
    title: string;
    description: string;
    href: string;
    label: string;
}) {
    return (
        <div className="more-panel-soft rounded-[1.65rem] px-6 py-12 text-center sm:px-10">
            <p className="text-[0.72rem] uppercase tracking-[0.28em] text-[rgb(236,205,191)]">
                MORE
            </p>
            <h3
                className="mx-auto mt-4 max-w-2xl text-4xl leading-none text-white sm:text-5xl"
                style={serifStyle}
            >
                {title}
            </h3>
            <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-stone-300">
                {description}
            </p>
            <Link href={href} className={`${secondaryButtonClass} mt-7`}>
                {label}
                <ArrowRight size={15} />
            </Link>
        </div>
    );
}

export default function Home({
    novedades = [],
    seleccion = [],
    categorias = [],
    marcas = [],
}: HomeProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;

    const [coverIndex, setCoverIndex] = useState(0);
    const [heroIndex, setHeroIndex] = useState(0);
    const [coverRef, coverApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 6100, stopOnInteraction: false })],
    );
    const [heroRef, heroApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 5200, stopOnInteraction: false })],
    );

    useEffect(() => {
        if (!coverApi) {
            return;
        }

        const syncIndex = () => setCoverIndex(coverApi.selectedScrollSnap());

        syncIndex();
        coverApi.on('select', syncIndex);
        coverApi.on('reInit', syncIndex);

        return () => {
            coverApi.off('select', syncIndex);
            coverApi.off('reInit', syncIndex);
        };
    }, [coverApi]);

    useEffect(() => {
        if (!heroApi) {
            return;
        }

        const syncIndex = () => setHeroIndex(heroApi.selectedScrollSnap());

        syncIndex();
        heroApi.on('select', syncIndex);
        heroApi.on('reInit', syncIndex);

        return () => {
            heroApi.off('select', syncIndex);
            heroApi.off('reInit', syncIndex);
        };
    }, [heroApi]);

    const heroQuickLinks = useMemo<EditorialAccess[]>(
        () => [
            ...homeCollectionCards,
            {
                title: 'Guía de compra',
                label: 'Compra por estilo',
                href: shopRoutes.collections,
                image: 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1200',
            },
        ].slice(0, 3),
        [],
    );

    const activeCover = homeHeroSlides[coverIndex] ?? homeHeroSlides[0];
    const activeHero = homeHeroSlides[heroIndex] ?? homeHeroSlides[0];
    const featuredArrival = novedades[0];
    const arrivalGrid = novedades.slice(1, 5);
    const featuredCategories = categorias.slice(0, 4);
    const visibleBrands = marcas.slice(0, 10);
    const supportProducts = seleccion.length > 0
        ? seleccion.slice(0, 3)
        : novedades.slice(0, 3);
    const heroStats = [
        novedades.length > 0
            ? `${novedades.length} novedades`
            : 'Novedades en preparación',
        categorias.length > 0
            ? `${categorias.length} categorías`
            : 'Categorías disponibles',
        marcas.length > 0 ? `${marcas.length} marcas` : 'Selección boutique',
    ];

    const homeBackground =
        'radial-gradient(circle at 16% 0%, rgba(181,122,109,0.15), transparent 25%), radial-gradient(circle at 84% 18%, rgba(223,184,166,0.065), transparent 24%), linear-gradient(180deg, #0a0d11 0%, #06080c 43%, #030407 100%)';

    return (
        <div
            className="more-store-bg relative min-h-screen overflow-x-hidden bg-[#030407] text-white"
            style={{ background: homeBackground }}
        >
            <Head title="MORE | Mati Boutique" />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[860px] bg-[linear-gradient(180deg,rgba(181,122,109,0.1),transparent_62%)]" />
                <div className="absolute inset-x-0 top-28 h-px bg-[linear-gradient(90deg,transparent,rgba(223,184,166,0.22),transparent)]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24" style={{ background: homeBackground }}>
                <section className="relative min-h-[100svh] overflow-hidden">
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        transition={{ duration: 0.8, ease: [0.22, 1, 0.36, 1] }}
                        className="absolute inset-0"
                    >
                        <div ref={coverRef} className="h-full overflow-hidden">
                            <div className="flex h-full">
                                {homeHeroSlides.map((slide) => (
                                    <div
                                        key={`cover-${slide.title}`}
                                        className="relative min-w-0 flex-[0_0_100%]"
                                    >
                                        <img
                                            src={slide.image}
                                            alt={slide.title}
                                            className="h-[100svh] min-h-[620px] w-full object-cover"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-r from-black/88 via-black/56 to-black/16" />
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/84 via-black/18 to-black/18" />
                                        <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.18),transparent_40%,rgba(0,0,0,0.16))]" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </motion.div>

                    <div className="relative z-10 flex min-h-[100svh] items-center px-5 pb-24 pt-28 sm:px-8 sm:pt-32 lg:px-14">
                        <div className="mx-auto grid w-full max-w-[92rem] gap-10 lg:grid-cols-[minmax(0,0.82fr)_minmax(280px,0.32fr)] lg:items-end">
                            <motion.div
                                key={activeCover.title}
                                initial={{ opacity: 0, y: 18 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.52, ease: [0.22, 1, 0.36, 1] }}
                                className="max-w-[850px] py-10 sm:py-12"
                            >
                                <p className="text-[0.7rem] uppercase tracking-[0.32em] text-[rgb(236,205,191)] sm:text-[0.72rem]">
                                    MORE / Mati Boutique
                                </p>
                                <h1
                                    className="mt-5 max-w-[820px] text-[clamp(3.15rem,7.6vw,7.15rem)] leading-[0.94] text-white"
                                    style={serifStyle}
                                >
                                    Moda seleccionada para vestir con estilo.
                                </h1>
                                <p className="mt-6 max-w-[620px] text-base leading-8 text-stone-100 sm:text-lg">
                                    {activeCover.subtitle}
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Link
                                        href={shopRoutes.catalog}
                                        className={primaryButtonClass}
                                    >
                                        Explorar catálogo
                                        <ArrowRight size={16} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.newIn}
                                        className={secondaryButtonClass}
                                    >
                                        Ver novedades
                                    </Link>
                                </div>
                            </motion.div>

                            <div className="hidden border-l border-white/14 pl-6 lg:block">
                                <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-300">
                                    {activeCover.title}
                                </p>
                                <p className="mt-4 text-sm leading-7 text-stone-200/90">
                                    {activeCover.note}
                                </p>
                                <Link
                                    href={shopRoutes.collections}
                                    className={`${editorialLinkClass} mt-7`}
                                >
                                    Ver guía de compra
                                    <ArrowRight size={14} />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="absolute bottom-6 left-5 right-5 z-20 mx-auto flex max-w-[92rem] items-center justify-between gap-5 sm:left-8 sm:right-8 lg:left-14 lg:right-14">
                        <div className="flex items-center gap-3">
                            {homeHeroSlides.map((slide, index) => (
                                <button
                                    key={`cover-dot-${slide.title}`}
                                    type="button"
                                    onClick={() => coverApi?.scrollTo(index)}
                                    className={[
                                        'h-2 rounded-full transition-all duration-300',
                                        coverIndex === index
                                            ? 'w-12 bg-white'
                                            : 'w-2 bg-white/35 hover:bg-white/70',
                                    ].join(' ')}
                                    aria-label={`Ir a ${slide.title}`}
                                />
                            ))}
                        </div>
                        <CarouselButtons
                            onPrev={() => coverApi?.scrollPrev()}
                            onNext={() => coverApi?.scrollNext()}
                        />
                    </div>
                </section>

                <section className="pt-24">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Novedades"
                                title="Novedades para estrenar."
                                description="Últimas prendas incorporadas a la tienda, con precio, categoría y disponibilidad visibles."
                            />
                            <Link href={shopRoutes.newIn} className={secondaryButtonClass}>
                                Ver todas
                                <ArrowRight size={15} />
                            </Link>
                        </div>

                        <div className="mt-12">
                            {featuredArrival ? (
                                <div className="grid gap-6 xl:grid-cols-[1.08fr_0.92fr]">
                                    <ProductSpotlight product={featuredArrival} />

                                    {arrivalGrid.length > 0 ? (
                                        <motion.div
                                            variants={stagger}
                                            initial="hidden"
                                            whileInView="visible"
                                            viewport={{ once: true, amount: 0.12 }}
                                            className="grid gap-x-5 gap-y-10 sm:grid-cols-2"
                                        >
                                            {arrivalGrid.map((product) => (
                                                <motion.div
                                                    key={product.slug}
                                                    variants={reveal}
                                                >
                                                    <ProductCard
                                                        product={product}
                                                        imageClassName="h-[310px] sm:h-[340px]"
                                                    />
                                                </motion.div>
                                            ))}
                                        </motion.div>
                                    ) : (
                                        <EmptyCommercialState
                                            title="Primera novedad disponible."
                                            description="Cuando se agreguen más prendas, esta sección mostrará las últimas llegadas de la tienda."
                                            href={shopRoutes.catalog}
                                            label="Recorrer catálogo"
                                        />
                                    )}
                                </div>
                            ) : (
                                <EmptyCommercialState
                                    title="Próximamente nuevas prendas."
                                    description="Cuando haya novedades activas, las verás aquí con la información necesaria para comprar."
                                    href={shopRoutes.catalog}
                                    label="Explorar catálogo"
                                />
                            )}
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Categorías destacadas"
                                title="Compra por categoría."
                                description="Elige una categoría y entra directo a las prendas disponibles."
                            />
                            <Link href={shopRoutes.catalog} className={secondaryButtonClass}>
                                Ver catálogo completo
                            </Link>
                        </div>

                        {featuredCategories.length > 0 ? (
                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={{ once: true, amount: 0.16 }}
                                className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                {featuredCategories.map((categoria, index) => {
                                    const visual =
                                        homeCategoryVisuals[index % homeCategoryVisuals.length];

                                    return (
                                        <motion.div
                                            key={categoria.id}
                                            variants={reveal}
                                        >
                                            <Link
                                                href={categoria.href}
                                                className="group relative block min-h-[300px] overflow-hidden rounded-[1.35rem] border border-white/10 bg-[#070a0f] sm:min-h-[340px] lg:min-h-[360px]"
                                            >
                                                <img
                                                    src={visual.image}
                                                    alt={categoria.name}
                                                    className="h-full w-full object-cover transition duration-[900ms] group-hover:scale-[1.035]"
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/88 via-black/18 to-transparent" />
                                                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.1),transparent_46%)] opacity-80 transition group-hover:opacity-100" />
                                                <div className="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                                    <p className="text-[0.66rem] uppercase tracking-[0.24em] text-[rgb(236,205,191)]">
                                                        Categoría {String(index + 1).padStart(2, '0')}
                                                    </p>
                                                    <h3
                                                        className="mt-3 text-3xl leading-none text-white sm:text-4xl"
                                                        style={serifStyle}
                                                    >
                                                        {categoria.name}
                                                    </h3>
                                                    {categoria.desc && (
                                                        <p className="mt-3 max-w-md text-sm leading-6 text-stone-100">
                                                            {categoria.desc}
                                                        </p>
                                                    )}
                                                </div>
                                            </Link>
                                        </motion.div>
                                    );
                                })}
                            </motion.div>
                        ) : (
                            <div className="mt-12">
                                <EmptyCommercialState
                                    title="Categorías próximamente."
                                    description="Cuando haya categorías activas, podrás entrar desde aquí al catálogo correspondiente."
                                    href={shopRoutes.catalog}
                                    label="Ir al catálogo"
                                />
                            </div>
                        )}
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-center">
                            <motion.div
                                initial={{ opacity: 0, x: -22 }}
                                whileInView={{ opacity: 1, x: 0 }}
                                viewport={{ once: true, amount: 0.25 }}
                                transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
                                className="relative min-h-[430px] overflow-hidden rounded-[1.55rem] border border-white/10 bg-[#070a0f]"
                            >
                                <img
                                    src="https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1600"
                                    alt="Editorial Mati Boutique"
                                    className="h-full w-full object-cover"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/84 via-black/14 to-transparent" />
                                <div className="absolute bottom-0 left-0 right-0 p-7 sm:p-9">
                                    <p className="text-[0.7rem] uppercase tracking-[0.28em] text-stone-200">
                                        Selección MORE
                                    </p>
                                    <p
                                        className="mt-4 max-w-lg text-4xl leading-none text-white sm:text-5xl"
                                        style={serifStyle}
                                    >
                                        Prendas para armar looks completos.
                                    </p>
                                </div>
                            </motion.div>

                            <motion.div
                                initial={{ opacity: 0, y: 24 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, amount: 0.3 }}
                                transition={{ duration: 0.68, ease: [0.22, 1, 0.36, 1] }}
                                className="lg:pl-8"
                            >
                                <SectionHeading
                                    eyebrow="Guía de compra"
                                    title="Encuentra piezas para cada ocasión."
                                    description="Explora prendas y accesorios pensados para combinar entre sí y comprar con seguridad."
                                />
                                <div className="mt-9 grid gap-4 sm:grid-cols-3">
                                    {['Para el día', 'Para la noche', 'Accesorios'].map((item) => (
                                        <div
                                            key={item}
                                            className="border-t border-[rgba(223,184,166,0.24)] pt-4"
                                        >
                                            <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                                {item}
                                            </p>
                                            <p className="mt-3 text-sm leading-7 text-stone-200">
                                                Detalles que ayudan a elegir mejor cada prenda.
                                            </p>
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={shopRoutes.collections}
                                        className={primaryButtonClass}
                                    >
                                        Ver guía de compra
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.editorial}
                                        className={editorialLinkClass}
                                    >
                                        <Play size={14} />
                                        Ver editorial
                                    </Link>
                                </div>
                            </motion.div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="grid gap-8 lg:grid-cols-[0.86fr_1.14fr] lg:items-start">
                            <div>
                                <SectionHeading
                                    eyebrow="Marcas y destacados"
                                    title="Explora la selección actual."
                                    description="Accede a marcas disponibles y prendas destacadas para encontrar más rápido lo que buscas."
                                />

                                {visibleBrands.length > 0 && (
                                    <div className="mt-8 flex flex-wrap gap-3">
                                        {visibleBrands.map((marca) => (
                                            <Link
                                                key={marca.id}
                                                href={marca.href}
                                                className="more-chip px-4 py-3 text-[0.68rem] uppercase tracking-[0.22em] text-stone-200 transition hover:-translate-y-px hover:border-[rgba(181,122,109,0.28)] hover:bg-[rgba(181,122,109,0.08)]"
                                            >
                                                {marca.name}
                                            </Link>
                                        ))}
                                    </div>
                                )}

                                <Link
                                    href={shopRoutes.catalog}
                                    className={`${editorialLinkClass} mt-9`}
                                >
                                    Explorar selección completa
                                    <ArrowRight size={14} />
                                </Link>
                            </div>

                            {supportProducts.length > 0 ? (
                                <motion.div
                                    variants={stagger}
                                    initial="hidden"
                                    whileInView="visible"
                                    viewport={{ once: true, amount: 0.14 }}
                                    className="grid gap-x-5 gap-y-10 sm:grid-cols-3"
                                >
                                    {supportProducts.map((product) => (
                                        <motion.div key={product.slug} variants={reveal}>
                                            <ProductCard
                                                product={product}
                                                imageClassName="h-[300px] lg:h-[330px]"
                                            />
                                        </motion.div>
                                    ))}
                                </motion.div>
                            ) : (
                                <EmptyCommercialState
                                    title="Selección en preparación."
                                    description="Cuando haya más productos disponibles, verás aquí prendas recomendadas del catálogo."
                                    href={shopRoutes.catalog}
                                    label="Ver catálogo"
                                />
                            )}
                        </div>
                    </div>
                </section>

                <section className="pt-24">
                    <div className={sectionShell}>
                        <div className="mb-6 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                            <motion.div
                                initial={{ opacity: 0, y: 18 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true, amount: 0.35 }}
                                transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
                                className="max-w-xl"
                            >
                                <p className="text-[0.66rem] uppercase tracking-[0.3em] text-[rgb(236,205,191)]">
                                    Recomendación MORE
                                </p>
                                <h2
                                    className="mt-3 text-3xl leading-none text-white sm:text-4xl"
                                    style={serifStyle}
                                >
                                    Ideas para seguir comprando.
                                </h2>
                                <p className="mt-3 max-w-lg text-sm leading-6 text-stone-300">
                                    Accesos rápidos para volver al catálogo, novedades o guía de compra.
                                </p>
                            </motion.div>
                            <CarouselButtons
                                onPrev={() => heroApi?.scrollPrev()}
                                onNext={() => heroApi?.scrollNext()}
                            />
                        </div>

                        <motion.div
                            initial={{ opacity: 0, y: 22 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.18 }}
                            transition={{ duration: 0.65, ease: [0.22, 1, 0.36, 1] }}
                            className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]"
                        >
                            <div
                                ref={heroRef}
                                className="relative min-h-[300px] overflow-hidden rounded-[1.35rem] border border-white/10 bg-[#05070a] shadow-[0_22px_68px_rgba(0,0,0,0.34)] sm:min-h-[340px]"
                            >
                                <div className="flex h-full">
                                    {homeHeroSlides.map((slide) => (
                                        <div
                                            key={slide.title}
                                            className="relative min-w-0 flex-[0_0_100%]"
                                        >
                                            <img
                                                src={slide.image}
                                                alt={slide.title}
                                                className="h-full min-h-[300px] w-full object-cover sm:min-h-[340px]"
                                            />
                                            <div className="absolute inset-0 bg-gradient-to-r from-black/84 via-black/46 to-black/12" />
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/82 via-black/12 to-transparent" />
                                        </div>
                                    ))}
                                </div>

                                <div className="absolute inset-0 flex flex-col justify-end p-5 sm:p-6">
                                    <motion.div
                                        key={activeHero.title}
                                        initial={{ opacity: 0, y: 12 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        transition={{ duration: 0.38 }}
                                        className="max-w-2xl"
                                    >
                                        <p className="text-[0.68rem] uppercase tracking-[0.3em] text-[rgb(236,205,191)]">
                                            {activeHero.title}
                                        </p>
                                        <h2
                                            className="mt-3 max-w-xl text-3xl leading-[0.98] text-white sm:text-4xl lg:text-5xl"
                                            style={serifStyle}
                                        >
                                            MORE por Mati Boutique
                                        </h2>
                                        <p className="mt-3 max-w-lg text-sm leading-6 text-stone-100">
                                            Sigue explorando novedades, categorías y prendas disponibles en MORE.
                                        </p>
                                    </motion.div>

                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {heroStats.map((stat) => (
                                            <span
                                                key={stat}
                                                className="more-chip px-3 py-1.5 text-[0.6rem] uppercase tracking-[0.18em] text-stone-200"
                                            >
                                                {stat}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <aside className="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                                {heroQuickLinks.map((card) => (
                                    <Link
                                        key={card.title}
                                        href={card.href}
                                        className="group relative min-h-[118px] overflow-hidden rounded-[1.1rem] border border-white/10 bg-[#070a0f] sm:min-h-[136px] lg:min-h-[104px]"
                                    >
                                        <img
                                            src={card.image}
                                            alt={card.title}
                                            className="h-full w-full object-cover transition duration-[900ms] group-hover:scale-[1.04]"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/86 via-black/18 to-transparent" />
                                        <div className="absolute inset-x-0 bottom-0 p-4">
                                            <p className="text-[0.62rem] uppercase tracking-[0.24em] text-stone-200">
                                                {card.label}
                                            </p>
                                            <h3
                                                className="mt-1.5 text-xl leading-none text-white sm:text-2xl lg:text-xl"
                                                style={serifStyle}
                                            >
                                                {card.title}
                                            </h3>
                                        </div>
                                    </Link>
                                ))}
                            </aside>
                        </motion.div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="relative overflow-hidden rounded-[1.8rem] border border-[rgba(223,184,166,0.18)] bg-[#070a0f]">
                            <img
                                src="https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600"
                                alt="Universo MORE"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/88 via-black/54 to-black/20" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent" />
                            <div className="relative grid min-h-[380px] gap-8 p-7 sm:p-9 lg:grid-cols-[1fr_auto] lg:items-end lg:p-10">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.3em] text-[rgb(236,205,191)]">
                                        MORE por Mati Boutique
                                    </p>
                                    <h2
                                        className="mt-5 max-w-3xl text-4xl leading-[0.94] text-white sm:text-5xl lg:text-6xl"
                                        style={serifStyle}
                                    >
                                        Descubre el universo MORE.
                                    </h2>
                                    <p className="mt-5 max-w-xl text-sm leading-7 text-stone-100 sm:text-base">
                                        Explora todas las prendas disponibles y encuentra tu próxima pieza favorita.
                                    </p>
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row lg:flex-col">
                                    <Link
                                        href={shopRoutes.catalog}
                                        className={primaryButtonClass}
                                    >
                                        Navegar catálogo
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.collections}
                                        className={secondaryButtonClass}
                                    >
                                        Ver guía de compra
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <LandingFooter user={user} />
        </div>
    );
}
