import type { SuperadminFormSection as FormSectionData } from '@/types/superadmin';
import type { ChangeEvent } from 'react';
import { useState } from 'react';

type FormValue = string | number | boolean | File | null;

function normalizeHex(value: string) {
    const hex = value.trim().replace('#', '').toUpperCase();
    return /^([A-F0-9]{6})$/.test(hex) ? `#${hex}` : null;
}

function hexToRgb(value: string) {
    const hex = normalizeHex(value);
    if (!hex) return null;
    return {
        r: Number.parseInt(hex.slice(1, 3), 16),
        g: Number.parseInt(hex.slice(3, 5), 16),
        b: Number.parseInt(hex.slice(5, 7), 16),
    };
}

function colorDistance(left: string, right: string) {
    const a = hexToRgb(left);
    const b = hexToRgb(right);
    if (!a || !b) return Number.MAX_SAFE_INTEGER;
    return Math.sqrt(((a.r - b.r) ** 2) + ((a.g - b.g) ** 2) + ((a.b - b.b) ** 2));
}

function hexToHsl(value: string) {
    const rgb = hexToRgb(value) ?? { r: 0, g: 0, b: 0 };
    const r = rgb.r / 255;
    const g = rgb.g / 255;
    const b = rgb.b / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const lightness = (max + min) / 2;
    const delta = max - min;
    const saturation = delta === 0 ? 0 : delta / (1 - Math.abs((2 * lightness) - 1));

    let hue = 0;

    if (delta !== 0) {
        if (max === r) hue = 60 * (((g - b) / delta) % 6);
        else if (max === g) hue = 60 * (((b - r) / delta) + 2);
        else hue = 60 * (((r - g) / delta) + 4);
    }

    if (hue < 0) hue += 360;

    return { h: Math.round(hue), s: Math.round(saturation * 100), l: Math.round(lightness * 100) };
}

function suggestedColorName(hex: string, family: string) {
    const knownTone = closestKnownTone(hex, family);

    if (knownTone) {
        return knownTone;
    }

    const hsl = hexToHsl(hex);
    const modifier = hsl.l <= 24
        ? 'OSCURO'
        : hsl.l >= 78
        ? 'CLARO'
        : hsl.s <= 18
        ? 'SUAVE'
        : hsl.s >= 70 && hsl.l >= 35 && hsl.l <= 65
        ? 'INTENSO'
        : '';
    const plainFamilies = ['NEGRO', 'BLANCO', 'GRIS', 'BEIGE', 'CAMEL', 'MARRON', 'DORADO', 'PLATEADO'];

    if (plainFamilies.includes(family) && !['OSCURO', 'CLARO'].includes(modifier)) {
        return family;
    }

    return `${family} ${modifier}`.trim();
}

function colorOrderBase(family: string) {
    const order: Record<string, number> = {
        NEGRO: 100,
        BLANCO: 200,
        GRIS: 300,
        BEIGE: 400,
        CAMEL: 500,
        MARRON: 600,
        AZUL: 700,
        CELESTE: 750,
        VERDE: 800,
        ROJO: 900,
        BORGONA: 950,
        ROSA: 1000,
        AMARILLO: 1100,
        NARANJA: 1200,
        MORADO: 1300,
        DORADO: 1400,
        PLATEADO: 1500,
    };

    return order[family] ?? 1600;
}

function closestKnownTone(hex: string, family: string) {
    const candidates = knownColorTones[family] ?? [];
    const extended = family === 'BORGONA' ? [...candidates, ...(knownColorTones.ROJO ?? [])] : candidates;
    const best = extended
        .map((tone) => ({ ...tone, distance: colorDistance(hex, tone.hex) }))
        .sort((left, right) => left.distance - right.distance)[0];

    return best && best.distance <= 95 ? best.name : null;
}

