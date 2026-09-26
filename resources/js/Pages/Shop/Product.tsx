import SectionHeading from '@/Components/landing/SectionHeading';
import ProductGallery from '@/Components/shop/ProductGallery';
import ProductCard from '@/Components/shop/ProductCard';
import ProductRail from '@/Components/shop/ProductRail';
import StoreLayout from '@/Components/shop/StoreLayout';
import { addPurchaseCartItem } from '@/lib/purchaseCart';
import {
    formatAvailabilityLabel,
    formatPrice,
    getAvailabilityFromStock,
    shopRoutes,
} from '@/lib/shop';
import type { ShopProduct, ShopVariant } from '@/types/shop';
import type { LandingPageProps } from '@/types/landing';
import axios from 'axios';
import { motion } from 'framer-motion';
import { Link, router, usePage } from '@inertiajs/react';
import { Heart, Minus, Plus, ShoppingBag } from 'lucide-react';
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
function colorLabel(variant: ShopVariant) {
    return variant.palette_type === 'equilibrada' ? 'Multicolor' : variant.color;
}
function colorSwatch(variant: ShopVariant) {
    const colors = variant.palette?.map((item) => item.hex).filter((hex): hex is string => Boolean(hex)) ?? [];
    if (variant.palette_type === 'equilibrada' && colors.length > 1) {
        return `conic-gradient(${colors.map((hex, index) => `${hex} ${index * 100 / colors.length}% ${(index + 1) * 100 / colors.length}%`).join(', ')})`;
    }
    return variant.color_hex ?? '#ffffff';
}


type ProductPageProps = {
    product: ShopProduct;
    variants: ShopVariant[];
    details: { patterns: string[]; textures: string[]; materials: Array<{ name: string; percentage: string | number | null }> };
    relatedProducts: ShopProduct[];
    completeLookProducts: ShopProduct[];
    isFavorite: boolean;
};

