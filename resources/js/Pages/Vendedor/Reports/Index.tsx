import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { SuperadminTone } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { CheckCheck, Download, ExternalLink, Printer, Search } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';

type Badge = {
    label: string;
    tone?: SuperadminTone;
    key?: string | null;
};

type SummaryCard = {
    label: string;
    value: string;
    note: string;
    tone?: SuperadminTone;
    href?: string;
};

type ChartPoint = {
    label: string;
    value: number;
    amount?: number;
};

type ReportNotification = {
    id: string;
    title: string;
    message: string;
    type?: string | null;
    url?: string | null;
    severity?: string | null;
    read_at?: string | null;
    created_at?: string | null;
};

type SaleRow = {
    id: number;
    primary: string;
    secondary: string;
    date: string;
    number: string;
    customer: string;
    total: string;
    paymentMethod: string;
    status: Badge;
    delivery: string;
    showHref: string;
};

type ReservationRow = {
    id: number;
    primary: string;
    secondary: string;
    date: string;
    number: string;
    customer: string;
    total: string;
    paid: string;
    balance: string;
    status: Badge;
    showHref: string;
};

type PaymentRow = {
    id: string;
    primary: string;
    secondary: string;
    date: string;
    type: string;
    operation: string;
    method: string;
    amount: string;
    status: Badge;
    action: string;
};

type PickupRow = {
    id: string;
    primary: string;
    secondary: string;
    date: string;
    type: string;
    number: string;
    customer: string;
    status: Badge;
};

type DamagedProductRow = {
    id: number;
    primary: string;
    secondary: string;
    date: string;
    code: string;
    name: string;
    cause: string;
    damageType: string;
    status: Badge;
    showHref: string;
};

type ReportsProps = {
    filters: {
        date_from: string;
        date_to: string;
    };
    filterAction: string;
    exportHref: string;
    printHref: string;
    scopeLabel: string;
    summary: SummaryCard[];
    charts: {
        salesByDay: ChartPoint[];
        reservationsByState: ChartPoint[];
        paymentsByMethod: ChartPoint[];
        pickupsByState: ChartPoint[];
    };
    tables: {
        sales: SaleRow[];
        reservations: ReservationRow[];
        payments: PaymentRow[];
        pickups: PickupRow[];
        damagedProducts: DamagedProductRow[];
    };
    notifications: {
        unreadCount: number;
        items: ReportNotification[];
        endpoints: {
            index: string;
            markAll: string;
            markRead: string;
        };
    };
    limits: {
        tableRows: number;
        tableNote: string;
    };
};

function severityTone(severity?: string | null): SuperadminTone {
    if (severity === 'success') return 'success';
    if (severity === 'warning') return 'warning';
    if (severity === 'error') return 'danger';

    return 'info';
}

