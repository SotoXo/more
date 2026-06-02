import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminEmptyState from '@/Components/Superadmin/SuperadminEmptyState';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import ReportFilters from '@/Components/Superadmin/Reports/ReportFilters';
import ReportSectionContent from '@/Components/Superadmin/Reports/ReportSectionContent';
import ReportSummary from '@/Components/Superadmin/Reports/ReportSummary';
import {
    reportSections,
    type OperationalReport,
    type ReportSectionKey,
    type ReportsProps,
} from '@/Components/Superadmin/Reports/types';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

function reportHasData(report: OperationalReport) {
    return (
        report.metrics.length > 0 ||
        report.charts.some((chart) => chart.data.length > 0) ||
        report.rankings.some((ranking) => ranking.items.length > 0) ||
        report.tables.some((table) => table.rows.length > 0)
    );
}

function queryStringFromUrl(pageUrl: string) {
    return pageUrl.includes('?') ? pageUrl.split('?')[1].split('#')[0] : '';
}

function sectionQueryValue(section: ReportSectionKey) {
    return reportSections.find((item) => item.key === section)?.queryValue ?? 'resumen';
}

function sectionFromUrl(pageUrl: string): ReportSectionKey {
    const params = new URLSearchParams(queryStringFromUrl(pageUrl));
    const requestedSection = params.get('section') ?? pageUrl.split('#')[1] ?? '';
    const matchingSection = reportSections.find(
        (section) => section.queryValue === requestedSection || section.key === requestedSection,
    );

    return matchingSection?.key ?? 'summary';
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
    const page = usePage();
    const activeSection = useMemo(() => sectionFromUrl(page.url), [page.url]);
    const queryString = queryStringFromUrl(page.url);
    const reportsByKey = useMemo(
        () =>
            reports.reduce<Record<string, OperationalReport>>((accumulator, report) => {
                accumulator[report.key] = report;
                return accumulator;
            }, {}),
        [reports],
    );
    const activeReport = activeSection === 'summary' ? null : reportsByKey[activeSection];
    const hasReportData = reports.some(reportHasData);
    const activeSectionQuery = sectionQueryValue(activeSection);

    return (
        <SuperadminLayout title="Mati Boutique | Reportes operativos">
            <div className="space-y-6">
                <SuperadminPageHeader
                    eyebrow="Reportes"
                    title="Reportes operativos"
                    description="Vista administrativa para revisar inventario, ventas, reservas, pagos y clientes de Mati Boutique."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Reportes operativos' },
                    ]}
                    actions={[{ label: 'Volver al panel', href: '/superadmin', kind: 'ghost' }]}
                />

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            <SuperadminBadge tone="accent">Solo lectura</SuperadminBadge>
                        </div>
                        <p className="max-w-3xl text-sm leading-6 text-[color:var(--admin-muted)] lg:text-right">
                            Usa los filtros una sola vez y navega desde el sidebar principal de reportes.
                            Cada seccion mantiene sus propias acciones de CSV y vista imprimible.
                        </p>
                    </div>
                </section>

                <ReportFilters
                    action="/superadmin/reportes"
                    controls={filterControls}
                    sortOptions={sortOptions}
                    sortBy={sortBy}
                    sortDirection={sortDirection}
                    appliedFilters={appliedFilters}
                    preserveQuery={{ section: activeSectionQuery }}
                />

                {hasReportData ? (
                    activeSection === 'summary' ? (
                        <ReportSummary metrics={metrics} reportsByKey={reportsByKey} />
                    ) : activeReport ? (
                        <ReportSectionContent report={activeReport} queryString={queryString} />
                    ) : (
                        <SuperadminEmptyState
                            state={{
                                title: 'Reporte no disponible',
                                description: 'La seccion seleccionada no tiene datos preparados para esta vista.',
                            }}
                        />
                    )
                ) : (
                    <SuperadminEmptyState state={emptyState} />
                )}
            </div>
        </SuperadminLayout>
    );
}
