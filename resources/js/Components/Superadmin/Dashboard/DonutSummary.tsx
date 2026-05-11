import type { SuperadminChartPoint, SuperadminTone } from '@/types/superadmin';
import EmptyChartState from './EmptyChartState';

const toneColor: Record<SuperadminTone, string> = {
    accent: 'rgb(var(--admin-accent-rgb))',
    success: 'rgb(var(--admin-success-rgb))',
    warning: 'rgb(var(--admin-warning-rgb))',
    danger: 'rgb(var(--admin-danger-rgb))',
    info: 'rgb(var(--admin-info-rgb))',
    neutral: 'rgb(var(--admin-muted-rgb))',
};

export default function DonutSummary({
    data,
    emptyMessage,
}: {
    data: SuperadminChartPoint[];
    emptyMessage?: string;
}) {
    const filtered = data.filter((item) => item.value > 0);
    const total = filtered.reduce((sum, item) => sum + item.value, 0);
    let offset = 25;

    if (filtered.length === 0 || total === 0) {
        return <EmptyChartState message={emptyMessage} compact />;
    }

    return (
        <div className="sa-compact-donut">
            <div className="sa-compact-donut__chart">
                <svg viewBox="0 0 42 42" className="h-full w-full -rotate-90">
                    <circle
                        cx="21"
                        cy="21"
                        r="15.9"
                        fill="transparent"
                        stroke="var(--admin-surface-soft)"
                        strokeWidth="4"
                    />
                    {filtered.map((item) => {
                        const percent = (item.value / total) * 100;
                        const strokeDasharray = `${percent} ${100 - percent}`;
                        const currentOffset = offset;
                        offset -= percent;

                        return (
                            <circle
                                key={item.label}
                                cx="21"
                                cy="21"
                                r="15.9"
                                fill="transparent"
                                stroke={toneColor[item.tone ?? 'accent']}
                                strokeWidth="4"
                                strokeDasharray={strokeDasharray}
                                strokeDashoffset={currentOffset}
                                strokeLinecap="round"
                            />
                        );
                    })}
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <p className="text-2xl font-semibold text-[color:var(--admin-text)]">{total}</p>
                    <p className="mt-1 text-[0.62rem] uppercase tracking-[0.16em] text-[color:var(--admin-muted)]">
                        Total
                    </p>
                </div>
            </div>

            <div className="sa-compact-donut__legend min-w-0 space-y-2.5">
                {filtered.map((item) => (
                    <div key={item.label} className="flex items-center justify-between gap-3">
                        <span className="inline-flex min-w-0 items-center gap-2">
                            <span
                                className="h-2.5 w-2.5 rounded-full"
                                style={{ backgroundColor: toneColor[item.tone ?? 'accent'] }}
                            />
                            <span className="truncate text-sm text-[color:var(--admin-muted)]">{item.label}</span>
                        </span>
                        <span className="text-sm font-medium text-[color:var(--admin-text)]">{item.value}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
