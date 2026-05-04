import SectionHeading from '@/Components/landing/SectionHeading';
import ProductRail from '@/Components/shop/ProductRail';
import StoreLayout from '@/Components/shop/StoreLayout';
import { shopRoutes } from '@/lib/shop';
import type {
    ShopCuratedFeature,
    ShopCuratedLink,
    ShopCuratedMoment,
    ShopCuratedRailContent,
    ShopEditorialMode,
    ShopProduct,
} from '@/types/shop';
import { motion } from 'framer-motion';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type EditorialProps = {
    mode: ShopEditorialMode;
    heroImage: string;
    highlights: string[];
    navigationLinks: ShopCuratedLink[];
    featureCards: ShopCuratedFeature[];
    momentCards: ShopCuratedMoment[];
    railProducts: ShopProduct[];
    railContent: ShopCuratedRailContent;
};

export default function Editorial({
    mode,
    heroImage,
    highlights,
    navigationLinks,
    featureCards,
    momentCards,
    railProducts,
    railContent,
}: EditorialProps) {
    const isCollections = mode === 'collections';
    const primaryButtonClass =
        'more-cta-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]';
    const editorialLinkClass =
        'more-cta-editorial text-[0.72rem] uppercase tracking-[0.22em]';

    const pageCopy = isCollections
        ? {
            title: 'MORE | Guía de compra',
            heroEyebrow: 'Guía de compra',
            heroTitle: 'Encuentra prendas por estilo y categoría.',
            heroDescription:
                'Accesos rápidos para explorar la tienda según lo que necesitas comprar.',
            summaryEyebrow: 'Compra más fácil',
            summaryTitle: 'Elige una ruta y entra directo al catálogo.',
            featuresEyebrow: 'Comprar por categoría',
            featuresTitle: 'Entradas al catálogo',
            featuresDescription:
                'Categorías activas para ver prendas disponibles sin perder tiempo.',
        }
        : {
            title: 'MORE | Editorial Mati Boutique',
            heroEyebrow: 'Inspiración MORE',
            heroTitle: 'Ideas para elegir mejor.',
            heroDescription:
                'Una guía visual con prendas, marcas y categorías disponibles en la tienda.',
            summaryEyebrow: 'Selección actual',
            summaryTitle: 'Prendas y categorías para descubrir nuevas combinaciones.',
            featuresEyebrow: 'Ideas de compra',
            featuresTitle: 'Explora por estilo',
            featuresDescription:
                'Accesos visuales para seguir comprando dentro del catálogo.',
        };

    return (
        <StoreLayout title={pageCopy.title}>
            <section className="px-4 sm:px-6 lg:px-8">
                <div className={`${sectionShell} px-0`}>
                    <motion.div
                        initial={{ opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
                        className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]"
                    >
                        <div className="relative overflow-hidden rounded-[2.2rem] border border-white/10">
                            <img
                                src={heroImage}
                                alt={isCollections ? 'Guía de compra' : 'Inspiración MORE'}
                                className="h-[420px] w-full object-cover sm:h-[520px]"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/72 via-black/28 to-transparent" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/72 via-transparent to-transparent" />
                            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.14),transparent_42%)]" />
                            <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                                <p className="text-[0.7rem] uppercase tracking-[0.28em] text-stone-200">
                                    {pageCopy.heroEyebrow}
                                </p>
                                <h1
                                    className="mt-4 max-w-2xl text-5xl leading-[0.92] text-white sm:text-6xl"
                                    style={serifStyle}
                                >
                                    {pageCopy.heroTitle}
                                </h1>
                                <p className="mt-4 max-w-lg text-sm leading-7 text-stone-100">
                                    {pageCopy.heroDescription}
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-4">
                            <div className="more-panel rounded-[2rem] p-7 sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                                    {pageCopy.summaryEyebrow}
                                </p>
                                <p
                                    className="mt-5 text-4xl leading-none text-white"
                                    style={serifStyle}
                                >
                                    {pageCopy.summaryTitle}
                                </p>
                                <div className="mt-6 flex flex-wrap gap-2">
                                    {highlights.map((item) => (
                                        <span
                                            key={item}
                                            className="more-pill rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-200"
                                        >
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="more-panel-soft rounded-[2rem] p-7 sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    Mati Boutique
                                </p>
                                <div className="mt-5 flex flex-col gap-3">
                                    {navigationLinks.map((link) => (
                                        <Link
                                            key={`${link.label}-${link.href}`}
                                            href={link.href}
                                            className="inline-flex items-center justify-between rounded-[1.3rem] border border-white/10 px-4 py-4 text-sm uppercase tracking-[0.2em] text-white transition duration-300 hover:border-[rgba(181,122,109,0.26)] hover:bg-[rgba(181,122,109,0.08)]"
                                        >
                                            <span className="flex flex-col items-start gap-1">
                                                <span>{link.label}</span>
                                                {link.meta && (
                                                    <span className="text-[0.62rem] tracking-[0.18em] text-stone-400">
                                                        {link.meta}
                                                    </span>
                                                )}
                                            </span>
                                            <ArrowRight size={15} />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </section>

            <section className="pt-16">
                <div className={sectionShell}>
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <SectionHeading
                            eyebrow={pageCopy.featuresEyebrow}
                            title={pageCopy.featuresTitle}
                            description={pageCopy.featuresDescription}
                        />
                        <Link
                            href={shopRoutes.catalog}
                            className={primaryButtonClass}
                        >
                            Ver catálogo
                        </Link>
                    </div>

                    <div className="mt-10 grid gap-5 lg:grid-cols-3">
                        {featureCards.map((feature) => (
                            <Link
                                key={`${feature.title}-${feature.href}`}
                                href={feature.href}
                                className="group relative overflow-hidden rounded-[2rem] border border-white/10"
                            >
                                <img
                                    src={feature.image}
                                    alt={feature.title}
                                    className="h-[420px] w-full object-cover transition duration-[900ms] group-hover:scale-[1.03]"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/76 via-transparent to-transparent" />
                                <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.12),transparent_40%)] opacity-75 transition duration-500 group-hover:opacity-100" />
                                <div className="absolute bottom-0 left-0 right-0 p-6">
                                    <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-200">
                                        {feature.eyebrow}
                                    </p>
                                    <p
                                        className="mt-3 text-4xl leading-none text-white"
                                        style={serifStyle}
                                    >
                                        {feature.title}
                                    </p>
                                    <p className="mt-3 max-w-sm text-sm leading-7 text-stone-100">
                                        {feature.description}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {!isCollections && momentCards.length > 0 && (
                <section className="pt-16">
                    <div className={sectionShell}>
                        <div className="grid gap-4 md:grid-cols-3">
                            {momentCards.map((moment) => (
                                <Link
                                    key={`${moment.title}-${moment.href}`}
                                    href={moment.href}
                                    className="group more-panel-soft overflow-hidden rounded-[1.9rem]"
                                >
                                    <img
                                        src={moment.image}
                                        alt={moment.title}
                                        className="h-[300px] w-full object-cover transition duration-700 group-hover:scale-[1.03]"
                                    />
                                    <div className="p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                            {moment.note}
                                        </p>
                                        <p
                                            className="mt-2 text-3xl leading-none text-white"
                                            style={serifStyle}
                                        >
                                            {moment.title}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {railProducts.length > 0 && (
                <section className="pt-16">
                    <div className={sectionShell}>
                        <ProductRail
                            eyebrow={railContent.eyebrow}
                            title={railContent.title}
                            description={railContent.description}
                            products={railProducts}
                        />
                    </div>
                </section>
            )}

            <section className="pt-16">
                <div className={sectionShell}>
                    <div className="more-panel-soft rounded-[2rem] p-7 sm:p-8">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    MORE por Mati Boutique
                                </p>
                                <p
                                    className="mt-3 text-3xl leading-none text-white"
                                    style={serifStyle}
                                >
                                    {isCollections
                                        ? 'Del estilo al producto.'
                                        : 'De la inspiración a la compra.'}
                                </p>
                            </div>
                            <Link
                                href={shopRoutes.newIn}
                                className={editorialLinkClass}
                            >
                                Ir a novedades
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </StoreLayout>
    );
}
