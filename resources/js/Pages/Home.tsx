import LandingFooter from '@/Components/landing/LandingFooter';
import LandingHeader from '@/Components/landing/LandingHeader';
import SectionHeading from '@/Components/landing/SectionHeading';
import {
    homeBrandEditorialCards,
    homeCategoryVisuals,
    homeCollectionCards,
    homeHeroSlides,
} from '@/data/storefront';
import { shopRoutes } from '@/lib/shop';
import type {
    HomeBrandLink,
    HomeCategoryLink,
    ShopProduct,
} from '@/types/shop';
import type { LandingPageProps } from '@/types/landing';
import { Head, Link, usePage } from '@inertiajs/react';
import Autoplay from 'embla-carousel-autoplay';
import useEmblaCarousel from 'embla-carousel-react';
import { motion, type Variants } from 'framer-motion';
import { ArrowRight, ChevronLeft, ChevronRight, Play } from 'lucide-react';
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
        transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] },
    },
};

const stagger: Variants = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.08 } },
};

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';
const panelClass = 'more-panel rounded-[2rem]';
const primaryButtonClass =
    'more-cta-primary px-6 py-3 text-[0.78rem] uppercase tracking-[0.22em]';
const secondaryButtonClass =
    'more-cta-secondary px-6 py-3 text-[0.78rem] uppercase tracking-[0.22em]';
const editorialLinkClass =
    'more-cta-editorial text-[0.74rem] uppercase tracking-[0.24em]';
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
                className="more-icon-button inline-flex h-11 w-11 items-center justify-center"
            >
                <ChevronLeft size={18} />
            </button>
            <button
                type="button"
                onClick={onNext}
                className="more-icon-button inline-flex h-11 w-11 items-center justify-center"
            >
                <ChevronRight size={18} />
            </button>
        </div>
    );
}

