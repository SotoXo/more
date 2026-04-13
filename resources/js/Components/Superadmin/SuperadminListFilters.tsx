import type {
    SuperadminAppliedFilter,
    SuperadminFilterControl,
    SuperadminSortOption,
} from '@/types/superadmin';
import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';
import SuperadminBadge from './SuperadminBadge';

type FilterState = Record<string, string>;

function buildInitialState(controls: SuperadminFilterControl[]) {
    return controls.reduce<FilterState>((accumulator, control) => {
        accumulator[control.key] = control.value ?? '';
        return accumulator;
    }, {});
}

export default function SuperadminListFilters({
    action,
    controls,
    sortOptions,
    sortBy,
    sortDirection,
    appliedFilters,
}: {
    action: string;
    controls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
}) {
    const [filters, setFilters] = useState<FilterState>(() => buildInitialState(controls));
    const [currentSortBy, setCurrentSortBy] = useState(sortBy ?? '');
    const [currentSortDirection, setCurrentSortDirection] = useState(sortDirection ?? 'desc');

    useEffect(() => {
        setFilters(buildInitialState(controls));
        setCurrentSortBy(sortBy ?? '');
        setCurrentSortDirection(sortDirection ?? 'desc');
    }, [controls, sortBy, sortDirection]);

    const hasControls = controls.length > 0 || sortOptions.length > 0;
    const activeCount = useMemo(
        () =>
            Object.values(filters).filter((value) => value !== '').length
            + (currentSortBy ? 1 : 0),
        [filters, currentSortBy],
    );

    const submit = (event?: FormEvent<HTMLFormElement>) => {
        event?.preventDefault();

        const query = Object.entries(filters).reduce<Record<string, string>>((accumulator, [key, value]) => {
            if (value !== '') {
                accumulator[key] = value;
            }

            return accumulator;
        }, {});

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

        router.get(action, {}, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    };

    if (!hasControls) {
        return null;
    }

    return (
        <section className="sa-panel rounded-[1.6rem] p-5">
            <form onSubmit={submit} className="space-y-5">
                <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">
                            Filtros y orden
                        </p>
                        <p className="mt-2 text-sm text-stone-300">
                            Ajusta la vista con criterios comerciales y operativos.
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

                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    {controls.map((control) => (
                        <label key={control.key} className="space-y-2">
                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                {control.label}
                            </span>

                            {control.type === 'select' ? (
                                <select
                                    value={filters[control.key] ?? ''}
                                    onChange={(event) =>
                                        setFilters((current) => ({
                                            ...current,
                                            [control.key]: event.target.value,
                                        }))
                                    }
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
                                    value={filters[control.key] ?? ''}
                                    onChange={(event) =>
                                        setFilters((current) => ({
                                            ...current,
                                            [control.key]: event.target.value,
                                        }))
                                    }
                                    placeholder={control.placeholder}
                                    className="sa-input w-full"
                                />
                            )}
                        </label>
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
