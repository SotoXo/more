import type { SuperadminFormFieldOption } from '@/types/superadmin';
import { ChevronDown, Plus, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

export default function SuperadminComboboxField({
    label,
    value,
    options,
    placeholder,
    searchPlaceholder,
    error,
    hint,
    createLabel,
    onChange,
    onCreate,
}: {
    label: string;
    value: string;
    options: SuperadminFormFieldOption[];
    placeholder: string;
    searchPlaceholder?: string;
    error?: string;
    hint?: string;
    createLabel?: string;
    onChange: (value: string) => void;
    onCreate?: (seed: string) => void;
}) {
    const containerRef = useRef<HTMLDivElement | null>(null);
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    useEffect(() => {
        if (!open) {
            setQuery('');
            return;
        }

        const handlePointerDown = (event: MouseEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handlePointerDown);

        return () => document.removeEventListener('mousedown', handlePointerDown);
    }, [open]);

    const selected = useMemo(
        () => options.find((option) => option.value === value) ?? null,
        [options, value],
    );

    const filteredOptions = useMemo(() => {
        if (query.trim() === '') {
            return options;
        }

        const normalizedQuery = query.trim().toLowerCase();

        return options.filter((option) => option.label.toLowerCase().includes(normalizedQuery));
    }, [options, query]);

    return (
        <div ref={containerRef} className="relative space-y-2">
            <div className="flex items-center justify-between gap-3">
                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                    {label}
                </span>
                {onCreate && (
                    <button
                        type="button"
                        onClick={() => onCreate(query)}
                        className="inline-flex items-center gap-1 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400 transition hover:text-white"
                    >
                        <Plus size={12} />
                        {createLabel ?? 'Crear nueva'}
                    </button>
                )}
            </div>

            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className={`sa-input flex w-full items-center justify-between text-left ${open ? 'border-white/18' : ''}`}
            >
                <span className={selected ? 'text-white' : 'text-stone-400'}>
                    {selected?.label ?? placeholder}
                </span>
                <ChevronDown size={16} className={`transition ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="absolute z-30 mt-2 w-full rounded-[1.2rem] border border-white/10 bg-[#111924] p-3 shadow-[0_24px_60px_rgba(4,8,15,0.48)]">
                    <div className="relative">
                        <Search size={14} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-500" />
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={searchPlaceholder ?? 'Buscar'}
                            className="sa-input w-full pl-9"
                        />
                    </div>

                    <div className="mt-3 max-h-56 space-y-1 overflow-y-auto">
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((option) => (
                                <button
                                    key={`${label}-${option.value}`}
                                    type="button"
                                    onClick={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                    className={`flex w-full items-center justify-between rounded-[1rem] px-3 py-2 text-left text-sm transition ${
                                        option.value === value
                                            ? 'bg-[rgba(124,140,255,0.18)] text-white'
                                            : 'text-stone-300 hover:bg-white/6 hover:text-white'
                                    }`}
                                >
                                    <span>{option.label}</span>
                                    {option.value === value && (
                                        <span className="text-[0.64rem] uppercase tracking-[0.18em] text-stone-300">
                                            Actual
                                        </span>
                                    )}
                                </button>
                            ))
                        ) : (
                            <div className="rounded-[1rem] border border-dashed border-white/10 px-3 py-4 text-sm text-stone-400">
                                No hay coincidencias para esta busqueda.
                            </div>
                        )}
                    </div>
                </div>
            )}

            {hint && <p className="text-sm text-stone-400">{hint}</p>}
            {error && <p className="text-sm text-rose-300">{error}</p>}
        </div>
    );
}
