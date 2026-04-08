import { formatPrice, shopRoutes, type ShopProduct } from '@/data/shop';
import { Link } from '@inertiajs/react';
import { Eye, Heart } from 'lucide-react';
import { useState } from 'react';

type ProductCardProps = {
    product: ShopProduct;
    imageClassName?: string;
};

export default function ProductCard({
    product,
    imageClassName = 'h-[400px]',
}: ProductCardProps) {
    const [isFavorite, setIsFavorite] = useState(false);

    return (
        <article className="group relative">
            <div className="relative overflow-hidden rounded-[1.8rem] border border-white/8 bg-[#0b1016]">
                <Link href={shopRoutes.product(product.slug)} className="block">
                    <img
                        src={product.image}
                        alt={product.name}
                        className={`${imageClassName} w-full object-cover transition duration-[900ms] group-hover:scale-[1.03]`}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/56 via-transparent to-transparent" />
                </Link>

                <div className="absolute left-4 top-4 flex items-center gap-2">
                    <span className="rounded-full border border-white/12 bg-black/25 px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] text-stone-100 backdrop-blur">
                        {product.category}
                    </span>
                    {product.isNew && (
                        <span className="rounded-full border border-sky-300/20 bg-sky-300/12 px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] text-sky-100">
                            New In
                        </span>
                    )}
                </div>

                <button
                    type="button"
                    onClick={() => setIsFavorite((value) => !value)}
                    aria-label={`Guardar ${product.name}`}
                    className="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/12 bg-black/25 text-white backdrop-blur transition duration-300 hover:-translate-y-px hover:border-white/22 hover:bg-black/40"
                >
                    <Heart
                        size={16}
                        className={isFavorite ? 'fill-white text-white' : ''}
                    />
                </button>

                <div className="absolute inset-x-0 bottom-0 flex items-end justify-between p-4">
                    <span className="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-[0.62rem] uppercase tracking-[0.18em] text-stone-100 backdrop-blur">
                        {product.availability}
                    </span>

                    <Link
                        href={shopRoutes.product(product.slug)}
                        className="inline-flex items-center gap-2 rounded-full border border-white/12 bg-black/30 px-4 py-2 text-[0.64rem] uppercase tracking-[0.2em] text-white opacity-0 backdrop-blur transition duration-300 group-hover:opacity-100"
                    >
                        <Eye size={14} />
                        Quick view
                    </Link>
                </div>
            </div>

            <div className="space-y-3 pt-5">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                            {product.line}
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
                    <span className="pt-1 text-sm text-stone-200">
                        {formatPrice(product.price)}
                    </span>
                </div>

                <p className="text-sm text-stone-400">
                    {product.brand} / {product.style}
                </p>
            </div>
        </article>
    );
}