function closestColorFamilyOption(hex: string, families: FormSectionData['fields'][number]['options'] = []) {
    const hsl = hexToHsl(hex);
    const hueFamily = hsl.s >= 20
        ? hsl.h >= 15 && hsl.h < 35 && hsl.l < 38
            ? 'MARRON'
            : hsl.h >= 15 && hsl.h < 35 && hsl.s < 65
            ? 'CAMEL'
            : hsl.h >= 15 && hsl.h < 35
            ? 'NARANJA'
            : hsl.h >= 35 && hsl.h < 65 && hsl.l >= 75
            ? 'BEIGE'
            : hsl.h >= 35 && hsl.h < 75
            ? 'AMARILLO'
            : hsl.h >= 75 && hsl.h < 165
            ? 'VERDE'
            : hsl.h >= 165 && hsl.h < 195
            ? 'CELESTE'
            : hsl.h >= 195 && hsl.h < 255
            ? 'AZUL'
            : hsl.h >= 255 && hsl.h < 310
            ? 'MORADO'
            : hsl.h >= 310 && hsl.h < 345
            ? 'ROSA'
            : 'ROJO'
        : null;

    if (hueFamily) {
        const option = families.find((family) => family.value === hueFamily || family.label === hueFamily);
        if (option) return option;
    }

    return families
        .filter((option) => option.swatch)
        .map((option) => ({ option, distance: colorDistance(hex, option.swatch ?? '#000000') }))
        .sort((left, right) => left.distance - right.distance)[0]?.option;
}

const knownColorTones: Record<string, { name: string; hex: string }[]> = {
    VERDE: [
        { name: 'VERDE BOSQUE OSCURO', hex: '#0D2608' },
        { name: 'VERDE BOTELLA', hex: '#0B3D2E' },
        { name: 'VERDE PINO OSCURO', hex: '#174A2A' },
        { name: 'VERDE OLIVA', hex: '#66704A' },
        { name: 'VERDE ESMERALDA', hex: '#047857' },
        { name: 'VERDE MENTA', hex: '#98D8B7' },
        { name: 'VERDE SALVIA', hex: '#9CAF88' },
        { name: 'VERDE LIMON', hex: '#B7D93D' },
    ],
    AZUL: [
        { name: 'AZUL MARINO', hex: '#1F2A44' },
        { name: 'AZUL PETROLEO', hex: '#0F4C5C' },
        { name: 'AZUL NOCHE', hex: '#0B1F3A' },
        { name: 'AZUL REY', hex: '#1D4ED8' },
        { name: 'AZUL CIELO', hex: '#7DB7E8' },
        { name: 'CELESTE', hex: '#6F9FD8' },
        { name: 'TURQUESA', hex: '#14B8A6' },
    ],
    CELESTE: [
        { name: 'CELESTE', hex: '#6F9FD8' },
        { name: 'AZUL CIELO', hex: '#7DB7E8' },
        { name: 'TURQUESA', hex: '#14B8A6' },
    ],
    ROJO: [
        { name: 'ROJO VINO', hex: '#6F1D2C' },
        { name: 'ROJO CEREZA', hex: '#B91C1C' },
        { name: 'ROJO RUBI', hex: '#9B111E' },
        { name: 'ROJO LADRILLO', hex: '#8B3A2E' },
        { name: 'ROJO INTENSO', hex: '#DC2626' },
        { name: 'BORGONA', hex: '#8A2332' },
    ],
    BORGONA: [
        { name: 'BORGONA', hex: '#8A2332' },
        { name: 'ROJO VINO', hex: '#6F1D2C' },
    ],
    ROSA: [
        { name: 'ROSA PALO', hex: '#D8A7B1' },
        { name: 'ROSA PASTEL', hex: '#F4B6C2' },
        { name: 'ROSA FUCSIA', hex: '#C026D3' },
        { name: 'ROSA EMPOLVADO', hex: '#C894A2' },
    ],
    BEIGE: [
        { name: 'CREMA', hex: '#F2E8D8' },
        { name: 'BEIGE ARENA', hex: '#D8C3A5' },
        { name: 'BEIGE CLARO', hex: '#E8D8C3' },
        { name: 'MARFIL', hex: '#F7F0DF' },
    ],
    CAMEL: [
        { name: 'CAMEL', hex: '#B88956' },
        { name: 'BEIGE ARENA', hex: '#D8C3A5' },
    ],
    MARRON: [
        { name: 'MARRON CHOCOLATE', hex: '#4E2F1E' },
        { name: 'MARRON CAFE', hex: '#6F4E37' },
        { name: 'MARRON TABACO', hex: '#8B5A2B' },
    ],
    GRIS: [
        { name: 'GRIS PERLA', hex: '#D1D5DB' },
        { name: 'GRIS CEMENTO', hex: '#9CA3AF' },
        { name: 'GRIS ACERO', hex: '#64748B' },
        { name: 'GRIS OSCURO', hex: '#374151' },
    ],
    MORADO: [
        { name: 'LILA', hex: '#C8A2C8' },
        { name: 'LAVANDA', hex: '#BFA7E6' },
        { name: 'MORADO UVA', hex: '#6D28D9' },
        { name: 'MORADO OSCURO', hex: '#3B0764' },
    ],
    NEGRO: [
        { name: 'NEGRO', hex: '#111111' },
        { name: 'NEGRO CARBON', hex: '#1F1F1F' },
    ],
    BLANCO: [
        { name: 'BLANCO', hex: '#F7F6F2' },
        { name: 'BLANCO HUESO', hex: '#EDE6D6' },
        { name: 'MARFIL', hex: '#F7F0DF' },
    ],
    AMARILLO: [
        { name: 'AMARILLO', hex: '#EAB308' },
        { name: 'AMARILLO MOSTAZA', hex: '#C99700' },
    ],
    NARANJA: [
        { name: 'NARANJA', hex: '#EA580C' },
        { name: 'NARANJA TERRACOTA', hex: '#C65A2E' },
    ],
    DORADO: [{ name: 'DORADO', hex: '#C8A96A' }],
    PLATEADO: [{ name: 'PLATEADO', hex: '#C0C0C0' }],
};

