import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type InventoryRow = VendedorReadOnlyRecord & {
    productId: number | null;
    sku: string;
    color: string;
    size: string;
    price: string;
    available: string;
    committed: string;
    location: string;
    stockLevel: VendedorBadge;
    showHref: string | null;
};

type StockProps = {
    items: InventoryRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters & {
        productLabel?: string | null;
    };
    options: {
        colors: VendedorOption[];
        sizes: VendedorOption[];
        locations: VendedorOption[];
        stock: VendedorOption[];
        statuses: VendedorOption[];
    };
};

const columns: VendedorReadOnlyColumn<InventoryRow>[] = [
    { key: 'sku', label: 'SKU', render: (row) => row.sku },
    { key: 'color', label: 'Color', render: (row) => row.color },
    { key: 'size', label: 'Talla', render: (row) => row.size },
    { key: 'price', label: 'Precio', align: 'right', render: (row) => row.price },
    { key: 'available', label: 'Disponible', align: 'right', render: (row) => row.available },
    { key: 'committed', label: 'Comprometido', align: 'right', render: (row) => row.committed },
    { key: 'location', label: 'Ubicacion', render: (row) => row.location },
    {
        key: 'level',
        label: 'Nivel',
        render: (row) => (
            <SuperadminBadge tone={row.stockLevel.tone}>
                {row.stockLevel.label}
            </SuperadminBadge>
        ),
    },
];

export default function Stock({ items, summary, filters, options }: StockProps) {
    const rows = items.map((item) => ({
        ...item,
        actions: item.showHref ? [{ label: 'Ver prenda', href: item.showHref }] : [],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Existencias">
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Inventario
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Existencias
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Consulta de inventario en modo lectura con stock disponible y stock comprometido.
                    </p>
                </header>

                <VendedorSummaryGrid items={summary} />

                {filters.productLabel && (
                    <section className="sa-panel-soft rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                        Filtro por prenda: <span className="font-semibold text-[color:var(--admin-text)]">{filters.productLabel}</span>
                    </section>
                )}

                <VendedorFilterPanel
                    action="/vendedor/existencias"
                    resetHref="/vendedor/existencias"
                    filters={filters}
                    hidden={{ product: filters.product }}
                    fields={[
                        { name: 'search', label: 'Búsqueda', placeholder: 'Prenda, SKU, color o ubicación' },
                        { name: 'color', label: 'Color', type: 'select', options: options.colors },
                        { name: 'size', label: 'Talla', type: 'select', options: options.sizes },
                        { name: 'location', label: 'Ubicacion', type: 'select', options: options.locations },
                        { name: 'stock', label: 'Stock', type: 'select', options: options.stock },
                        { name: 'status', label: 'Estado', type: 'select', options: options.statuses },
                    ]}
                />

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin existencias para mostrar"
                    emptyDescription="No hay registros de inventario que coincidan con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
