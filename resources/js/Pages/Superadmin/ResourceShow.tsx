import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type {
    SuperadminBreadcrumb,
    SuperadminDetailSection,
    SuperadminMetric,
    SuperadminResourceMeta,
    SuperadminTableColumn,
    SuperadminTableRow,
    SuperadminTimelineItem,
} from '@/types/superadmin';
import { usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { FileText } from 'lucide-react';

type ResourceShowProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    recordLabel: string;
    summaryMetrics: SuperadminMetric[];
    detailSections: SuperadminDetailSection[];
    timeline: SuperadminTimelineItem[];
    relatedColumns?: SuperadminTableColumn[];
    relatedRows?: SuperadminTableRow[];
    editHref?: string | null;
};

export default function ResourceShow({
    meta,
    breadcrumbs,
    recordLabel,
    summaryMetrics,
    detailSections,
    timeline,
    relatedColumns = [],
    relatedRows = [],
    editHref = null,
}: ResourceShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    return (
        <SuperadminLayout title={`MORE | ${recordLabel}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={recordLabel}
                    description={`Detalle de ${meta.singular}.`}
                    breadcrumbs={breadcrumbs}
                    actions={[
                        { label: 'Volver al modulo', href: meta.indexHref, kind: 'ghost' },
                        ...(editHref ? [{ label: 'Editar', href: editHref, kind: 'secondary' as const }] : []),
                    ]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {summaryMetrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="space-y-4">
                    {detailSections.map((section) => (
                        <div key={section.title} className="sa-panel rounded-[1.6rem] p-6">
                            <div className="border-b border-white/8 pb-5">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                    {section.title}
                                </p>
                                {section.description && (
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                        {section.description}
                                    </p>
                                )}
                            </div>
                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                {section.fields.map((field) => (
                                    <div
                                        key={`${section.title}-${field.label}`}
                                        className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                    >
                                        <p className="text-[0.72rem] uppercase tracking-[0.16em] text-stone-400">
                                            {field.label}
                                        </p>
                                        <p className="mt-3 text-base leading-7 text-white">
                                            {field.value}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}

                    {relatedColumns.length > 0 && relatedRows.length > 0 && (
                        <div className="space-y-4">
                            <div className="sa-panel rounded-[1.6rem] p-5">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                    Registros relacionados
                                </p>
                            </div>
                            <SuperadminDataTable columns={relatedColumns} rows={relatedRows} />
                        </div>
                    )}

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-[#0d141d] text-[rgb(194,214,232)]">
                                <FileText size={18} />
                            </div>
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Historial
                            </p>
                        </div>

                        <div className="mt-5 grid gap-3 lg:grid-cols-3">
                            {timeline.map((item, index) => (
                                <motion.div
                                    key={`${item.title}-${item.date}`}
                                    initial={{ opacity: 0, y: 16 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.28,
                                        delay: index * 0.04,
                                        ease: [0.22, 1, 0.36, 1],
                                    }}
                                    className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="text-sm font-medium text-white">{item.title}</p>
                                        <SuperadminBadge tone={item.tone}>{item.date}</SuperadminBadge>
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-stone-400">
                                        {item.note}
                                    </p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </SuperadminLayout>
    );
}
