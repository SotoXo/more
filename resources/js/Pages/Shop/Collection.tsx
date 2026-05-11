import ShopFiltersPanel from '@/Components/shop/ShopFiltersPanel';
import ProductCard from '@/Components/shop/ProductCard';
import StoreLayout from '@/Components/shop/StoreLayout';
import StorefrontImage from '@/Components/shop/StorefrontImage';
import ShopToolbar, { type ShopViewMode } from '@/Components/shop/ShopToolbar';
import { collectionPresentation } from '@/data/storefront';
import {
    createDefaultFilters,
    filterProducts,
    shopRoutes,
    sortProducts,
} from '@/lib/shop';
import type {
    ShopCollectionKey,
    ShopFilterOptions,
    ShopFilterState,
    ShopProduct,
    ShopSortKey,
} from '@/types/shop';
import { motion } from 'framer-motion';
import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type MultiFilterKey = keyof Pick<
    ShopFilterState,
    'categories' | 'sizes' | 'colors' | 'styles' | 'brands' | 'locations'
>;

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};
const primaryButtonClass =
    'more-cta-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]';
const secondaryButtonClass =
    'more-cta-secondary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]';

type CollectionProps = {
    collection: ShopCollectionKey;
    products: ShopProduct[];
    filterOptions: ShopFilterOptions;
    initialFilters: ShopFilterState;
};

export default function Collection({
    collection,
    products,
    filterOptions,
    initialFilters,
}: CollectionProps) {
    const config = collectionPresentation[collection];
    const [filters, setFilters] = useState<ShopFilterState>(initialFilters);
    const [sort, setSort] = useState<ShopSortKey>(
        collection === 'novedades' ? 'newest' : 'recommended',
    );
    const [viewMode, setViewMode] = useState<ShopViewMode>('amplia');
    const [isMobileFiltersOpen, setIsMobileFiltersOpen] = useState(false);

    useEffect(() => {
        setFilters(initialFilters);
        setSort(collection === 'novedades' ? 'newest' : 'recommended');
        setIsMobileFiltersOpen(false);
    }, [collection, initialFilters]);

    const filteredProducts = useMemo(
        () => sortProducts(filterProducts(products, filters), sort),
        [filters, products, sort],
    );

    const secondaryHref =
        collection === 'catalogo' ? shopRoutes.newIn : shopRoutes.catalog;
    const secondaryLabel =
        collection === 'catalogo' ? 'Ver novedades' : 'Ver catálogo';
    const gridClass =
        viewMode === 'compacta'
            ? 'grid gap-x-5 gap-y-10 sm:grid-cols-2 xl:grid-cols-4'
            : 'grid gap-x-6 gap-y-12 sm:grid-cols-2 xl:grid-cols-3';

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
        <StoreLayout title={`Mati Boutique | ${config.title}`}>
            <section className="px-3 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <motion.div
                        initial={{ opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
                        className="more-panel relative overflow-hidden rounded-[1.45rem] p-4 sm:rounded-[2rem] sm:p-5 lg:p-6"
                    >
                        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-stretch">
                            <div className="flex min-h-[260px] min-w-0 flex-col justify-between p-3 sm:min-h-[320px] sm:p-5 lg:min-h-[360px] lg:p-6">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2 text-[0.68rem] uppercase tracking-[0.22em] text-stone-300">
                                        <Link href={shopRoutes.home} className="transition hover:text-white">
                                            Inicio
                                        </Link>
                                        <ChevronRight size={13} className="text-stone-500" />
                                        <span className="text-[rgb(236,205,191)]">
                                            {config.title}
                                        </span>
                                    </div>
                                    <p className="mt-7 text-[0.68rem] uppercase tracking-[0.24em] text-stone-300 sm:mt-8 sm:text-[0.7rem] sm:tracking-[0.28em]">
                                        {config.eyebrow}
                                    </p>
                                    <h1
                                        className="mt-4 max-w-full break-words text-4xl leading-[0.94] text-white sm:text-6xl lg:max-w-3xl lg:text-[5.4rem] lg:leading-[0.9]"
                                        style={serifStyle}
                                    >
                                        {config.title}
                                    </h1>
                                    <p className="mt-5 max-w-[22rem] text-sm leading-7 text-stone-200 sm:max-w-2xl sm:text-base">
                                        {config.subtitle}
                                    </p>
                                    <p className="mt-4 max-w-[22rem] text-sm leading-7 text-stone-300 sm:max-w-2xl">
                                        {config.description}
                                    </p>
                                </div>

                                <div className="mt-8 flex max-w-full flex-wrap items-center gap-3">
                                    <span className="more-pill-active rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]">
                                        {products.length} {products.length === 1 ? 'producto' : 'productos'}
                                    </span>
                                    {config.highlights.map((item) => (
                                        <span
                                            key={item}
                                            className="more-pill rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-200"
                                        >
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="relative hidden overflow-hidden rounded-[1.6rem] border border-white/10 lg:block">
                                <StorefrontImage
                                    src={config.heroImage}
                                    alt={config.title}
                                    className="h-full min-h-[360px] w-full object-cover"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/78 via-black/10 to-transparent" />
                                <div className="absolute bottom-0 left-0 right-0 p-5">
                                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-200">
                                        También puedes ver
                                    </p>
                                    <Link
                                        href={secondaryHref}
                                        className={`${primaryButtonClass} mt-4`}
                                    >
                                        {secondaryLabel}
                                        <ArrowRight size={15} />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </motion.div>
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
                                viewMode={viewMode}
                                onSortChange={setSort}
                                onViewModeChange={setViewMode}
                                onOpenFilters={() => setIsMobileFiltersOpen(true)}
                            />

                            {filteredProducts.length > 0 ? (
                                <div className={gridClass}>
                                    {filteredProducts.map((product) => (
                                        <ProductCard key={product.slug} product={product} />
                                    ))}
                                </div>
                            ) : (
                                <div className="more-panel-soft rounded-[2rem] px-6 py-12 text-center">
                                    <p
                                        className="text-4xl leading-none text-white"
                                        style={serifStyle}
                                    >
                                        No encontramos esa combinación.
                                    </p>
                                    <p className="mt-4 text-sm text-stone-300">
                                        Limpia los filtros para ver más productos disponibles.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => setFilters(createDefaultFilters())}
                                        className={`${secondaryButtonClass} mt-6`}
                                    >
                                        Limpiar filtros
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {isMobileFiltersOpen && (
                <div className="fixed inset-0 z-[60] overflow-y-auto bg-[#05070b]/90 px-4 py-24 backdrop-blur-xl lg:hidden">
                    <div className="mx-auto max-w-lg pb-8">
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
