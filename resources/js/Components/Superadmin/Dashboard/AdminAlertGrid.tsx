import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type { SuperadminDashboardAlert } from '@/types/superadmin';
import { Link } from '@inertiajs/react';

export default function AdminAlertGrid({
    alerts,
}: {
    alerts: SuperadminDashboardAlert[];
}) {
    return (
        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {alerts.map((alert) => {
                const content = (
                    <div className="sa-alert-tile">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm font-medium text-[color:var(--admin-text)]">{alert.title}</p>
                                <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">{alert.value}</p>
                            </div>
                            <SuperadminBadge tone={alert.tone}>{alert.tone === 'success' ? 'Correcto' : 'Revisar'}</SuperadminBadge>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {alert.description}
                        </p>
                    </div>
                );

                return alert.href ? (
                    <Link key={alert.title} href={alert.href} className="block">
                        {content}
                    </Link>
                ) : (
                    <div key={alert.title}>{content}</div>
                );
            })}
        </div>
    );
}
