import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type { SuperadminDashboardListItem } from '@/types/superadmin';
import { Link } from '@inertiajs/react';

export default function SummaryList({
    items,
    emptyMessage,
}: {
    items: SuperadminDashboardListItem[];
    emptyMessage: string;
}) {
    if (items.length === 0) {
        return (
            <div className="sa-list-empty">
                {emptyMessage}
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {items.map((item, index) => {
                const content = (
                    <div className="sa-summary-row">
                        <div className="min-w-0">
                            <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">{item.title}</p>
                            {item.meta && (
                                <p className="mt-1 truncate text-xs text-[color:var(--admin-muted)]">{item.meta}</p>
                            )}
                        </div>
                        <div className="flex shrink-0 flex-col items-end gap-2 text-right">
                            <p className="text-sm font-semibold text-[color:var(--admin-text)]">{item.value}</p>
                            {item.label && <SuperadminBadge tone={item.tone}>{item.label}</SuperadminBadge>}
                        </div>
                    </div>
                );

                return item.href ? (
                    <Link key={`${item.title}-${index}`} href={item.href} className="block">
                        {content}
                    </Link>
                ) : (
                    <div key={`${item.title}-${index}`}>{content}</div>
                );
            })}
        </div>
    );
}