function TogglePreview({
    checked,
    disabled = false,
    onClick,
}: {
    checked: boolean;
    disabled?: boolean;
    onClick?: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={[
                'inline-flex h-7 w-12 items-center rounded-full border p-1 transition',
                disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer',
                checked
                    ? 'border-[rgba(126,154,178,0.34)] bg-[rgba(126,154,178,0.2)]'
                    : 'border-white/10 bg-white/[0.04]',
            ].join(' ')}
        >
            <span
                className={[
                    'h-5 w-5 rounded-full bg-white transition',
                    checked ? 'translate-x-5' : 'translate-x-0',
                ].join(' ')}
            />
        </button>
    );
}

function PasswordField({
    field,
    value,
    disabled,
    hasError,
    onChange,
    onBlur,
}: {
    field: FormSectionData['fields'][number];
    value: string;
    disabled: boolean;
    hasError: boolean;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
    onBlur: () => void;
}) {
    const [show, setShow] = useState(false);

    return (
        <div className="relative mt-3">
            <input
                type={show ? 'text' : 'password'}
                aria-invalid={hasError}
                className={`sa-input w-full px-4 py-3 pr-12 text-sm ${
                    hasError ? 'border-[rgba(183,108,112,0.5)] bg-[rgba(183,108,112,0.07)]' : ''
                }`}
                placeholder={field.placeholder}
                value={value}
                maxLength={field.maxLength}
                disabled={disabled}
                onBlur={onBlur}
                onChange={onChange}
            />
            <button
                type="button"
                tabIndex={-1}
                onClick={() => setShow((prev) => !prev)}
                className="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-stone-400 transition hover:text-stone-200"
                aria-label={show ? 'Ocultar contraseña' : 'Mostrar contraseña'}
            >
                {show ? (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                    </svg>
                ) : (
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                )}
            </button>
        </div>
    );
}

