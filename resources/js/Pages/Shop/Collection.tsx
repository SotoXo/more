import ShopFiltersPanel from '@/Components/shop/ShopFiltersPanel';
import ProductCard from '@/Components/shop/ProductCard';
import StoreLayout from '@/Components/shop/StoreLayout';
import ShopToolbar from '@/Components/shop/ShopToolbar';
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
import { ArrowRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type MultiFilterKey = keyof Pick<
    ShopFilterState,
    'categories' | 'sizes' | 'colors' | 'styles' | 'brands'
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
const editorialLinkClass =
    'more-cta-editorial text-[0.72rem] uppercase tracking-[0.22em]';

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
        collection === 'catalogo'
            ? shopRoutes.newIn
            : collection === 'novedades'
              ? shopRoutes.collections
              : shopRoutes.catalog;
    const secondaryLabel =
        collection === 'catalogo'
            ? 'Ver novedades'
            : collection === 'novedades'
              ? 'Ver colecciones'
              : 'Ver catálogo';

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
                    <motion.div
                        initial={{ opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
                        className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]"
                    >
                        <div className="relative overflow-hidden rounded-[2.2rem] border border-white/10">
                            <img
                                src={config.heroImage}
                                alt={config.title}
                                className="h-[380px] w-full object-cover sm:h-[470px] lg:h-[560px]"
                            />
                            <div className="absolute inset-0 bg-gradient-to-r from-black/72 via-black/28 to-transparent" />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/72 via-transparent to-transparent" />
                            <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.14),transparent_40%)]" />
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
                            <div className="more-panel rounded-[2rem] p-7 sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                                    Curaduría Mati Boutique
                                </p>
                                <p
                                    className="mt-5 text-4xl leading-none text-white"
                                    style={serifStyle}
                                >
                                    {products.length} piezas en escena.
                                </p>
                                <p className="mt-5 max-w-md text-sm leading-7 text-stone-300">
                                    {config.description}
                                </p>

                                <div className="mt-6 flex flex-wrap gap-2">
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

                            <div className="more-panel-soft rounded-[2rem] p-7 sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                                    Sigue el recorrido
                                </p>
                                <p className="mt-4 max-w-sm text-sm leading-7 text-stone-300">
                                    Sigue hacia otra edición de la tienda o entra en una lectura
                                    más inspiracional de la selección.
                                </p>
                                <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Link
                                        href={secondaryHref}
                                        className={primaryButtonClass}
                                    >
                                        {secondaryLabel}
                                        <ArrowRight size={15} />
                                    </Link>
                                    <Link
                                        href={shopRoutes.editorial}
                                        className={editorialLinkClass}
                                    >
                                        Editorial Mati Boutique
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
                                <div className="more-panel-soft rounded-[2rem] px-6 py-12 text-center">
                                    <p
                                        className="text-4xl leading-none text-white"
                                        style={serifStyle}
                                    >
                                        No encontramos esa combinación.
                                    </p>
                                    <p className="mt-4 text-sm text-stone-300">
                                        Limpia los filtros y vuelve a mirar la edición completa.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => setFilters(createDefaultFilters())}
                                        className={`${secondaryButtonClass} mt-6`}
                                    >
                                        Ver toda la categoría
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
