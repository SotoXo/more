import type { SuperadminChartPoint } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import EmptyChartState from './EmptyChartState';

export default function HorizontalBarList({
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

    return (
        <div className="sa-ranked-list">
            {filtered.map((item, index) => {
                const width = `${Math.max(6, Math.round((item.value / max) * 100))}%`;
                const row = (
                    <div className="sa-ranked-list__row">
                        <div className="flex items-center gap-3">
                            <span className="sa-ranked-list__rank">{index + 1}</span>
                            <div className="min-w-0 flex-1">
                                <div className="mb-2 flex items-center justify-between gap-3">
                                    <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">{item.label}</p>
                                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">{item.value}</p>
                                </div>
                                <div className="sa-ranked-list__track">
                                    <span className="sa-ranked-list__line" style={{ width }} />
                                </div>
                            </div>
                        </div>
                    </div>
                );

                return item.href ? (
                    <Link key={item.label} href={item.href} className="block">
                        {row}
                    </Link>
                ) : (
                    <div key={item.label}>{row}</div>
                );
            })}
        </div>
    );
}
