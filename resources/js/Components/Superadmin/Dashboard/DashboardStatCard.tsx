import type { SuperadminDashboardMetric, SuperadminTone } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Boxes,
    CalendarClock,
    ClipboardList,
    Heart,
    Receipt,
    ShieldCheck,
    Shirt,
    Users2,
    Warehouse,
} from 'lucide-react';

const iconMap = {
    alert: AlertTriangle,
    boxes: Boxes,
    calendar: CalendarClock,
    clipboard: ClipboardList,
    heart: Heart,
    receipt: Receipt,
    shield: ShieldCheck,
    shirt: Shirt,
    users: Users2,
    warehouse: Warehouse,
};

function toneClass(tone?: SuperadminTone) {
    return `sa-tone-${tone ?? 'neutral'}`;
}

export default function DashboardStatCard({
    metric,
}: {
    metric: SuperadminDashboardMetric;
}) {
    const Icon = iconMap[metric.icon as keyof typeof iconMap] ?? Warehouse;
    const content = (
        <div
            className={[
                'sa-dashboard-stat',
                metric.priority === 'high' ? 'sa-dashboard-stat--high' : '',
                toneClass(metric.tone),
            ].join(' ')}
        >
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="sa-kicker">{metric.label}</p>
                    <p className="mt-2.5 text-3xl font-semibold leading-none">
                        {metric.value}
                    </p>
                </div>
                <span className="sa-dashboard-stat__icon">
                    <Icon size={18} />
                </span>
            </div>
            <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                {metric.note}
            </p>
        </div>
    );

    if (!metric.href) {
        return content;
    }

    return (
        <Link href={metric.href} className="group block">
            {content}
        </Link>
    );
}
