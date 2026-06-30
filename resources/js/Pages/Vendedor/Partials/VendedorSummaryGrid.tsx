import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type { SuperadminTone } from '@/types/superadmin';

export type VendedorSummaryItem = {
    label: string;
    value: string;
    tone?: SuperadminTone;
};

export default function VendedorSummaryGrid({ items }: { items: VendedorSummaryItem[] }) {
    return (
        <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {items.map((item) => (
                <div key={item.label} className="sa-dashboard-card rounded-2xl">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">
                                {item.label}
                            </p>
                            <p className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)]">
                                {item.value}
                            </p>
                        </div>
                        <SuperadminBadge tone={item.tone ?? 'neutral'}>
                            Lectura
                        </SuperadminBadge>
                    </div>
                </div>
            ))}
        </section>
    );
}
