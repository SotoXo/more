import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import type { SuperadminMetric } from '@/types/superadmin';

export default function ReportMetricGrid({
    metrics,
    columns = 'xl:grid-cols-4',
}: {
    metrics: SuperadminMetric[];
    columns?: string;
}) {
    if (metrics.length === 0) {
        return null;
    }

    return (
        <div className={`grid gap-4 md:grid-cols-2 ${columns}`}>
            {metrics.map((metric, index) => (
                <SuperadminMetricCard
                    key={`${metric.label}-${index}`}
                    metric={metric}
                    index={index}
                />
            ))}
        </div>
    );
}
