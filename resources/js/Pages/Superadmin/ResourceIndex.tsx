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
    SuperadminEmptyState as EmptyStateData,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminResourceMeta,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
} from '@/types/superadmin';
import { usePage } from '@inertiajs/react';

type ResourceIndexProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    metrics: SuperadminMetric[];
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
    filterControls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
    emptyState: EmptyStateData;
};

export default function ResourceIndex({
    meta,
    breadcrumbs,
    metrics,
    columns,
    rows,
    filterControls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    emptyState,
}: ResourceIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    return (
        <SuperadminLayout title={`MORE | ${meta.title}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={meta.title}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                    actions={meta.createHref ? [{ label: 'Nuevo registro', href: meta.createHref, kind: 'primary' }] : []}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="space-y-4">
                    <SuperadminListFilters
                        action={meta.indexHref}
                        controls={filterControls}
                        sortOptions={sortOptions}
                        sortBy={sortBy}
                        sortDirection={sortDirection}
                        appliedFilters={appliedFilters}
                    />

                    {rows.length > 0 ? (
                        <SuperadminDataTable columns={columns} rows={rows} />
                    ) : (
                        <SuperadminEmptyState state={emptyState} />
                    )}
                </section>
            </div>
        </SuperadminLayout>
    );
}
