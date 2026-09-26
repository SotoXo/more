import StoreLayout from '@/Components/shop/StoreLayout';
import { formatPrice, shopRoutes } from '@/lib/shop';
import type { Pedido, Reserva } from '@/types';
import { Link } from '@inertiajs/react';
import { Package, ShoppingBag } from 'lucide-react';
import { useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type CustomerTab = 'actuales' | 'historial' | 'compras';

const estadoStyle: Record<string, string> = {
    por_procesar: 'border-amber-400/20 bg-amber-400/8 text-amber-200',
    confirmada: 'border-sky-400/20 bg-sky-400/8 text-sky-300',
    lista_recojo: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    completada: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    recogida: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    cancelada: 'border-red-900/30 bg-red-900/10 text-red-300',
    rechazada: 'border-red-900/30 bg-red-900/10 text-red-300',
    vencida: 'border-white/10 bg-white/[0.04] text-stone-400',
};

const estadoLabel: Record<string, string> = {
    por_procesar: 'En revision',
    confirmada: 'Confirmada',
    lista_recojo: 'Lista para recojo',
    completada: 'Reserva y compra completada',
    recogida: 'Reserva y compra completada',
    cancelada: 'Cancelada',
    rechazada: 'Rechazada',
    vencida: 'Vencida',
};

const pagoLabel: Record<string, string> = {
    pendiente_revision: 'Comprobante en revision',
    anticipo_confirmado: 'Adelanto confirmado',
    pagado_total: 'Pagado completamente',
    rechazado: 'Pago rechazado',
};

const compraStyle: Record<string, string> = {
    en_revision: 'border-amber-500/20 bg-amber-500/8 text-amber-200',
    registrada: 'border-emerald-500/20 bg-emerald-500/8 text-emerald-300',
    incompleta: 'border-orange-500/20 bg-orange-500/8 text-orange-200',
    cancelada: 'border-red-900/30 bg-red-900/10 text-red-300',
};

const compraLabel: Record<string, string> = {
    en_revision: 'En revision',
    registrada: 'Compra confirmada',
    incompleta: 'Compra incompleta',
    cancelada: 'Compra cancelada',
};

function reservaMessage(reserva: Reserva, mode: 'active' | 'history') {
    if (mode === 'history') {
        return reserva.estado === 'completada' || reserva.estado === 'recogida'
            ? 'Reserva y compra completada.'
            : reserva.estado === 'cancelada'
            ? 'Reserva cancelada.'
            : reserva.estado === 'vencida'
            ? 'Reserva vencida.'
            : reserva.estado === 'rechazada'
            ? 'Reserva rechazada.'
            : 'Reserva finalizada.';
    }

    if (reserva.estado === 'por_procesar') {
        return 'Estamos revisando tu reserva. Te notificaremos cuando sea confirmada.';
    }

    if (reserva.estado === 'lista_recojo') {
        return 'Tu prenda esta lista para recojo.';
    }

    if (reserva.estadoPago === 'pagado_total') {
        return 'Tu reserva fue pagada completamente.';
    }

    if (reserva.estadoPago === 'anticipo_confirmado') {
        return 'Tu reserva fue confirmada con adelanto.';
    }

    return 'Tu reserva fue confirmada.';
}

function ReservaCard({ reserva, mode }: { reserva: Reserva; mode: 'active' | 'history' }) {
    return (
        <div className="rounded-[2rem] border border-white/10 bg-white/[0.03] p-7">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                        Reserva #{reserva.numero}
                    </p>
                    {reserva.fecha && <p className="mt-2 text-xs text-stone-500">Creada: {reserva.fecha}</p>}
                    <p className="mt-3 max-w-md text-sm leading-6 text-stone-300">
                        {reservaMessage(reserva, mode)}
                    </p>
                    {reserva.obsPago && (
                        <p className="mt-2 max-w-md text-sm leading-6 text-stone-400">{reserva.obsPago}</p>
                    )}
                </div>
                <span
                    className={[
                        'rounded-full border px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]',
                        estadoStyle[reserva.estado] ?? estadoStyle.por_procesar,
                    ].join(' ')}
                >
                    {estadoLabel[reserva.estado] ?? 'En seguimiento'}
                </span>
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                {[
                    ['Estado de pago', pagoLabel[reserva.estadoPago] ?? 'En revision'],
                    ['Total', formatPrice(reserva.total)],
                    ['Pagado', formatPrice(reserva.montoPagado)],
                    ['Saldo', formatPrice(reserva.saldo)],
                    ['Disponible desde', reserva.fechaDisponibleRecojo ?? 'Por confirmar'],
                    ['Fecha límite de recojo', reserva.fechaLimiteRecojo ?? 'Por confirmar'],
                    ['Punto de recojo', reserva.puntoRecojo ?? 'Recojo en tienda Mati Boutique'],
                ].map(([label, value]) => (
                    <div key={label} className="rounded-[1.2rem] border border-white/8 bg-white/[0.02] p-4">
                        <p className="text-[0.62rem] uppercase tracking-[0.18em] text-stone-500">{label}</p>
                        <p className="mt-2 text-sm text-white">{value}</p>
                    </div>
                ))}
            </div>

            <ItemList items={reserva.items} emptyIcon="reservation" />

            {(reserva.reciboReservaUrl || reserva.reciboVentaUrl) && (
                <div className="mt-6 flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row">
                    {reserva.reciboReservaUrl && (
                        <a
                            href={reserva.reciboReservaUrl}
                            className="rounded-full border border-white/15 px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-stone-200 transition hover:border-white/30 hover:text-white"
                        >
                            Ver recibo de reserva
                        </a>
                    )}
                    {reserva.reciboVentaUrl && (
                        <a
                            href={reserva.reciboVentaUrl}
                            className="rounded-full bg-white px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-slate-950 transition hover:bg-stone-200"
                        >
                            Ver recibo de venta
                        </a>
                    )}
                </div>
            )}
        </div>
    );
}

function compraMessage(compra: Pedido) {
    return compra.estado === 'en_revision'
        ? 'Estamos revisando tu comprobante.'
        : compra.estado === 'registrada'
        ? 'Compra confirmada.'
        : compra.estado === 'incompleta'
        ? 'Compra incompleta.'
        : compra.estado === 'cancelada'
        ? 'Compra cancelada.'
        : 'Mati Boutique esta haciendo seguimiento a tu compra.';
}

function CompraCard({ compra }: { compra: Pedido }) {
    return (
        <div className="rounded-[2rem] border border-white/10 bg-white/[0.03] p-7">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                        Compra #{compra.numero}
                    </p>
                    {compra.fecha && <p className="mt-2 text-xs text-stone-500">{compra.fecha}</p>}
                    <p className="mt-3 max-w-md text-sm leading-6 text-stone-300">{compraMessage(compra)}</p>
                    {compra.observacionPago && (
                        <p className="mt-2 max-w-md text-sm leading-6 text-stone-400">{compra.observacionPago}</p>
                    )}
                </div>
                <span
                    className={[
                        'rounded-full border px-4 py-2 text-[0.68rem] uppercase tracking-[0.2em]',
                        compraStyle[compra.estado] ?? compraStyle.en_revision,
                    ].join(' ')}
                >
                    {compraLabel[compra.estado] ?? 'En seguimiento'}
                </span>
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-3">
                {[
                    ['Total', formatPrice(compra.total)],
                    ['Método de pago', compra.pago ? compra.pago.replaceAll('_', ' ') : 'Sin metodo'],
                    ['Estado', compraLabel[compra.estado] ?? 'En seguimiento'],
                ].map(([label, value]) => (
                    <div key={label} className="rounded-[1.2rem] border border-white/8 bg-white/[0.02] p-4">
                        <p className="text-[0.62rem] uppercase tracking-[0.18em] text-stone-500">{label}</p>
                        <p className="mt-2 text-sm capitalize text-white">{value}</p>
                    </div>
                ))}
            </div>

            <ItemList items={compra.items} emptyIcon="purchase" />

            <div className="mt-6 flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center">
                {compra.comprobanteUrl && (
                    <a
                        href={compra.comprobanteUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded-full border border-white/15 px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-stone-200 transition hover:border-white/30 hover:text-white"
                    >
                        Ver comprobante subido
                    </a>
                )}
                {compra.reciboUrl ? (
                    <a
                        href={compra.reciboUrl}
                        className="rounded-full bg-white px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-slate-950 transition hover:bg-stone-200"
                    >
                        Ver recibo de venta
                    </a>
                ) : (
                    compra.estado === 'en_revision' && (
                        <p className="text-sm text-stone-400">
                            El recibo estara disponible cuando el pago sea confirmado.
                        </p>
                    )
                )}
            </div>
        </div>
    );
}

function ItemList({
    items,
    emptyIcon,
}: {
    items: Reserva['items'] | Pedido['items'];
    emptyIcon: 'reservation' | 'purchase';
}) {
    if (items.length === 0) {
        return null;
    }

    const Icon = emptyIcon === 'reservation' ? Package : ShoppingBag;

    return (
        <div className="mt-6 space-y-3">
            {items.map((item) => (
                <div key={item.id} className="flex gap-4 rounded-[1.4rem] border border-white/8 bg-white/[0.02] p-4">
                    {item.imagen ? (
                        <img src={item.imagen} alt={item.nombre} className="h-16 w-14 shrink-0 rounded-[0.9rem] object-cover" />
                    ) : (
                        <div className="flex h-16 w-14 shrink-0 items-center justify-center rounded-[0.9rem] bg-white/[0.04]">
                            <Icon size={16} className="text-stone-600" />
                        </div>
                    )}
                    <div>
                        <p className="text-[0.65rem] uppercase tracking-[0.18em] text-stone-400">{item.marca}</p>
                        <Link
                            href={shopRoutes.product(item.slug)}
                            className="mt-0.5 block text-base text-white hover:text-stone-200"
                            style={serifStyle}
                        >
                            {item.nombre}
                        </Link>
                        <p className="mt-1 text-[0.68rem] text-stone-500">
                            {item.talla} / {item.color} / x{item.cantidad} / {formatPrice(item.subtotal)}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
}

function EmptyState({
    icon,
    title,
    description,
}: {
    icon: React.ReactNode;
    title: string;
    description: string;
}) {
    return (
        <div className="mt-10 rounded-[2.2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/[0.03] text-stone-500">
                {icon}
            </div>
            <p className="mt-6 text-3xl text-white" style={serifStyle}>{title}</p>
            <p className="mt-3 text-sm text-stone-400">{description}</p>
            <Link
                href={shopRoutes.catalog}
                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
            >
                Explorar catálogo
            </Link>
        </div>
    );
}

export default function Reservas({
    reservasActuales = [],
    historialReservas = [],
    compras = [],
}: {
    reservasActuales: Reserva[];
    historialReservas: Reserva[];
    compras: Pedido[];
}) {
    const [activeTab, setActiveTab] = useState<CustomerTab>('actuales');
    const tabs = [
        { key: 'actuales' as const, label: 'Mis reservas', count: reservasActuales.length },
        { key: 'historial' as const, label: 'Historial de reservas', count: historialReservas.length },
        { key: 'compras' as const, label: 'Mis compras', count: compras.length },
    ];
    const copy = {
        actuales: {
            title: 'Mis reservas',
            subtitle: 'Revisa el estado de tus reservas activas.',
        },
        historial: {
            title: 'Historial de reservas',
            subtitle: 'Consulta tus reservas finalizadas, canceladas o vencidas.',
        },
        compras: {
            title: 'Mis compras',
            subtitle: 'Consulta tus compras realizadas y comprobantes en revision.',
        },
    }[activeTab];

    return (
        <StoreLayout title="Mati Boutique | Mis reservas">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-5xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">Mi cuenta</p>
                            <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                                {copy.title}
                            </h1>
                            <p className="mt-4 max-w-2xl text-sm leading-6 text-stone-400">{copy.subtitle}</p>
                        </div>
                        <Link
                            href="/cuenta"
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            Volver a mi cuenta
                        </Link>
                    </div>

                    <div className="mt-8 flex flex-col gap-2 rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-2 sm:flex-row">
                        {tabs.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => setActiveTab(tab.key)}
                                className={
                                    activeTab === tab.key
                                        ? 'flex flex-1 items-center justify-center gap-2 rounded-[1rem] bg-white px-4 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-slate-950'
                                        : 'flex flex-1 items-center justify-center gap-2 rounded-[1rem] px-4 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400 transition hover:bg-white/[0.04] hover:text-white'
                                }
                            >
                                {tab.label}
                                <span className="rounded-full border border-current/20 px-2 py-0.5 text-[0.62rem]">{tab.count}</span>
                            </button>
                        ))}
                    </div>

                    {activeTab === 'actuales' && (
                        reservasActuales.length === 0 ? (
                            <EmptyState
                                icon={<Package size={22} />}
                                title="No tienes reservas activas en este momento."
                                description="Cuando reserves una prenda y este en revision o recojo, aparecera aqui."
                            />
                        ) : (
                            <div className="mt-10 space-y-6">
                                {reservasActuales.map((reserva) => (
                                    <ReservaCard key={reserva.id} reserva={reserva} mode="active" />
                                ))}
                            </div>
                        )
                    )}

                    {activeTab === 'historial' && (
                        historialReservas.length === 0 ? (
                            <EmptyState
                                icon={<Package size={22} />}
                                title="Aun no tienes historial de reservas."
                                description="Tus reservas completadas, canceladas, vencidas o rechazadas se guardaran en este apartado."
                            />
                        ) : (
                            <div className="mt-10 space-y-6">
                                {historialReservas.map((reserva) => (
                                    <ReservaCard key={reserva.id} reserva={reserva} mode="history" />
                                ))}
                            </div>
                        )
                    )}

                    {activeTab === 'compras' && (
                        compras.length === 0 ? (
                            <EmptyState
                                icon={<ShoppingBag size={22} />}
                                title="Aun no tienes compras registradas."
                                description="Cuando finalices una compra online, podras revisar aqui su estado."
                            />
                        ) : (
                            <div className="mt-10 space-y-6">
                                {compras.map((compra) => (
                                    <CompraCard key={compra.id} compra={compra} />
                                ))}
                            </div>
                        )
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
