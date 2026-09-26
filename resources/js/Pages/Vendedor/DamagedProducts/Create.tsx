import { damagedProductNameError } from '@/lib/commercialValidation';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ImagePlus, Save, X } from 'lucide-react';
import { useEffect, useMemo, type FormEvent } from 'react';

type Option = {
    label: string;
    value: string;
};

type InventoryOption = Option & {
    inventoryId: number;
    variantId: number | null;
    productId: number | null;
    productName: string;
    sku: string;
    color: string;
    size: string;
    imageUrl: string | null;
    availableStock: number;
    committedStock: number;
    location: string;
    active: boolean;
    statusLabel: string;
};

type DamagedProductCreateProps = {
    options: {
        causes: Option[];
        damageTypes: Option[];
        severities: Option[];
        inventories: InventoryOption[];
    };
    submitAction: string;
    indexHref: string;
};

type DamagedProductForm = {
    nombre_prenda_pdr: string;
    descripcion_prenda_pdr: string;
    cod_prenda_pdr: string;
    cod_prenda_variante_pdr: string;
    cod_inventario_pdr: string;
    causa_pdr: string;
    tipo_danio_pdr: string;
    severidad_pdr: string;
    cantidad_pdr: string;
    observacion_pdr: string;
    afecta_inventario_pdr: string;
    imagenes: File[];
};

function suggestedDescription(inventory: InventoryOption) {
    return `Variante ${inventory.sku} - ${inventory.color} - ${inventory.size} - Stock disponible ${inventory.availableStock} - Stock comprometido ${inventory.committedStock} - Ubicación ${inventory.location}`;
}

