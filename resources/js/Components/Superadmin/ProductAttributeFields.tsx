import SuperadminComboboxField from '@/Components/Superadmin/SuperadminComboboxField';
import type { SuperadminFormFieldOption } from '@/types/superadmin';
import { Check, Flower2, Layers3, Search, Shapes } from 'lucide-react';
import { useId, useState, type CSSProperties } from 'react';

type Kind = 'pattern' | 'texture';
type PaletteType = 'dominante' | 'equilibrada';

function normalize(value: string) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase(); }

function surfaceStyle(label: string, kind: Kind): CSSProperties {
    const name = normalize(label);
    const line = 'rgba(220, 205, 180, .64)';
    const base = 'rgba(220, 205, 180, .09)';
    let backgroundImage = 'none';
    if (name.includes('RAYA') || name.includes('ACANAL')) backgroundImage = `repeating-linear-gradient(${kind === 'texture' ? '90deg' : '135deg'}, ${line} 0 2px, transparent 2px 9px)`;
    else if (name.includes('CUADRO') || name.includes('WAFFLE') || name.includes('TEJID')) backgroundImage = `repeating-linear-gradient(0deg, ${line} 0 1px, transparent 1px 10px), repeating-linear-gradient(90deg, ${line} 0 1px, transparent 1px 10px)`;
    else if (name.includes('DEGRAD') || name.includes('ATERCIOPEL')) backgroundImage = `linear-gradient(120deg, rgba(220,205,180,.07), ${line})`;
    else if (name.includes('DENIM')) backgroundImage = `repeating-linear-gradient(145deg, ${line} 0 1px, transparent 1px 5px)`;
    else if (name.includes('ANIMAL') || name.includes('CALAD')) backgroundImage = `radial-gradient(circle, ${line} 20%, transparent 23%)`;
    else if (name.includes('CAMUFL') || name.includes('ESTAMP') || name.includes('TEXTUR')) backgroundImage = `radial-gradient(ellipse at 25% 32%, ${line} 0 12%, transparent 14%), radial-gradient(ellipse at 75% 68%, ${line} 0 18%, transparent 20%)`;
    else if (name.includes('FELPA')) backgroundImage = `radial-gradient(circle, ${line} 0 2px, transparent 3px)`;
    return { backgroundColor: base, backgroundImage, backgroundSize: name.includes('ANIMAL') || name.includes('CALAD') || name.includes('FELPA') ? '12px 12px' : undefined };
}

