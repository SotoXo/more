import StoreLayout from '@/Components/shop/StoreLayout';
import { formatPrice, shopRoutes } from '@/lib/shop';
import type { Pedido } from '@/types';
import { Link } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

const estadoStyle: Record<string, string> = {
    completada: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    registrada: 'border-white/10 bg-white/[0.04] text-stone-300',
    cancelada: 'border-red-900/30 bg-red-900/10 text-red-300',
    pendiente: 'border-amber-500/20 bg-amber-500/8 text-amber-200',
};

function PedidoCard({ pedido }: { pedido: Pedido }) {
    return (
        <div className="rounded-[2rem] border border-white/10 bg-white/[0.03] p-7">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                        Pedido #{pedido.numero}
                    </p>
                    <p className="mt-2 text-3xl text-white" style={serifStyle}>
                        {formatPrice(pedido.total)}
                    </p>
                    {pedido.fecha && (
                        <p className="mt-2 text-xs text-stone-500">{pedido.fecha}</p>
                    )}
                </div>
                <span
                    className={[
                        'rounded-full border px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]',
                        estadoStyle[pedido.estado] ?? estadoStyle.registrada,
                    ].join(' ')}
                >
                    {pedido.estado}
                </span>
            </div>

            {pedido.items.length > 0 && (
                <div className="mt-6 space-y-3">
                    {pedido.items.map((item) => (
                        <div
                            key={item.id}
                            className="flex gap-4 rounded-[1.4rem] border border-white/8 bg-white/[0.02] p-4"
                        >
                            {item.imagen ? (
                                <img
                                    src={item.imagen}
                                    alt={item.nombre}
                                    className="h-16 w-14 shrink-0 rounded-[0.9rem] object-cover"
                                />
                            ) : (
                                <div className="flex h-16 w-14 shrink-0 items-center justify-center rounded-[0.9rem] bg-white/[0.04]">
                                    <ShoppingBag size={16} className="text-stone-600" />
                                </div>
                            )}
                            <div className="flex flex-1 items-center justify-between gap-4">
                                <div>
                                    <p className="text-[0.65rem] uppercase tracking-[0.18em] text-stone-400">
                                        {item.marca}
                                    </p>
                                    <Link
                                        href={shopRoutes.product(item.slug)}
                                        className="mt-0.5 block text-base text-white hover:text-stone-200"
                                        style={serifStyle}
                                    >
                                        {item.nombre}
                                    </Link>
                                    <p className="mt-1 text-[0.68rem] text-stone-500">
                                        {item.talla} · {item.color} · ×{item.cantidad}
                                    </p>
                                </div>
                                <span className="shrink-0 text-sm text-stone-300">
                                    {formatPrice(item.subtotal)}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <div className="mt-5 flex items-center justify-between border-t border-white/10 pt-5 text-sm">
                <span className="text-stone-400">Total</span>
                <span className="text-white">{formatPrice(pedido.total)}</span>
            </div>
        </div>
    );
}

export default function Pedidos({ pedidos }: { pedidos: Pedido[] }) {
    return (
        <StoreLayout title="MORE | Mis Pedidos">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-5xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                                Mi cuenta
                            </p>
                            <h1
                                className="mt-3 text-5xl leading-none text-white sm:text-6xl"
                                style={serifStyle}
                            >
                                Mis pedidos
                            </h1>
                        </div>
                        <Link
                            href="/cuenta"
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            Volver a mi cuenta
                        </Link>
                    </div>

                    {pedidos.length === 0 ? (
                        <div className="mt-14 rounded-[2.2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <ShoppingBag size={32} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                Aún no tienes pedidos.
                            </p>
                            <p className="mt-3 text-sm text-stone-400">
                                Cuando finalices una compra, la verás en este espacio.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
                            >
                                Explorar catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 space-y-6">
                            {pedidos.map((pedido) => (
                                <PedidoCard key={pedido.id} pedido={pedido} />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
