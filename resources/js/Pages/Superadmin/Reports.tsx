import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminEmptyState from '@/Components/Superadmin/SuperadminEmptyState';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminListFilters from '@/Components/Superadmin/SuperadminListFilters';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type {
    SuperadminAppliedFilter,
    SuperadminEmptyState as EmptyStateData,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
} from '@/types/superadmin';

type ReportsProps = {
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

export default function Reports({
    metrics,
    columns,
    rows,
    filterControls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    emptyState,
}: ReportsProps) {
    return (
        <SuperadminLayout title="MORE | Reportes">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Reportes"
                    title="Reportes"
                    description="Explora variantes, precio y stock con filtros operativos."
                    breadcrumbs={[
                        { label: 'Superadmin', href: '/superadmin' },
                        { label: 'Reportes' },
                    ]}
                    actions={[{ label: 'Volver al panel', href: '/superadmin', kind: 'ghost' }]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <SuperadminListFilters
                    action="/superadmin/reportes"
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
            </div>
        </SuperadminLayout>
    );
}
