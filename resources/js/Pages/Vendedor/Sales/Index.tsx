import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type SaleRow = VendedorReadOnlyRecord & {
    number: string;
    date: string;
    customer: string;
    seller: string;
    total: string;
    paymentMethod: string;
    paymentStatus: VendedorBadge;
    saleStatus: VendedorBadge;
    showHref: string;
};

type SalesIndexProps = {
    sales: SaleRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        paymentMethods: VendedorOption[];
        statuses: VendedorOption[];
    };
    scopeLabel: string;
};

const columns: VendedorReadOnlyColumn<SaleRow>[] = [
    { key: 'date', label: 'Fecha', render: (row) => row.date },
    { key: 'customer', label: 'Cliente', render: (row) => row.customer },
    { key: 'seller', label: 'Vendedor', render: (row) => row.seller },
    { key: 'total', label: 'Total', align: 'right', render: (row) => row.total },
    { key: 'method', label: 'Metodo', render: (row) => row.paymentMethod },
    {
        key: 'payment',
        label: 'Pago',
        render: (row) => (
            <SuperadminBadge tone={row.paymentStatus.tone}>
                {row.paymentStatus.label}
            </SuperadminBadge>
        ),
    },
    {
        key: 'status',
        label: 'Venta',
        render: (row) => (
            <SuperadminBadge tone={row.saleStatus.tone}>
                {row.saleStatus.label}
            </SuperadminBadge>
        ),
    },
];

export default function Index({ sales, summary, filters, options, scopeLabel }: SalesIndexProps) {
    const rows = sales.map((sale) => ({
        ...sale,
        actions: [{ label: 'Ver detalle', href: sale.showHref }] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Ventas vendedor">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            Comercial
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Ventas
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {scopeLabel}
                        </p>
                    </div>
                    <Link href="/vendedor/ventas/crear" className="sa-button-primary h-11 px-4 text-sm font-semibold">
                        <Plus size={16} />
                        Nueva venta
                    </Link>
                </header>

                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/ventas"
                    resetHref="/vendedor/ventas"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Busqueda', placeholder: 'Numero, cliente o vendedor' },
                        { name: 'date_from', label: 'Desde', type: 'date' },
                        { name: 'date_to', label: 'Hasta', type: 'date' },
                        { name: 'payment_method', label: 'Metodo de pago', type: 'select', options: options.paymentMethods },
                        { name: 'status', label: 'Estado', type: 'select', options: options.statuses },
                    ]}
                />

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin ventas para mostrar"
                    emptyDescription="No hay ventas registradas con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
