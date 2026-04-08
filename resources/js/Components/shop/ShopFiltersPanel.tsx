import {
    priceFilterOptions,
    type PriceFilterKey,
    type ShopAvailability,
    type ShopFilterOptions,
    type ShopFilterState,
} from '@/data/shop';
import { RotateCcw, X } from 'lucide-react';

type MultiFilterKey = 'categories' | 'sizes' | 'colors' | 'styles' | 'brands';

type ShopFiltersPanelProps = {
    filters: ShopFilterState;
    options: ShopFilterOptions;
    onToggle: (group: MultiFilterKey, value: string) => void;
    onPriceChange: (value: PriceFilterKey) => void;
    onAvailabilityChange: (value: 'all' | ShopAvailability) => void;
    onClear: () => void;
    onClose?: () => void;
    className?: string;
};

const availabilityOptions: Array<'all' | ShopAvailability> = [
    'all',
    'Disponible',
    'Ultimas piezas',
    'Agotado',
];

function FilterGroup({
    title,
    items,
    selectedValues,
    onToggle,
}: {
    title: string;
    items: string[];
    selectedValues: string[];
    onToggle: (value: string) => void;
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                {title}
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
                {items.map((item) => {
                    const active = selectedValues.includes(item);

                    return (
                        <button
                            key={item}
                            type="button"
                            onClick={() => onToggle(item)}
                            className={[
                                'rounded-full border px-3 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition duration-300',
                                active
                                    ? 'border-white/25 bg-white/[0.12] text-white'
                                    : 'border-white/10 bg-white/[0.03] text-stone-300 hover:border-white/18 hover:bg-white/[0.06]',
                            ].join(' ')}
                        >
                            {item}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export default function ShopFiltersPanel({
    filters,
    options,
    onToggle,
    onPriceChange,
    onAvailabilityChange,
    onClear,
    onClose,
    className = '',
}: ShopFiltersPanelProps) {
    return (
        <div
            className={`rounded-[2rem] border border-white/10 bg-[#0c1117]/95 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-xl ${className}`.trim()}
        >
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                        Mati Boutique filters
                    </p>
                    <h3
                        className="mt-3 text-3xl leading-none text-white"
                        style={{
                            fontFamily:
                                '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
                        }}
                    >
                        Refine the selection
                    </h3>
                </div>

                {onClose && (
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-white transition hover:border-white/18 hover:bg-white/[0.08]"
                        aria-label="Cerrar filtros"
                    >
                        <X size={16} />
                    </button>
                )}
            </div>

            <button
                type="button"
                onClick={onClear}
                className="mt-5 inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.22em] text-stone-300 transition hover:text-white"
            >
                <RotateCcw size={14} />
                Limpiar filtros
            </button>

            <div className="mt-8 space-y-8">
                <FilterGroup
                    title="Categoria"
                    items={options.categories}
                    selectedValues={filters.categories}
                    onToggle={(value) => onToggle('categories', value)}
                />
                <FilterGroup
                    title="Talla"
                    items={options.sizes}
                    selectedValues={filters.sizes}
                    onToggle={(value) => onToggle('sizes', value)}
                />
                <FilterGroup
                    title="Color"
                    items={options.colors}
                    selectedValues={filters.colors}
                    onToggle={(value) => onToggle('colors', value)}
                />
                <FilterGroup
                    title="Estilo"
                    items={options.styles}
                    selectedValues={filters.styles}
                    onToggle={(value) => onToggle('styles', value)}
                />
                <FilterGroup
                    title="Marca"
                    items={options.brands}
                    selectedValues={filters.brands}
                    onToggle={(value) => onToggle('brands', value)}
                />

                <div>
                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                        Precio
                    </p>
                    <div className="mt-4 space-y-2">
                        {priceFilterOptions.map((option) => {
                            const active = filters.price === option.value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => onPriceChange(option.value)}
                                    className={[
                                        'flex w-full items-center justify-between rounded-[1.2rem] border px-4 py-3 text-left text-sm transition duration-300',
                                        active
                                            ? 'border-white/24 bg-white/[0.09] text-white'
                                            : 'border-white/10 bg-white/[0.03] text-stone-300 hover:border-white/18 hover:bg-white/[0.06]',
                                    ].join(' ')}
                                >
                                    <span>{option.label}</span>
                                    {active && <span className="text-[0.68rem] uppercase tracking-[0.18em]">Activo</span>}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div>
                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-400">
                        Disponibilidad
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {availabilityOptions.map((option) => {
                            const label = option === 'all' ? 'Todas' : option;
                            const active = filters.availability === option;

                            return (
                                <button
                                    key={option}
                                    type="button"
                                    onClick={() => onAvailabilityChange(option)}
                                    className={[
                                        'rounded-full border px-3 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition duration-300',
                                        active
                                            ? 'border-white/24 bg-white/[0.12] text-white'
                                            : 'border-white/10 bg-white/[0.03] text-stone-300 hover:border-white/18 hover:bg-white/[0.06]',
                                    ].join(' ')}
                                >
                                    {label}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
}
