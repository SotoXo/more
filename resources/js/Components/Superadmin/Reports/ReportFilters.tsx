import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type {
    SuperadminAppliedFilter,
    SuperadminFilterControl,
    SuperadminSortOption,
} from '@/types/superadmin';
import { router } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';

type FilterState = Record<string, string>;

const primaryFilterKeys = ['period', 'date_from', 'date_to'];

function buildInitialState(controls: SuperadminFilterControl[]) {
    return controls.reduce<FilterState>((accumulator, control) => {
        accumulator[control.key] = control.value ?? '';
        return accumulator;
    }, {});
}

function FilterField({
    control,
    value,
    onChange,
}: {
    control: SuperadminFilterControl;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="space-y-2">
            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                {control.label}
            </span>

            {control.type === 'select' ? (
                <select
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="sa-input w-full"
                >
                    <option value="">Todos</option>
                    {control.options?.map((option) => (
                        <option key={`${control.key}-${option.value}`} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ) : (
                <input
                    type={control.type ?? 'text'}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    placeholder={control.placeholder}
                    className="sa-input w-full"
                />
            )}
        </label>
    );
}

export default function ReportFilters({
    action,
    controls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
    preserveQuery = {},
}: {
    action: string;
    controls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
    preserveQuery?: Record<string, string>;
}) {
    const [filters, setFilters] = useState<FilterState>(() => buildInitialState(controls));
    const [currentSortBy, setCurrentSortBy] = useState(sortBy ?? '');
    const [currentSortDirection, setCurrentSortDirection] = useState(sortDirection ?? 'desc');
    const [advancedOpen, setAdvancedOpen] = useState(false);

    useEffect(() => {
        setFilters(buildInitialState(controls));
        setCurrentSortBy(sortBy ?? '');
        setCurrentSortDirection(sortDirection ?? 'desc');
    }, [controls, sortBy, sortDirection]);

    const primaryControls = controls.filter((control) => primaryFilterKeys.includes(control.key));
    const advancedControls = controls.filter((control) => !primaryFilterKeys.includes(control.key));
    const hasAdvancedControls = advancedControls.length > 0 || sortOptions.length > 0;
    const activeCount = useMemo(
        () =>
            Object.values(filters).filter((value) => value !== '').length
            + (currentSortBy ? 1 : 0),
        [filters, currentSortBy],
    );
    const advancedCount = useMemo(
        () =>
            advancedControls.filter((control) => (filters[control.key] ?? '') !== '').length
            + (currentSortBy ? 1 : 0),
        [advancedControls, filters, currentSortBy],
    );

    useEffect(() => {
        if (advancedCount > 0) {
            setAdvancedOpen(true);
        }
    }, [advancedCount]);

    const setFilter = (key: string, value: string) => {
        setFilters((current) => ({
            ...current,
            [key]: value,
        }));
    };

    const submit = (event?: FormEvent<HTMLFormElement>) => {
        event?.preventDefault();

        const query = Object.entries(filters).reduce<Record<string, string>>((accumulator, [key, value]) => {
            if (value !== '') {
                accumulator[key] = value;
            }

            return accumulator;
        }, { ...preserveQuery });

        if (currentSortBy) {
            query.sort_by = currentSortBy;
            query.sort_dir = currentSortDirection;
        }

        router.get(action, query, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    };

    const reset = () => {
        setFilters(buildInitialState(controls.map((control) => ({ ...control, value: '' }))));
        setCurrentSortBy('');
        setCurrentSortDirection('desc');

        router.get(action, preserveQuery, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    };

    if (controls.length === 0 && sortOptions.length === 0) {
        return null;
    }

    return (
        <section className="sa-panel rounded-[1.6rem] p-5">
            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">
                            Filtros del reporte
                        </p>
                        <p className="mt-2 text-sm text-stone-300">
                            El periodo se aplica a todas las secciones; los filtros avanzados se usan cuando corresponden.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {activeCount > 0 && (
                            <SuperadminBadge tone="accent">
                                {activeCount} activo{activeCount > 1 ? 's' : ''}
                            </SuperadminBadge>
                        )}
                        <button
                            type="button"
                            onClick={reset}
                            className="sa-button-ghost px-4 py-2 text-[0.72rem] uppercase tracking-[0.18em]"
                        >
                            Limpiar
                        </button>
                        <button
                            type="submit"
                            className="sa-button-secondary px-4 py-2 text-[0.72rem] uppercase tracking-[0.18em]"
                        >
                            Aplicar
                        </button>
                    </div>
                </div>

                <div className="grid gap-3 md:grid-cols-3">
                    {primaryControls.map((control) => (
                        <FilterField
                            key={control.key}
                            control={control}
                            value={filters[control.key] ?? ''}
                            onChange={(value) => setFilter(control.key, value)}
                        />
                    ))}
                </div>

                {hasAdvancedControls && (
                    <div className="border-t border-white/8 pt-4">
                        <button
                            type="button"
                            onClick={() => setAdvancedOpen((current) => !current)}
                            className="flex w-full items-center justify-between gap-3 text-left"
                        >
                            <span>
                                <span className="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-stone-300">
                                    Filtros avanzados
                                </span>
                                <span className="mt-1 block text-sm text-stone-400">
                                    Vendedor, metodo, estados, cliente y responsable.
                                </span>
                            </span>
                            <span className="flex items-center gap-2">
                                {advancedCount > 0 && <SuperadminBadge tone="accent">{advancedCount}</SuperadminBadge>}
                                <ChevronDown
                                    size={18}
                                    className={`transition duration-200 ${advancedOpen ? 'rotate-180' : ''}`}
                                />
                            </span>
                        </button>

                        {advancedOpen && (
                            <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {advancedControls.map((control) => (
                                    <FilterField
                                        key={control.key}
                                        control={control}
                                        value={filters[control.key] ?? ''}
                                        onChange={(value) => setFilter(control.key, value)}
                                    />
                                ))}

                                {sortOptions.length > 0 && (
                                    <>
                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Ordenar por
                                            </span>
                                            <select
                                                value={currentSortBy}
                                                onChange={(event) => setCurrentSortBy(event.target.value)}
                                                className="sa-input w-full"
                                            >
                                                <option value="">Orden por defecto</option>
                                                {sortOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Direccion
                                            </span>
                                            <select
                                                value={currentSortDirection}
                                                onChange={(event) => setCurrentSortDirection(event.target.value)}
                                                className="sa-input w-full"
                                            >
                                                <option value="asc">Ascendente</option>
                                                <option value="desc">Descendente</option>
                                            </select>
                                        </label>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                )}

                {appliedFilters.length > 0 && (
                    <div className="flex flex-wrap gap-2 border-t border-white/8 pt-4">
                        {appliedFilters.map((filter) => (
                            <SuperadminBadge key={`${filter.label}-${filter.value}`} tone="neutral">
                                {filter.label}: {filter.value}
                            </SuperadminBadge>
                        ))}
                    </div>
                )}
            </form>
        </section>
    );
}
