import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type AlertRow = VendedorReadOnlyRecord & {
    sku: string;
    color: string;
    size: string;
    available: string;
    committed: string;
    location: string;
    stockLevel: VendedorBadge;
    showHref: string | null;
};

type StockAlertsProps = {
    items: AlertRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    alerts: {
        limit: number;
        withoutStock: number;
        critical: number;
        low: number;
    };
    options: {
        colors: VendedorOption[];
        sizes: VendedorOption[];
        locations: VendedorOption[];
    };
};

const columns: VendedorReadOnlyColumn<AlertRow>[] = [
    { key: 'sku', label: 'SKU', render: (row) => row.sku },
    { key: 'color', label: 'Color', render: (row) => row.color },
    { key: 'size', label: 'Talla', render: (row) => row.size },
    { key: 'available', label: 'Disponible', align: 'right', render: (row) => row.available },
    { key: 'committed', label: 'Comprometido', align: 'right', render: (row) => row.committed },
    { key: 'location', label: 'Ubicacion', render: (row) => row.location },
    {
        key: 'level',
        label: 'Nivel de alerta',
        render: (row) => (
            <SuperadminBadge tone={row.stockLevel.tone}>
                {row.stockLevel.label}
            </SuperadminBadge>
        ),
    },
];

export default function StockAlerts({ items, summary, filters, alerts, options }: StockAlertsProps) {
    const rows = items.map((item) => ({
        ...item,
        actions: item.showHref ? [{ label: 'Ver prenda', href: item.showHref }] : [],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Alertas de stock">
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Inventario
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Alertas de stock
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Lectura de variantes con stock disponible menor o igual a {alerts.limit}.
                    </p>
                </header>

                <VendedorSummaryGrid items={summary} />

                <section className="grid gap-3 md:grid-cols-3">
                    <div className="sa-dashboard-card rounded-2xl">
                        <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Sin stock</p>
                        <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">{alerts.withoutStock}</p>
                    </div>
                    <div className="sa-dashboard-card rounded-2xl">
                        <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Critico</p>
                        <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">{alerts.critical}</p>
                    </div>
                    <div className="sa-dashboard-card rounded-2xl">
                        <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Bajo</p>
                        <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">{alerts.low}</p>
                    </div>
                </section>

                <VendedorFilterPanel
                    action="/vendedor/alertas-stock"
                    resetHref="/vendedor/alertas-stock"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Búsqueda', placeholder: 'Prenda, SKU, color o ubicación' },
                        { name: 'color', label: 'Color', type: 'select', options: options.colors },
                        { name: 'size', label: 'Talla', type: 'select', options: options.sizes },
                        { name: 'location', label: 'Ubicacion', type: 'select', options: options.locations },
                    ]}
                />

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin alertas de stock"
                    emptyDescription="No hay variantes con stock disponible bajo para los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
