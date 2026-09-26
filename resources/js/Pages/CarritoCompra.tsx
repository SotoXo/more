import StoreLayout from '@/Components/shop/StoreLayout';
import {
    clearPurchaseCart,
    PurchaseCartItem,
    purchaseCartTotal,
    purchaseQuantityError,
    readPurchaseCart,
    writePurchaseCart,
} from '@/lib/purchaseCart';
import { formatPrice, shopRoutes } from '@/lib/shop';
import { Link, router } from '@inertiajs/react';
import { ShoppingBag, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function CarritoCompra() {
    const [items, setItems] = useState<PurchaseCartItem[]>([]);
    const [quantities, setQuantities] = useState<Record<number, string>>({});

    useEffect(() => {
        setItems(readPurchaseCart());
    }, []);

    const total = useMemo(() => purchaseCartTotal(items), [items]);
    const hasInvalidQuantity = items.some(item => purchaseQuantityError(quantities[item.variantId] ?? item.quantity, item.stock));

    function persist(nextItems: PurchaseCartItem[]) {
        setItems(nextItems);
        writePurchaseCart(nextItems);
    }

    function updateQuantity(variantId: number, quantity: string) {
        setQuantities(current => ({ ...current, [variantId]: quantity }));
        const item = items.find(item => item.variantId === variantId);
        if (!item || purchaseQuantityError(quantity, item.stock)) return;
        persist(items.map(item => item.variantId === variantId ? { ...item, quantity: Number(quantity) } : item));
    }

    function removeItem(variantId: number) {
        persist(items.filter((item) => item.variantId !== variantId));
    }

    function emptyCart() {
        setItems([]);
        clearPurchaseCart();
    }

    return (
        <StoreLayout title="Mati Boutique | Tu carrito">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-6xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                                Compra online
                            </p>
                            <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                                Tu carrito
                            </h1>
                        </div>
                        <Link
                            href={shopRoutes.catalog}
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            Seguir comprando
                        </Link>
                    </div>

                    {items.length === 0 ? (
                        <div className="mt-12 rounded-[2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <ShoppingBag size={34} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                Tu carrito está vacío.
                            </p>
                            <p className="mt-3 text-sm text-stone-400">
                                Agrega prendas desde el catálogo para continuar con la compra.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
                            >
                                Explorar catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_22rem]">
                            <div className="space-y-4">
                                {items.map((item) => (
                                    <div
                                        key={item.variantId}
                                        className="grid gap-4 rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[5rem_1fr_auto]"
                                    >
                                        {item.image ? (
                                            <img
                                                src={item.image}
                                                alt={item.name}
                                                className="h-24 w-20 rounded-[1rem] object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-24 w-20 items-center justify-center rounded-[1rem] bg-white/[0.04]">
                                                <ShoppingBag size={18} className="text-stone-600" />
                                            </div>
                                        )}
                                        <div>
                                            <p className="text-[0.64rem] uppercase tracking-[0.18em] text-stone-400">
                                                {item.brand}
                                            </p>
                                            <Link
                                                href={shopRoutes.product(item.slug)}
                                                className="mt-1 block text-2xl text-white transition hover:text-stone-200"
                                                style={serifStyle}
                                            >
                                                {item.name}
                                            </Link>
                                            <p className="mt-2 text-sm text-stone-400">
                                                {item.color} / {item.size} / {item.sku || 'Sin SKU'}
                                            </p>
                                            <p className="mt-2 text-sm text-stone-300">
                                                {formatPrice(item.price)} c/u
                                            </p>
                                        </div>
                                        <div className="flex flex-col gap-3 sm:items-end">
                                            <input
                                                type="number"
                                                min="1"
                                                max={item.stock}
                                                step="1"
                                                aria-label={`Cantidad de ${item.name}`}
                                                aria-invalid={Boolean(purchaseQuantityError(quantities[item.variantId] ?? item.quantity, item.stock))}
                                                aria-describedby={`quantity-error-${item.variantId}`}
                                                value={quantities[item.variantId] ?? item.quantity}
                                                onChange={(event) => updateQuantity(item.variantId, event.target.value)}
                                                className="w-24 rounded-[1rem] border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-[rgb(223,184,166)]"
                                            />
                                            <p id={`quantity-error-${item.variantId}`} className="max-w-52 text-sm text-red-200" role="status">{purchaseQuantityError(quantities[item.variantId] ?? item.quantity, item.stock)}</p>
                                            <p className="text-sm font-medium text-white">
                                                {formatPrice(item.price * item.quantity)}
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => removeItem(item.variantId)}
                                                className="inline-flex items-center gap-2 text-sm text-red-200 transition hover:text-red-100"
                                            >
                                                <Trash2 size={14} />
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <aside className="h-fit rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-6">
                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                    Resumen de compra
                                </p>
                                <div className="mt-5 space-y-3 border-b border-white/10 pb-5 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="text-stone-400">Prendas</span>
                                        <span className="text-white">{items.reduce((sum, item) => sum + item.quantity, 0)}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-stone-400">Subtotal</span>
                                        <span className="text-white">{formatPrice(total)}</span>
                                    </div>
                                </div>
                                <div className="mt-5 flex items-center justify-between text-base">
                                    <span className="text-stone-300">Total</span>
                                    <span className="text-xl font-semibold text-white">{formatPrice(total)}</span>
                                </div>
                                <button
                                    type="button"
                                    disabled={hasInvalidQuantity}
                                    onClick={() => { if (!hasInvalidQuantity) router.get(shopRoutes.checkout); }}
                                    className="mt-6 inline-flex w-full justify-center rounded-full bg-white px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Continuar con la compra
                                </button>
                                <button
                                    type="button"
                                    onClick={emptyCart}
                                    className="mt-3 w-full rounded-full border border-white/10 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-stone-300 transition hover:border-red-200/30 hover:text-red-100"
                                >
                                    Vaciar carrito
                                </button>
                            </aside>
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
