import SectionHeading from '@/Components/landing/SectionHeading';
import ProductGallery from '@/Components/shop/ProductGallery';
import ProductCard from '@/Components/shop/ProductCard';
import ProductRail from '@/Components/shop/ProductRail';
import StoreLayout from '@/Components/shop/StoreLayout';
import {
    formatPrice,
    getCompleteLookProducts,
    getProductBySlug,
    getRelatedProducts,
    shopRoutes,
} from '@/data/shop';
import { Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useState } from 'react';

const sectionShell = 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function Product({ productSlug }: { productSlug: string }) {
    const product = getProductBySlug(productSlug);

    if (!product) {
        return (
            <StoreLayout title="MORE | Producto">
                <section className="px-4 sm:px-6 lg:px-8">
                    <div className={`${sectionShell} px-0`}>
                        <div className="rounded-[2.2rem] border border-white/10 bg-white/[0.04] px-6 py-16 text-center">
                            <p className="text-[0.72rem] uppercase tracking-[0.26em] text-stone-400">
                                Mati Boutique
                            </p>
                            <h1
                                className="mt-4 text-5xl leading-none text-white"
                                style={serifStyle}
                            >
                                Producto no encontrado.
                            </h1>
                            <p className="mt-4 text-sm text-stone-300">
                                Vuelve a la seleccion principal y descubre otras piezas.
                            </p>
                            <div className="mt-8 flex justify-center">
                                <Link
                                    href={shopRoutes.newIn}
                                    className="rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
                                >
                                    Ver novedades
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </StoreLayout>
        );
    }

    const relatedProducts = getRelatedProducts(product, 4);
    const completeLookProducts = getCompleteLookProducts(product, 3);
    const [selectedSize, setSelectedSize] = useState(product.sizes[0] ?? null);
    const [isFavorite, setIsFavorite] = useState(false);

    const departmentHref =
        product.department === 'mujer'
            ? shopRoutes.women
            : product.department === 'hombre'
              ? shopRoutes.men
              : shopRoutes.accessories;

    return (
        <StoreLayout title={`MORE | ${product.name}`}>
            <section className="px-4 sm:px-6 lg:px-8">
                <div className={`${sectionShell} px-0`}>
                    <div className="flex flex-wrap items-center gap-3 text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                        <Link href={shopRoutes.home} className="transition hover:text-white">
                            MORE
                        </Link>
                        <span>/</span>
                        <Link href={departmentHref} className="transition hover:text-white">
                            {product.department}
                        </Link>
                        <span>/</span>
                        <span className="text-stone-200">{product.name}</span>
                    </div>

                    <div className="mt-8 grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                        <ProductGallery product={product} />

                        <div className="lg:sticky lg:top-28">
                            <div className="rounded-[2.2rem] border border-white/10 bg-white/[0.05] p-6 backdrop-blur-xl sm:p-8">
                                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                    {product.line}
                                </p>
                                <div className="mt-4 flex items-start justify-between gap-4">
                                    <div>
                                        <h1
                                            className="text-5xl leading-[0.92] text-white sm:text-6xl"
                                            style={serifStyle}
                                        >
                                            {product.name}
                                        </h1>
                                        <p className="mt-4 max-w-lg text-sm leading-7 text-stone-300">
                                            {product.description}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => setIsFavorite((value) => !value)}
                                        aria-label="Guardar producto"
                                        className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/12 bg-white/[0.04] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.08]"
                                    >
                                        <Heart
                                            size={17}
                                            className={isFavorite ? 'fill-white text-white' : ''}
                                        />
                                    </button>
                                </div>

                                <div className="mt-8 flex flex-wrap items-center gap-3">
                                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em] text-stone-200">
                                        {formatPrice(product.price)}
                                    </span>
                                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em] text-stone-200">
                                        {product.color}
                                    </span>
                                    <span className="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[0.7rem] uppercase tracking-[0.2em] text-stone-200">
                                        {product.availability}
                                    </span>
                                </div>

                                <div className="mt-8 border-t border-white/10 pt-8">
                                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                                        Tallas
                                    </p>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {product.sizes.map((size) => {
                                            const active = selectedSize === size;

                                            return (
                                                <button
                                                    key={size}
                                                    type="button"
                                                    onClick={() => setSelectedSize(size)}
                                                    className={[
                                                        'rounded-full border px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] transition duration-300',
                                                        active
                                                            ? 'border-white/24 bg-white/[0.12] text-white'
                                                            : 'border-white/10 bg-white/[0.03] text-stone-300 hover:border-white/18 hover:bg-white/[0.06]',
                                                    ].join(' ')}
                                                >
                                                    {size}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-[1.5rem] border border-white/10 bg-[#0c1117] p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                            Brand
                                        </p>
                                        <p className="mt-3 text-sm text-stone-100">
                                            {product.brand}
                                        </p>
                                    </div>
                                    <div className="rounded-[1.5rem] border border-white/10 bg-[#0c1117] p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                            Style
                                        </p>
                                        <p className="mt-3 text-sm text-stone-100">
                                            {product.style}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={departmentHref}
                                        className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition duration-300 hover:-translate-y-px hover:bg-stone-200"
                                    >
                                        Volver a la seleccion
                                    </Link>
                                    <Link
                                        href={shopRoutes.newIn}
                                        className="inline-flex items-center justify-center rounded-full border border-white/12 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.05]"
                                    >
                                        Ver nuevas entradas
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="pt-16">
                <div className={sectionShell}>
                    <ProductRail
                        eyebrow="Related pieces"
                        title="Productos relacionados"
                        description="Mas opciones dentro del mismo mood editorial."
                        products={relatedProducts}
                    />
                </div>
            </section>

            <section className="pt-16">
                <div className={sectionShell}>
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <SectionHeading
                            eyebrow="Complete the look"
                            title="Completa el look"
                            description="Una combinacion pensada para mantener el tono y el ritmo de la pieza principal."
                        />
                        <Link
                            href={shopRoutes.collections}
                            className="inline-flex items-center justify-center rounded-full border border-white/12 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.05]"
                        >
                            Ver colecciones
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
        </StoreLayout>
    );
}
