import ShopFiltersPanel from '@/Components/shop/ShopFiltersPanel';
import ProductCard from '@/Components/shop/ProductCard';
import StoreLayout from '@/Components/shop/StoreLayout';
import ShopToolbar from '@/Components/shop/ShopToolbar';
import {
    createDefaultFilters,
    filterProducts,
    getCollectionProducts,
    getProductFilterOptions,
    shopCollections,
    shopRoutes,
    sortProducts,
    type ShopCollectionKey,
    type ShopFilterState,
    type ShopSortKey,
} from '@/data/shop';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';

type MultiFilterKey = keyof Pick<
    ShopFilterState,
    'categories' | 'sizes' | 'colors' | 'styles' | 'brands'
>;

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function Collection({
    collection,
}: {
    collection: ShopCollectionKey;
}) {
    const config = shopCollections[collection];
    const collectionProducts = getCollectionProducts(collection);
    const [filters, setFilters] = useState(createDefaultFilters);
    const [sort, setSort] = useState<ShopSortKey>(
        collection === 'novedades' ? 'newest' : 'recommended',
    );
    const [isMobileFiltersOpen, setIsMobileFiltersOpen] = useState(false);

    const filterOptions = getProductFilterOptions(collectionProducts);
    const filteredProducts = sortProducts(
        filterProducts(collectionProducts, filters),
        sort,
    );

    const secondaryHref =
        collection === 'novedades' ? shopRoutes.collections : shopRoutes.newIn;
    const secondaryLabel =
        collection === 'novedades' ? 'Ver colecciones' : 'Ver novedades';

    function toggleFilter(group: MultiFilterKey, value: string) {
        setFilters((current) => {
            const selected = current[group];
            const nextValues = selected.includes(value)
                ? selected.filter((item) => item !== value)
                : [...selected, value];

            return { ...current, [group]: nextValues };
        });
    }

    return (
        <StoreLayout title={`MORE | ${config.title}`}>
            <section className="px-4 sm:px-6 lg:px-8">
                <div className={`${sectionShell} px-0`}>
                    <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                        <div className="relative overflow-hidden rounded-[2.2rem] border border-white/10">
                            <img
                                src={config.heroImage}
                                alt={config.title}
                                className="h-[380px] w-full object-cover sm:h-[470px] lg:h-[560px]"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/72 via-black/28 to-transparent" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/72 via-transparent to-transparent" />
                            <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8 lg:p-10">
                                <p className="text-[0.7rem] uppercase tracking-[0.28em] text-stone-200">
                                    {config.eyebrow}
                                </p>
                                <h1
                                    className="mt-4 text-5xl leading-[0.9] text-white sm:text-6xl lg:text-[5.2rem]"
                                    style={serifStyle}
                                >
                                    {config.title}
                                </h1>
                                <p className="mt-4 max-w-lg text-sm leading-7 text-stone-100 sm:text-base">
                                    {config.subtitle}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-col gap-4">
                            <div className="rounded-[2rem] border border-white/10 bg-white/[0.05] p-7 backdrop-blur-xl sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    Mati Boutique curation
                                </p>
                                <p
                                    className="mt-5 text-4xl leading-none text-white"
                                    style={serifStyle}
                                >
                                    {collectionProducts.length} piezas en seleccion.
                                </p>
                                <p className="mt-5 max-w-md text-sm leading-7 text-stone-300">
                                    {config.description}
                                </p>

                                <div className="mt-6 flex flex-wrap gap-2">
                                    {config.highlights.map((item) => (
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
                                    Explore further
                                </p>
                                <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={secondaryHref}
                                        className="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition duration-300 hover:-translate-y-px hover:bg-stone-200"
                                    >
                                        {secondaryLabel}
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.editorial}
                                        className="inline-flex items-center justify-center rounded-full border border-white/12 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Editorial Mati Boutique
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="pt-14 sm:pt-16">
                <div className={sectionShell}>
                    <div className="grid gap-8 lg:grid-cols-[320px_minmax(0,1fr)]">
                        <aside className="hidden lg:block">
                            <div className="sticky top-28">
                                <ShopFiltersPanel
                                    filters={filters}
                                    options={filterOptions}
                                    onToggle={toggleFilter}
                                    onPriceChange={(value) =>
                                        setFilters((current) => ({
                                            ...current,
                                            price: value,
                                        }))
                                    }
                                    onAvailabilityChange={(value) =>
                                        setFilters((current) => ({
                                            ...current,
                                            availability: value,
                                        }))
                                    }
                                    onClear={() => setFilters(createDefaultFilters())}
                                />
                            </div>
                        </aside>

                        <div className="space-y-8">
                            <ShopToolbar
                                title={config.title}
                                resultCount={filteredProducts.length}
                                sort={sort}
                                onSortChange={setSort}
                                onOpenFilters={() => setIsMobileFiltersOpen(true)}
                            />

                            {filteredProducts.length > 0 ? (
                                <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                                    {filteredProducts.map((product) => (
                                        <ProductCard key={product.slug} product={product} />
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-[2rem] border border-white/10 bg-white/[0.04] px-6 py-12 text-center">
                                    <p
                                        className="text-4xl leading-none text-white"
                                        style={serifStyle}
                                    >
                                        No hay coincidencias por ahora.
                                    </p>
                                    <p className="mt-4 text-sm text-stone-300">
                                        Limpia filtros o vuelve a la seleccion completa.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => setFilters(createDefaultFilters())}
                                        className="mt-6 inline-flex rounded-full border border-white/12 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Ver toda la categoria
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {isMobileFiltersOpen && (
                <div className="fixed inset-0 z-[60] bg-[#05070b]/86 px-4 py-24 backdrop-blur-xl lg:hidden">
                    <div className="mx-auto max-w-lg">
                        <ShopFiltersPanel
                            filters={filters}
                            options={filterOptions}
                            onToggle={toggleFilter}
                            onPriceChange={(value) =>
                                setFilters((current) => ({
                                    ...current,
                                    price: value,
                                }))
                            }
                            onAvailabilityChange={(value) =>
                                setFilters((current) => ({
                                    ...current,
                                    availability: value,
                                }))
                            }
                            onClear={() => setFilters(createDefaultFilters())}
                            onClose={() => setIsMobileFiltersOpen(false)}
                        />
                    </div>
                </div>
            )}
        </StoreLayout>
    );
}
