import { Link } from '@inertiajs/react';
import { RotateCcw, Search } from 'lucide-react';

export type VendedorOption = {
    value: string;
    label: string;
};

export type VendedorFilterField = {
    name: string;
    label: string;
    type?: 'date' | 'search' | 'select';
    placeholder?: string;
    options?: VendedorOption[];
};

export type VendedorFilters = Record<string, string | null | undefined>;

export default function VendedorFilterPanel({
    action,
    fields,
    filters,
    resetHref,
    hidden = {},
}: {
    action: string;
    fields: VendedorFilterField[];
    filters: VendedorFilters;
    resetHref: string;
    hidden?: VendedorFilters;
}) {
    return (
        <form action={action} method="get" className="sa-panel rounded-2xl p-4">
            {Object.entries(hidden).map(([key, value]) => (
                value ? <input key={key} type="hidden" name={key} value={value} /> : null
            ))}

            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                {fields.map((field) => (
                    <label key={field.name} className="space-y-2">
                        <span className="text-xs font-semibold text-[color:var(--admin-muted)]">
                            {field.label}
                        </span>
                        {field.type === 'select' ? (
                            <select
                                name={field.name}
                                defaultValue={filters[field.name] ?? ''}
                                className="sa-input min-h-11 px-3 text-sm"
                            >
                                <option value="">Todos</option>
                                {(field.options ?? []).map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        ) : (
                            <input
                                type={field.type === 'date' ? 'date' : 'text'}
                                name={field.name}
                                defaultValue={filters[field.name] ?? ''}
                                placeholder={field.placeholder}
                                className="sa-input min-h-11 px-3 text-sm"
                            />
                        )}
                    </label>
                ))}
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-end gap-2">
                <Link href={resetHref} className="sa-button-ghost h-10 px-4 text-xs font-semibold">
                    <RotateCcw size={15} />
                    Limpiar
                </Link>
                <button type="submit" className="sa-button-secondary h-10 px-4 text-xs font-semibold">
                    <Search size={15} />
                    Buscar
                </button>
            </div>
        </form>
    );
}
