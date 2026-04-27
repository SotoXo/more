import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminEmptyState from '@/Components/Superadmin/SuperadminEmptyState';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminListFilters from '@/Components/Superadmin/SuperadminListFilters';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type {
    SuperadminAction,
    SuperadminAppliedFilter,
    SuperadminBreadcrumb,
    SuperadminEmptyState as EmptyStateData,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminResourceMeta,
    SuperadminSegmentedFilter,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
    SuperadminTone,
} from '@/types/superadmin';
import { Link, usePage } from '@inertiajs/react';

type ResourceIndexProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    metrics: SuperadminMetric[];
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
    headerActions?: SuperadminAction[];
    segmentedFilters?: SuperadminSegmentedFilter[];
    filterAction?: string;
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
    headerActions = [],
    segmentedFilters = [],
    filterAction,
    filterControls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    emptyState,
}: ResourceIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const moduleStatusTone: SuperadminTone =
        meta.moduleStatus === 'crud'
            ? 'success'
            : meta.moduleStatus === 'not_ready'
            ? 'warning'
            : 'accent';

    return (
        <SuperadminLayout title={`MORE | ${meta.title}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={meta.title}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                    actions={headerActions}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {(meta.moduleStatusLabel || meta.moduleStatusDescription) && (
                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                {meta.moduleStatusLabel && (
                                    <SuperadminBadge tone={moduleStatusTone}>
                                        {meta.moduleStatusLabel}
                                    </SuperadminBadge>
                                )}
                                {meta.moduleIsSecondary && (
                                    <SuperadminBadge tone="neutral">
                                        Secundario
                                    </SuperadminBadge>
                                )}
                            </div>
                            {meta.moduleStatusDescription && (
                                <p className="max-w-3xl text-sm leading-6 text-stone-300">
                                    {meta.moduleStatusDescription}
                                </p>
                            )}
                        </div>
                    </section>
                )}

                {segmentedFilters.length > 0 && (
                    <section className="sa-panel rounded-[1.6rem] p-4">
                        <div className="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
                            {segmentedFilters.map((filter) => (
                                <Link
                                    key={`${filter.label}-${filter.href}`}
                                    href={filter.href}
                                    className={[
                                        'flex items-center justify-between gap-3 rounded-[1.1rem] border px-4 py-3 text-sm transition lg:min-w-[12rem]',
                                        filter.active
                                            ? 'border-[rgba(126,154,178,0.34)] bg-[rgba(126,154,178,0.18)] text-white'
                                            : 'border-white/8 bg-[#0d141d] text-stone-300 hover:border-white/14 hover:text-white',
                                    ].join(' ')}
                                >
                                    <span className="font-medium">{filter.label}</span>
                                    {filter.count && (
                                        <span className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">
                                            {filter.count}
                                        </span>
                                    )}
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="space-y-4">
                    <SuperadminListFilters
                        action={filterAction ?? meta.indexHref}
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
