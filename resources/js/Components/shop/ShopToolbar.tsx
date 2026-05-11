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
        <div className="more-panel-soft flex min-w-0 flex-col gap-5 rounded-[1.35rem] px-4 py-5 sm:rounded-[1.8rem] sm:px-5 lg:flex-row lg:items-center lg:justify-between">
            <div className="min-w-0">
                <p className="break-words text-[0.66rem] uppercase tracking-[0.2em] text-stone-300 sm:text-[0.68rem] sm:tracking-[0.24em]">
                    Catálogo / {title}
                </p>
                <div className="mt-2 flex min-w-0 items-center gap-3">
                    <span className="h-px w-8 shrink-0 bg-[linear-gradient(90deg,rgba(223,184,166,0.92),transparent)]" />
                    <p className="text-sm text-stone-200">
                        {resultCount} {resultCount === 1 ? 'producto encontrado' : 'productos encontrados'}
                    </p>
                </div>
            </div>

            <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    onClick={onOpenFilters}
                    className="more-cta-secondary inline-flex w-full px-4 py-3 text-[0.7rem] uppercase tracking-[0.22em] sm:w-auto lg:hidden"
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

                <label className="flex min-w-0 flex-col gap-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-300 sm:flex-row sm:items-center sm:gap-3 sm:tracking-[0.22em]">
                    Ordenar por
                    <select
                        value={sort}
                        onChange={(event) =>
                            onSortChange(event.target.value as ShopSortKey)
                        }
                        className="w-full min-w-0 rounded-full border border-white/12 bg-[#070a0f] px-4 py-3 text-[0.72rem] uppercase tracking-[0.16em] text-white focus:border-[rgba(181,122,109,0.42)] focus:outline-none sm:w-auto sm:tracking-[0.18em]"
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
