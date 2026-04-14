import type { SuperadminFormSection as FormSectionData } from '@/types/superadmin';
import type { ChangeEvent } from 'react';
import { useState } from 'react';

type FormValue = string | number | boolean | File | null;

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
    onChange,
}: {
    field: FormSectionData['fields'][number];
    value: string;
    disabled: boolean;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}) {
    const [show, setShow] = useState(false);

    return (
        <div className="relative mt-3">
            <input
                type={show ? 'text' : 'password'}
                className="sa-input w-full px-4 py-3 pr-12 text-sm"
                placeholder={field.placeholder}
                value={value}
                maxLength={field.maxLength}
                disabled={disabled}
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
    const isToggleChecked = (value: FormValue) =>
        value === true || value === 1 || value === '1';

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

            onChange(field.name, nextValue);
        };

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

            <div className="mt-6 grid gap-4 md:grid-cols-2">
                {section.fields.map((field) => {
                    const value = data[field.name] ?? field.value;
                    const disabled = readOnly || processing || Boolean(field.disabled);
                    const error = errors[field.name];

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
                            </div>

                            {field.type === 'textarea' ? (
                                <textarea
                                    className="sa-input mt-3 min-h-[150px] px-4 py-3 text-sm"
                                    placeholder={field.placeholder}
                                    value={String(value ?? '')}
                                    maxLength={field.maxLength}
                                    disabled={disabled}
                                    onChange={handleTextualChange(field)}
                                />
                            ) : field.type === 'select' ? (
                                <select
                                    className="sa-input mt-3 px-4 py-3 text-sm"
                                    value={String(value ?? '')}
                                    disabled={disabled}
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
                                        onClick={() =>
                                            onChange(field.name, !isToggleChecked(value))
                                        }
                                    />
                                </div>
                            ) : field.type === 'file' ? (
                                <div className="mt-3 space-y-3">
                                    <input
                                        type="file"
                                        accept={field.accept}
                                        className="sa-input w-full px-4 py-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-[rgba(126,154,178,0.18)] file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.16em] file:text-[rgb(205,223,236)]"
                                        disabled={disabled}
                                        onChange={(event) =>
                                            onChange(
                                                field.name,
                                                event.currentTarget.files?.[0] ?? null,
                                            )
                                        }
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
                                    onChange={handleTextualChange(field)}
                                />
                            ) : (
                                <input
                                    type={field.type === 'color' ? 'text' : field.type ?? 'text'}
                                    className="sa-input mt-3 px-4 py-3 text-sm"
                                    placeholder={field.placeholder}
                                    value={String(value ?? '')}
                                    maxLength={field.maxLength}
                                    disabled={disabled}
                                    onChange={handleTextualChange(field)}
                                />
                            )}

                            {field.hint && (
                                <p className="mt-2 text-xs leading-6 text-stone-500">
                                    {field.hint}
                                </p>
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
