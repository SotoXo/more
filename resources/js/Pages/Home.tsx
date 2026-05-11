import LandingFooter from '@/Components/landing/LandingFooter';
import LandingHeader from '@/Components/landing/LandingHeader';
import SectionHeading from '@/Components/landing/SectionHeading';
import ProductCard from '@/Components/shop/ProductCard';
import StorefrontImage from '@/Components/shop/StorefrontImage';
import {
    homeCategoryVisuals,
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
} from 'lucide-react';
import { useEffect, useState } from 'react';

type HomeProps = {
    novedades: ShopProduct[];
    seleccion: ShopProduct[];
    categorias: HomeCategoryLink[];
    marcas: HomeBrandLink[];
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
                <StorefrontImage
                    src={product.image}
                    alt={product.name}
                    className="h-full w-full object-cover transition duration-[1000ms] group-hover:scale-[1.035]"
                />
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
                        className="mt-5 max-w-full break-words text-4xl leading-[0.96] text-white sm:max-w-lg sm:text-6xl sm:leading-[0.92]"
                        style={serifStyle}
                    >
                        {product.name}
                    </h3>
                    <p className="mt-5 max-w-md text-sm leading-7 text-stone-200/90">
                        {product.description ||
                            'Prenda disponible en Mati Boutique. Revisa talla, color y precio antes de reservar.'}
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
                                {product.line || product.style || 'Selección Mati Boutique'}
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
                Mati Boutique
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
    const [coverRef, coverApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 6100, stopOnInteraction: false })],
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

    const activeCover = homeHeroSlides[coverIndex] ?? homeHeroSlides[0];
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
            <Head title="Mati Boutique" />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[860px] bg-[linear-gradient(180deg,rgba(181,122,109,0.1),transparent_62%)]" />
                <div className="absolute inset-x-0 top-28 h-px bg-[linear-gradient(90deg,transparent,rgba(223,184,166,0.22),transparent)]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24" style={{ background: homeBackground }}>
                <section className="relative min-h-[88svh] overflow-hidden lg:min-h-[100svh]">
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
                                        <StorefrontImage
                                            src={slide.image}
                                            alt={slide.title}
                                            className="h-[88svh] min-h-[560px] w-full object-cover lg:h-[100svh] lg:min-h-[620px]"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-r from-black/88 via-black/56 to-black/16" />
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/84 via-black/18 to-black/18" />
                                        <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.18),transparent_40%,rgba(0,0,0,0.16))]" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </motion.div>

                    <div className="relative z-10 flex min-h-[88svh] items-center px-4 pb-20 pt-24 sm:px-8 sm:pt-32 lg:min-h-[100svh] lg:px-14">
                        <div className="mx-auto grid w-full max-w-[92rem] gap-10 lg:grid-cols-[minmax(0,0.82fr)_minmax(280px,0.32fr)] lg:items-end">
                            <motion.div
                                key={activeCover.title}
                                initial={{ opacity: 0, y: 18 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.52, ease: [0.22, 1, 0.36, 1] }}
                                className="max-w-[850px] py-8 sm:py-12"
                            >
                                <p className="max-w-full text-[0.66rem] uppercase tracking-[0.26em] text-[rgb(236,205,191)] sm:text-[0.72rem] sm:tracking-[0.32em]">
                                    Mati Boutique
                                </p>
                                <h1
                                    className="mt-5 max-w-[calc(100vw-2rem)] break-words text-[clamp(2.2rem,10.5vw,3.2rem)] leading-[1] text-white sm:max-w-[820px] sm:text-[clamp(3.2rem,7vw,7.15rem)] sm:leading-[0.94]"
                                    style={serifStyle}
                                >
                                    <span className="block">Prendas para</span>
                                    <span className="block">reservar</span>
                                    <span className="block">con estilo.</span>
                                </h1>
                                <p className="mt-6 max-w-[22rem] text-base leading-7 text-stone-100 sm:max-w-[620px] sm:text-lg sm:leading-8">
                                    {activeCover.subtitle}
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Link
                                        href={shopRoutes.catalog}
                                        className={`${primaryButtonClass} w-full sm:w-auto`}
                                    >
                                        Ver catálogo
                                        <ArrowRight size={16} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.newIn}
                                        className={`${secondaryButtonClass} w-full sm:w-auto`}
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
                                    href={shopRoutes.catalog}
                                    className={`${editorialLinkClass} mt-7`}
                                >
                                    Ver catálogo
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
                                    description="Cuando haya novedades activas, las verás aquí con talla, color y disponibilidad."
                                    href={shopRoutes.catalog}
                                    label="Ver catálogo"
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
                                title="Colecciones por categoría."
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
                                                <StorefrontImage
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
                        <div className="grid gap-8 lg:grid-cols-[0.86fr_1.14fr] lg:items-start">
                            <div>
                                <SectionHeading
                                    eyebrow="Marcas y destacados"
                                    title="Prendas destacadas."
                                    description="Accede a marcas disponibles y prendas recientes para encontrar más rápido lo que buscas."
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
                                    Ver selección completa
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

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="relative overflow-hidden rounded-[1.8rem] border border-[rgba(223,184,166,0.18)] bg-[#070a0f]">
                            <StorefrontImage
                                src="https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1600"
                                alt="Reserva de prendas en Mati Boutique"
                                className="absolute inset-0 h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/88 via-black/54 to-black/20" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent" />
                            <div className="relative grid min-h-[380px] gap-8 p-7 sm:p-9 lg:grid-cols-[1fr_auto] lg:items-end lg:p-10">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.3em] text-[rgb(236,205,191)]">
                                        Mati Boutique
                                    </p>
                                    <h2
                                        className="mt-5 max-w-3xl text-4xl leading-[0.94] text-white sm:text-5xl lg:text-6xl"
                                        style={serifStyle}
                                    >
                                        Reserva tus prendas favoritas.
                                    </h2>
                                    <p className="mt-5 max-w-xl text-sm leading-7 text-stone-100 sm:text-base">
                                        Consulta tallas, colores y disponibilidad antes de reservar.
                                    </p>
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row lg:flex-col">
                                    <Link
                                        href={shopRoutes.catalog}
                                        className={`${primaryButtonClass} w-full sm:w-auto lg:w-full`}
                                    >
                                        Ver catálogo
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.newIn}
                                        className={`${secondaryButtonClass} w-full sm:w-auto lg:w-full`}
                                    >
                                        Ver novedades
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
