import { sortOptions } from '@/lib/shop';
import type { ShopSortKey } from '@/types/shop';
import { Grid2X2, Rows3, SlidersHorizontal } from 'lucide-react';

export type ShopViewMode = 'amplia' | 'compacta';

type ShopToolbarProps = {
    title: string;
    resultCount: number;
    sort: ShopSortKey;
    viewMode: ShopViewMode;
    onSortChange: (value: ShopSortKey) => void;
    onViewModeChange: (value: ShopViewMode) => void;
    onOpenFilters: () => void;
};

export default function ShopToolbar({
    title,
    resultCount,
    sort,
    viewMode,
    onSortChange,
    onViewModeChange,
    onOpenFilters,
}: ShopToolbarProps) {
    return (
        <div className="more-panel-soft flex flex-col gap-5 rounded-[1.8rem] px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-300">
                    Catálogo / {title}
                </p>
                <div className="mt-2 flex items-center gap-3">
                    <span className="h-px w-8 bg-[linear-gradient(90deg,rgba(223,184,166,0.92),transparent)]" />
                    <p className="text-sm text-stone-200">
                        {resultCount} {resultCount === 1 ? 'producto encontrado' : 'productos encontrados'}
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

                <div className="flex items-center gap-2 rounded-full border border-white/10 bg-black/20 p-1">
                    {[
                        { value: 'amplia' as const, icon: Rows3, label: 'Vista amplia' },
                        { value: 'compacta' as const, icon: Grid2X2, label: 'Vista compacta' },
                    ].map((option) => {
                        const Icon = option.icon;
                        const active = viewMode === option.value;

                        return (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => onViewModeChange(option.value)}
                                aria-label={option.label}
                                title={option.label}
                                className={[
                                    'inline-flex h-9 w-9 items-center justify-center rounded-full transition',
                                    active
                                        ? 'bg-white text-[#090b10]'
                                        : 'text-stone-300 hover:bg-white/8 hover:text-white',
                                ].join(' ')}
                            >
                                <Icon size={15} />
                            </button>
                        );
                    })}
                </div>

                <label className="flex items-center gap-3 text-[0.68rem] uppercase tracking-[0.22em] text-stone-300">
                    Ordenar por
                    <select
                        value={sort}
                        onChange={(event) =>
                            onSortChange(event.target.value as ShopSortKey)
                        }
                        className="rounded-full border border-white/12 bg-[#070a0f] px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-white focus:border-[rgba(181,122,109,0.42)] focus:outline-none"
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
