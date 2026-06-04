import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminEmptyState from '@/Components/Superadmin/SuperadminEmptyState';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminListFilters from '@/Components/Superadmin/SuperadminListFilters';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type {
    SuperadminAppliedFilter,
    SuperadminBreadcrumb,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
    SuperadminTone,
} from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download, X } from 'lucide-react';
import { useMemo } from 'react';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Pagination = {
    links: PaginationLink[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

type SelectedMovement = {
    id: number;
    code: string;
    date: string;
    type: string;
    typeTone: SuperadminTone;
    quantity: string;
    description: string;
    reference: string;
    user: string;
    product: string;
    variant: string;
    sku: string;
    inventory: string;
    stock: { label: string; value: string }[];
    locations: { label: string; value: string }[];
    metadata: Record<string, unknown> | unknown[];
};

type InventoryMovementsProps = {
    meta: {
        sectionLabel: string;
        title: string;
        description: string;
        indexHref: string;
    };
    breadcrumbs: SuperadminBreadcrumb[];
    metrics: SuperadminMetric[];
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
    pagination: Pagination;
    filterAction: string;
    filterControls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
    selectedMovement: SelectedMovement | null;
    closeDetailHref: string;
    exportHref: string;
};

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

function hasMetadata(metadata: SelectedMovement['metadata']) {
    return Array.isArray(metadata) ? metadata.length > 0 : Object.keys(metadata ?? {}).length > 0;
}

export default function InventoryMovements({
    meta,
    breadcrumbs,
    metrics,
    columns,
    rows,
    pagination,
    filterAction,
    filterControls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    selectedMovement,
    closeDetailHref,
    exportHref,
}: InventoryMovementsProps) {
    const metadataText = useMemo(
        () => selectedMovement && hasMetadata(selectedMovement.metadata)
            ? JSON.stringify(selectedMovement.metadata, null, 2)
            : 'Sin metadata registrada.',
        [selectedMovement],
    );

    return (
        <SuperadminLayout title={`Mati Boutique | ${meta.title}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={meta.title}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                />

                <section className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-stone-300">
                        {pagination.meta.total} movimiento{pagination.meta.total === 1 ? '' : 's'} registrado{pagination.meta.total === 1 ? '' : 's'}
                    </p>
                    <a
                        href={exportHref}
                        className="sa-button-secondary inline-flex min-h-11 items-center justify-center gap-2 px-4 text-[0.72rem] uppercase tracking-[0.18em]"
                    >
                        <Download size={15} />
                        Exportar CSV
                    </a>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <SuperadminListFilters
                    action={filterAction}
                    controls={filterControls}
                    sortOptions={sortOptions}
                    sortBy={sortBy}
                    sortDirection={sortDirection}
                    appliedFilters={appliedFilters}
                />

                {rows.length > 0 ? (
                    <section className="space-y-4">
                        <SuperadminDataTable columns={columns} rows={rows} />

                        <div className="flex flex-col gap-3 rounded-[1.2rem] border border-white/8 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-stone-400">
                                {pagination.meta.from ?? 0}-{pagination.meta.to ?? 0} de {pagination.meta.total}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {pagination.links.map((link, index) => {
                                    const label = paginationLabel(link.label);
                                    const isEdge = index === 0 || index === pagination.links.length - 1;

                                    if (!link.url) {
                                        return (
                                            <span
                                                key={`${label}-${index}`}
                                                className="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-white/8 px-3 text-sm text-stone-500"
                                            >
                                                {index === 0 ? <ChevronLeft size={15} /> : isEdge ? <ChevronRight size={15} /> : label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={`${label}-${index}`}
                                            href={link.url}
                                            preserveScroll
                                            className={[
                                                'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm transition duration-200',
                                                link.active
                                                    ? 'border-[rgba(216,189,143,0.36)] bg-[rgba(216,189,143,0.14)] text-white'
                                                    : 'border-white/8 text-stone-300 hover:border-[rgba(126,154,178,0.28)] hover:bg-[#111922] hover:text-white',
                                            ].join(' ')}
                                        >
                                            {index === 0 ? <ChevronLeft size={15} /> : isEdge ? <ChevronRight size={15} /> : label}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                ) : (
                    <SuperadminEmptyState
                        state={{
                            title: 'Sin movimientos para los filtros actuales',
                            description: 'Los movimientos apareceran cuando inventario, ventas o reservas modifiquen stock.',
                            actionLabel: 'Limpiar filtros',
                            actionHref: meta.indexHref,
                        }}
                    />
                )}

                {selectedMovement && (
                    <div className="fixed inset-0 z-50 flex items-end bg-black/60 p-0 backdrop-blur-sm sm:items-center sm:p-6">
                        <div className="sa-panel max-h-[92vh] w-full overflow-y-auto rounded-t-[1.6rem] p-5 shadow-2xl sm:mx-auto sm:max-w-4xl sm:rounded-[1.6rem] sm:p-6">
                            <div className="flex items-start justify-between gap-4">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <SuperadminBadge tone={selectedMovement.typeTone}>{selectedMovement.type}</SuperadminBadge>
                                        <SuperadminBadge tone="neutral">{selectedMovement.code}</SuperadminBadge>
                                        <SuperadminBadge tone="accent">{selectedMovement.quantity}</SuperadminBadge>
                                    </div>
                                    <p className="mt-4 text-xl font-medium text-white">Detalle de movimiento</p>
                                    <p className="mt-2 text-sm leading-6 text-stone-300">
                                        {selectedMovement.description}
                                    </p>
                                </div>
                                <Link
                                    href={closeDetailHref}
                                    preserveScroll
                                    className="sa-button-ghost inline-flex h-10 w-10 shrink-0 items-center justify-center p-0"
                                    title="Cerrar"
                                >
                                    <X size={16} />
                                </Link>
                            </div>

                            <div className="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {[
                                    ['Fecha', selectedMovement.date],
                                    ['Prenda', selectedMovement.product],
                                    ['Variante', selectedMovement.variant],
                                    ['SKU', selectedMovement.sku],
                                    ['Inventario', selectedMovement.inventory],
                                    ['Referencia', selectedMovement.reference],
                                    ['Usuario', selectedMovement.user],
                                ].map(([label, value]) => (
                                    <div key={label} className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                        <p className="text-xs uppercase tracking-[0.16em] text-stone-400">{label}</p>
                                        <p className="mt-2 break-words text-sm text-white">{value}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-5 grid gap-3 md:grid-cols-2">
                                <div className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-xs uppercase tracking-[0.16em] text-stone-400">Stock</p>
                                    <dl className="mt-3 grid gap-3 sm:grid-cols-2">
                                        {selectedMovement.stock.map((item) => (
                                            <div key={item.label}>
                                                <dt className="text-xs text-stone-500">{item.label}</dt>
                                                <dd className="mt-1 text-lg font-medium text-white">{item.value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                </div>

                                <div className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-xs uppercase tracking-[0.16em] text-stone-400">Ubicacion</p>
                                    <dl className="mt-3 grid gap-3">
                                        {selectedMovement.locations.map((item) => (
                                            <div key={item.label}>
                                                <dt className="text-xs text-stone-500">{item.label}</dt>
                                                <dd className="mt-1 text-sm text-white">{item.value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                </div>
                            </div>

                            <div className="mt-5 rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-xs uppercase tracking-[0.16em] text-stone-400">Metadata</p>
                                <pre className="mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-stone-200">
                                    {metadataText}
                                </pre>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SuperadminLayout>
    );
}
