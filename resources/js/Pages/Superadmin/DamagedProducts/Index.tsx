import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminEmptyState from '@/Components/Superadmin/SuperadminEmptyState';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminListFilters from '@/Components/Superadmin/SuperadminListFilters';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type {
    SuperadminAppliedFilter,
    SuperadminBreadcrumb,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
} from '@/types/superadmin';
import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';

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

type DamagedProductsIndexProps = {
    meta: {
        sectionLabel: string;
        title: string;
        description: string;
        indexHref: string;
        createHref: string;
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
};

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

export default function DamagedProductsIndex({
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
}: DamagedProductsIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    return (
        <SuperadminLayout title={`Mati Boutique | ${meta.title}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={meta.title}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                    actions={[
                        { label: 'Registrar prenda dañada', href: meta.createHref, kind: 'primary' },
                    ]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-stone-300">
                        {pagination.meta.total} reporte{pagination.meta.total === 1 ? '' : 's'} registrado{pagination.meta.total === 1 ? '' : 's'}
                    </p>
                    <Link
                        href={meta.createHref}
                        className="sa-button-secondary inline-flex min-h-11 items-center justify-center gap-2 px-4 text-[0.72rem] uppercase tracking-[0.18em]"
                    >
                        <Plus size={15} />
                        Registrar
                    </Link>
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
                            title: 'Sin reportes para los filtros actuales',
                            description: 'Registra un caso nuevo o limpia los filtros para revisar todo el historial.',
                            actionLabel: 'Registrar prenda dañada',
                            actionHref: meta.createHref,
                        }}
                    />
                )}
            </div>
        </SuperadminLayout>
    );
}
