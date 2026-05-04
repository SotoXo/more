import ProductCard from '@/Components/shop/ProductCard';
import StoreLayout from '@/Components/shop/StoreLayout';
import { shopRoutes } from '@/lib/shop';
import type { ShopProduct } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function Favoritos({ products }: { products: ShopProduct[] }) {
    const [favoriteProducts, setFavoriteProducts] = useState(products);

    return (
        <StoreLayout title="MORE | Mis Favoritos">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                                Mi cuenta
                            </p>
                            <h1
                                className="mt-3 text-5xl leading-none text-white sm:text-6xl"
                                style={serifStyle}
                            >
                                Mis favoritos
                            </h1>
                            {favoriteProducts.length > 0 && (
                                <p className="mt-3 text-sm text-stone-400">
                                    {favoriteProducts.length} {favoriteProducts.length === 1 ? 'pieza guardada' : 'piezas guardadas'}
                                </p>
                            )}
                        </div>
                        <Link
                            href="/cuenta"
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            Volver a mi cuenta
                        </Link>
                    </div>

                    {favoriteProducts.length === 0 ? (
                        <div className="mt-14 rounded-[2.2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <Heart size={32} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                Tu lista está vacía.
                            </p>
                            <p className="mt-3 text-sm text-stone-400">
                                Guarda prendas que te interesen y vuelve a revisarlas cuando quieras.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200"
                            >
                                Explorar catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            {favoriteProducts.map((product) => (
                                <ProductCard
                                    key={product.slug}
                                    product={product}
                                    initialIsFavorite
                                    onFavoriteChange={(isFavorite) => {
                                        if (isFavorite) {
                                            return;
                                        }

                                        setFavoriteProducts((current) =>
                                            current.filter(
                                                (currentProduct) => currentProduct.id !== product.id,
                                            ),
                                        );
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