function notificationDate(value?: string | null) {
    if (!value) return 'Sin fecha';

    try {
        return new Intl.DateTimeFormat('es-BO', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    } catch {
        return 'Sin fecha';
    }
}

function maxValue(points: ChartPoint[]) {
    return Math.max(1, ...points.map((point) => point.value));
}

function MiniBars({ title, points }: { title: string; points: ChartPoint[] }) {
    const max = maxValue(points);

    return (
        <section className="sa-panel rounded-2xl p-4">
            <div className="flex items-center justify-between gap-3 border-b border-[color:var(--admin-border)] pb-3">
                <p className="text-sm font-semibold text-[color:var(--admin-text)]">{title}</p>
                <SuperadminBadge tone="neutral">{points.length}</SuperadminBadge>
            </div>
            {points.length > 0 ? (
                <div className="mt-4 space-y-3">
                    {points.slice(0, 8).map((point) => (
                        <div key={point.label} className="space-y-1.5">
                            <div className="flex items-center justify-between gap-3 text-xs">
                                <span className="truncate text-[color:var(--admin-muted)]">{point.label}</span>
                                <span className="font-semibold text-[color:var(--admin-text)]">{point.value}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-[color:var(--admin-surface-soft)]">
                                <div
                                    className="h-full rounded-full bg-[color:var(--admin-accent)]"
                                    style={{ width: `${Math.max(8, (point.value / max) * 100)}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <p className="mt-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    Sin datos en el periodo.
                </p>
            )}
        </section>
    );
}

function MetricGrid({ summary }: { summary: SummaryCard[] }) {
    return (
        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {summary.map((item) => {
                const content = (
                    <div className="sa-dashboard-stat">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">{item.label}</p>
                                <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">{item.value}</p>
                            </div>
                            <SuperadminBadge tone={item.tone ?? 'neutral'}>Periodo</SuperadminBadge>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{item.note}</p>
                    </div>
                );

                return item.href ? (
                    <Link key={item.label} href={item.href} className="block">
                        {content}
                    </Link>
                ) : (
                    <div key={item.label}>{content}</div>
                );
            })}
        </section>
    );
}

function ReportTable<T>({
    title,
    rows,
    columns,
    empty,
}: {
    title: string;
    rows: T[];
    columns: Array<{
        key: string;
        label: string;
        align?: 'left' | 'right';
        render: (row: T) => ReactNode;
    }>;
    empty: string;
}) {
    return (
        <section className="sa-panel rounded-2xl p-4">
            <div className="flex items-center justify-between gap-3 border-b border-[color:var(--admin-border)] pb-3">
                <p className="text-sm font-semibold text-[color:var(--admin-text)]">{title}</p>
                <SuperadminBadge tone="neutral">{rows.length}</SuperadminBadge>
            </div>

            {rows.length > 0 ? (
                <div className="mt-4 overflow-x-auto">
                    <table className="sa-table min-w-[860px]">
                        <thead>
                            <tr>
                                {columns.map((column) => (
                                    <th key={column.key} className={column.align === 'right' ? 'text-right' : 'text-left'}>
                                        {column.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => (
                                <tr key={String((row as { id?: string | number }).id ?? index)} className="sa-table-row">
                                    {columns.map((column) => (
                                        <td key={column.key} className={column.align === 'right' ? 'text-right' : 'text-left'}>
                                            {column.render(row)}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="mt-4 text-sm leading-6 text-[color:var(--admin-muted)]">{empty}</p>
            )}
        </section>
    );
}

function NotificationPanel({
    notifications,
}: {
    notifications: ReportsProps['notifications'];
}) {
    const [items, setItems] = useState(notifications.items);
    const [unreadCount, setUnreadCount] = useState(notifications.unreadCount);
    const [processing, setProcessing] = useState<string | null>(null);

    const markRead = async (item: ReportNotification) => {
        setProcessing(item.id);

        try {
            const response = await axios.post<{
                unread_count: number;
                notification?: ReportNotification;
            }>(notifications.endpoints.markRead.replace('__ID__', item.id), {}, {
                headers: { Accept: 'application/json' },
            });

            setUnreadCount(Number(response.data.unread_count ?? 0));
            setItems((current) =>
                current.map((row) =>
                    row.id === item.id
                        ? { ...row, read_at: response.data.notification?.read_at ?? new Date().toISOString() }
                        : row,
                ),
            );
        } finally {
            setProcessing(null);
        }
    };

    const markAll = async () => {
        setProcessing('all');

        try {
            await axios.post(notifications.endpoints.markAll, {}, { headers: { Accept: 'application/json' } });
            setUnreadCount(0);
            setItems((current) => current.map((item) => ({ ...item, read_at: item.read_at ?? new Date().toISOString() })));
        } finally {
            setProcessing(null);
        }
    };

    return (
        <section className="sa-panel rounded-2xl p-4">
            <div className="flex flex-col gap-3 border-b border-[color:var(--admin-border)] pb-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">Notificaciones operativas</p>
                    <p className="mt-1 text-xs text-[color:var(--admin-muted)]">
                        {unreadCount > 0 ? `${unreadCount} sin leer` : 'Todo al dia'}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => void markAll()}
                    disabled={unreadCount === 0 || processing === 'all'}
                    className="sa-button-secondary h-10 px-4 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <CheckCheck size={15} />
                    Leer todas
                </button>
            </div>

            {items.length > 0 ? (
                <div className="mt-4 grid gap-3 lg:grid-cols-2">
                    {items.map((item) => {
                        const unread = !item.read_at;

                        return (
                            <article key={item.id} className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <SuperadminBadge tone={severityTone(item.severity)}>{item.type ?? 'operativa'}</SuperadminBadge>
                                            {unread && <SuperadminBadge tone="warning">Sin leer</SuperadminBadge>}
                                        </div>
                                        <p className="mt-3 text-sm font-semibold text-[color:var(--admin-text)]">{item.title}</p>
                                        <p className="mt-2 line-clamp-2 text-sm leading-6 text-[color:var(--admin-muted)]">{item.message}</p>
                                        <p className="mt-2 text-xs text-[color:var(--admin-muted)]">{notificationDate(item.created_at)}</p>
                                    </div>
                                </div>
                                <div className="mt-3 flex flex-wrap justify-end gap-2">
                                    {unread && (
                                        <button
                                            type="button"
                                            onClick={() => void markRead(item)}
                                            disabled={processing === item.id}
                                            className="sa-button-ghost h-9 px-3 text-xs font-semibold"
                                        >
                                            Marcar leida
                                        </button>
                                    )}
                                    {item.url && (
                                        <Link href={item.url} className="sa-button-secondary h-9 px-3 text-xs font-semibold">
                                            <ExternalLink size={14} />
                                            Abrir
                                        </Link>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            ) : (
                <p className="mt-4 text-sm leading-6 text-[color:var(--admin-muted)]">No tienes notificaciones operativas recientes.</p>
            )}
        </section>
    );
}

export default function Index({
    filters,
    filterAction,
    exportHref,
    printHref,
    scopeLabel,
    summary,
    charts,
    tables,
    notifications,
    limits,
}: ReportsProps) {
    return (
        <VendedorLayout title="Mati Boutique | Mis reportes">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">Analisis</p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Mis reportes
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">{scopeLabel}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <a href={exportHref} className="sa-button-secondary inline-flex min-h-11 items-center justify-center gap-2 px-4 text-xs font-semibold">
                            <Download size={15} />
                            CSV
                        </a>
                        <Link href={printHref} className="sa-button-ghost inline-flex min-h-11 items-center justify-center gap-2 px-4 text-xs font-semibold">
                            <Printer size={15} />
                            Imprimir
                        </Link>
                    </div>
                </header>

                <form action={filterAction} method="get" className="sa-panel rounded-2xl p-4">
                    <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                        <label className="space-y-2">
                            <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Fecha desde</span>
                            <input type="date" name="date_from" defaultValue={filters.date_from} className="sa-input min-h-11 w-full px-3 text-sm" />
                        </label>
                        <label className="space-y-2">
                            <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Fecha hasta</span>
                            <input type="date" name="date_to" defaultValue={filters.date_to} className="sa-input min-h-11 w-full px-3 text-sm" />
                        </label>
                        <button type="submit" className="sa-button-secondary h-11 px-4 text-xs font-semibold">
                            <Search size={15} />
                            Filtrar
                        </button>
                    </div>
                </form>

                <MetricGrid summary={summary} />

                <section className="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <MiniBars title="Ventas por dia" points={charts.salesByDay} />
                    <MiniBars title="Reservas por estado" points={charts.reservationsByState} />
                    <MiniBars title="Pagos por metodo" points={charts.paymentsByMethod} />
                    <MiniBars title="Recojos por estado" points={charts.pickupsByState} />
                </section>

                <section className="sa-panel-muted rounded-2xl p-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    {limits.tableNote}
                </section>

                <div className="space-y-5">
                    <ReportTable<SaleRow>
                        title="Ventas del vendedor"
                        rows={tables.sales}
                        empty="Sin ventas en el periodo."
                        columns={[
                            { key: 'date', label: 'Fecha', render: (row) => row.date },
                            { key: 'number', label: 'Venta', render: (row) => <Link href={row.showHref} className="font-semibold text-[color:var(--admin-text)]">{row.number}</Link> },
                            { key: 'customer', label: 'Cliente', render: (row) => row.customer },
                            { key: 'total', label: 'Total', align: 'right', render: (row) => row.total },
                            { key: 'method', label: 'Método', render: (row) => row.paymentMethod },
                            { key: 'status', label: 'Estado', render: (row) => <SuperadminBadge tone={row.status.tone}>{row.status.label}</SuperadminBadge> },
                            { key: 'delivery', label: 'Entrega', render: (row) => row.delivery },
                        ]}
                    />

                    <ReportTable<ReservationRow>
                        title="Reservas del vendedor"
                        rows={tables.reservations}
                        empty="Sin reservas en el periodo."
                        columns={[
                            { key: 'date', label: 'Fecha', render: (row) => row.date },
                            { key: 'number', label: 'Reserva', render: (row) => <Link href={row.showHref} className="font-semibold text-[color:var(--admin-text)]">{row.number}</Link> },
                            { key: 'customer', label: 'Cliente', render: (row) => row.customer },
                            { key: 'total', label: 'Total', align: 'right', render: (row) => row.total },
                            { key: 'paid', label: 'Pagado', align: 'right', render: (row) => row.paid },
                            { key: 'balance', label: 'Saldo', align: 'right', render: (row) => row.balance },
                            { key: 'status', label: 'Estado', render: (row) => <SuperadminBadge tone={row.status.tone}>{row.status.label}</SuperadminBadge> },
                        ]}
                    />

                    <ReportTable<PaymentRow>
                        title="Pagos gestionados"
                        rows={tables.payments}
                        empty="Sin pagos gestionados en el periodo."
                        columns={[
                            { key: 'date', label: 'Fecha', render: (row) => row.date },
                            { key: 'type', label: 'Tipo', render: (row) => row.type },
                            { key: 'operation', label: 'Operacion', render: (row) => row.operation },
                            { key: 'method', label: 'Método', render: (row) => row.method },
                            { key: 'amount', label: 'Monto', align: 'right', render: (row) => row.amount },
                            { key: 'status', label: 'Estado', render: (row) => <SuperadminBadge tone={row.status.tone}>{row.status.label}</SuperadminBadge> },
                            { key: 'action', label: 'Acción', render: (row) => row.action },
                        ]}
                    />

                    <ReportTable<PickupRow>
                        title="Recojos y entregas"
                        rows={tables.pickups}
                        empty="Sin recojos o entregas realizadas en el periodo."
                        columns={[
                            { key: 'date', label: 'Fecha', render: (row) => row.date },
                            { key: 'type', label: 'Tipo', render: (row) => row.type },
                            { key: 'number', label: 'Número', render: (row) => row.number },
                            { key: 'customer', label: 'Cliente', render: (row) => row.customer },
                            { key: 'status', label: 'Estado', render: (row) => <SuperadminBadge tone={row.status.tone}>{row.status.label}</SuperadminBadge> },
                        ]}
                    />

                    <ReportTable<DamagedProductRow>
                        title="Prendas dañadas reportadas"
                        rows={tables.damagedProducts}
                        empty="Sin reportes de prendas dañadas en el período."
                        columns={[
                            { key: 'date', label: 'Fecha', render: (row) => row.date },
                            { key: 'code', label: 'Código', render: (row) => <Link href={row.showHref} className="font-semibold text-[color:var(--admin-text)]">{row.code}</Link> },
                            { key: 'name', label: 'Prenda', render: (row) => row.name },
                            { key: 'cause', label: 'Causa', render: (row) => row.cause },
                            { key: 'damageType', label: 'Tipo', render: (row) => row.damageType },
                            { key: 'status', label: 'Estado', render: (row) => <SuperadminBadge tone={row.status.tone}>{row.status.label}</SuperadminBadge> },
                        ]}
                    />
                </div>

                <NotificationPanel notifications={notifications} />
            </div>
        </VendedorLayout>
    );
}
