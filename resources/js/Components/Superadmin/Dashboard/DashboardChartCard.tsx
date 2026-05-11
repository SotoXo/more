import type { ReactNode } from 'react';

export default function DashboardChartCard({
    title,
    description,
    children,
    className = '',
}: {
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={`sa-dashboard-card ${className}`.trim()}>
            <div className="mb-4">
                <p className="sa-kicker">{title}</p>
                {description && (
                    <p className="mt-1.5 text-sm leading-6 text-[color:var(--admin-muted)]">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}
