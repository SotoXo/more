import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Banknote,
    CalendarClock,
    CreditCard,
    LucideIcon,
    PackageSearch,
    Receipt,
    ShieldAlert,
    Shirt,
    Truck,
    Warehouse,
} from 'lucide-react';

type VendedorDashboardMetric = {
    key: string;
    label: string;
    value: string;
    note: string;
    tone?: 'neutral' | 'accent' | 'success' | 'warning' | 'info' | 'danger';
    icon: string;
    href?: string | null;
};

type DashboardProps = {
    dashboard: {
        metrics: VendedorDashboardMetric[];
    };
};

const iconMap: Record<string, LucideIcon> = {
    alert: AlertTriangle,
    banknote: Banknote,
    calendar: CalendarClock,
    'credit-card': CreditCard,
    package: PackageSearch,
    receipt: Receipt,
    'shield-alert': ShieldAlert,
    shirt: Shirt,
    truck: Truck,
    warehouse: Warehouse,
};

function toneClass(tone?: VendedorDashboardMetric['tone']) {
    return `sa-tone-${tone ?? 'neutral'}`;
}

function MetricCard({ metric }: { metric: VendedorDashboardMetric }) {
    const Icon = iconMap[metric.icon] ?? PackageSearch;
    const content = (
        <div className={['sa-dashboard-stat', toneClass(metric.tone)].join(' ')}>
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                        {metric.label}
                    </p>
                    <p className="mt-2.5 text-3xl font-semibold leading-none text-[color:var(--admin-text)]">
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

export default function Dashboard({ dashboard }: DashboardProps) {
    return (
        <VendedorLayout title="Mati Boutique | Resumen vendedor">
            <div className="space-y-7">
                <header className="max-w-4xl">
                    <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                        Panel vendedor
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        Resumen operativo
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Consulta diaria de prendas, existencias y alertas para el equipo vendedor de Mati Boutique.
                    </p>
                </header>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {dashboard.metrics.map((metric) => (
                        <MetricCard key={metric.key} metric={metric} />
                    ))}
                </section>

                <section className="sa-panel rounded-2xl p-5">
                    <div className="grid gap-4 lg:grid-cols-3">
                        {[
                            {
                                title: 'Consulta real',
                                description: 'Prendas, existencias y alertas ya leen datos del inventario.',
                            },
                            {
                                title: 'Operación comercial',
                                description: 'Ventas, reservas y revisión de comprobantes ya trabajan con datos reales.',
                            },
                            {
                                title: 'Inventario protegido',
                                description: 'No se recalcula disponible ni se modifica stock.',
                            },
                        ].map((item) => (
                            <div key={item.title} className="space-y-2">
                                <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                                    {item.title}
                                </p>
                                <p className="text-sm leading-6 text-[color:var(--admin-muted)]">
                                    {item.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </VendedorLayout>
    );
}
