import { Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { SuperadminErrorState as ErrorStateData } from '@/types/superadmin';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function SuperadminErrorState({
    state,
}: {
    state: ErrorStateData;
}) {
    return (
        <div className="sa-panel rounded-[2rem] border border-[rgba(183,108,112,0.22)] p-6 sm:p-8">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex gap-4">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[rgba(183,108,112,0.22)] bg-[rgba(183,108,112,0.1)] text-[rgb(245,208,210)]">
                        <AlertTriangle size={18} />
                    </div>
                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.24em] text-[rgb(245,208,210)]">
                            Estado de error
                        </p>
                        <p className="mt-3 text-4xl leading-none text-white" style={serifStyle}>
                            {state.title}
                        </p>
                        <p className="mt-4 max-w-2xl text-sm leading-7 text-stone-300">
                            {state.description}
                        </p>
                    </div>
                </div>
                {state.actionLabel && state.actionHref && (
                    <Link
                        href={state.actionHref}
                        className="sa-button-ghost px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]"
                    >
                        {state.actionLabel}
                    </Link>
                )}
            </div>
        </div>
    );
}
