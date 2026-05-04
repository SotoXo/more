import StoreLayout from '@/Components/shop/StoreLayout';
import { formatPrice, shopRoutes } from '@/lib/shop';
import type { CartItem } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type CarritoProps = {
    items: CartItem[];
    subtotal: number;
};

function CartItemRow({
    item,
    onUpdate,
    onRemove,
}: {
    item: CartItem;
    onUpdate: (varianteId: number, cantidad: number) => void;
    onRemove: (varianteId: number) => void;
}) {
    return (
        <div className="flex flex-col gap-4 rounded-[1.8rem] border border-white/8 bg-white/[0.03] p-5 sm:flex-row sm:gap-5">
            <Link href={shopRoutes.product(String(item.prenda_id))} className="shrink-0">
                {item.imagen ? (
                    <img
                        src={item.imagen}
                        alt={item.nombre}
                        className="h-24 w-20 rounded-[1.2rem] object-cover"
                    />
                ) : (
                    <div className="flex h-24 w-20 items-center justify-center rounded-[1.2rem] bg-white/[0.04]">
                        <ShoppingBag size={20} className="text-stone-600" />
                    </div>
                )}
            </Link>

            <div className="flex flex-1 flex-col justify-between">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-[0.65rem] uppercase tracking-[0.22em] text-stone-400">
                            {item.marca}
                        </p>
                        <Link
                            href={shopRoutes.product(String(item.prenda_id))}
                            className="mt-1 block text-xl leading-tight text-white hover:text-stone-200"
                            style={serifStyle}
                        >
                            {item.nombre}
                        </Link>
                        <p className="mt-1.5 text-[0.7rem] uppercase tracking-[0.18em] text-stone-400">
                            {item.talla} · {item.color}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => onRemove(item.variante_id)}
                        className="shrink-0 text-stone-500 transition hover:text-white"
                        aria-label="Eliminar"
                    >
                        <Trash2 size={15} />
                    </button>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <div className="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-1 py-1">
                        <button
                            type="button"
                            onClick={() => onUpdate(item.variante_id, Math.max(1, item.cantidad - 1))}
                            className="flex h-7 w-7 items-center justify-center rounded-full transition hover:bg-white/[0.08]"
                        >
                            <Minus size={12} />
                        </button>
                        <span className="w-5 text-center text-sm text-white">{item.cantidad}</span>
                        <button
                            type="button"
                            onClick={() => onUpdate(item.variante_id, item.cantidad + 1)}
                            className="flex h-7 w-7 items-center justify-center rounded-full transition hover:bg-white/[0.08]"
                        >
                            <Plus size={12} />
                        </button>
                    </div>
                    <span className="text-sm text-stone-200">
                        {formatPrice(item.precio * item.cantidad)}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function Carrito({ items, subtotal }: CarritoProps) {
    const [cartItems, setCartItems] = useState(items);
    const total = cartItems.reduce((sum, i) => sum + i.precio * i.cantidad, 0);

    function handleUpdate(varianteId: number, cantidad: number) {
        router.patch(
            `/api/carrito/${varianteId}`,
            { cantidad },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCartItems((prev) =>
                        prev.map((i) => (i.variante_id === varianteId ? { ...i, cantidad } : i)),
                    );
                },
            },
        );
    }

    function handleRemove(varianteId: number) {
        router.delete(`/api/carrito/${varianteId}`, {
            preserveScroll: true,
            onSuccess: () => {
                setCartItems((prev) => prev.filter((i) => i.variante_id !== varianteId));
            },
        });
    }

    return (
        <StoreLayout title="MORE | Bolsa">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                        Mati Boutique
                    </p>
                    <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                        Tu bolsa
                    </h1>

                    {cartItems.length === 0 ? (
                        <div className="mt-14 rounded-[2.2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <ShoppingBag size={32} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                Tu bolsa está vacía.
                            </p>
                            <p className="mt-3 text-sm text-stone-400">
                                Explora el catálogo y agrega tus prendas favoritas.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200"
                            >
                                Explorar catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_360px]">
                            <div className="space-y-4">
                                {cartItems.map((item) => (
                                    <CartItemRow
                                        key={item.variante_id}
                                        item={item}
                                        onUpdate={handleUpdate}
                                        onRemove={handleRemove}
                                    />
                                ))}
                            </div>

                            <div className="rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 lg:self-start lg:sticky lg:top-28">
                                <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                                    Resumen
                                </p>

                                <div className="mt-6 space-y-3">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-stone-300">Subtotal</span>
                                        <span className="text-white">{formatPrice(total)}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-stone-300">Envío</span>
                                        <span className="text-stone-400">Se confirma al finalizar</span>
                                    </div>
                                </div>

                                <div className="mt-6 border-t border-white/10 pt-6">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm uppercase tracking-[0.18em] text-stone-200">
                                            Total
                                        </span>
                                        <span className="text-2xl text-white" style={serifStyle}>
                                            {formatPrice(total)}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    className="mt-8 w-full rounded-full bg-white py-4 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200"
                                >
                                    Finalizar compra
                                </button>

                                <Link
                                    href={shopRoutes.catalog}
                                    className="mt-3 block text-center text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                                >
                                    Seguir comprando
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
