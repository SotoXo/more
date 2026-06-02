import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import ReportCharts from './ReportCharts';
import ReportExportActions from './ReportExportActions';
import ReportMetricGrid from './ReportMetricGrid';
import type { OperationalReport, ReportRanking } from './types';

function RankingCard({ ranking }: { ranking: ReportRanking }) {
    return (
        <div className="sa-panel rounded-[1.6rem] p-5">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-[color:var(--admin-text)]">
                    {ranking.title}
                </h3>
                <SuperadminBadge tone="neutral">{ranking.items.length}</SuperadminBadge>
            </div>

            {ranking.items.length > 0 ? (
                <ol className="mt-5 space-y-3">
                    {ranking.items.map((item, index) => (
                        <li
                            key={`${ranking.title}-${item.label}-${index}`}
                            className="flex items-center justify-between gap-4 border-b border-[color:var(--admin-border)] pb-3 last:border-b-0 last:pb-0"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                                    {item.label}
                                </p>
                                {item.note && (
                                    <p className="mt-1 text-xs text-[color:var(--admin-muted)]">
                                        {item.note}
                                    </p>
                                )}
                            </div>
                            <SuperadminBadge tone={item.tone ?? 'accent'}>{item.value}</SuperadminBadge>
                        </li>
                    ))}
                </ol>
            ) : (
                <p className="mt-5 text-sm leading-6 text-[color:var(--admin-muted)]">
                    Sin datos para este ranking con los filtros actuales.
                </p>
            )}
        </div>
    );
}

export default function ReportSectionContent({
    report,
    queryString,
}: {
    report: OperationalReport;
    queryString: string;
}) {
    const visibleTables = report.tables.filter((table) => table.rows.length > 0);

    return (
        <section className="space-y-5">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div className="min-w-0">
                    <p className="text-[0.72rem] uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Reporte
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">
                        {report.title}
                    </h2>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-[color:var(--admin-muted)]">
                        {report.description}
                    </p>
                </div>

                <ReportExportActions report={report} queryString={queryString} />
            </div>

            <ReportMetricGrid metrics={report.metrics} />

            <ReportCharts charts={report.charts} />

            {report.rankings.length > 0 && (
                <div className="grid gap-4 xl:grid-cols-3">
                    {report.rankings.map((ranking) => (
                        <RankingCard key={`${report.key}-${ranking.title}`} ranking={ranking} />
                    ))}
                </div>
            )}

            {visibleTables.length > 0 && (
                <div className="space-y-4">
                    {visibleTables.map((table) => (
                        <div key={`${report.key}-${table.title}`} className="space-y-3">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-[color:var(--admin-text)]">
                                    {table.title}
                                </h3>
                                {table.description && (
                                    <p className="mt-2 text-sm text-[color:var(--admin-muted)]">
                                        {table.description}
                                    </p>
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
