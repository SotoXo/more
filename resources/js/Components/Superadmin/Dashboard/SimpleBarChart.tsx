import type { SuperadminChartPoint } from '@/types/superadmin';
import EmptyChartState from './EmptyChartState';

export default function SimpleBarChart({
    data,
    emptyMessage,
}: {
    data: SuperadminChartPoint[];
    emptyMessage?: string;
}) {
    const filtered = data.filter((item) => item.value > 0);
    const max = Math.max(...filtered.map((item) => item.value), 0);

    if (filtered.length === 0 || max === 0) {
        return <EmptyChartState message={emptyMessage} />;
    }

    return (
        <div className="sa-bar-chart" role="img" aria-label="Gráfico de barras">
            {filtered.map((item, index) => {
                const height = Math.max(10, Math.round((item.value / max) * 100));
                return (
                    <div key={`${item.label}-${index}`} className="sa-bar-chart__item">
                        <div className="sa-bar-chart__track">
                            <span
                                className="sa-bar-chart__bar"
                                style={{ height: `${height}%` }}
                            />
                        </div>
                        <div className="min-w-0 text-center">
                            <p className="truncate text-xs font-medium text-[color:var(--admin-text)]">
                                {item.value}
                            </p>
                            <p className="mt-1 line-clamp-2 text-[0.68rem] leading-4 text-[color:var(--admin-muted)]">
                                {item.label}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