export function VisualAttributePicker({ title, kind, items, selected, onToggle, error }: {
    title: string; kind: Kind; items: SuperadminFormFieldOption[]; selected: string[];
    onToggle: (id: string) => void; error?: string;
}) {
    return <fieldset className="min-w-0 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
        <legend className="px-2 text-sm text-white">{title}</legend>
        <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
            {items.map((item) => {
                const active = selected.includes(item.value);
                const disabled = item.active === false && !active;
                const name = normalize(item.label);
                return <button key={item.value} type="button" aria-pressed={active} disabled={disabled} onClick={() => onToggle(item.value)}
                    className={`min-w-0 rounded-[1rem] border p-3 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d7bea0] ${active ? 'border-[#bfa786] bg-[#bfa786]/10 text-white' : 'border-white/10 bg-white/[0.02] text-stone-300 hover:border-white/25'} disabled:cursor-not-allowed disabled:opacity-40`}>
                    <span className="flex items-start justify-between gap-1">
                        <span aria-hidden="true" className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-white/15 text-[#d7bea0]" style={surfaceStyle(item.label, kind)}>
                            {name.includes('FLORAL') ? <Flower2 size={19} /> : name.includes('GEOMETR') ? <Shapes size={19} /> : name === 'OTRO' || name === 'OTRA' ? <Layers3 size={18} /> : null}
                        </span>
                        {active && <Check aria-hidden="true" size={15} className="text-[#d7bea0]" />}
                    </span>
                    <span className="mt-2 block break-words text-[0.72rem] uppercase tracking-[0.08em]">{item.label}</span>
                </button>;
            })}
        </div>
        {items.length === 0 && <p className="mt-2 text-sm text-stone-400">Gestiona este catálogo desde Administración.</p>}
        {error && <p className="mt-2 text-sm text-rose-300">{error}</p>}
    </fieldset>;
}

function swatch(color: SuperadminFormFieldOption) { return color.hex || color.swatch || color.dominantSwatch || '#ffffff'; }

const neutralFamilies = new Set(['NEGRO', 'BLANCO', 'BEIGE', 'CAMEL', 'GRIS']);
const familyFilters = ['TODOS', 'NEUTROS', 'ROJOS', 'NARANJAS', 'AMARILLOS', 'VERDES', 'AZULES', 'MORADOS', 'ROSAS', 'MARRONES', 'OTROS'] as const;
type FamilyFilter = typeof familyFilters[number];

function colorFilterFamily(color: SuperadminFormFieldOption): FamilyFilter {
    const family = normalize(color.group ?? '');
    if (neutralFamilies.has(family)) return 'NEUTROS';
    if (family === 'ROJO') return 'ROJOS';
    if (family === 'NARANJA') return 'NARANJAS';
    if (family === 'AMARILLO') return 'AMARILLOS';
    if (family === 'VERDE') return 'VERDES';
    if (family === 'AZUL') return 'AZULES';
    if (family === 'MORADO') return 'MORADOS';
    if (family === 'ROSA') return 'ROSAS';
    if (family === 'MARRON') return 'MARRONES';
    return 'OTROS';
}

export function PaletteEditor({ row, colors, referenceError, paletteError, hint, onTypeChange, onReferenceChange, onCreateColor, onAdditionalToggle }: {
    row: { cod_colores_prv: string; colores_adicionales: string[]; tip_pal_prv: PaletteType };
    colors: SuperadminFormFieldOption[]; referenceError?: string; paletteError?: string; hint?: string;
    onTypeChange: (value: PaletteType) => void; onReferenceChange: (value: string) => void;
    onCreateColor: (seed: string) => void; onAdditionalToggle: (value: string, selected: boolean) => void;
}) {
    const searchId = useId();
    const [query, setQuery] = useState('');
    const [family, setFamily] = useState<FamilyFilter>('TODOS');
    const balanced = row.tip_pal_prv === 'equilibrada';
    const reference = colors.find((item) => item.value === row.cod_colores_prv);
    const referenceOptions = colors.filter((item) => item.active !== false || item.value === row.cod_colores_prv);
    const palette = [reference, ...row.colores_adicionales.map((id) => colors.find((item) => item.value === id))].filter((item): item is SuperadminFormFieldOption => Boolean(item));
    const search = normalize(query.trim());
    const additionalColors = colors.filter((item) => item.value !== row.cod_colores_prv
        && (family === 'TODOS' || colorFilterFamily(item) === family)
        && (search === '' || normalize([item.label, item.group ?? ''].join(' ')).includes(search)));
    const availableFamilies = familyFilters.filter((filter) => filter === 'TODOS' || colors.some((item) => colorFilterFamily(item) === filter));

    return <div className="min-w-0 rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4 sm:p-5">
        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Características · colores</p>
        <div className="mt-4 grid gap-2 sm:grid-cols-2" role="group" aria-label="Tipo de paleta">
            {([['dominante', 'Color dominante', 'Un color predomina visualmente.'], ['equilibrada', 'Multicolor equilibrado', 'Ningún color predomina.']] as const).map(([value, label, description]) => <button key={value} type="button" aria-pressed={row.tip_pal_prv === value} onClick={() => onTypeChange(value)} className={`rounded-xl border p-3 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d7bea0] ${row.tip_pal_prv === value ? 'border-[#bfa786] bg-[#bfa786]/10' : 'border-white/10 hover:border-white/25'}`}>
                <span className="block text-sm text-white">{label}</span><span className="mt-1 block text-xs text-stone-400">{description}</span>
            </button>)}
        </div>
        <div className="mt-5">
            <SuperadminComboboxField label={balanced ? 'Color de referencia para catálogo' : 'Color dominante'} value={row.cod_colores_prv} options={referenceOptions} placeholder="Selecciona un color" searchPlaceholder="Buscar color" error={referenceError} hint={balanced ? 'Se utiliza para compatibilidad y clasificación. No implica que sea visualmente dominante.' : hint} createLabel="Nuevo color" createMode="header" onChange={onReferenceChange} onCreate={onCreateColor} />
        </div>
        <div className="mt-5 rounded-xl border border-white/8 bg-white/[0.03] p-3">
            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Paleta de esta variante</p>
            {palette.length > 0
                ? <div className="mt-3 flex flex-wrap gap-2">{palette.map((item) => <span key={item.value} className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs ${!balanced && item.value === row.cod_colores_prv ? 'border-[#bfa786] text-white' : 'border-white/10 text-stone-300'}`}><span aria-hidden="true" className="h-4 w-4 rounded-full border-2 border-white/35" style={{ backgroundColor: swatch(item) }} />{item.label}{!balanced && item.value === row.cod_colores_prv ? ' · dominante' : ''}</span>)}</div>
                : <p className="mt-2 text-xs text-stone-400">Selecciona un color para comenzar la paleta.</p>}
            {balanced && palette.length > 0 && <p className="mt-3 text-xs text-stone-400">Esta variante no tiene un color visual predominante.</p>}
        </div>
        <div className="mt-5">
            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">{balanced ? 'Otros colores de la paleta' : 'Colores complementarios'}</p>
            <label className="mt-3 block text-[0.68rem] uppercase tracking-[0.18em] text-stone-400" htmlFor={searchId}>Buscar color</label>
            <div className="relative mt-2">
                <Search aria-hidden="true" size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-500" />
                <input id={searchId} type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Nombre o familia, por ejemplo azul" className="sa-input w-full pl-9" />
            </div>
            <div className="mt-3 flex flex-wrap gap-1.5" role="group" aria-label="Filtrar colores por familia">
                {availableFamilies.map((filter) => <button key={filter} type="button" aria-pressed={family === filter} onClick={() => setFamily(filter)} className={`rounded-full border px-2.5 py-1 text-[0.65rem] uppercase tracking-[0.08em] transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d7bea0] ${family === filter ? 'border-[#bfa786] bg-[#bfa786]/10 text-white' : 'border-white/10 text-stone-400 hover:border-white/25 hover:text-white'}`}>{filter}</button>)}
            </div>
            <p className="mt-3 text-xs text-stone-400" aria-live="polite">{additionalColors.length} colores encontrados · {row.colores_adicionales.length} seleccionados</p>
            <div className="mt-2 max-h-64 overflow-y-auto rounded-xl border border-white/8 bg-white/[0.02] p-2">
                {additionalColors.length > 0
                    ? <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                        {additionalColors.map((item) => {
                            const selected = row.colores_adicionales.includes(item.value);
                            return <button key={item.value} type="button" aria-pressed={selected} disabled={item.active === false && !selected} onClick={() => onAdditionalToggle(item.value, !selected)} className={`flex min-w-0 items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-xs transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d7bea0] ${selected ? 'border-[#bfa786] bg-[#bfa786]/10 text-white' : 'border-white/10 text-stone-300 hover:border-white/25'} disabled:cursor-not-allowed disabled:opacity-40`}>
                                <span aria-hidden="true" className="h-5 w-5 shrink-0 rounded-full border-2 border-white/35 shadow-[0_0_0_1px_rgba(0,0,0,.45)]" style={{ backgroundColor: swatch(item) }} />
                                <span className="min-w-0 break-words">{item.label}</span>{selected && <Check size={13} className="ml-auto shrink-0 text-[#d7bea0]" />}
                            </button>;
                        })}
                    </div>
                    : <p className="px-2 py-4 text-sm text-stone-400">No hay colores para esta búsqueda o familia.</p>}
            </div>
            {paletteError && <p className="mt-2 text-sm text-rose-300">{paletteError}</p>}
        </div>
    </div>;
}
