import type { SuperadminChartPoint } from '@/types/superadmin';
import EmptyChartState from './EmptyChartState';

export default function MiniAreaChart({
    data,
    emptyMessage,
}: {
    data: SuperadminChartPoint[];
    emptyMessage?: string;
}) {
    const filtered = data.filter((item) => item.value > 0);
    const max = Math.max(...filtered.map((item) => item.value), 0);

    if (filtered.length === 0 || max === 0) {
        return <EmptyChartState message={emptyMessage} compact />;
    }

    const width = 320;
    const height = 118;
    const paddingX = 10;
    const paddingY = 16;
    const usableWidth = width - paddingX * 2;
    const usableHeight = height - paddingY * 2;
    const denominator = Math.max(filtered.length - 1, 1);
    const points = filtered.map((item, index) => {
        const x = paddingX + (index / denominator) * usableWidth;
        const y = paddingY + usableHeight - (item.value / max) * usableHeight;

        return { ...item, x, y };
    });
    const linePoints = points.map((point) => `${point.x},${point.y}`).join(' ');
    const areaPoints = [
        `${points[0].x},${height - paddingY}`,
        linePoints,
        `${points[points.length - 1].x},${height - paddingY}`,
    ].join(' ');
    const total = filtered.reduce((sum, item) => sum + item.value, 0);

    return (
        <div className="sa-mini-area">
            <div className="mb-3 flex items-end justify-between gap-4">
                <div>
                    <p className="text-2xl font-semibold text-[color:var(--admin-text)]">{total}</p>
                    <p className="text-xs text-[color:var(--admin-muted)]">movimientos en el periodo</p>
                </div>
                <p className="text-xs font-medium text-[color:var(--admin-muted)]">
                    Máximo {max}
                </p>
            </div>
            <svg viewBox={`0 0 ${width} ${height}`} role="img" aria-label="Tendencia del periodo">
                <defs>
                    <linearGradient id="mini-area-gradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stopColor="rgb(var(--admin-accent-rgb))" stopOpacity="0.28" />
                        <stop offset="100%" stopColor="rgb(var(--admin-accent-rgb))" stopOpacity="0" />
                    </linearGradient>
                </defs>
                <polygon points={areaPoints} fill="url(#mini-area-gradient)" />
                <polyline points={linePoints} fill="none" stroke="rgb(var(--admin-accent-rgb))" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                {points.map((point, index) => (
                    <circle
                        key={`${point.label}-${index}`}
                        cx={point.x}
                        cy={point.y}
                        r={index === points.length - 1 ? 4 : 2.6}
                        fill="var(--admin-surface)"
                        stroke="rgb(var(--admin-accent-rgb))"
                        strokeWidth="2"
                    />
                ))}
            </svg>
            <div className="mt-2 flex justify-between gap-3 text-[0.68rem] text-[color:var(--admin-muted)]">
                <span className="truncate">{filtered[0]?.label}</span>
                <span className="truncate">{filtered[filtered.length - 1]?.label}</span>
            </div>
        </div>
    );
}
