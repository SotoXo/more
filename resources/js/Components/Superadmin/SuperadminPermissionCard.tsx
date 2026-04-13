import type { SuperadminPermissionNotice } from '@/types/superadmin';
import { ShieldAlert } from 'lucide-react';

export default function SuperadminPermissionCard({
    notice,
}: {
    notice: SuperadminPermissionNotice;
}) {
    return (
        <div className="sa-panel rounded-[1.5rem] p-4">
            <div className="flex items-start gap-4">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-[rgba(126,154,178,0.24)] bg-[rgba(126,154,178,0.1)] text-[rgb(194,214,232)]">
                    <ShieldAlert size={18} />
                </div>
                <div className="min-w-0">
                    <p className="text-[0.7rem] uppercase tracking-[0.18em] text-stone-400">
                        Acceso
                    </p>
                    <p className="mt-2 text-base font-medium text-white">{notice.title}</p>
                    <p className="mt-2 text-sm leading-6 text-stone-300">
                        {notice.description}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {notice.items.map((item) => (
                            <span
                                key={item}
                                className="sa-badge px-3 py-1.5 text-[0.68rem] uppercase tracking-[0.16em] text-stone-200"
                            >
                                {item}
                            </span>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
