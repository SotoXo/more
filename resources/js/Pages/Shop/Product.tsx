import SectionHeading from '@/Components/landing/SectionHeading';
import ProductGallery from '@/Components/shop/ProductGallery';
import ProductCard from '@/Components/shop/ProductCard';
import ProductRail from '@/Components/shop/ProductRail';
import StoreLayout from '@/Components/shop/StoreLayout';
import {
    formatPrice,
    getAvailabilityFromStock,
    shopRoutes,
} from '@/lib/shop';
import type { ShopProduct, ShopVariant } from '@/types/shop';
import type { LandingPageProps } from '@/types/landing';
import axios from 'axios';
import { motion } from 'framer-motion';
import { Link, router, usePage } from '@inertiajs/react';
import { Heart, ShoppingBag } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};
const primaryButtonClass =
    'more-cta-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] disabled:cursor-not-allowed disabled:opacity-60';
const secondaryButtonClass =
    'more-cta-secondary px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]';

type ProductPageProps = {
    product: ShopProduct;
    variants: ShopVariant[];
    relatedProducts: ShopProduct[];
    completeLookProducts: ShopProduct[];
    isFavorite: boolean;
};

export default function Product({
    product,
    variants,
    relatedProducts,
    completeLookProducts,
    isFavorite,
}: ProductPageProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;
    const [favorite, setFavorite] = useState(isFavorite);
    const [favoritePending, setFavoritePending] = useState(false);
    const [addToCartPending, setAddToCartPending] = useState(false);
    const [statusMessage, setStatusMessage] = useState<string | null>(null);
    const headingMeta =
        product.line ||
        [product.brand, product.style].filter(Boolean).join(' / ') ||
        product.category;

    const colorOptions = useMemo(() => {
        const seen = new Set<string>();

        return variants.filter((variant) => {
            const key = `${variant.color}|${variant.color_hex ?? ''}`;

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        });
    }, [variants]);

    const [selectedColor, setSelectedColor] = useState<string | null>(
        colorOptions[0]?.color ?? product.color ?? null,
    );
    const availableVariants = useMemo(() => {
        if (!selectedColor) {
            return variants;
        }

        return variants.filter((variant) => variant.color === selectedColor);
    }, [selectedColor, variants]);
    const [selectedSize, setSelectedSize] = useState<string | null>(
        availableVariants.find((variant) => variant.stock > 0)?.talla ??
            availableVariants[0]?.talla ??
            product.sizes[0] ??
            null,
    );

    useEffect(() => {
        setFavorite(isFavorite);
    }, [isFavorite, product.id]);

    useEffect(() => {
        const nextSize =
            availableVariants.find((variant) => variant.stock > 0)?.talla ??
            availableVariants[0]?.talla ??
            null;

        if (!availableVariants.some((variant) => variant.talla === selectedSize)) {
            setSelectedSize(nextSize);
        }
    }, [availableVariants, selectedSize]);

    const selectedVariant = useMemo(
        () =>
            availableVariants.find((variant) => variant.talla === selectedSize) ??
            availableVariants[0] ??
            null,
        [availableVariants, selectedSize],
    );

    const displayPrice = selectedVariant?.precio ?? product.price;
    const displayAvailability = selectedVariant
        ? getAvailabilityFromStock(selectedVariant.stock)
        : product.availability;
    const departmentHref =
        product.department === 'mujer'
            ? shopRoutes.women
            : product.department === 'hombre'
              ? shopRoutes.men
              : shopRoutes.accessories;
    const departmentLabel =
        product.department === 'mujer'
            ? 'Mujer'
            : product.department === 'hombre'
              ? 'Hombre'
              : 'Accesorios';

    async function handleToggleFavorite() {
        if (!user) {
            router.visit('/login');
            return;
        }

        if (favoritePending) {
            return;
        }

        setFavoritePending(true);
        setStatusMessage(null);

        try {
            const response = await axios.post('/api/favoritos/toggle', {
                prenda_id: product.id,
            });
            const nextValue = Boolean(response.data?.is_favorite);

            setFavorite(nextValue);
            setStatusMessage(
                nextValue
                    ? 'Producto guardado en favoritos.'
                    : 'Producto eliminado de favoritos.',
            );
        } catch (error) {
            if (
                axios.isAxiosError(error) &&
                [401, 403, 419].includes(error.response?.status ?? 0)
            ) {
                router.visit('/login');
                return;
            }

            console.error('No se pudo actualizar favoritos.', error);
            setStatusMessage('No pudimos actualizar tus favoritos. Intenta de nuevo.');
        } finally {
            setFavoritePending(false);
        }
    }

    async function handleAddToCart() {
        if (!selectedVariant || selectedVariant.stock <= 0 || addToCartPending) {
            return;
        }

        setAddToCartPending(true);
        setStatusMessage(null);

        try {
            await axios.post('/api/carrito/agregar', {
                variante_id: selectedVariant.id,
                cantidad: 1,
            });
            setStatusMessage('Prenda agregada a tus reservas.');
        } catch (error) {
            console.error('No se pudo agregar al carrito.', error);
            setStatusMessage('No pudimos reservar esta prenda. Intenta de nuevo.');
        } finally {
            setAddToCartPending(false);
        }
    }

    return (
        <StoreLayout title={`Mati Boutique | ${product.name}`}>
            <section className="px-3 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="flex max-w-full flex-wrap items-center gap-3 break-words text-[0.66rem] uppercase tracking-[0.18em] text-stone-400 sm:text-[0.68rem] sm:tracking-[0.22em]">
                        <Link href={shopRoutes.home} className="transition hover:text-white">
                            Mati Boutique
                        </Link>
                        <span>/</span>
                        <Link href={departmentHref} className="transition hover:text-white">
                            {departmentLabel}
                        </Link>
                        <span>/</span>
                        <span className="text-[rgb(223,184,166)]">{product.name}</span>
                    </div>

                    <motion.div
                        initial={{ opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
                        className="mt-8 grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start"
                    >
                        <ProductGallery product={product} />

                        <div className="lg:sticky lg:top-28">
                            <div className="more-panel rounded-[1.45rem] p-5 sm:rounded-[2.2rem] sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                                    {headingMeta}
                                </p>
                                <div className="mt-4 flex items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        <p className="text-[0.72rem] uppercase tracking-[0.24em] text-[rgb(223,184,166)]">
                                            Selección Mati Boutique
                                        </p>
                                        <h1
                                            className="mt-3 break-words text-4xl leading-[0.96] text-white sm:text-5xl xl:text-6xl"
                                            style={serifStyle}
                                        >
                                            {product.name}
                                        </h1>
                                        <p className="mt-4 max-w-[22rem] text-sm leading-7 text-stone-300 sm:max-w-lg">
                                            {product.description}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={handleToggleFavorite}
                                        aria-label="Guardar producto"
                                        disabled={favoritePending}
                                        className="more-icon-button inline-flex h-11 w-11 items-center justify-center"
                                    >
                                        <Heart
                                            size={17}
                                            className={favorite ? 'fill-white text-white' : ''}
                                        />
                                    </button>
                                </div>

                                <div className="mt-8 flex flex-wrap items-center gap-3">
                                    <span className="more-pill-active rounded-full px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em]">
                                        {formatPrice(displayPrice)}
                                    </span>
                                    <span className="more-pill rounded-full px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em]">
                                        {selectedColor ?? product.color}
                                    </span>
                                    <span className="more-pill rounded-full px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em]">
                                        {displayAvailability}
                                    </span>
                                </div>

                                {colorOptions.length > 0 && (
                                    <div className="mt-8 border-t border-white/10 pt-8">
                                        <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-500">
                                            Colores
                                        </p>
                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {colorOptions.map((variant) => {
                                                const active = selectedColor === variant.color;

                                                return (
                                                    <button
                                                        key={`${variant.id}-${variant.color}`}
                                                        type="button"
                                                        onClick={() => setSelectedColor(variant.color)}
                                                        className={[
                                                            'inline-flex items-center gap-2 rounded-full border px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] transition duration-300',
                                                            active
                                                                ? 'more-pill-active'
                                                                : 'more-pill text-stone-300 hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.08)]',
                                                        ].join(' ')}
                                                    >
                                                        <span
                                                            className="h-3 w-3 rounded-full border border-white/10"
                                                            style={{
                                                                backgroundColor:
                                                                    variant.color_hex ?? '#ffffff',
                                                            }}
                                                        />
                                                        {variant.color}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                <div className="mt-8 border-t border-white/10 pt-8">
                                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-500">
                                        Tallas
                                    </p>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {availableVariants.map((variant) => {
                                            const active = selectedSize === variant.talla;

                                            return (
                                                <button
                                                    key={variant.id}
                                                    type="button"
                                                    onClick={() => setSelectedSize(variant.talla)}
                                                    className={[
                                                        'rounded-full border px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] transition duration-300',
                                                        active
                                                            ? 'more-pill-active'
                                                            : 'more-pill text-stone-300 hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.08)]',
                                                        variant.stock <= 0
                                                            ? 'opacity-40 line-through'
                                                            : '',
                                                    ].join(' ')}
                                                >
                                                    {variant.talla}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                    <div className="more-panel-soft rounded-[1.5rem] p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-500">
                                            Marca
                                        </p>
                                        <p className="mt-3 text-sm text-stone-100">
                                            {product.brand}
                                        </p>
                                    </div>
                                    <div className="more-panel-soft rounded-[1.5rem] p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-500">
                                            Disponibilidad
                                        </p>
                                        <p className="mt-3 text-sm text-stone-100">
                                            {selectedVariant
                                                ? `${selectedVariant.stock} unidades disponibles`
                                                : displayAvailability}
                                        </p>
                                    </div>
                                </div>

                                {statusMessage && (
                                    <p className="mt-6 text-sm text-stone-300">{statusMessage}</p>
                                )}

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <button
                                        type="button"
                                        onClick={handleAddToCart}
                                        disabled={
                                            !selectedVariant ||
                                            selectedVariant.stock <= 0 ||
                                            addToCartPending
                                        }
                                        className={`${primaryButtonClass} w-full sm:w-auto`}
                                    >
                                        <ShoppingBag size={15} />
                                        Reservar prenda
                                    </button>
                                    <Link
                                        href="/bolsa"
                                        className={`${secondaryButtonClass} w-full sm:w-auto`}
                                    >
                                        Ver reservas
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </section>

            {relatedProducts.length > 0 && (
                <section className="pt-16">
                    <div className={sectionShell}>
                        <ProductRail
                            eyebrow="También puede interesarte"
                            title="Productos relacionados"
                            description="Prendas similares que puedes revisar antes de decidir."
                            products={relatedProducts}
                        />
                    </div>
                </section>
            )}

            {completeLookProducts.length > 0 && (
                <section className="pt-16">
                    <div className={sectionShell}>
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                            <SectionHeading
                                eyebrow="Cierra el look"
                                title="Completa el look"
                                description="Prendas y accesorios que combinan con este producto."
                            />
                            <Link
                                href={shopRoutes.catalog}
                                className={secondaryButtonClass}
                            >
                                Ver catálogo
                            </Link>
                        </div>

                        <div className="mt-10 grid gap-6 lg:grid-cols-3">
                            {completeLookProducts.map((lookProduct) => (
                                <ProductCard
                                    key={lookProduct.slug}
                                    product={lookProduct}
                                    imageClassName="h-[340px]"
                                />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </StoreLayout>
    );
}
