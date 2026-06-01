import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
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
    SuperadminTone,
} from '@/types/superadmin';

type ReportRankingItem = {
    label: string;
    value: string;
    note?: string;
    tone?: SuperadminTone;
};

type ReportRanking = {
    title: string;
    items: ReportRankingItem[];
};

type ReportTable = {
    title: string;
    description?: string;
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
};

type OperationalReport = {
    key: string;
    title: string;
    description: string;
    metrics: SuperadminMetric[];
    rankings: ReportRanking[];
    tables: ReportTable[];
};

type ReportsProps = {
    metrics: SuperadminMetric[];
    reports: OperationalReport[];
    filterControls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
    emptyState: EmptyStateData;
};

function RankingCard({ ranking }: { ranking: ReportRanking }) {
    return (
        <div className="sa-panel rounded-[1.6rem] p-5">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-white">
                    {ranking.title}
                </h3>
                <SuperadminBadge tone="neutral">{ranking.items.length}</SuperadminBadge>
            </div>

            {ranking.items.length > 0 ? (
                <ol className="mt-5 space-y-3">
                    {ranking.items.map((item, index) => (
                        <li
                            key={`${ranking.title}-${item.label}-${index}`}
                            className="flex items-center justify-between gap-4 border-b border-white/6 pb-3 last:border-b-0 last:pb-0"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-white">{item.label}</p>
                                {item.note && <p className="mt-1 text-xs text-stone-400">{item.note}</p>}
                            </div>
                            <SuperadminBadge tone={item.tone ?? 'accent'}>{item.value}</SuperadminBadge>
                        </li>
                    ))}
                </ol>
            ) : (
                <p className="mt-5 text-sm leading-6 text-stone-300">
                    Sin datos para este ranking con los filtros actuales.
                </p>
            )}
        </div>
    );
}

function ReportSection({ report }: { report: OperationalReport }) {
    const hasTables = report.tables.some((table) => table.rows.length > 0);

    return (
        <section className="space-y-5">
            <div className="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p className="text-[0.72rem] uppercase tracking-[0.2em] text-stone-400">
                        Reporte
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-white">{report.title}</h2>
                </div>
                <p className="max-w-3xl text-sm leading-6 text-stone-300">{report.description}</p>
            </div>

            {report.metrics.length > 0 && (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {report.metrics.map((metric, index) => (
                        <SuperadminMetricCard
                            key={`${report.key}-${metric.label}`}
                            metric={metric}
                            index={index}
                        />
                    ))}
                </div>
            )}

            {report.rankings.length > 0 && (
                <div className="grid gap-4 xl:grid-cols-3">
                    {report.rankings.map((ranking) => (
                        <RankingCard key={`${report.key}-${ranking.title}`} ranking={ranking} />
                    ))}
                </div>
            )}

            {hasTables && (
                <div className="space-y-4">
                    {report.tables
                        .filter((table) => table.rows.length > 0)
                        .map((table) => (
                            <div key={`${report.key}-${table.title}`} className="space-y-3">
                                <div>
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-white">
                                        {table.title}
                                    </h3>
                                    {table.description && (
                                        <p className="mt-2 text-sm text-stone-300">{table.description}</p>
                                    )}
                                </div>
                                <SuperadminDataTable columns={table.columns} rows={table.rows} />
                            </div>
                        ))}
                </div>
            )}
        </section>
    );
}

export default function Reports({
    metrics,
    reports,
    filterControls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    emptyState,
}: ReportsProps) {
    const hasReportData = reports.some(
        (report) =>
            report.metrics.length > 0 ||
            report.rankings.some((ranking) => ranking.items.length > 0) ||
            report.tables.some((table) => table.rows.length > 0),
    );

    return (
        <SuperadminLayout title="Mati Boutique | Reportes operativos">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Reportes"
                    title="Reportes operativos"
                    description="Vista administrativa para revisar inventario, ventas, reservas y catalogo de Mati Boutique."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Reportes operativos' },
                    ]}
                    actions={[{ label: 'Volver al panel', href: '/superadmin', kind: 'ghost' }]}
                />

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <SuperadminBadge tone="accent">Solo lectura</SuperadminBadge>
                        <p className="max-w-3xl text-sm leading-6 text-stone-300">
                            Los filtros de fecha, vendedor, metodo de pago y estados se aplican a ventas y reservas.
                            Inventario y catalogo muestran el estado actual de la base.
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

                {hasReportData ? (
                    reports.map((report) => <ReportSection key={report.key} report={report} />)
                ) : (
                    <SuperadminEmptyState state={emptyState} />
                )}
            </div>
        </SuperadminLayout>
    );
}
