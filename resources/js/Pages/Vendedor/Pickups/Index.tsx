import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type PickupRow = VendedorReadOnlyRecord & {
    type: string;
    typeLabel: string;
    number: string;
    customer: string;
    date: string;
    limitAt: string;
    total: string;
    paid: string;
    balance: string;
    paymentStatus: VendedorBadge;
    logisticsStatus: string;
    location: string;
    showHref: string;
};

type PickupTab = {
    label: string;
    href: string;
    active: boolean;
    count: string;
};

type PickupsIndexProps = {
    pickups: PickupRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        types: VendedorOption[];
        paymentStatuses: VendedorOption[];
        logisticsStatuses: VendedorOption[];
    };
    tabs: PickupTab[];
    scopeLabel: string;
};

const columns: VendedorReadOnlyColumn<PickupRow>[] = [
    { key: 'type', label: 'Tipo', render: (row) => row.typeLabel },
    { key: 'number', label: 'Número', render: (row) => row.number },
    { key: 'customer', label: 'Cliente', render: (row) => row.customer },
    { key: 'date', label: 'Fecha', render: (row) => row.date },
    { key: 'total', label: 'Total', align: 'right', render: (row) => row.total },
    { key: 'paid', label: 'Pagado', align: 'right', render: (row) => row.paid },
    { key: 'balance', label: 'Saldo', align: 'right', render: (row) => row.balance },
    {
        key: 'payment',
        label: 'Pago',
        render: (row) => (
            <SuperadminBadge tone={row.paymentStatus.tone}>
                {row.paymentStatus.label}
            </SuperadminBadge>
        ),
    },
    { key: 'logistics', label: 'Logística', render: (row) => row.logisticsStatus },
    { key: 'location', label: 'Ubicación', render: (row) => row.location },
];

function PickupTabs({ tabs }: { tabs: PickupTab[] }) {
    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Link
                    key={tab.href}
                    href={tab.href}
                    className={[
                        'inline-flex h-10 items-center gap-2 rounded-xl border px-4 text-xs font-semibold transition',
                        tab.active
                            ? 'border-[rgba(216,189,143,0.38)] bg-[rgba(216,189,143,0.14)] text-[color:var(--admin-text)]'
                            : 'border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]',
                    ].join(' ')}
                >
                    {tab.label}
                    <span className="rounded-full border border-white/10 px-2 py-0.5 text-[0.65rem]">
                        {tab.count}
                    </span>
                </Link>
            ))}
        </div>
    );
}

export default function Index({ pickups, summary, filters, options, tabs, scopeLabel }: PickupsIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const rows = pickups.map((pickup) => ({
        ...pickup,
        actions: [{ label: 'Ver entrega', href: pickup.showHref }] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Recojos y entregas">
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Comercial
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Recojos y entregas
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        {scopeLabel}
                    </p>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />
                <PickupTabs tabs={tabs} />
                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/recojos"
                    resetHref={`/vendedor/recojos?tab=${filters.tab ?? 'ventas'}`}
                    filters={filters}
                    hidden={{ tab: filters.tab ?? 'ventas' }}
                    fields={[
                        { name: 'search', label: 'Búsqueda', placeholder: 'Número o cliente' },
                        { name: 'type', label: 'Tipo', type: 'select', options: options.types },
                        { name: 'payment_status', label: 'Estado de pago', type: 'select', options: options.paymentStatuses },
                        { name: 'logistics_status', label: 'Estado logistico', type: 'select', options: options.logisticsStatuses },
                        { name: 'date_from', label: 'Desde', type: 'date' },
                        { name: 'date_to', label: 'Hasta', type: 'date' },
                    ]}
                />

                <section className="sa-panel-muted rounded-2xl p-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    No se puede entregar porque el pago no está confirmado al 100%. Las ventas entregadas no modifican stock; las reservas recogidas usan el flujo central de conversión y no descuentan el stock disponible otra vez.
                </section>

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin operaciones para mostrar"
                    emptyDescription="No hay ventas o reservas en esta bandeja con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
