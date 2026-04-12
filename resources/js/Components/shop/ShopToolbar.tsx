import { sortOptions } from '@/lib/shop';
import type { ShopSortKey } from '@/types/shop';
import { SlidersHorizontal } from 'lucide-react';

type ShopToolbarProps = {
    title: string;
    resultCount: number;
    sort: ShopSortKey;
    onSortChange: (value: ShopSortKey) => void;
    onOpenFilters: () => void;
};

export default function ShopToolbar({
    title,
    resultCount,
    sort,
    onSortChange,
    onOpenFilters,
}: ShopToolbarProps) {
    return (
        <div className="more-panel-soft flex flex-col gap-4 rounded-[1.8rem] px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-500">
                    {title}
                </p>
                <div className="mt-2 flex items-center gap-3">
                    <span className="h-px w-8 bg-[linear-gradient(90deg,rgba(223,184,166,0.92),transparent)]" />
                    <p className="text-sm text-stone-200">
                    {resultCount} piezas disponibles
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    onClick={onOpenFilters}
                    className="more-cta-secondary inline-flex px-4 py-3 text-[0.7rem] uppercase tracking-[0.22em] lg:hidden"
                >
                    <SlidersHorizontal size={15} />
                    Filtros
                </button>

                <label className="flex items-center gap-3 text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                    Ordenar
                    <select
                        value={sort}
                        onChange={(event) =>
                            onSortChange(event.target.value as ShopSortKey)
                        }
                        className="rounded-full border border-white/10 bg-[#0c1117] px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-white focus:border-[rgba(181,122,109,0.34)] focus:outline-none"
                    >
                        {sortOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </label>
            </div>
        </div>
    );
}
