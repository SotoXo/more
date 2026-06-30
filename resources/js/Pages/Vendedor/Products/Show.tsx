import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Warehouse } from 'lucide-react';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';

type ProductDetail = {
    id: number;
    name: string;
    description: string;
    image: string | null;
    gallery: string[];
    category: string;
    brand: string;
    season: string;
    gender: string;
    price: string;
    status: VendedorBadge;
    available: string;
    committed: string;
    colors: string[];
    sizes: string[];
    locations: string[];
};

type VariantRow = VendedorReadOnlyRecord & {
    sku: string;
    color: string;
    size: string;
    price: string;
    available: string;
    committed: string;
    location: string;
    stockLevel: VendedorBadge;
};

type ProductShowProps = {
    product: ProductDetail;
    variants: VariantRow[];
};

const variantColumns: VendedorReadOnlyColumn<VariantRow>[] = [
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

function DetailItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="sa-panel-soft rounded-2xl p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-2 text-sm font-medium text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

export default function Show({ product, variants }: ProductShowProps) {
    return (
        <VendedorLayout title={`Mati Boutique | ${product.name}`}>
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="max-w-4xl">
                        <Link href="/vendedor/prendas" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                            <ArrowLeft size={15} />
                            Consultar prendas
                        </Link>
                        <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                            Detalle solo lectura
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            {product.name}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {product.description}
                        </p>
                    </div>
                    <Link href={`/vendedor/existencias?product=${product.id}`} className="sa-button-secondary h-11 px-4 text-sm font-semibold">
                        <Warehouse size={16} />
                        Ver existencias
                    </Link>
                </header>

                <section className="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <div className="sa-panel rounded-2xl p-4">
                        {product.image ? (
                            <img src={product.image} alt={product.name} className="aspect-[4/5] w-full rounded-2xl object-cover" />
                        ) : (
                            <div className="flex aspect-[4/5] w-full items-center justify-center rounded-2xl border border-dashed border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] text-sm text-[color:var(--admin-muted)]">
                                Sin imagen
                            </div>
                        )}
                        {product.gallery.length > 1 && (
                            <div className="mt-3 grid grid-cols-4 gap-2">
                                {product.gallery.slice(0, 8).map((image) => (
                                    <img key={image} src={image} alt={product.name} className="aspect-square rounded-xl object-cover" />
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="space-y-4">
                        <section className="sa-panel rounded-2xl p-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <SuperadminBadge tone={product.status.tone}>
                                    {product.status.label}
                                </SuperadminBadge>
                                <SuperadminBadge tone="success">
                                    Disponible {product.available}
                                </SuperadminBadge>
                                <SuperadminBadge tone="warning">
                                    Comprometido {product.committed}
                                </SuperadminBadge>
                            </div>
                            <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                <DetailItem label="Categoria" value={product.category} />
                                <DetailItem label="Marca" value={product.brand} />
                                <DetailItem label="Temporada" value={product.season} />
                                <DetailItem label="Genero" value={product.gender} />
                                <DetailItem label="Precio base" value={product.price} />
                                <DetailItem label="Ubicaciones" value={product.locations.join(', ') || 'Sin ubicacion'} />
                                <DetailItem label="Colores" value={product.colors.join(', ') || 'Sin color'} />
                                <DetailItem label="Tallas" value={product.sizes.join(', ') || 'Sin talla'} />
                            </div>
                        </section>
                    </div>
                </section>

                <section className="space-y-3">
                    <div>
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                            Variantes e inventario
                        </p>
                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                            Disponible corresponde a cdi_inv y comprometido corresponde a cre_inv.
                        </p>
                    </div>
                    <VendedorReadOnlyTable
                        rows={variants}
                        columns={variantColumns}
                        emptyTitle="Sin variantes"
                        emptyDescription="Esta prenda no tiene variantes activas para consulta."
                    />
                </section>
            </div>
        </VendedorLayout>
    );
}
