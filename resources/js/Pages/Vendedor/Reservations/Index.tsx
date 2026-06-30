import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type ReservationRow = VendedorReadOnlyRecord & {
    number: string;
    date: string;
    customer: string;
    responsible: string;
    total: string;
    paid: string;
    balance: string;
    expiresAt: string;
    status: VendedorBadge;
    paymentStatus: VendedorBadge;
    showHref: string;
};

type ReservationsIndexProps = {
    reservations: ReservationRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        statuses: VendedorOption[];
        customers: VendedorOption[];
    };
    scopeLabel: string;
};

const columns: VendedorReadOnlyColumn<ReservationRow>[] = [
    { key: 'date', label: 'Fecha', render: (row) => row.date },
    { key: 'customer', label: 'Cliente', render: (row) => row.customer },
    { key: 'responsible', label: 'Responsable', render: (row) => row.responsible },
    { key: 'total', label: 'Total', align: 'right', render: (row) => row.total },
    { key: 'paid', label: 'Pagado', align: 'right', render: (row) => row.paid },
    { key: 'balance', label: 'Saldo', align: 'right', render: (row) => row.balance },
    { key: 'expiresAt', label: 'Vence', render: (row) => row.expiresAt },
    {
        key: 'payment',
        label: 'Pago',
        render: (row) => (
            <SuperadminBadge tone={row.paymentStatus.tone}>
                {row.paymentStatus.label}
            </SuperadminBadge>
        ),
    },
];

export default function Index({ reservations, summary, filters, options, scopeLabel }: ReservationsIndexProps) {
    const rows = reservations.map((reservation) => ({
        ...reservation,
        actions: [{ label: 'Ver detalle', href: reservation.showHref }] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Reservas vendedor">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            Comercial
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Reservas
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {scopeLabel}
                        </p>
                    </div>
                    <Link href="/vendedor/reservas/crear" className="sa-button-primary h-11 px-4 text-sm font-semibold">
                        <Plus size={16} />
                        Nueva reserva
                    </Link>
                </header>

                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/reservas"
                    resetHref="/vendedor/reservas"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Busqueda', placeholder: 'Numero, cliente o responsable' },
                        { name: 'date_from', label: 'Desde', type: 'date' },
                        { name: 'date_to', label: 'Hasta', type: 'date' },
                        { name: 'status', label: 'Estado', type: 'select', options: options.statuses },
                        { name: 'customer', label: 'Cliente', type: 'select', options: options.customers },
                    ]}
                />

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin reservas para mostrar"
                    emptyDescription="No hay reservas registradas con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
