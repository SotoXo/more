import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { SuperadminLogEntry, SuperadminMetric } from '@/types/superadmin';
import { motion } from 'framer-motion';

type LogsProps = {
    metrics: SuperadminMetric[];
    entries: SuperadminLogEntry[];
};

export default function Logs({
    metrics,
    entries,
}: LogsProps) {
    return (
        <SuperadminLayout title="Mati Boutique | Registro de actividad">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Actividad"
                    title="Registro de actividad"
                    description="Movimientos recientes de usuarios, prendas, ventas y reservas."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Registro de actividad' },
                    ]}
                    actions={[
                        { label: 'Volver al panel', href: '/superadmin', kind: 'ghost' },
                    ]}
                />

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <SuperadminBadge tone="accent">Solo lectura</SuperadminBadge>
                        <p className="max-w-3xl text-sm leading-6 text-stone-300">
                            Esta vista reúne actividad reciente para seguimiento administrativo; no reemplaza una auditoría transaccional completa.
                        </p>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                        Actividad reciente
                    </p>

                    <div className="mt-5 space-y-3">
                        {entries.map((entry, index) => (
                            <motion.div
                                key={entry.id}
                                initial={{ opacity: 0, y: 16 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{
                                    duration: 0.3,
                                    delay: index * 0.04,
                                    ease: [0.22, 1, 0.36, 1],
                                }}
                                className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 transition duration-200 hover:border-[rgba(126,154,178,0.22)]"
                            >
                                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium text-white">{entry.title}</p>
                                            <SuperadminBadge tone={entry.tone}>{entry.id}</SuperadminBadge>
                                        </div>
                                        <p className="mt-2 text-sm leading-6 text-stone-300">
                                            {entry.description}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0">
                                        <SuperadminBadge tone="neutral">{entry.date}</SuperadminBadge>
                                    </div>
                                </div>
                            </motion.div>
                        ))}
                    </div>
                </section>
            </div>
        </SuperadminLayout>
    );
}
