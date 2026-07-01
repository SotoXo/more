import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type PaymentRow = VendedorReadOnlyRecord & {
    date: string;
    type: string;
    typeLabel: string;
    number: string;
    customer: string;
    paymentMethod: string;
    amount: string;
    status: VendedorBadge;
    origin: string;
    showHref: string;
};

type PaymentTab = {
    label: string;
    href: string;
    active: boolean;
    count: string;
};

type PaymentsIndexProps = {
    payments: PaymentRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        types: VendedorOption[];
        methods: VendedorOption[];
        statuses: VendedorOption[];
    };
    tabs: PaymentTab[];
    scopeLabel: string;
};

const columns: VendedorReadOnlyColumn<PaymentRow>[] = [
    { key: 'date', label: 'Fecha', render: (row) => row.date },
    { key: 'type', label: 'Tipo', render: (row) => row.typeLabel },
    { key: 'number', label: 'Número', render: (row) => row.number },
    { key: 'customer', label: 'Cliente', render: (row) => row.customer },
    { key: 'method', label: 'Método', render: (row) => row.paymentMethod },
    { key: 'amount', label: 'Monto', align: 'right', render: (row) => row.amount },
    {
        key: 'status',
        label: 'Estado',
        render: (row) => (
            <SuperadminBadge tone={row.status.tone}>
                {row.status.label}
            </SuperadminBadge>
        ),
    },
    { key: 'origin', label: 'Origen', render: (row) => row.origin },
];

function PaymentTabs({ tabs }: { tabs: PaymentTab[] }) {
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

export default function Index({ payments, summary, filters, options, tabs, scopeLabel }: PaymentsIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const rows = payments.map((payment) => ({
        ...payment,
        actions: [{ label: 'Revisar', href: payment.showHref }] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Revision de pagos">
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Comercial
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Revision de comprobantes
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        {scopeLabel}
                    </p>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />
                <PaymentTabs tabs={tabs} />
                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/pagos"
                    resetHref="/vendedor/pagos"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Búsqueda', placeholder: 'Número, cliente o método' },
                        { name: 'status', label: 'Estado', type: 'select', options: options.statuses },
                        { name: 'type', label: 'Tipo', type: 'select', options: options.types },
                        { name: 'method', label: 'Método de pago', type: 'select', options: options.methods },
                        { name: 'date_from', label: 'Desde', type: 'date' },
                        { name: 'date_to', label: 'Hasta', type: 'date' },
                    ]}
                />

                <section className="sa-panel-muted rounded-2xl p-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    Esta revisión confirma o rechaza comprobantes subidos por clientes. No registra pagos del vendedor ni escribe inventario directamente desde esta pantalla.
                </section>

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin comprobantes"
                    emptyDescription="No hay comprobantes de clientes con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
