import { formatPrice, shopRoutes } from '@/lib/shop';
import type { ShopProduct } from '@/types/shop';
import type { LandingPageProps } from '@/types/landing';
import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/react';
import { Eye, Heart } from 'lucide-react';
import { useEffect, useState } from 'react';

type ProductCardProps = {
    product: ShopProduct;
    imageClassName?: string;
    initialIsFavorite?: boolean;
    onFavoriteChange?: (isFavorite: boolean) => void;
};

export default function ProductCard({
    product,
    imageClassName = 'h-[400px]',
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
            <div className="more-panel-soft relative overflow-hidden rounded-[1.8rem]">
                <Link href={shopRoutes.product(product.slug)} className="block">
                    <img
                        src={product.image}
                        alt={product.name}
                        className={`${imageClassName} w-full object-cover transition duration-[900ms] group-hover:scale-[1.04]`}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                    <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(181,122,109,0.08),transparent_34%,transparent)] opacity-70 transition duration-500 group-hover:opacity-100" />
                </Link>

                <div className="absolute left-4 top-4 flex items-center gap-2">
                    <span className="more-pill rounded-full px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] backdrop-blur">
                        {product.category}
                    </span>
                    {product.isNew && (
                        <span className="more-pill-active rounded-full px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em]">
                            Novedad
                        </span>
                    )}
                </div>

                <button
                    type="button"
                    onClick={handleFavoriteClick}
                    aria-label={`Guardar ${product.name}`}
                    disabled={isUpdatingFavorite}
                    className="more-icon-button absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center backdrop-blur"
                >
                    <Heart
                        size={16}
                        className={isFavorite ? 'fill-white text-white' : ''}
                    />
                </button>

                <div className="absolute inset-x-0 bottom-0 flex items-end justify-between p-4">
                    <span className="more-pill rounded-full px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] backdrop-blur">
                        {product.availability}
                    </span>

                    <Link
                        href={shopRoutes.product(product.slug)}
                        className="more-cta-secondary inline-flex px-4 py-2 text-[0.64rem] uppercase tracking-[0.2em] opacity-0 backdrop-blur group-hover:opacity-100"
                    >
                        <Eye size={14} />
                        Ver pieza
                    </Link>
                </div>
            </div>

            <div className="space-y-3 pt-5">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                            {product.line || product.category}
                        </p>
                        <Link
                            href={shopRoutes.product(product.slug)}
                            className="mt-2 block text-[1.95rem] leading-none text-white transition hover:text-stone-200"
                            style={{
                                fontFamily:
                                    '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
                            }}
                        >
                            {product.name}
                        </Link>
                    </div>
                    <span className="pt-1 text-sm text-[rgb(223,184,166)]">
                        {formatPrice(product.price)}
                    </span>
                </div>

                <div className="more-divider max-w-[9rem]" />
                {meta && <p className="text-sm text-stone-400">{meta}</p>}
            </div>
        </article>
    );
}
