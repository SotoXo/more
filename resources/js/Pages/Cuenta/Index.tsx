import StoreLayout from '@/Components/shop/StoreLayout';
import type { Pedido } from '@/types';
import { formatPrice, shopRoutes } from '@/lib/shop';
import { Link } from '@inertiajs/react';
import { Heart, Package, ShoppingBag, User2 } from 'lucide-react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type CuentaIndexProps = {
    pedidosCount: number;
    reservasCount: number;
    favoritosCount: number;
    ultimoPedido: Pedido | null;
};

function StatCard({
    icon,
    count,
    label,
    href,
}: {
    icon: React.ReactNode;
    count: number;
    label: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="group flex flex-col gap-4 rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 transition hover:-translate-y-1 hover:border-white/18 hover:bg-white/[0.06]"
        >
            <div className="flex items-center justify-between">
                <span className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-stone-300">
                    {icon}
                </span>
                <span className="text-4xl text-white" style={serifStyle}>
                    {count}
                </span>
            </div>
            <p className="text-[0.7rem] uppercase tracking-[0.22em] text-stone-400">{label}</p>
        </Link>
    );
}

export default function CuentaIndex({
    pedidosCount,
    reservasCount,
    favoritosCount,
    ultimoPedido,
}: CuentaIndexProps) {
    const ultimoPedidoLabel = ultimoPedido?.estado === 'en_revision'
        ? 'En revision'
        : ultimoPedido?.estado === 'registrada'
        ? 'Compra confirmada'
        : ultimoPedido?.estado === 'incompleta'
        ? 'Compra incompleta'
        : ultimoPedido?.estado === 'cancelada'
        ? 'Compra cancelada'
        : 'En seguimiento';

    return (
        <StoreLayout title="Mati Boutique | Mi Cuenta">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                        Mati Boutique
                    </p>
                    <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                        Mi cuenta
                    </h1>

                    {/* Stats */}
                    <div className="mt-10 grid gap-4 sm:grid-cols-3">
                        <StatCard
                            icon={<ShoppingBag size={18} />}
                            count={pedidosCount}
                            label="Compras"
                            href="/cuenta/pedidos"
                        />
                        <StatCard
                            icon={<Package size={18} />}
                            count={reservasCount}
                            label="Reservas"
                            href="/cuenta/reservas"
                        />
                        <StatCard
                            icon={<Heart size={18} />}
                            count={favoritosCount}
                            label="Favoritos"
                            href="/cuenta/favoritos"
                        />
                    </div>

                    {/* Last order */}
                    {ultimoPedido && (
                        <div className="mt-10">
                            <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                                Ultima compra
                            </p>
                            <div className="mt-5 rounded-[2rem] border border-white/10 bg-white/[0.03] p-7">
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400">
                                            #{ultimoPedido.numero}
                                        </p>
                                        <p className="mt-2 text-2xl text-white" style={serifStyle}>
                                            {formatPrice(ultimoPedido.total)}
                                        </p>
                                    </div>
                                    <span
                                        className={[
                                            'rounded-full border px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]',
                                            ultimoPedido.estado === 'completada'
                                                ? 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300'
                                                : 'border-white/10 bg-white/[0.04] text-stone-300',
                                        ].join(' ')}
                                    >
                                        {ultimoPedidoLabel}
                                    </span>
                                </div>

                                {ultimoPedido.items.length > 0 && (
                                    <div className="mt-6 flex flex-wrap gap-3">
                                        {ultimoPedido.items.slice(0, 3).map((item) => (
                                            <Link
                                                key={item.id}
                                                href={shopRoutes.product(item.slug)}
                                                className="flex items-center gap-3 rounded-[1.2rem] border border-white/8 bg-white/[0.03] px-4 py-3 transition hover:border-white/14"
                                            >
                                                {item.imagen && (
                                                    <img
                                                        src={item.imagen}
                                                        alt={item.nombre}
                                                        className="h-12 w-10 rounded-[0.6rem] object-cover"
                                                    />
                                                )}
                                                <div>
                                                    <p className="text-[0.65rem] uppercase tracking-[0.18em] text-stone-400">
                                                        {item.marca}
                                                    </p>
                                                    <p className="mt-0.5 text-sm text-white">{item.nombre}</p>
                                                    <p className="mt-0.5 text-[0.65rem] text-stone-500">
                                                        {item.talla} · {item.color}
                                                    </p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}

                                <Link
                                    href="/cuenta/pedidos"
                                    className="mt-6 inline-flex text-[0.7rem] uppercase tracking-[0.22em] text-stone-400 transition hover:text-white"
                                >
                                    Ver mis compras
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Quick links */}
                    <div className="mt-10">
                        <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                            Accesos rápidos
                        </p>
                        <div className="mt-5 flex flex-wrap gap-3">
                            <Link
                                href="/profile"
                                className="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-5 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-200 transition hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.08]"
                            >
                                <User2 size={14} />
                                Editar perfil
                            </Link>
                            <Link
                                href={shopRoutes.newIn}
                                className="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-5 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-200 transition hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.08]"
                            >
                                <ShoppingBag size={14} />
                                Explorar novedades
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </StoreLayout>
    );
}
