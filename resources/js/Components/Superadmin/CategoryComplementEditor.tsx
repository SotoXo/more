import { useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';

export type CategoryComplementSettings = {
    saveUrl: string;
    options: Array<{ value: string; label: string }>;
    rows: Array<{ destino_id: string; prioridad: number; activo: boolean }>;
};

export default function CategoryComplementEditor({ settings }: { settings: CategoryComplementSettings }) {
    const { data, setData, put, processing, errors } = useForm<{ rows: CategoryComplementSettings['rows'] }>({
        rows: settings.rows,
    });
    useEffect(() => {
        setData('rows', settings.rows);
    }, [settings.rows, setData]);

    const available = settings.options.filter((option) =>
        !data.rows.some((row) => row.destino_id === option.value));

    function change(index: number, patch: Partial<CategoryComplementSettings['rows'][number]>) {
        setData('rows', data.rows.map((row, position) => position === index ? { ...row, ...patch } : row));
    }

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        put(settings.saveUrl, { preserveScroll: true });
    }

    return (
        <form onSubmit={save} className="sa-panel space-y-5 rounded-[1.6rem] p-5">
            <div>
                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Combinaciones del catálogo</p>
                <h2 className="mt-2 text-2xl text-white">Combina con</h2>
                <p className="mt-2 text-sm text-stone-400">Elige categorías complementarias y su prioridad. Cada dirección se configura por separado.</p>
            </div>

            {data.rows.length === 0 && <p className="text-sm text-stone-400">Todavía no hay categorías complementarias.</p>}
            <div className="space-y-3">
                {data.rows.map((row, index) => (
                    <div key={row.destino_id} className="grid gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-3 sm:grid-cols-[minmax(0,1fr)_7rem_auto_auto] sm:items-end">
                        <label className="space-y-1 text-xs text-stone-300">
                            <span className="block">Categoría</span>
                            <select value={row.destino_id} onChange={(event) => change(index, { destino_id: event.target.value })} className="sa-input w-full">
                                {settings.options.filter((option) => option.value === row.destino_id || !data.rows.some((item) => item.destino_id === option.value)).map((option) =>
                                    <option key={option.value} value={option.value}>{option.label}</option>)}
                            </select>
                        </label>
                        <label className="space-y-1 text-xs text-stone-300">
                            <span className="block">Prioridad</span>
                            <input type="number" min={1} max={100} value={row.prioridad} onChange={(event) => change(index, { prioridad: Number(event.target.value) })} className="sa-input w-full" />
                        </label>
                        <label className="flex items-center gap-2 pb-3 text-xs text-stone-300">
                            <input type="checkbox" checked={row.activo} onChange={(event) => change(index, { activo: event.target.checked })} />
                            Activa
                        </label>
                        <button type="button" onClick={() => setData('rows', data.rows.filter((_, position) => position !== index))} className="sa-button-ghost px-4 py-3 text-xs">Quitar</button>
                        {(errors[`rows.${index}.destino_id` as keyof typeof errors] || errors[`rows.${index}.prioridad` as keyof typeof errors]) &&
                            <p className="text-xs text-red-200 sm:col-span-4">{errors[`rows.${index}.destino_id` as keyof typeof errors] ?? errors[`rows.${index}.prioridad` as keyof typeof errors]}</p>}
                    </div>
                ))}
            </div>

            {errors.rows && <p className="text-sm text-red-200">{errors.rows}</p>}
            <div className="flex flex-wrap gap-3">
                <button type="button" disabled={available.length === 0} onClick={() => setData('rows', [...data.rows, { destino_id: available[0].value, prioridad: data.rows.length + 1, activo: true }])} className="sa-button-ghost px-4 py-3 text-xs disabled:opacity-50">Agregar categoría</button>
                <button type="submit" disabled={processing} className="sa-button-primary px-5 py-3 text-xs disabled:opacity-50">{processing ? 'Guardando' : 'Guardar combinaciones'}</button>
            </div>
        </form>
    );
}
