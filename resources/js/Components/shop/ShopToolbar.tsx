import { sortOptions, type ShopSortKey } from '@/data/shop';
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
        <div className="flex flex-col gap-4 rounded-[1.8rem] border border-white/10 bg-white/[0.04] px-5 py-5 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                    {title}
                </p>
                <p className="mt-2 text-sm text-stone-200">
                    {resultCount} piezas disponibles
                </p>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    onClick={onOpenFilters}
                    className="inline-flex items-center justify-center gap-2 rounded-full border border-white/12 bg-white/[0.04] px-4 py-3 text-[0.7rem] uppercase tracking-[0.22em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.08] lg:hidden"
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
                        className="rounded-full border border-white/10 bg-[#0c1117] px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-white focus:border-white/20 focus:outline-none"
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
