import ReportCharts from './ReportCharts';
import ReportMetricGrid from './ReportMetricGrid';
import type { OperationalReport, ReportChart } from './types';
import type { SuperadminMetric } from '@/types/superadmin';

type ReportsByKey = Record<string, OperationalReport | undefined>;

function findMetric(report: OperationalReport | undefined, labels: string[]) {
    if (!report) {
        return null;
    }

    return labels
        .map((label) => report.metrics.find((metric) => metric.label.toLowerCase() === label))
        .find(Boolean) ?? null;
}

function withLabel(metric: SuperadminMetric | null, label: string): SuperadminMetric | null {
    return metric ? { ...metric, label } : null;
}

export default function ReportSummary({
    metrics,
    reportsByKey,
}: {
    metrics: SuperadminMetric[];
    reportsByKey: ReportsByKey;
}) {
    const sales = reportsByKey.sales;
    const reservations = reportsByKey.reservations;
    const payments = reportsByKey.payments;
    const inventory = reportsByKey.inventory;
    const customers = reportsByKey.customers;
    const catalog = reportsByKey.catalog;

    const summaryMetrics = [
        withLabel(findMetric(sales, ['confirmadas']), 'Ventas confirmadas'),
        withLabel(findMetric(sales, ['en revision']), 'Ventas en revision'),
        withLabel(findMetric(reservations, ['por procesar']), 'Reservas pendientes'),
        withLabel(findMetric(payments, ['pendientes']), 'Pagos pendientes'),
        withLabel(findMetric(inventory, ['bajo stock']), 'Stock bajo'),
        withLabel(findMetric(customers, ['clientes frecuentes']), 'Clientes frecuentes'),
    ].filter(Boolean) as SuperadminMetric[];

    const operationalPulse = summaryMetrics.length > 0 ? summaryMetrics : metrics.slice(0, 6);
    const overviewCharts = [
        sales?.charts.find((chart) => chart.title.toLowerCase().includes('estados')),
        reservations?.charts.find((chart) => chart.title.toLowerCase().includes('estado')),
        inventory?.charts.find((chart) => chart.title.toLowerCase().includes('stock operativo')),
    ].filter(Boolean) as ReportChart[];
    const catalogCharts = catalog?.charts.filter((chart) => chart.data.length > 0).slice(0, 1) ?? [];

    return (
        <section className="space-y-5">
            <div className="sa-panel rounded-[1.6rem] p-5">
                <p className="text-[0.72rem] uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                    Resumen operativo
                </p>
                <div className="mt-2 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold text-[color:var(--admin-text)]">
                            Pulso de Mati Boutique
                        </h2>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-[color:var(--admin-muted)]">
                            Vista rapida de ventas, reservas, pagos, inventario y actividad comercial con
                            los filtros aplicados.
                        </p>
                    </div>
                </div>
            </div>

            <ReportMetricGrid metrics={operationalPulse} columns="xl:grid-cols-3" />

            <ReportCharts charts={overviewCharts} />

            {catalog && (catalog.metrics.length > 0 || catalogCharts.length > 0) && (
                <div className="space-y-4">
                    <div>
                        <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-[color:var(--admin-text)]">
                            Senales de catálogo
                        </h3>
                        <p className="mt-2 text-sm text-[color:var(--admin-muted)]">
                            Informacion complementaria del catálogo sin convertirla en una pestana principal.
                        </p>
                    </div>
                    <ReportMetricGrid metrics={catalog.metrics} columns="xl:grid-cols-4" />
                    <ReportCharts charts={catalogCharts} />
                </div>
            )}
        </section>
    );
}
