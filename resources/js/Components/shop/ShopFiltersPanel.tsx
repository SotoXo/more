import { formatAvailabilityLabel, priceFilterOptions } from '@/lib/shop';
import type {
    PriceFilterKey,
    ShopAvailability,
    ShopFilterOptions,
    ShopFilterState,
} from '@/types/shop';
import { RotateCcw, X } from 'lucide-react';

type MultiFilterKey =
    | 'categories'
    | 'sizes'
    | 'colors'
    | 'styles'
    | 'brands'
    | 'locations';

type ShopFiltersPanelProps = {
    filters: ShopFilterState;
    options: ShopFilterOptions;
    onToggle: (group: MultiFilterKey, value: string) => void;
    onPriceChange: (value: PriceFilterKey) => void;
    onAvailabilityChange: (value: 'all' | ShopAvailability) => void;
    onHideSoldOutChange: (value: boolean) => void;
    onClear: () => void;
    onClose?: () => void;
    className?: string;
};

const availabilityOptions: Array<'all' | ShopAvailability> = [
    'all',
    'Disponible',
    'Últimas piezas',
    'Agotado',
];

const availabilityLabels: Record<'all' | ShopAvailability, string> = {
    all: 'Todas',
    Disponible: formatAvailabilityLabel('Disponible'),
    'Últimas piezas': formatAvailabilityLabel('Últimas piezas'),
    Agotado: formatAvailabilityLabel('Agotado'),
};

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
            <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-300">
                {title}
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
                {items.map((item) => {
                    const active = selectedValues.includes(item);

                    return (
                        <button
                            key={item}
                            type="button"
                            aria-pressed={active}
                            onClick={() => onToggle(item)}
                            className={[
                                'focus-visible:outline focus-visible:outline-2 focus-visible:outline-white rounded-full border px-3 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition duration-300',
                                active
                                    ? 'more-pill-active'
                                    : 'more-pill text-stone-200 hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.08)]',
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
    onHideSoldOutChange,
    onClear,
    onClose,
    className = '',
}: ShopFiltersPanelProps) {
    return (
        <div
            className={`more-panel-soft rounded-[1.8rem] p-5 ${className}`.trim()}
        >
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-300">
                        Filtros
                    </p>
                    <h3
                        className="mt-3 text-2xl leading-none text-white"
                        style={{
                            fontFamily:
                                '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
                        }}
                    >
                        Refinar catálogo
                    </h3>
                    <p className="mt-3 max-w-xs text-sm leading-6 text-stone-300">
                        Encuentra prendas por categoría, talla, color, marca o estilo.
                    </p>
                </div>

                {onClose && (
                    <button
                        type="button"
                        onClick={onClose}
                        className="more-icon-button inline-flex h-10 w-10 items-center justify-center"
                        aria-label="Cerrar filtros"
                    >
                        <X size={16} />
                    </button>
                )}
            </div>

            <button
                type="button"
                onClick={onClear}
                className="mt-5 inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.22em] text-stone-300 transition hover:text-[rgb(223,184,166)]"
            >
                <RotateCcw size={14} />
                Limpiar filtros
            </button>

            <div className="mt-8 space-y-8">
                <div className="more-divider" />
                <FilterGroup
                    title="Categoría"
                    items={options.categories}
                    selectedValues={filters.categories}
                    onToggle={(value) => onToggle('categories', value)}
                />
                <div className="more-divider" />
                <FilterGroup
                    title="Talla"
                    items={options.sizes}
                    selectedValues={filters.sizes}
                    onToggle={(value) => onToggle('sizes', value)}
                />
                <div className="more-divider" />
                <FilterGroup
                    title="Color"
                    items={options.colors}
                    selectedValues={filters.colors}
                    onToggle={(value) => onToggle('colors', value)}
                />
                <div className="more-divider" />
                <FilterGroup
                    title="Estilo"
                    items={options.styles}
                    selectedValues={filters.styles}
                    onToggle={(value) => onToggle('styles', value)}
                />
                <div className="more-divider" />
                <FilterGroup
                    title="Marca"
                    items={options.brands}
                    selectedValues={filters.brands}
                    onToggle={(value) => onToggle('brands', value)}
                />
                <div className="more-divider" />
                <FilterGroup
                    title="Ubicación"
                    items={options.locations}
                    selectedValues={filters.locations}
                    onToggle={(value) => onToggle('locations', value)}
                />
                <div className="more-divider" />

                <div>
                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-300">
                        Precio
                    </p>
                    <div className="mt-4 space-y-2">
                        {priceFilterOptions.map((option) => {
                            const active = filters.price === option.value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => onPriceChange(option.value)}
                                    className={[
                                        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-white flex w-full items-center justify-between rounded-[1.2rem] border px-4 py-3 text-left text-sm transition duration-300',
                                        active
                                            ? 'border-[rgba(181,122,109,0.28)] bg-[rgba(181,122,109,0.12)] text-white'
                                            : 'border-white/10 bg-black/20 text-stone-200 hover:border-[rgba(181,122,109,0.22)] hover:bg-[rgba(181,122,109,0.07)]',
                                    ].join(' ')}
                                >
                                    <span>{option.label}</span>
                                    {active && (
                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-[rgb(223,184,166)]">
                                            Activo
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
                <div className="more-divider" />

                <div>
                    <label className="flex cursor-pointer items-center gap-3 rounded-[1.2rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-stone-200">
                        <input type="checkbox" checked={filters.hideSoldOut} onChange={(event) => onHideSoldOutChange(event.target.checked)}
                            className="h-4 w-4 rounded border-white/30 bg-transparent text-[rgb(223,184,166)] focus:ring-[rgb(223,184,166)]" />
                        <span className="text-[0.7rem] uppercase tracking-[0.18em]">Ocultar agotados</span>
                    </label>
                    <p className="mt-2 text-xs text-stone-400">Al desactivarlo, las prendas agotadas aparecen al final.</p>
                </div>
                <div className="more-divider" />
                <div>
                    <p className="text-[0.7rem] uppercase tracking-[0.24em] text-stone-300">
                        Disponibilidad
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {availabilityOptions.map((option) => {
                            const label = availabilityLabels[option];
                            const active = filters.availability === option;

                            return (
                                <button
                                    key={option}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => onAvailabilityChange(option)}
                                    className={[
                                        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-white rounded-full border px-3 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition duration-300',
                                        active
                                            ? 'more-pill-active'
                                            : 'more-pill text-stone-200 hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.08)]',
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
