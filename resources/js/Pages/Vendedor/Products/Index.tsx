import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type ProductRow = VendedorReadOnlyRecord & {
    category: string;
    brand: string;
    season: string;
    gender: string;
    price: string;
    availability: VendedorBadge;
    available: string;
    variantsCount: string;
    availableVariants: string;
    colors: string;
    sizes: string;
    showHref: string;
    stockHref: string;
};

type ProductIndexProps = {
    products: ProductRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        categories: VendedorOption[];
        brands: VendedorOption[];
        colors: VendedorOption[];
        sizes: VendedorOption[];
        locations: VendedorOption[];
        availability: VendedorOption[];
        statuses: VendedorOption[];
    };
};

const columns: VendedorReadOnlyColumn<ProductRow>[] = [
    { key: 'category', label: 'Categoria', render: (row) => row.category },
    { key: 'brand', label: 'Marca', render: (row) => row.brand },
    { key: 'season', label: 'Temporada', render: (row) => row.season },
    { key: 'price', label: 'Precio', align: 'right', render: (row) => row.price },
    { key: 'available', label: 'Disponible', align: 'right', render: (row) => row.available },
    {
        key: 'availability',
        label: 'Stock',
        render: (row) => (
            <SuperadminBadge tone={row.availability.tone}>
                {row.availability.label}
            </SuperadminBadge>
        ),
    },
    { key: 'variants', label: 'Variantes', align: 'right', render: (row) => `${row.availableVariants}/${row.variantsCount}` },
    { key: 'colors', label: 'Colores', render: (row) => row.colors },
    { key: 'sizes', label: 'Tallas', render: (row) => row.sizes },
];

export default function Index({ products, summary, filters, options }: ProductIndexProps) {
    const rows = products.map((product) => ({
        ...product,
        actions: [
            { label: 'Ver detalle', href: product.showHref },
            { label: 'Ver existencias', href: product.stockHref },
        ] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Consultar prendas">
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Catalogo
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Consultar prendas
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Búsqueda operativa de prendas, variantes, colores, tallas y disponibilidad real.
                    </p>
                </header>

                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/prendas"
                    resetHref="/vendedor/prendas"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Búsqueda', placeholder: 'Nombre, SKU, color o talla' },
                        { name: 'category', label: 'Categoria', type: 'select', options: options.categories },
                        { name: 'brand', label: 'Marca', type: 'select', options: options.brands },
                        { name: 'color', label: 'Color', type: 'select', options: options.colors },
                        { name: 'size', label: 'Talla', type: 'select', options: options.sizes },
                        { name: 'availability', label: 'Disponibilidad', type: 'select', options: options.availability },
                        { name: 'location', label: 'Ubicacion', type: 'select', options: options.locations },
                        { name: 'status', label: 'Estado', type: 'select', options: options.statuses },
                    ]}
                />

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin prendas para mostrar"
                    emptyDescription="No hay prendas que coincidan con los filtros aplicados."
                />
            </div>
        </VendedorLayout>
    );
}
