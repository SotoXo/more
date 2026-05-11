import type { SuperadminMetric } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { motion } from 'framer-motion';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

function toneClass(tone?: SuperadminMetric['tone']) {
    return {
        accent: 'border-t-[rgba(126,154,178,0.8)]',
        success: 'border-t-[rgba(113,168,148,0.8)]',
        warning: 'border-t-[rgba(201,160,106,0.8)]',
        danger: 'border-t-[rgba(183,108,112,0.8)]',
        info: 'border-t-[rgba(125,177,208,0.8)]',
        neutral: 'border-t-white/10',
    }[tone ?? 'neutral'];
}

export default function SuperadminMetricCard({
    metric,
    index = 0,
}: {
    metric: SuperadminMetric;
    index?: number;
}) {
    const content = (
        <motion.div
            initial={{ opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.42, delay: index * 0.04, ease: [0.22, 1, 0.36, 1] }}
            className={`sa-panel rounded-[1.6rem] border-t-2 p-5 ${toneClass(metric.tone)}`}
        >
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-[0.72rem] uppercase tracking-[0.2em] text-stone-300">
                        {metric.label}
                    </p>
                    <p className="mt-3 text-4xl leading-none text-white" style={serifStyle}>
                        {metric.value}
                    </p>
                </div>
                {metric.href && (
                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-[#121b25] text-stone-300">
                        <ArrowUpRight size={14} />
                    </span>
                )}
            </div>
            <p className="mt-5 text-sm leading-6 text-stone-300">{metric.note}</p>
        </motion.div>
    );

    if (!metric.href) {
        return content;
    }

    return (
        <Link href={metric.href} className="group block">
            <div className="relative transition duration-200 group-hover:translate-y-[-1px]">{content}</div>
        </Link>
    );
}
