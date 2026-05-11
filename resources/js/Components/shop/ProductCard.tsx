import { formatPrice, shopRoutes } from '@/lib/shop';
import StorefrontImage from '@/Components/shop/StorefrontImage';
import type { ShopProduct } from '@/types/shop';
import type { LandingPageProps } from '@/types/landing';
import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/react';
import { CalendarCheck, Eye, Heart } from 'lucide-react';
import { useEffect, useState } from 'react';

type ProductCardProps = {
    product: ShopProduct;
    imageClassName?: string;
    initialIsFavorite?: boolean;
    onFavoriteChange?: (isFavorite: boolean) => void;
};

export default function ProductCard({
    product,
    imageClassName = 'aspect-[3/4]',
    initialIsFavorite,
    onFavoriteChange,
}: ProductCardProps) {
    const page = usePage();
    const user = (page.props as unknown as LandingPageProps).auth?.user ?? null;
    const [isFavorite, setIsFavorite] = useState(
        initialIsFavorite ?? product.isFavorite ?? false,
    );
    const [isUpdatingFavorite, setIsUpdatingFavorite] = useState(false);
    const meta = [product.brand, product.style].filter(Boolean).join(' / ');
    const hoverImage = product.gallery.find((image) => image !== product.image);
    const hasStock = (product.totalStock ?? 0) > 0 || product.availability !== 'Agotado';
    const availabilityLabel =
        product.availability === 'Últimas piezas'
            ? 'Últimas unidades'
            : product.availability;
    const visibleColors = product.colors?.slice(0, 4) ?? [];
    const extraColors = Math.max(0, (product.colors?.length ?? 0) - visibleColors.length);
    const visibleSizes = product.sizes.slice(0, 4);
    const extraSizes = Math.max(0, product.sizes.length - visibleSizes.length);

    useEffect(() => {
        setIsFavorite(initialIsFavorite ?? product.isFavorite ?? false);
    }, [initialIsFavorite, product.id, product.isFavorite]);

    async function handleFavoriteClick() {
        if (!user) {
            router.visit('/login');
            return;
        }

        if (isUpdatingFavorite) {
            return;
        }

        setIsUpdatingFavorite(true);

        try {
            const response = await axios.post('/api/favoritos/toggle', {
                prenda_id: product.id,
            });
            const nextValue = Boolean(response.data?.is_favorite);

            setIsFavorite(nextValue);
            onFavoriteChange?.(nextValue);
        } catch (error) {
            if (
                axios.isAxiosError(error) &&
                [401, 403, 419].includes(error.response?.status ?? 0)
            ) {
                router.visit('/login');
                return;
            }

            console.error('No se pudo actualizar favoritos.', error);
        } finally {
            setIsUpdatingFavorite(false);
        }
    }

    return (
        <article className="group relative">
            <div className="relative overflow-hidden rounded-[1.65rem] border border-white/10 bg-[#070a0f] shadow-[0_24px_70px_rgba(0,0,0,0.32)]">
                <Link href={shopRoutes.product(product.slug)} className="block">
                    <div className={`${imageClassName} relative w-full overflow-hidden bg-[#0b0f14]`}>
                        <StorefrontImage
                            src={product.image}
                            alt={product.name}
                            className="h-full w-full object-cover transition duration-[900ms] group-hover:scale-[1.035]"
                        />
                        {hoverImage && (
                            <StorefrontImage
                                src={hoverImage}
                                alt={product.name}
                                className="absolute inset-0 h-full w-full object-cover opacity-0 transition duration-[900ms] group-hover:scale-[1.035] group-hover:opacity-100"
                            />
                        )}
                    </div>
                    <div className="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/58 to-transparent" />
                    <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/72 via-black/18 to-transparent opacity-80 transition group-hover:opacity-100" />
                </Link>

                <div className="absolute left-3 top-3 flex flex-wrap items-center gap-2">
                    {product.isNew && (
                        <span className="rounded-full border border-[rgba(223,184,166,0.34)] bg-black/40 px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] text-stone-100 backdrop-blur">
                            Nuevo
                        </span>
                    )}
                    <span
                        className={[
                            'rounded-full border px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] backdrop-blur',
                            product.availability === 'Agotado'
                                ? 'border-red-300/20 bg-red-950/30 text-red-100'
                                : product.availability === 'Últimas piezas'
                                  ? 'border-[rgba(223,184,166,0.34)] bg-black/40 text-[rgb(236,205,191)]'
                                  : 'border-white/12 bg-black/34 text-stone-100',
                        ].join(' ')}
                    >
                        {availabilityLabel}
                    </span>
                </div>

                <button
                    type="button"
                    onClick={handleFavoriteClick}
                    aria-label={
                        isFavorite
                            ? `Quitar ${product.name} de favoritos`
                            : `Agregar ${product.name} a favoritos`
                    }
                    title={isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'}
                    disabled={isUpdatingFavorite}
                    className="more-icon-button absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center backdrop-blur"
                >
                    <Heart
                        size={16}
                        className={isFavorite ? 'fill-white text-white' : ''}
                    />
                </button>

                <div className="absolute inset-x-0 bottom-0 flex translate-y-3 items-end justify-between gap-2 p-3 opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                    <Link
                        href={shopRoutes.product(product.slug)}
                        className="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-white/14 bg-black/50 px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.18em] text-white backdrop-blur transition hover:border-[rgba(223,184,166,0.38)] hover:bg-black/70"
                    >
                        <Eye size={14} />
                        Ver producto
                    </Link>
                    {hasStock && (
                        <Link
                            href={shopRoutes.product(product.slug)}
                            className="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-[rgba(223,184,166,0.34)] bg-[rgba(223,184,166,0.92)] px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.18em] text-[#170f11] backdrop-blur transition hover:bg-white"
                        >
                            <CalendarCheck size={14} />
                            Reservar prenda
                        </Link>
                    )}
                </div>
            </div>

            <div className="space-y-3 pt-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <p className="truncate text-[0.68rem] uppercase tracking-[0.22em] text-stone-300">
                            {product.category}
                        </p>
                        <Link
                            href={shopRoutes.product(product.slug)}
                            className="mt-1.5 block break-words text-2xl leading-none text-white transition hover:text-stone-200"
                            style={{
                                fontFamily:
                                    '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
                            }}
                        >
                            {product.name}
                        </Link>
                    </div>
                    <span className="shrink-0 pt-1 text-sm font-medium text-[rgb(236,205,191)]">
                        {formatPrice(product.price)}
                    </span>
                </div>

                {meta && <p className="truncate text-sm text-stone-400">{meta}</p>}

                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-white/8 pt-3">
                    <div className="flex min-w-0 items-center gap-2">
                        {visibleColors.length > 0 ? (
                            <>
                                <span className="text-[0.62rem] uppercase tracking-[0.18em] text-stone-500">
                                    Colores
                                </span>
                                <div className="flex items-center gap-1.5">
                                    {visibleColors.map((color) => (
                                        <span
                                            key={`${color.name}-${color.hex ?? ''}`}
                                            title={color.name}
                                            className="h-3.5 w-3.5 rounded-full border border-white/20"
                                            style={{
                                                backgroundColor: color.hex ?? '#d8d0c9',
                                            }}
                                        />
                                    ))}
                                    {extraColors > 0 && (
                                        <span className="text-[0.62rem] text-stone-400">
                                            +{extraColors}
                                        </span>
                                    )}
                                </div>
                            </>
                        ) : (
                            <span className="text-[0.62rem] uppercase tracking-[0.18em] text-stone-500">
                                {product.color}
                            </span>
                        )}
                    </div>

                    {visibleSizes.length > 0 && (
                        <div className="flex items-center gap-1.5 text-[0.64rem] uppercase tracking-[0.16em] text-stone-300">
                            <span>Tallas</span>
                            <span>{visibleSizes.join(' ')}</span>
                            {extraSizes > 0 && <span>+{extraSizes}</span>}
                        </div>
                    )}
                </div>
            </div>
        </article>
    );
}
