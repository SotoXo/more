import SectionHeading from '@/Components/landing/SectionHeading';
import ProductRail from '@/Components/shop/ProductRail';
import StoreLayout from '@/Components/shop/StoreLayout';
import {
    editorialFeatures,
    editorialMoments,
    getProductsBySlugs,
    homeArrivalSlugs,
    homeSelectionSlugs,
    shopRoutes,
    type ShopEditorialMode,
} from '@/data/shop';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function Editorial({ mode }: { mode: ShopEditorialMode }) {
    const isCollections = mode === 'collections';
    const railProducts = getProductsBySlugs(
        isCollections ? homeArrivalSlugs : homeSelectionSlugs,
    );

    return (
        <StoreLayout
            title={
                isCollections ? 'MORE | Colecciones' : 'MORE | Editorial Mati Boutique'
            }
        >
            <section className="px-4 sm:px-6 lg:px-8">
                <div className={`${sectionShell} px-0`}>
                    <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <div className="relative overflow-hidden rounded-[2.2rem] border border-white/10">
                            <img
                                src={
                                    isCollections
                                        ? 'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=1600'
                                        : 'https://images.pexels.com/photos/1066171/pexels-photo-1066171.jpeg?auto=compress&cs=tinysrgb&w=1600'
                                }
                                alt={isCollections ? 'Colecciones' : 'Editorial'}
                                className="h-[420px] w-full object-cover sm:h-[520px]"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/72 via-black/28 to-transparent" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/72 via-transparent to-transparent" />
                            <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                                <p className="text-[0.7rem] uppercase tracking-[0.28em] text-stone-200">
                                    {isCollections
                                        ? 'Curated by Mati Boutique'
                                        : 'MORE journal'}
                                </p>
                                <h1
                                    className="mt-4 max-w-2xl text-5xl leading-[0.92] text-white sm:text-6xl"
                                    style={serifStyle}
                                >
                                    {isCollections
                                        ? 'Colecciones listas para vender estilo.'
                                        : 'Editorial Mati Boutique.'}
                                </h1>
                                <p className="mt-4 max-w-lg text-sm leading-7 text-stone-100">
                                    {isCollections
                                        ? 'Looks y edits pensados para navegar la tienda como una boutique real.'
                                        : 'Composiciones limpias, capas suaves y una mirada mas editorial sobre la seleccion.'}
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-4">
                            <div className="rounded-[2rem] border border-white/10 bg-white/[0.05] p-7 backdrop-blur-xl sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    {isCollections ? 'Shop flows' : 'Editorial notes'}
                                </p>
                                <p
                                    className="mt-5 text-4xl leading-none text-white"
                                    style={serifStyle}
                                >
                                    {isCollections
                                        ? 'Mujer, Hombre, Accesorios y New In.'
                                        : 'Quiet light. Clean silhouettes. Better rhythm.'}
                                </p>
                                <div className="mt-6 flex flex-wrap gap-2">
                                    {(isCollections
                                        ? ['Mujer', 'Hombre', 'Accesorios', 'New In']
                                        : ['Old money', 'Quiet luxury', 'Tailoring', 'Soft neutrals']
                                    ).map((item) => (
                                        <span
                                            key={item}
                                            className="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-200"
                                        >
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-[2rem] border border-white/10 bg-[#0c1117] p-7 sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    Mati Boutique
                                </p>
                                <div className="mt-5 flex flex-col gap-3">
                                    <Link
                                        href={shopRoutes.women}
                                        className="inline-flex items-center justify-between rounded-[1.3rem] border border-white/10 px-4 py-4 text-sm uppercase tracking-[0.2em] text-white transition duration-300 hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Mujer
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.men}
                                        className="inline-flex items-center justify-between rounded-[1.3rem] border border-white/10 px-4 py-4 text-sm uppercase tracking-[0.2em] text-white transition duration-300 hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Hombre
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.accessories}
                                        className="inline-flex items-center justify-between rounded-[1.3rem] border border-white/10 px-4 py-4 text-sm uppercase tracking-[0.2em] text-white transition duration-300 hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Accesorios
                                        <ArrowRight size={15} />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="pt-16">
                <div className={sectionShell}>
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <SectionHeading
                            eyebrow={isCollections ? 'Shop the edit' : 'The journal'}
                            title={
                                isCollections ? 'Colecciones destacadas' : 'Frames from the season'
                            }
                            description={
                                isCollections
                                    ? 'Una navegacion comercial para entrar directo a la seleccion.'
                                    : 'Una capa visual mas aspiracional para seguir entrando al catalogo.'
                            }
                        />
                        <Link
                            href={shopRoutes.newIn}
                            className="inline-flex items-center justify-center rounded-full border border-white/12 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.05]"
                        >
                            Ver novedades
                        </Link>
                    </div>

                    <div className="mt-10 grid gap-5 lg:grid-cols-3">
                        {editorialFeatures.map((feature) => (
                            <Link
                                key={feature.title}
                                href={feature.href}
                                className="group relative overflow-hidden rounded-[2rem] border border-white/10"
                            >
                                <img
                                    src={feature.image}
                                    alt={feature.title}
                                    className="h-[420px] w-full object-cover transition duration-[900ms] group-hover:scale-[1.03]"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/74 via-transparent to-transparent" />
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
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {!isCollections && (
                <section className="pt-16">
                    <div className={sectionShell}>
                        <div className="grid gap-4 md:grid-cols-3">
                            {editorialMoments.map((moment) => (
                                <article
                                    key={moment.title}
                                    className="overflow-hidden rounded-[1.9rem] border border-white/10 bg-[#0b1016]"
                                >
                                    <img
                                        src={moment.image}
                                        alt={moment.title}
                                        className="h-[300px] w-full object-cover"
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
                                </article>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <section className="pt-16">
                <div className={sectionShell}>
                    <ProductRail
                        eyebrow={isCollections ? 'Now available' : 'Selection Mati Boutique'}
                        title={
                            isCollections ? 'Piezas para entrar al catalogo' : 'Prendas para seguir el mood'
                        }
                        description={
                            isCollections
                                ? 'Un primer carrusel comercial para entrar a producto desde la home.'
                                : 'Una extension natural del editorial hacia el detalle de producto.'
                        }
                        products={railProducts}
                    />
                </div>
            </section>
        </StoreLayout>
    );
}
