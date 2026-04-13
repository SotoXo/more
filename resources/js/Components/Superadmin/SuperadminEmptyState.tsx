import { Link } from '@inertiajs/react';
import { FolderOpenDot } from 'lucide-react';
import type { SuperadminEmptyState as EmptyStateData } from '@/types/superadmin';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function SuperadminEmptyState({
    state,
}: {
    state: EmptyStateData;
}) {
    return (
        <div className="sa-panel-muted rounded-[2rem] px-6 py-14 text-center">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-white/10 bg-white/[0.03] text-stone-400">
                <FolderOpenDot size={22} />
            </div>
            <p className="mt-6 text-4xl leading-none text-white" style={serifStyle}>
                {state.title}
            </p>
            <p className="mx-auto mt-4 max-w-xl text-sm leading-7 text-stone-300">
                {state.description}
            </p>
            {state.actionLabel && state.actionHref && (
                <Link
                    href={state.actionHref}
                    className="sa-button-secondary mt-8 px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]"
                >
                    {state.actionLabel}
                </Link>
            )}
        </div>
    );
}
