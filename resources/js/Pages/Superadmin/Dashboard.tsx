import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type {
    SuperadminActivityItem,
    SuperadminInsightCard,
    SuperadminMetric,
} from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowUpRight } from 'lucide-react';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type DashboardProps = {
    metrics: SuperadminMetric[];
    moduleCards: SuperadminInsightCard[];
    alerts: SuperadminInsightCard[];
    activity: SuperadminActivityItem[];
};

export default function Dashboard({
    metrics,
    moduleCards,
    alerts,
    activity,
}: DashboardProps) {
    return (
        <SuperadminLayout title="MORE | Superadmin">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Panel"
                    title="Superadmin"
                    description="Resumen general del sistema."
                    breadcrumbs={[{ label: 'Superadmin' }]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard
                            key={metric.label}
                            metric={metric}
                            index={index}
                        />
                    ))}
                </section>

                <section className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-4">
                    {moduleCards.map((card, index) => (
                        <motion.div
                            key={card.title}
                            initial={{ opacity: 0, y: 18 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.32,
                                delay: index * 0.04,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                            className="sa-panel rounded-[1.6rem] p-5"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">
                                        {card.eyebrow}
                                    </p>
                                    <p className="mt-3 text-2xl text-white" style={serifStyle}>
                                        {card.title}
                                    </p>
                                </div>
                                {card.href && (
                                    <Link href={card.href} className="sa-button-ghost h-10 w-10 p-0">
                                        <ArrowUpRight size={14} />
                                    </Link>
                                )}
                            </div>
                            <p className="mt-4 text-sm leading-6 text-stone-300">
                                {card.description}
                            </p>
                        </motion.div>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                            Alertas
                        </p>
                        <div className="mt-5 space-y-3">
                            {alerts.map((alert) => (
                                <div
                                    key={alert.title}
                                    className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="text-sm font-medium text-white">{alert.title}</p>
                                        <SuperadminBadge tone={alert.tone}>
                                            {alert.eyebrow}
                                        </SuperadminBadge>
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-stone-400">
                                        {alert.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                            Ultimos movimientos
                        </p>
                        <div className="mt-5 space-y-3">
                            {activity.map((item) => (
                                <div
                                    key={`${item.title}-${item.date}`}
                                    className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="text-sm font-medium text-white">{item.title}</p>
                                        <SuperadminBadge tone={item.tone}>
                                            {item.date}
                                        </SuperadminBadge>
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-stone-400">
                                        {item.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </SuperadminLayout>
    );
}