export default function Product({
    product,
    variants,
    details,
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
    const [selectedAction, setSelectedAction] = useState<'reservar' | 'comprar'>('reservar');
    const [reservationQuantity, setReservationQuantity] = useState<number | string>(1);
    const [purchaseQuantity, setPurchaseQuantity] = useState<number | string>(1);
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
        if (typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('accion') === 'comprar') {
            setSelectedAction('comprar');
        }
    }, []);

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

    const soldOut = !variants.some(variant => variant.stock > 0);
    const validQuantity = (value: number | string, max: number) => /^\d+$/.test(String(value)) && Number.isSafeInteger(Number(value)) && Number(value) >= 1 && Number(value) <= max;
    const displayPrice = selectedVariant?.precio ?? product.price;
    const maxReservationQuantity = Math.max(0, selectedVariant?.stock ?? 0);
    const maxPurchaseQuantity = Math.max(0, selectedVariant?.stock ?? 0);
    const reservationUnitPrice = selectedVariant?.precio ?? product.price;
    const reservationTotal = Number((reservationUnitPrice * Number(reservationQuantity)).toFixed(2));
    const purchaseUnitPrice = selectedVariant?.precio ?? product.price;
    const purchaseTotal = Number((purchaseUnitPrice * Number(purchaseQuantity)).toFixed(2));
    const displayAvailability = selectedVariant
        ? getAvailabilityFromStock(selectedVariant.stock)
        : product.availability;
    const displayAvailabilityLabel = formatAvailabilityLabel(displayAvailability);
    const departmentHref =
        product.department === 'mujer'
            ? shopRoutes.women
            : product.department === 'hombre'
              ? shopRoutes.men
              : product.department === 'unisex'
                ? `${shopRoutes.catalog}?genero=unisex`
                : shopRoutes.accessories;
    const departmentLabel =
        product.department === 'mujer'
            ? 'Mujer'
            : product.department === 'hombre'
              ? 'Hombre'
              : product.department === 'unisex'
                ? 'Unisex'
                : 'Accesorios';

    useEffect(() => {
        setReservationQuantity((current) => {
            if (!selectedVariant || selectedVariant.stock <= 0) {
                return 1;
            }

            return Math.min(Math.max(1, Number(current)), selectedVariant.stock);
        });

        setPurchaseQuantity((current) => {
            if (!selectedVariant || selectedVariant.stock <= 0) {
                return 1;
            }

            return Math.min(Math.max(1, Number(current)), selectedVariant.stock);
        });
    }, [selectedVariant]);

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

        if (!validQuantity(reservationQuantity, maxReservationQuantity)) {
            setStatusMessage('No hay stock suficiente para reservar esa cantidad.');
            return;
        }

        setAddToCartPending(true);
        setStatusMessage(null);

        try {
            const response = await axios.post('/api/carrito/agregar', {
                variante_id: selectedVariant.id,
                cantidad: Number(reservationQuantity),
            });
            window.dispatchEvent(
                new CustomEvent('mati-boutique:reservation-bag-updated', {
                    detail: { count: response.data?.cart_count },
                }),
            );
            setStatusMessage('Prenda agregada a la bolsa de reservas.');
        } catch (error) {
            if (
                axios.isAxiosError(error) &&
                [401, 403, 419].includes(error.response?.status ?? 0)
            ) {
                setStatusMessage('Debes iniciar sesion para realizar una reserva.');
                router.visit('/login');
                return;
            }

            console.error('No se pudo agregar al carrito.', error);
            setStatusMessage(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'No pudimos reservar esta prenda. Intenta de nuevo.')
                    : 'No pudimos reservar esta prenda. Intenta de nuevo.',
            );
        } finally {
            setAddToCartPending(false);
        }
    }

    function handleAddToPurchaseCart() {
        setSelectedAction('comprar');
        setStatusMessage(null);

        if (!selectedVariant || !validQuantity(purchaseQuantity, maxPurchaseQuantity)) {
            setStatusMessage('No hay stock suficiente para comprar esta prenda.');
            return;
        }

        try {
            addPurchaseCartItem({
            variantId: selectedVariant.id,
            productId: product.id,
            slug: product.slug,
            name: product.name,
            brand: product.brand,
            image: product.image,
            color: colorLabel(selectedVariant),
            size: selectedVariant.talla,
            sku: selectedVariant.sku,
            price: selectedVariant.precio,
            quantity: Number(purchaseQuantity),
            stock: selectedVariant.stock,
            });
        } catch (error) {
            setStatusMessage(error instanceof Error ? error.message : 'Revisa la cantidad del carrito.');
            return;
        }

        setStatusMessage('Prenda agregada al carrito.');
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
                                        {selectedVariant ? colorLabel(selectedVariant) : selectedColor ?? product.color}
                                    </span>
                                    <span className="more-pill rounded-full px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em]">
                                        {displayAvailabilityLabel}
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
                                                            className="h-3 w-3 rounded-full border-2 border-white/40"
                                                            style={{
                                                                background: colorSwatch(variant),
                                                            }}
                                                        />
                                                        {colorLabel(variant)}
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
                                            {displayAvailabilityLabel}
                                        </p>
                                    </div>
                                </div>

                                {(details.patterns.length > 0 || details.textures.length > 0 || details.materials.length > 0 || (selectedVariant?.palette?.length ?? 0) > 1) && (
                                    <div className="mt-6 space-y-2 rounded-[1.4rem] border border-white/8 bg-white/[0.03] p-5 text-sm text-stone-200">
                                        <p className="mb-3 text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">Detalles</p>
                                        {details.patterns.length > 0 && <p><span className="text-stone-400">Patrones: </span>{details.patterns.join(', ')}</p>}
                                        {details.textures.length > 0 && <p><span className="text-stone-400">Texturas: </span>{details.textures.join(', ')}</p>}
                                        {details.materials.length > 0 && <p><span className="text-stone-400">Materiales: </span>{details.materials.every((item) => item.percentage !== null) ? details.materials.map((item) => `${item.percentage}% ${item.name}`).join(', ') : details.materials.map((item) => item.name).join(', ')}</p>}
                                        {selectedVariant?.palette_type === 'equilibrada' && <p className="text-stone-400">Paleta equilibrada: esta variante no tiene un color visual predominante.</p>}
                                        {(selectedVariant?.palette?.length ?? 0) > 1 && <div><span className="text-stone-400">Colores de esta variante: </span><span className="inline-flex flex-wrap gap-2">{selectedVariant?.palette?.map((item) => <span key={item.name} className="inline-flex items-center gap-1"><span className="h-3 w-3 rounded-full border-2 border-white/40" style={{ backgroundColor: item.hex ?? '#ffffff' }} />{item.name}</span>)}</span></div>}
                                    </div>
                                )}

                                {statusMessage && (
                                    <p className="mt-6 text-sm text-stone-300">{statusMessage}</p>
                                )}

                                <div className="mt-8 grid gap-2 rounded-[1.4rem] border border-white/8 bg-white/[0.03] p-2 sm:grid-cols-2">
                                    {[
                                        ['reservar', 'Reservar'],
                                        ['comprar', 'Comprar'],
                                    ].map(([value, label]) => (
                                        <button
                                            key={value}
                                            disabled={soldOut}
                                            type="button"
                                            onClick={() => {
                                                setSelectedAction(value as 'reservar' | 'comprar');
                                                setStatusMessage(null);
                                            }}
                                            className={
                                                selectedAction === value
                                                    ? 'rounded-[1rem] bg-white px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-slate-950 transition'
                                                    : 'rounded-[1rem] px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-300 transition hover:bg-white/[0.06] hover:text-white'
                                            }
                                        >
                                            {label}
                                        </button>
                                    ))}
                                </div>

                                {soldOut && <div className="mt-5 rounded-xl border border-white/10 p-5"><p className="text-white">AGOTADO</p><p className="mt-2 text-sm text-stone-300">Este producto no tiene unidades disponibles.</p><Link href={shopRoutes.catalog} className="mt-4 inline-block underline">Volver al catálogo</Link></div>}
                                {!soldOut && !validQuantity(selectedAction === 'comprar' ? purchaseQuantity : reservationQuantity, selectedVariant?.stock ?? 0) && <p className="mt-3 text-sm text-red-200" role="status">Ingresa una cantidad entera entre 1 y {selectedVariant?.stock ?? 0}.</p>}
                                {!soldOut && selectedAction === 'comprar' && (
                                    <div className="mt-5 space-y-5 rounded-[1.35rem] border border-white/8 bg-white/[0.03] p-5">
                                        <div>
                                            <p className="text-[0.68rem] uppercase tracking-[0.2em] text-[rgb(223,184,166)]">
                                                Compra online
                                            </p>
                                            <p className="mt-2 text-sm leading-6 text-stone-300">
                                                Agrega esta prenda al carrito. El pago y el comprobante se registran en el checkout.
                                            </p>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4">
                                                <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                    Variante
                                                </p>
                                                <p className="mt-2 text-sm text-white">
                                                    {selectedVariant
                                                        ? `${colorLabel(selectedVariant)} / ${selectedVariant.talla}`
                                                        : 'Selecciona color y talla'}
                                                </p>
                                            </div>
                                            <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4">
                                                <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                    Subtotal
                                                </p>
                                                <p className="mt-2 text-sm text-white">{formatPrice(purchaseTotal)}</p>
                                            </div>
                                        </div>

                                        <label className="block space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Cantidad
                                            </span>
                                            <input
                                                type="number"
                                                min="1"
                                                max={maxPurchaseQuantity || 1}
                                                aria-invalid={!validQuantity(purchaseQuantity, maxPurchaseQuantity)}
                                                value={purchaseQuantity}
                                                onChange={(event) => {
                                                    setPurchaseQuantity(event.target.value);
                                                }}
                                                disabled={maxPurchaseQuantity <= 0}
                                                className="w-full rounded-[1rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-white outline-none transition focus:border-[rgb(223,184,166)]"
                                            />
                                        </label>

                                        <div className="flex flex-col gap-3 sm:flex-row">
                                            <button
                                                type="button"
                                                onClick={handleAddToPurchaseCart}
                                                disabled={!selectedVariant || !validQuantity(purchaseQuantity, maxPurchaseQuantity)}
                                                className={`${primaryButtonClass} w-full justify-center sm:w-auto`}
                                            >
                                                <ShoppingBag size={15} />
                                                Agregar al carrito
                                            </button>
                                            <Link
                                                href={shopRoutes.cart}
                                                className={`${secondaryButtonClass} w-full justify-center sm:w-auto`}
                                            >
                                                Ver carrito
                                            </Link>
                                        </div>
                                    </div>
                                )}

                                {!soldOut && selectedAction === 'reservar' && (
                                    <div className="mt-5 space-y-5 rounded-[1.35rem] border border-white/8 bg-white/[0.03] p-5">
                                        <div>
                                            <p className="text-[0.68rem] uppercase tracking-[0.2em] text-[rgb(223,184,166)]">
                                                Reserva online
                                            </p>
                                            <p className="mt-2 text-sm leading-6 text-stone-300">
                                                Elige la cantidad y agrégala a tu bolsa. El stock se asegura al enviar la reserva con comprobante.
                                            </p>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4">
                                                <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                    Variante
                                                </p>
                                                <p className="mt-2 text-sm text-white">
                                                    {selectedVariant
                                                        ? `${colorLabel(selectedVariant)} / ${selectedVariant.talla}`
                                                        : 'Selecciona color y talla'}
                                                </p>
                                            </div>
                                            <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4">
                                                <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                    Subtotal reserva
                                                </p>
                                                <p className="mt-2 text-sm text-white">{formatPrice(reservationTotal)}</p>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between gap-4">
                                                <div>
                                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                        Cantidad a reservar
                                                    </p>
                                                    <p className="mt-1 text-xs text-stone-500">
                                                        Disponible ahora: {maxReservationQuantity}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2 rounded-full border border-white/10 bg-black/20 px-1 py-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => setReservationQuantity((current) => Math.max(1, Number(current) - 1))}
                                                        disabled={Number(reservationQuantity) <= 1 || maxReservationQuantity <= 0}
                                                        className="flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/[0.08] disabled:cursor-not-allowed disabled:opacity-50"
                                                        aria-label="Disminuir cantidad a reservar"
                                                    >
                                                        <Minus size={12} />
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max={maxReservationQuantity || 1}
                                                        aria-invalid={!validQuantity(reservationQuantity, maxReservationQuantity)}
                                                        value={reservationQuantity}
                                                        onChange={(event) => {
                                                            setReservationQuantity(event.target.value);
                                                        }}
                                                        disabled={maxReservationQuantity <= 0}
                                                        className="w-14 border-0 bg-transparent text-center text-sm text-white outline-none"
                                                        aria-label="Cantidad a reservar"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (Number(reservationQuantity) >= maxReservationQuantity) {
                                                                setStatusMessage('No hay stock suficiente para reservar esa cantidad.');
                                                                return;
                                                            }

                                                            setReservationQuantity((current) => Math.min(maxReservationQuantity, Number(current) + 1));
                                                        }}
                                                        disabled={maxReservationQuantity <= 0 || Number(reservationQuantity) >= maxReservationQuantity || !validQuantity(reservationQuantity, maxReservationQuantity)}
                                                        className="flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/[0.08] disabled:cursor-not-allowed disabled:opacity-50"
                                                        aria-label="Aumentar cantidad a reservar"
                                                    >
                                                        <Plus size={12} />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-col gap-3 sm:flex-row">
                                            <button
                                                type="button"
                                                onClick={handleAddToCart}
                                                disabled={
                                                    !selectedVariant ||
                                                    !validQuantity(reservationQuantity, maxReservationQuantity) ||
                                                    addToCartPending
                                                }
                                                className={`${primaryButtonClass} w-full sm:w-auto`}
                                            >
                                                <ShoppingBag size={15} />
                                                Reservar prenda
                                            </button>
                                            <Link
                                                href={shopRoutes.newReservation}
                                                className={`${secondaryButtonClass} w-full sm:w-auto`}
                                            >
                                                Ver bolsa
                                            </Link>
                                        </div>
                                    </div>
                                )}
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