export default function Home({
    novedades,
    seleccion,
    categorias,
    marcas,
}: HomeProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;

    const arrivals = novedades;
    const curatedSelection = seleccion;

    const [heroIndex, setHeroIndex] = useState(0);
    const [heroRef, heroApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 4800, stopOnInteraction: false })],
    );
    const [arrivalRef, arrivalApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 3400, stopOnInteraction: false })],
    );
    const [selectionRef, selectionApi] = useEmblaCarousel(
        { loop: true, align: 'start' },
        [Autoplay({ delay: 3600, stopOnInteraction: false })],
    );

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

    return (
        <div className="relative min-h-screen overflow-x-hidden bg-[#05070b] text-white">
            <Head title="MORE | Mati Boutique" />

            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[680px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_42%),radial-gradient(circle_at_20%_20%,rgba(181,122,109,0.18),transparent_30%),linear-gradient(180deg,#0b1016_0%,#05070b_68%)]" />
                <div className="absolute left-[-10%] top-[12%] h-80 w-80 rounded-full bg-white/6 blur-[120px]" />
                <div className="absolute right-[-12%] top-[30%] h-[28rem] w-[28rem] rounded-full bg-[rgba(181,122,109,0.12)] blur-[140px]" />
            </div>

            <LandingHeader user={user} />

            <main className="pb-24">
                <section className="px-4 pt-28 sm:px-6 sm:pt-32 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className={`${panelClass} overflow-hidden p-3 sm:p-4`}>
                            <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                                <div
                                    className="relative overflow-hidden rounded-[1.8rem]"
                                    ref={heroRef}
                                >
                                    <div className="flex">
                                        {homeHeroSlides.map((slide) => (
                                            <div
                                                key={slide.title}
                                                className="relative min-w-0 flex-[0_0_100%]"
                                            >
                                                <img
                                                    src={slide.image}
                                                    alt={slide.title}
                                                    className="h-[74vh] min-h-[590px] w-full object-cover scale-[1.02] transition duration-[1600ms]"
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-r from-black/72 via-black/26 to-transparent" />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/62 via-transparent to-transparent" />
                                                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.12),transparent_30%)]" />
                                                <div className="absolute left-6 top-6 rounded-full border border-white/15 bg-black/20 px-4 py-2 text-[0.68rem] uppercase tracking-[0.26em] text-stone-200 backdrop-blur sm:left-8 sm:top-8">
                                                    MORE por Mati Boutique
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                                        <motion.div
                                            key={homeHeroSlides[heroIndex]?.title}
                                            initial={{ opacity: 0, y: 16 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{ duration: 0.45 }}
                                            className="max-w-xl"
                                        >
                                            <p className="text-[0.72rem] uppercase tracking-[0.32em] text-stone-200">
                                                MORE
                                            </p>
                                            <h1
                                                className="mt-3 text-5xl leading-[0.92] text-white sm:text-6xl lg:text-[5.8rem]"
                                                style={serifStyle}
                                            >
                                                {homeHeroSlides[heroIndex]?.title}
                                            </h1>
                                            <p className="mt-4 text-base text-stone-100 sm:text-lg">
                                                {homeHeroSlides[heroIndex]?.subtitle}
                                            </p>
                                            <p className="mt-2 text-sm uppercase tracking-[0.22em] text-stone-300">
                                                {homeHeroSlides[heroIndex]?.note}
                                            </p>
                                        </motion.div>

                                        <div className="mt-7 flex flex-col gap-4 sm:flex-row sm:items-center">
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
                                                Entrar a Novedades
                                            </Link>
                                            <Link
                                                href={shopRoutes.editorial}
                                                className={editorialLinkClass}
                                            >
                                                <Play size={14} />
                                                Ver editorial
                                            </Link>
                                        </div>

                                        <div className="mt-6 flex flex-wrap gap-2">
                                            {[
                                                `${categorias.length} capítulos para recorrer`,
                                                `${marcas.length} firmas en selección`,
                                                `${novedades.length} piezas destacadas`,
                                            ].map((chip) => (
                                                <span
                                                    key={chip}
                                                    className="more-pill rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-200 transition duration-300 hover:-translate-y-px hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.08)]"
                                                >
                                                    {chip}
                                                </span>
                                            ))}
                                        </div>

                                        <div className="mt-7 flex items-center gap-3">
                                            {homeHeroSlides.map((slide, index) => (
                                                <button
                                                    key={slide.title}
                                                    type="button"
                                                    onClick={() => heroApi?.scrollTo(index)}
                                                    className={[
                                                        'h-2 rounded-full transition-all duration-300',
                                                        heroIndex === index
                                                            ? 'w-10 bg-white'
                                                            : 'w-2 bg-white/35',
                                                    ].join(' ')}
                                                    aria-label={`Ir a ${slide.title}`}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-4 lg:grid-rows-[1.15fr_0.85fr]">
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                                        {homeCollectionCards.map((card) => (
                                            <Link
                                                key={card.title}
                                                href={card.href}
                                                className="group relative overflow-hidden rounded-[1.8rem]"
                                            >
                                                <img
                                                    src={card.image}
                                                    alt={card.title}
                                                    className="h-[280px] w-full object-cover transition duration-700 group-hover:scale-[1.04]"
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
                                                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.12),transparent_40%)] opacity-80 transition duration-500 group-hover:opacity-100" />
                                                <div className="absolute bottom-0 left-0 right-0 p-6">
                                                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-200">
                                                        {card.label}
                                                    </p>
                                                    <h2
                                                        className="mt-3 text-3xl text-white"
                                                        style={serifStyle}
                                                    >
                                                        {card.title}
                                                    </h2>
                                                    <p className="mt-4 text-[0.68rem] uppercase tracking-[0.22em] text-stone-200">
                                                        Ver colección
                                                    </p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>

                                    <div className="more-panel-soft rounded-[1.8rem] p-6 sm:p-8">
                                        <div className="flex items-center justify-between">
                                            <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                                                Editorial Mati Boutique
                                            </p>
                                            <span className="more-pill rounded-full px-3 py-1 text-[0.65rem] uppercase tracking-[0.2em] text-stone-300">
                                                Edición insignia
                                            </span>
                                        </div>
                                        <h2
                                            className="mt-5 text-4xl leading-none text-white"
                                            style={serifStyle}
                                        >
                                            La elegancia entra en voz baja.
                                        </h2>
                                        <p className="mt-4 max-w-sm text-sm leading-7 text-stone-300">
                                            Capas pulidas, líneas serenas y una composición pensada para el día y la noche.
                                        </p>
                                        <div className="mt-5 flex flex-wrap gap-2">
                                            {['Sastrería suave', 'Brillo satinado', 'Noche urbana'].map(
                                                (tag) => (
                                                    <span
                                                        key={tag}
                                                        className="more-pill rounded-full px-3 py-1.5 text-[0.65rem] uppercase tracking-[0.2em] text-stone-300 transition duration-300 hover:-translate-y-px hover:border-[rgba(181,122,109,0.24)]"
                                                    >
                                                        {tag}
                                                    </span>
                                                ),
                                            )}
                                        </div>
                                        <Link
                                            href={shopRoutes.editorial}
                                            className={`${editorialLinkClass} mt-7`}
                                        >
                                            <Play size={14} />
                                            Ver editorial
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Novedades"
                                title="Novedades"
                                description="Piezas recién llegadas con líneas limpias y una presencia tranquila."
                            />
                            <CarouselButtons
                                onPrev={() => arrivalApi?.scrollPrev()}
                                onNext={() => arrivalApi?.scrollNext()}
                            />
                        </div>

                        <div className="mt-10 overflow-hidden" ref={arrivalRef}>
                            <div className="-ml-4 flex">
                                {arrivals.map((item) => (
                                    <div
                                        key={item.slug}
                                        className="min-w-0 flex-[0_0_84%] pl-4 sm:flex-[0_0_48%] lg:flex-[0_0_31%]"
                                    >
                                        <motion.article
                                            variants={reveal}
                                            initial="hidden"
                                            whileInView="visible"
                                            viewport={{ once: true, amount: 0.2 }}
                                            className="group transition duration-500 hover:-translate-y-1"
                                        >
                                            <Link href={shopRoutes.product(item.slug)} className="block">
                                                <div className="relative overflow-hidden rounded-[1.8rem]">
                                                    <img
                                                        src={item.image}
                                                        alt={item.name}
                                                        className="h-[430px] w-full object-cover transition duration-[900ms] group-hover:scale-[1.04]"
                                                    />
                                                    <div className="absolute inset-0 bg-gradient-to-t from-black/58 via-transparent to-transparent" />
                                                    <div className="absolute left-4 top-4 rounded-full border border-white/12 bg-black/20 px-3 py-1 text-[0.62rem] uppercase tracking-[0.2em] text-stone-100 backdrop-blur">
                                                        {item.category}
                                                    </div>
                                                </div>
                                                <div className="pt-5">
                                                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                                        {item.line || item.category}
                                                    </p>
                                                    <div className="mt-2 flex items-end justify-between gap-4">
                                                        <h3
                                                            className="text-3xl text-white"
                                                            style={serifStyle}
                                                        >
                                                            {item.name}
                                                        </h3>
                                                        <span className="text-sm text-[rgb(223,184,166)]">
                                                            Bs {item.price}
                                                        </span>
                                                    </div>
                                                </div>
                                            </Link>
                                        </motion.article>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Capítulos de estilo"
                                title="Categorías"
                                description="Recorre la boutique por capítulos y encuentra el tono que define tu guardarropa."
                            />
                            <Link href={shopRoutes.catalog} className={secondaryButtonClass}>
                                Ver catálogo completo
                            </Link>
                        </div>

                        <motion.div
                            variants={stagger}
                            initial="hidden"
                            whileInView="visible"
                            viewport={{ once: true, amount: 0.15 }}
                            className="mt-10 grid gap-4 md:grid-cols-12"
                        >
                            {categorias.map((categoria, index) => {
                                const visual =
                                    homeCategoryVisuals[index % homeCategoryVisuals.length];

                                return (
                                    <motion.div
                                        key={categoria.id}
                                        variants={reveal}
                                        className={visual.span}
                                    >
                                        <Link
                                            href={categoria.href}
                                            className="group relative block overflow-hidden rounded-[1.8rem]"
                                        >
                                            <img
                                                src={visual.image}
                                                alt={categoria.name}
                                                className="h-[300px] w-full object-cover transition duration-700 group-hover:scale-[1.04] sm:h-[360px]"
                                            />
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-transparent" />
                                            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.12),transparent_46%)] opacity-80 transition duration-500 group-hover:opacity-100" />
                                            <div className="absolute bottom-0 left-0 right-0 p-6">
                                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-200">
                                                    Capítulo curado
                                                </p>
                                                <h3
                                                    className="mt-3 text-4xl text-white"
                                                    style={serifStyle}
                                                >
                                                    {categoria.name}
                                                </h3>
                                                {categoria.desc && (
                                                    <p className="mt-3 max-w-sm text-sm leading-7 text-stone-100">
                                                        {categoria.desc}
                                                    </p>
                                                )}
                                            </div>
                                        </Link>
                                    </motion.div>
                                );
                            })}
                        </motion.div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className={`${panelClass} overflow-hidden px-6 py-8 sm:px-8 sm:py-10 lg:px-10`}>
                            <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                                <div>
                                    <SectionHeading
                                        eyebrow="Firmas seleccionadas"
                                        title="Firmas en tienda"
                                        description="Firmas que conviven bajo una misma mirada: calma, estructura y detalle."
                                    />

                                    <div className="mt-8 flex flex-wrap gap-3">
                                        {marcas.map((marca) => (
                                            <Link
                                                key={marca.id}
                                                href={marca.href}
                                                className="more-pill rounded-full px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-200 transition duration-300 hover:-translate-y-px hover:border-[rgba(181,122,109,0.28)] hover:bg-[rgba(181,122,109,0.08)]"
                                            >
                                                {marca.name}
                                            </Link>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    {homeBrandEditorialCards.map((card) => (
                                        <Link
                                            key={card.title}
                                            href={card.href}
                                            className="group more-panel-soft overflow-hidden rounded-[1.5rem] transition duration-300 hover:-translate-y-1"
                                        >
                                            <div className="overflow-hidden">
                                                <img
                                                    src={card.image}
                                                    alt={card.title}
                                                    className="h-[220px] w-full object-cover transition duration-[900ms] group-hover:scale-[1.04]"
                                                />
                                            </div>
                                            <div className="p-4">
                                                <p className="text-[0.7rem] uppercase tracking-[0.22em] text-stone-400">
                                                    {card.note}
                                                </p>
                                                <p
                                                    className="mt-2 text-2xl leading-none text-white"
                                                    style={serifStyle}
                                                >
                                                    {card.title}
                                                </p>
                                            </div>
                                        </Link>
                                    ))}
                                    </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="grid gap-4 lg:grid-cols-[1.12fr_0.88fr]">
                            <Link
                                href={shopRoutes.editorial}
                                className="relative overflow-hidden rounded-[2rem]"
                            >
                                <img
                                    src="https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1600"
                                    alt="Editorial Mati Boutique"
                                    className="h-[620px] w-full object-cover"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent" />
                                <div className="absolute bottom-0 left-0 right-0 p-8 sm:p-10">
                                    <p className="text-[0.72rem] uppercase tracking-[0.26em] text-stone-200">
                                        Editorial Mati Boutique
                                    </p>
                                    <h2
                                        className="mt-4 max-w-xl text-5xl leading-none text-white sm:text-6xl"
                                        style={serifStyle}
                                    >
                                        Una forma más serena de vestir.
                                    </h2>
                                </div>
                            </Link>

                            <div className="grid gap-4">
                                <div className={`${panelClass} p-8 sm:p-10`}>
                                    <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                                        Cuaderno MORE
                                    </p>
                                    <p
                                        className="mt-5 text-4xl leading-none text-white"
                                        style={serifStyle}
                                    >
                                        Brillo suave. Líneas precisas. Ritmo contenido.
                                    </p>
                                    <p className="mt-5 max-w-sm text-sm leading-7 text-stone-300">
                                        Una mirada de boutique para piezas que se sienten actuales, limpias y memorables.
                                    </p>
                                    <Link
                                        href={shopRoutes.editorial}
                                        className={`${editorialLinkClass} mt-7`}
                                    >
                                        <Play size={14} />
                                        Ver editorial
                                    </Link>
                                </div>

                                <Link href={shopRoutes.collections} className="overflow-hidden rounded-[2rem]">
                                    <img
                                        src="https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1400"
                                        alt="Lookbook"
                                        className="h-[250px] w-full object-cover transition duration-700 hover:scale-[1.03]"
                                    />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Selección Mati Boutique"
                                title="Piezas con presencia"
                                description="Una edición más ceñida para un guardarropa con calma y carácter."
                            />
                            <CarouselButtons
                                onPrev={() => selectionApi?.scrollPrev()}
                                onNext={() => selectionApi?.scrollNext()}
                            />
                        </div>

                        <div className="mt-10 overflow-hidden" ref={selectionRef}>
                            <div className="-ml-4 flex">
                                {curatedSelection.map((item) => (
                                    <div
                                        key={item.slug}
                                        className="min-w-0 flex-[0_0_82%] pl-4 sm:flex-[0_0_42%] lg:flex-[0_0_24%]"
                                    >
                                        <Link
                                            href={shopRoutes.product(item.slug)}
                                            className="group block transition duration-500 hover:-translate-y-1"
                                        >
                                            <div className="overflow-hidden rounded-[1.8rem]">
                                                <img
                                                    src={item.image}
                                                    alt={item.name}
                                                    className="h-[360px] w-full object-cover transition duration-[900ms] group-hover:scale-[1.04]"
                                                />
                                            </div>
                                            <div className="pt-5">
                                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                                    {item.line || item.category}
                                                </p>
                                                <h3
                                                    className="mt-2 text-3xl text-white"
                                                    style={serifStyle}
                                                >
                                                    {item.name}
                                                </h3>
                                            </div>
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="pt-28">
                    <div className={sectionShell}>
                        <div className="more-panel relative overflow-hidden rounded-[2.2rem] px-6 py-12 sm:px-10 lg:px-14">
                            <div className="absolute -right-10 top-0 h-44 w-44 rounded-full bg-[rgba(223,184,166,0.12)] blur-[90px]" />
                            <div className="absolute bottom-0 left-0 h-40 w-40 rounded-full bg-[rgba(103,63,69,0.16)] blur-[90px]" />

                            <div className="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.26em] text-stone-300">
                                        MORE por Mati Boutique
                                    </p>
                                    <h2
                                        className="mt-4 max-w-3xl text-4xl leading-none text-white sm:text-5xl"
                                        style={serifStyle}
                                    >
                                        Entra en el universo MORE.
                                    </h2>
                                </div>

                                <div className="flex flex-col gap-4 sm:flex-row lg:flex-col">
                                    <Link href={shopRoutes.collections} className={primaryButtonClass}>
                                        Ver colecciones
                                    </Link>
                                    <Link href={shopRoutes.newIn} className={secondaryButtonClass}>
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
