import type { ReactNode } from 'react';
import { toneClasses } from '@/lib/superadmin';
import type { SuperadminTone } from '@/types/superadmin';

export default function SuperadminBadge({
    children,
    tone = 'neutral',
    className = '',
}: {
    children: ReactNode;
    tone?: SuperadminTone;
    className?: string;
}) {
    return (
        <span
            className={`${toneClasses(tone)} px-3 py-1 text-[0.68rem] uppercase tracking-[0.18em] ${className}`.trim()}
        >
            {children}
        </span>
    );
}
