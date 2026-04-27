import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
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
                    title="Reportes de consulta"
                    description="Consulta variantes, precio y existencias con filtros operativos."
                    breadcrumbs={[
                        { label: 'Superadmin', href: '/superadmin' },
                        { label: 'Reportes' },
                    ]}
                    actions={[{ label: 'Volver al panel', href: '/superadmin', kind: 'ghost' }]}
                />

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <SuperadminBadge tone="accent">Solo lectura</SuperadminBadge>
                        <p className="max-w-3xl text-sm leading-6 text-stone-300">
                            Esta pantalla permite filtrar e inspeccionar resultados; no crea ni edita registros.
                        </p>
                    </div>
                </section>

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