export default function SuperadminFormSection({
    section,
    data,
    errors = {},
    processing = false,
    readOnly = false,
    onChange,
}: {
    section: FormSectionData;
    data: Record<string, FormValue>;
    errors?: Record<string, string>;
    processing?: boolean;
    readOnly?: boolean;
    onChange: (name: string, value: FormValue) => void;
}) {
    const [touched, setTouched] = useState<Record<string, boolean>>({});
    const isToggleChecked = (value: FormValue) =>
        value === true || value === 1 || value === '1';

    const markTouched = (name: string) => {
        setTouched((current) => ({ ...current, [name]: true }));
    };

    const clientErrorFor = (
        field: FormSectionData['fields'][number],
        value: FormValue,
    ) => {
        if (!touched[field.name] || readOnly || field.disabled) {
            return null;
        }

        const stringValue = String(value ?? '').trim();

        if (field.required && stringValue === '') {
            return 'Este campo es obligatorio.';
        }

        if (stringValue === '') {
            return null;
        }

        if (field.disallowNumbers && /\d/.test(stringValue)) {
            return 'No se permiten números en este campo.';
        }

        if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(stringValue)) {
            return 'Ingresa un correo electrónico válido.';
        }

        if (field.type === 'tel' && !/^[67]\d{7}$/.test(stringValue)) {
            return 'Ingresa un teléfono celular válido.';
        }

        if (field.type === 'number') {
            const numericValue = Number(stringValue);

            if (!Number.isFinite(numericValue)) {
                return 'Ingresa un número válido.';
            }

            if (typeof field.min === 'number' && numericValue < field.min) {
                return `El valor mínimo permitido es ${field.min}.`;
            }

            if (typeof field.max === 'number' && numericValue > field.max) {
                return `El valor máximo permitido es ${field.max}.`;
            }
        }

        if (field.pattern) {
            try {
                const regex = new RegExp(field.pattern);

                if (!regex.test(stringValue)) {
                    return field.invalidMessage ?? 'Revisa el formato de este campo.';
                }
            } catch {
                return null;
            }
        }

        return null;
    };

    const handleTextualChange =
        (field: FormSectionData['fields'][number]) =>
        (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
            let nextValue = event.currentTarget.value;

            if (field.transform === 'uppercase') {
                nextValue = nextValue.toUpperCase();
            }

            if (field.transform === 'lowercase') {
                nextValue = nextValue.toLowerCase();
            }

            if (field.transform === 'digits') {
                nextValue = nextValue.replace(/\D/g, '');
            }

            if (typeof field.maxLength === 'number') {
                nextValue = nextValue.slice(0, field.maxLength);
            }

            markTouched(field.name);
            onChange(field.name, nextValue);

            if (field.name === 'hex_col') {
                const families = section.fields.find((item) => item.name === 'fam_col')?.options ?? [];
                const suggested = closestColorFamilyOption(nextValue, families);

                if (suggested) {
                    onChange('fam_col', suggested.value);
                    onChange('hex_dom_col', suggested.swatch ?? nextValue);
                    onChange('nom_col', suggestedColorName(nextValue, suggested.value));
                    onChange('ord_col', colorOrderBase(suggested.value));
                }
            }
        };

    const selectColorFamily = (value: string) => {
        const field = section.fields.find((item) => item.name === 'fam_col');
        const selected = field?.options?.find((option) => option.value === value);

        markTouched('fam_col');
        onChange('fam_col', value);

        if (selected?.swatch) {
            onChange('hex_dom_col', selected.swatch);
            onChange('nom_col', suggestedColorName(String(data.hex_col ?? selected.swatch), value));
            onChange('ord_col', colorOrderBase(value));
        }
    };

    const suggestionsFor = (field: FormSectionData['fields'][number]) => {
        if (!field.suggestions?.length) {
            return [];
        }

        return field.suggestions.find((group) => {
            const segmentMatches = !group.segment || group.segment === data.seg_tal;
            const groupMatches = !group.group || group.group === data.gru_tal;
            const systemMatches = !group.system || group.system === data.sis_tal;

            return segmentMatches && groupMatches && systemMatches;
        })?.values ?? [];
    };

    const showsColorPreview = section.fields.some((field) => field.name === 'hex_col')
        && section.fields.some((field) => field.name === 'fam_col');
    const exactColor = normalizeHex(String(data.hex_col ?? '')) ?? '#000000';
    const dominantColor = normalizeHex(String(data.hex_dom_col ?? '')) ?? '#000000';
    const colorFamily = String(data.fam_col ?? '').trim();
    const colorName = colorFamily ? suggestedColorName(exactColor, colorFamily) : 'Nombre sugerido';

    return (
        <div className="sa-panel rounded-[1.6rem] p-6">
            <div className="border-b border-white/8 pb-5">
                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                    {section.title}
                </p>
                {section.description && (
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                        {section.description}
                    </p>
                )}
            </div>

            {showsColorPreview && (
                <div className="mt-5 rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Vista previa</p>
                    <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre sugerido</p>
                            <p className="mt-2 text-base text-white">{colorName}</p>
                            <p className="mt-1 text-sm text-stone-400">Familia: {colorFamily || 'Sin familia'}</p>
                            <p className="mt-1 text-xs text-stone-500">Color exacto: {exactColor} · Dominante: {dominantColor}</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="inline-flex h-12 w-12 rounded-full border border-white/15" style={{ backgroundColor: exactColor }} />
                            <span className="inline-flex h-12 w-12 rounded-full border border-white/15" style={{ backgroundColor: dominantColor }} />
                        </div>
                    </div>
                </div>
            )}

            <div className="mt-6 grid gap-4 md:grid-cols-2">
                {section.fields.map((field) => {
                    if (field.type === 'hidden') {
                        return null;
                    }

                    const value = data[field.name] ?? field.value;
                    const disabled = readOnly || processing || Boolean(field.disabled);
                    const clientError = clientErrorFor(field, value);
                    const error = errors[field.name] ?? clientError;
                    const errorClass = error
                        ? 'border-[rgba(183,108,112,0.5)] bg-[rgba(183,108,112,0.07)]'
                        : '';
                    const suggestions = suggestionsFor(field);

                    return (
                        <div
                            key={field.name}
                            className={field.type === 'textarea' ? 'md:col-span-2' : ''}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <label className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-200">
                                    {field.label}
                                </label>
                                {field.required && (
                                    <span className="text-[0.65rem] uppercase tracking-[0.14em] text-[rgb(194,214,232)]">
                                        Obligatorio
                                    </span>
                                )}
                                {!field.required && !field.disabled && (
                                    <span className="text-[0.65rem] uppercase tracking-[0.14em] text-stone-500">
                                        Opcional
                                    </span>
                                )}
                            </div>

                            {field.type === 'textarea' ? (
                                <textarea
                                    aria-invalid={Boolean(error)}
                                    className={`sa-input mt-3 min-h-[150px] px-4 py-3 text-sm ${errorClass}`}
                                    placeholder={field.placeholder}
                                    value={String(value ?? '')}
                                    maxLength={field.maxLength}
                                    disabled={disabled}
                                    onBlur={() => markTouched(field.name)}
                                    onChange={handleTextualChange(field)}
                                />
                            ) : field.type === 'color-family' ? (
                                <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                    {field.options?.map((option) => {
                                        const selected = String(value ?? '') === option.value;

                                        return (
                                            <button
                                                key={option.value}
                                                type="button"
                                                disabled={disabled}
                                                onClick={() => selectColorFamily(option.value)}
                                                className={[
                                                    'flex items-center gap-2 rounded-[1.1rem] border px-3 py-3 text-left text-xs uppercase tracking-[0.14em] transition',
                                                    selected
                                                        ? 'border-[rgba(126,154,178,0.38)] bg-[rgba(126,154,178,0.16)] text-white'
                                                        : 'border-white/8 bg-[#0d141d] text-stone-300 hover:border-white/16',
                                                ].join(' ')}
                                            >
                                                <span className="h-5 w-5 rounded-full border border-white/15" style={{ backgroundColor: option.swatch ?? '#000000' }} />
                                                <span>{option.label}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : field.type === 'select' ? (
                                <select
                                    aria-invalid={Boolean(error)}
                                    className={`sa-input mt-3 px-4 py-3 text-sm ${errorClass}`}
                                    value={String(value ?? '')}
                                    disabled={disabled}
                                    onBlur={() => markTouched(field.name)}
                                    onChange={handleTextualChange(field)}
                                >
                                    {!field.required && <option value="">Sin seleccionar</option>}
                                    {field.options?.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            ) : field.type === 'toggle' ? (
                                <div className="sa-input mt-3 flex items-center justify-between px-4 py-3">
                                    <div>
                                        <p
                                            className={[
                                                'text-sm font-medium',
                                                isToggleChecked(value)
                                                    ? 'text-emerald-400'
                                                    : 'text-stone-400',
                                            ].join(' ')}
                                        >
                                            {isToggleChecked(value) ? 'Activo' : 'Inactivo'}
                                        </p>
                                    </div>
                                    <TogglePreview
                                        checked={isToggleChecked(value)}
                                        disabled={disabled}
                                        onClick={() => {
                                            markTouched(field.name);
                                            onChange(field.name, !isToggleChecked(value));
                                        }}
                                    />
                                </div>
                            ) : field.type === 'file' ? (
                                <div className="mt-3 space-y-3">
                                    <input
                                        type="file"
                                        accept={field.accept}
                                        aria-invalid={Boolean(error)}
                                        className={`sa-input w-full px-4 py-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-[rgba(126,154,178,0.18)] file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.16em] file:text-[rgb(205,223,236)] ${errorClass}`}
                                        disabled={disabled}
                                        onBlur={() => markTouched(field.name)}
                                        onChange={(event) => {
                                            markTouched(field.name);
                                            onChange(
                                                field.name,
                                                event.currentTarget.files?.[0] ?? null,
                                            );
                                        }}
                                    />
                                    {field.value && (
                                        <p className="text-xs leading-6 text-stone-500">
                                            Archivo actual: {field.value}
                                        </p>
                                    )}
                                </div>
                            ) : field.type === 'password' ? (
                                <PasswordField
                                    field={field}
                                    value={String(value ?? '')}
                                    disabled={disabled}
                                    hasError={Boolean(error)}
                                    onChange={handleTextualChange(field)}
                                    onBlur={() => markTouched(field.name)}
                                />
                            ) : field.type === 'color' ? (
                                <div className="mt-3 flex items-center gap-3">
                                    <input
                                        type="color"
                                        aria-invalid={Boolean(error)}
                                        className={`h-12 w-20 rounded-xl border border-white/10 bg-[#0d141d] p-1 ${errorClass}`}
                                        value={normalizeHex(String(value ?? '')) ?? '#000000'}
                                        disabled={disabled}
                                        onBlur={() => markTouched(field.name)}
                                        onChange={handleTextualChange(field)}
                                    />
                                    <div>
                                        <p className="text-sm text-white">{normalizeHex(String(value ?? '')) ?? 'Sin color'}</p>
                                        <p className="text-xs leading-5 text-stone-500">Selección visual</p>
                                    </div>
                                </div>
                            ) : (
                                <input
                                    type={field.type ?? 'text'}
                                    aria-invalid={Boolean(error)}
                                    className={`sa-input mt-3 px-4 py-3 text-sm ${errorClass}`}
                                    placeholder={field.placeholder}
                                    value={String(value ?? '')}
                                    maxLength={field.maxLength}
                                    min={field.min}
                                    max={field.max}
                                    disabled={disabled}
                                    onBlur={() => markTouched(field.name)}
                                    onChange={handleTextualChange(field)}
                                />
                            )}

                            {field.hint && (
                                <p className="mt-2 text-xs leading-6 text-stone-500">
                                    {field.hint}
                                </p>
                            )}
                            {suggestions.length > 0 && !readOnly && !field.disabled && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {suggestions.map((suggestion) => (
                                        <button
                                            key={`${field.name}-${suggestion}`}
                                            type="button"
                                            disabled={disabled}
                                            onClick={() => {
                                                markTouched(field.name);
                                                onChange(field.name, suggestion);
                                            }}
                                            className="rounded-full border border-white/10 px-3 py-1.5 text-xs uppercase tracking-[0.12em] text-stone-300 transition hover:border-white/18 hover:bg-white/5 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {suggestion}
                                        </button>
                                    ))}
                                </div>
                            )}
                            {error && (
                                <p className="mt-2 text-xs leading-6 text-[rgb(245,208,210)]">
                                    {error}
                                </p>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