export default function Create({
    options,
    submitAction,
    indexHref,
}: DamagedProductCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const { data, setData, post, processing, errors } = useForm<DamagedProductForm>({
        nombre_prenda_pdr: '',
        descripcion_prenda_pdr: '',
        cod_prenda_pdr: '',
        cod_prenda_variante_pdr: '',
        cod_inventario_pdr: '',
        causa_pdr: options.causes[0]?.value ?? 'dano_en_tienda',
        tipo_danio_pdr: options.damageTypes[0]?.value ?? 'mancha',
        severidad_pdr: options.severities[0]?.value ?? 'leve',
        cantidad_pdr: '1',
        observacion_pdr: '',
        afecta_inventario_pdr: '0',
        imagenes: [],
    });

    const inventoriesById = useMemo(
        () => new Map(options.inventories.map((inventory) => [inventory.value, inventory])),
        [options.inventories],
    );
    const selectedInventory = inventoriesById.get(data.cod_inventario_pdr) ?? null;
    const nameError = damagedProductNameError(data.nombre_prenda_pdr);
    const errorEntries = Object.entries(errors as Record<string, string>);
    const previews = useMemo(
        () => data.imagenes.map((file) => ({
            file,
            url: URL.createObjectURL(file),
        })),
        [data.imagenes],
    );

    useEffect(() => () => {
        previews.forEach((preview) => URL.revokeObjectURL(preview.url));
    }, [previews]);

    const updateInventory = (value: string) => {
        const selected = inventoriesById.get(value);
        const shouldSuggestName = selected && data.nombre_prenda_pdr.trim() === '';
        const shouldSuggestDescription = selected && data.descripcion_prenda_pdr.trim() === '';

        setData({
            ...data,
            cod_inventario_pdr: value,
            cod_prenda_variante_pdr: selected?.variantId ? String(selected.variantId) : '',
            cod_prenda_pdr: selected?.productId ? String(selected.productId) : '',
            nombre_prenda_pdr: shouldSuggestName ? selected.productName : data.nombre_prenda_pdr,
            descripcion_prenda_pdr: shouldSuggestDescription ? suggestedDescription(selected) : data.descripcion_prenda_pdr,
            afecta_inventario_pdr: '0',
        });
    };

    const removeImage = (file: File) => {
        setData('imagenes', data.imagenes.filter((current) => current !== file));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (nameError) return;

        post(submitAction, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <VendedorLayout title="Mati Boutique | Nuevo reporte de daño">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            Inventario
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Nuevo reporte de prenda dañada
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            El reporte queda pendiente para revisión administrativa. No descuenta el stock disponible, no modifica el stock comprometido y no registra Kardex.
                        </p>
                    </div>
                    <Link href={indexHref} className="sa-button-ghost inline-flex min-h-11 items-center justify-center px-4 text-xs font-semibold">
                        Volver al listado
                    </Link>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {errorEntries.length > 0 && (
                    <section className="rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[color:var(--admin-text)]">
                            Revisa los datos antes de guardar.
                        </p>
                        <ul className="mt-3 space-y-1 text-sm text-[color:var(--admin-muted)]">
                            {errorEntries.map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </section>
                )}

                <form onSubmit={submit} className="space-y-5">
                    <input type="hidden" name="afecta_inventario_pdr" value="0" />

                    <section className="sa-panel rounded-2xl p-5">
                        <div className="border-b border-[color:var(--admin-border)] pb-4">
                            <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                                Datos del reporte
                            </p>
                        </div>

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Nombre de la prenda</span>
                                <input
                                    aria-invalid={Boolean(nameError)}
                                    onBlur={() => setData('nombre_prenda_pdr', data.nombre_prenda_pdr.trim().replace(/\s+/g, ' '))}
                                    value={data.nombre_prenda_pdr}
                                    onChange={(event) => setData('nombre_prenda_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    maxLength={180}
                                    disabled={processing}
                                    required
                                />
                                {nameError && <p className="text-xs text-red-300" role="status">{nameError}</p>}
                            </label>

                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Cantidad afectada</span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    value={data.cantidad_pdr}
                                    onChange={(event) => setData('cantidad_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                    required
                                />
                            </label>

                            <label className="space-y-2 md:col-span-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Descripción de la prenda dañada</span>
                                <textarea
                                    value={data.descripcion_prenda_pdr}
                                    onChange={(event) => setData('descripcion_prenda_pdr', event.target.value)}
                                    className="sa-input min-h-28 w-full resize-none"
                                    disabled={processing}
                                />
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-2xl p-5">
                        <div className="border-b border-[color:var(--admin-border)] pb-4">
                            <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                                Clasificación del daño
                            </p>
                        </div>

                        <div className="mt-5 grid gap-4 md:grid-cols-3">
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Causa</span>
                                <select
                                    value={data.causa_pdr}
                                    onChange={(event) => setData('causa_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                    required
                                >
                                    {options.causes.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Tipo de daño</span>
                                <select
                                    value={data.tipo_danio_pdr}
                                    onChange={(event) => setData('tipo_danio_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                    required
                                >
                                    {options.damageTypes.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Severidad</span>
                                <select
                                    value={data.severidad_pdr}
                                    onChange={(event) => setData('severidad_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                    required
                                >
                                    {options.severities.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-2xl p-5">
                        <div className="flex flex-col gap-3 border-b border-[color:var(--admin-border)] pb-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                                    Vinculacion opcional con inventario
                                </p>
                                <p className="mt-2 max-w-3xl text-sm leading-6 text-[color:var(--admin-muted)]">
                                    Vincular una prenda ayuda a identificar el daño, pero no modifica el stock. La revisión final la realiza el administrador.
                                </p>
                            </div>
                            <SuperadminBadge tone="success">No mueve stock</SuperadminBadge>
                        </div>

                        <div className="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
                            <div className="space-y-4">
                                <label className="space-y-2">
                                    <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Prenda / variante existente</span>
                                    <select
                                        value={data.cod_inventario_pdr}
                                        onChange={(event) => updateInventory(event.target.value)}
                                        className="sa-input w-full"
                                        disabled={processing}
                                    >
                                        <option value="">Registrar sin vincular inventario</option>
                                        {options.inventories.map((inventory) => (
                                            <option key={inventory.value} value={inventory.value}>
                                                {inventory.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                {selectedInventory && (
                                    <div className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 sm:grid-cols-2">
                                        <Detail label="SKU" value={selectedInventory.sku} />
                                        <Detail label="Color" value={selectedInventory.color} />
                                        <Detail label="Talla" value={selectedInventory.size} />
                                        <Detail label="Ubicacion" value={selectedInventory.location} />
                                        <Detail label="Stock disponible" value={String(selectedInventory.availableStock)} />
                                        <Detail label="Stock comprometido" value={String(selectedInventory.committedStock)} />
                                    </div>
                                )}
                            </div>

                            <div className="overflow-hidden rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)]">
                                {selectedInventory ? (
                                    <>
                                        {selectedInventory.imageUrl ? (
                                            <img
                                                src={selectedInventory.imageUrl}
                                                alt={selectedInventory.productName}
                                                className="h-72 w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-72 items-center justify-center px-4 text-center text-sm text-[color:var(--admin-muted)]">
                                                Sin imagen registrada
                                            </div>
                                        )}
                                        <div className="p-4">
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-base font-medium text-[color:var(--admin-text)]">{selectedInventory.productName}</p>
                                                    <p className="mt-1 text-sm text-[color:var(--admin-muted)]">{selectedInventory.sku}</p>
                                                </div>
                                                <SuperadminBadge tone={selectedInventory.active ? 'success' : 'neutral'}>
                                                    {selectedInventory.statusLabel}
                                                </SuperadminBadge>
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <div className="flex min-h-52 items-center justify-center px-4 text-center text-sm leading-6 text-[color:var(--admin-muted)]">
                                        Sin prenda vinculada. El reporte se guardara como control administrativo sin mover inventario.
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="sa-panel rounded-2xl p-5">
                        <div className="border-b border-[color:var(--admin-border)] pb-4">
                            <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                                Evidencia y observacion
                            </p>
                        </div>

                        <div className="mt-5 grid gap-4 lg:grid-cols-2">
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Imagenes de evidencia</span>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => setData('imagenes', Array.from(event.target.files ?? []))}
                                    className="sa-input w-full file:mr-4 file:rounded-full file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-[color:var(--admin-text)]"
                                    disabled={processing}
                                />
                                <p className="text-xs leading-5 text-[color:var(--admin-muted)]">
                                    JPG, JPEG, PNG o WEBP. Maximo 4 MB por imagen.
                                </p>
                            </label>

                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Observacion</span>
                                <textarea
                                    value={data.observacion_pdr}
                                    onChange={(event) => setData('observacion_pdr', event.target.value)}
                                    className="sa-input min-h-32 w-full resize-none"
                                    disabled={processing}
                                    required
                                />
                            </label>
                        </div>

                        {previews.length > 0 && (
                            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                {previews.map((preview) => (
                                    <div key={`${preview.file.name}-${preview.file.size}`} className="overflow-hidden rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)]">
                                        <img src={preview.url} alt={preview.file.name} className="h-40 w-full object-cover" />
                                        <div className="flex items-center justify-between gap-2 p-3">
                                            <p className="min-w-0 truncate text-xs text-[color:var(--admin-muted)]">
                                                {preview.file.name}
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => removeImage(preview.file)}
                                                className="sa-button-ghost h-8 w-8 shrink-0 p-0"
                                                title="Quitar imagen"
                                                aria-label={`Quitar ${preview.file.name}`}
                                            >
                                                <X size={14} />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    <div className="sa-panel rounded-2xl p-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Link href={indexHref} className="sa-button-ghost px-5 py-3 text-center text-xs font-semibold">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || Boolean(nameError)}
                                className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {processing ? <ImagePlus size={15} /> : <Save size={15} />}
                                {processing ? 'Guardando' : 'Guardar reporte'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </VendedorLayout>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</dt>
            <dd className="mt-1 break-words text-sm text-[color:var(--admin-text)]">{value}</dd>
        </div>
    );
}
