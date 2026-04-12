import StoreLayout from '@/Components/shop/StoreLayout';
import { shopRoutes } from '@/lib/shop';
import type { Reserva } from '@/types';
import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

const estadoStyle: Record<string, string> = {
    activa:     'border-sky-400/20 bg-sky-400/8 text-sky-300',
    completada: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    cancelada:  'border-red-900/30 bg-red-900/10 text-red-300',
    vencida:    'border-white/10 bg-white/[0.04] text-stone-400',
};

function ReservaCard({ reserva }: { reserva: Reserva }) {
    return (
        <div className="rounded-[2rem] border border-white/10 bg-white/[0.03] p-7">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                        Reserva #{reserva.numero}
                    </p>
                    {reserva.fecha && (
                        <p className="mt-2 text-xs text-stone-500">Creada: {reserva.fecha}</p>
                    )}
                    {reserva.vence && (
                        <p className="mt-1 text-xs text-stone-500">Vence: {reserva.vence}</p>
                    )}
                    {reserva.obs && (
                        <p className="mt-3 max-w-md text-sm leading-6 text-stone-400">{reserva.obs}</p>
                    )}
                </div>
                <span
                    className={[
                        'rounded-full border px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]',
                        estadoStyle[reserva.estado] ?? estadoStyle.activa,
                    ].join(' ')}
                >
                    {reserva.estado}
                </span>
            </div>

            {reserva.items.length > 0 && (
                <div className="mt-6 space-y-3">
                    {reserva.items.map((item) => (
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
                                    <Package size={16} className="text-stone-600" />
                                </div>
                            )}
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
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Reservas({ reservas }: { reservas: Reserva[] }) {
    return (
        <StoreLayout title="MORE | Mis Reservas">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-5xl">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                                Mi cuenta
                            </p>
                            <h1
                                className="mt-3 text-5xl leading-none text-white sm:text-6xl"
                                style={serifStyle}
                            >
                                Mis reservas
                            </h1>
                        </div>
                        <Link
                            href="/cuenta"
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            ← Volver
                        </Link>
                    </div>

                    {reservas.length === 0 ? (
                        <div className="mt-14 rounded-[2.2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <Package size={32} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                No tienes reservas activas.
                            </p>
                            <Link
                                href={shopRoutes.newIn}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
                            >
                                Explorar novedades
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 space-y-6">
                            {reservas.map((reserva) => (
                                <ReservaCard key={reserva.id} reserva={reserva} />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
