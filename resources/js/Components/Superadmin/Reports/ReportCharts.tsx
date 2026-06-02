import DashboardChartCard from '@/Components/Superadmin/Dashboard/DashboardChartCard';
import DonutSummary from '@/Components/Superadmin/Dashboard/DonutSummary';
import HorizontalBarList from '@/Components/Superadmin/Dashboard/HorizontalBarList';
import MiniAreaChart from '@/Components/Superadmin/Dashboard/MiniAreaChart';
import type { ReportChart } from './types';

function ChartContent({ chart }: { chart: ReportChart }) {
    if (chart.type === 'donut') {
        return <DonutSummary data={chart.data} emptyMessage={chart.emptyMessage} />;
    }

    if (chart.type === 'area') {
        return <MiniAreaChart data={chart.data} emptyMessage={chart.emptyMessage} />;
    }

    return <HorizontalBarList data={chart.data} emptyMessage={chart.emptyMessage} />;
}

export default function ReportCharts({
    charts,
    limit,
}: {
    charts: ReportChart[];
    limit?: number;
}) {
    const visibleCharts = typeof limit === 'number' ? charts.slice(0, limit) : charts;

    if (visibleCharts.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-4 xl:grid-cols-3">
            {visibleCharts.map((chart) => (
                <DashboardChartCard
                    key={`${chart.title}-${chart.type}`}
                    title={chart.title}
                    description={chart.description}
                >
                    <ChartContent chart={chart} />
                </DashboardChartCard>
            ))}
        </div>
    );
}
