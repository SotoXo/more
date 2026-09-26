import { damagedProductNameError } from '@/lib/commercialValidation';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb } from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ImagePlus, Save } from 'lucide-react';
import { useMemo, type FormEvent } from 'react';

type Option = {
    label: string;
    value: string;
};

type InventoryOption = Option & {
    inventoryId: number;
    variantId: number | null;
    productId: number | null;
    productName: string;
    product: string;
    sku: string;
    color: string;
    size: string;
    imageUrl: string | null;
    availableStock: number;
    committedStock: number;
    stock: number;
    reserved: number;
    location: string;
    active: boolean;
    statusLabel: string;
};

type DamagedProductCreateProps = {
    meta: {
        sectionLabel: string;
        title: string;
        description: string;
        indexHref: string;
    };
    breadcrumbs: SuperadminBreadcrumb[];
    options: {
        causes: Option[];
        damageTypes: Option[];
        severities: Option[];
        inventories: InventoryOption[];
    };
    submitAction: string;
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
    return `Variante ${inventory.sku} - ${inventory.color} - ${inventory.size} - Stock disponible: ${inventory.availableStock} - Ubicacion: ${inventory.location}`;
}

export default function DamagedProductsCreate({
    meta,
    breadcrumbs,
    options,
    submitAction,
}: DamagedProductCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const { data, setData, post, processing, errors } = useForm<DamagedProductForm>({
        nombre_prenda_pdr: '',
        descripcion_prenda_pdr: '',
        cod_prenda_pdr: '',
        cod_prenda_variante_pdr: '',
        cod_inventario_pdr: '',
        causa_pdr: 'dano_en_tienda',
        tipo_danio_pdr: 'mancha',
        severidad_pdr: 'leve',
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
    const quantity = Math.max(1, Number(data.cantidad_pdr) || 1);
    const shouldDiscountStock = selectedInventory && data.afecta_inventario_pdr === '1';
    const stockWarning = shouldDiscountStock && quantity > selectedInventory.availableStock;
    const quantityHint = !selectedInventory
        ? 'Esta cantidad es informativa y no modificará stock.'
        : shouldDiscountStock
        ? 'Esta cantidad será descontada del stock disponible cuando el daño sea confirmado.'
        : 'Esta cantidad quedará registrada en el reporte, pero no modificará stock.';

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
            afecta_inventario_pdr: selected ? data.afecta_inventario_pdr : '0',
        });
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
        <SuperadminLayout title={`Mati Boutique | ${meta.title}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={meta.title}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {errorEntries.length > 0 && (
                    <section className="rounded-[1.4rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[rgb(245,208,210)]">
                            Revisa los datos antes de guardar.
                        </p>
                        <ul className="mt-3 space-y-1 text-sm text-[rgb(245,208,210)]">
                            {errorEntries.map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </section>
                )}

                <form onSubmit={submit} className="space-y-5">
                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="border-b border-white/8 pb-5">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Datos del reporte
                            </p>
                        </div>

                        <div className="mt-6 grid gap-4 md:grid-cols-2">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre de la prenda</span>
                                <input
                                    aria-invalid={Boolean(nameError)}
                                    onBlur={() => setData('nombre_prenda_pdr', data.nombre_prenda_pdr.trim().replace(/\s+/g, ' '))}
                                    value={data.nombre_prenda_pdr}
                                    onChange={(event) => setData('nombre_prenda_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    maxLength={180}
                                    disabled={processing}
                                />
                                {nameError && <p className="text-xs text-red-300" role="status">{nameError}</p>}
                            </label>

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cantidad afectada</span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    value={data.cantidad_pdr}
                                    onChange={(event) => setData('cantidad_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                />
                                <p className="text-xs leading-5 text-stone-400">{quantityHint}</p>
                                {stockWarning && (
                                    <p className="text-xs leading-5 text-[rgb(245,208,210)]">
                                        La cantidad supera el stock disponible actual de la variante seleccionada.
                                    </p>
                                )}
                            </label>

                            <label className="space-y-2 md:col-span-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descripción de la prenda dañada</span>
                                <textarea
                                    value={data.descripcion_prenda_pdr}
                                    onChange={(event) => setData('descripcion_prenda_pdr', event.target.value)}
                                    className="sa-input min-h-28 w-full resize-none"
                                    disabled={processing}
                                />
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="border-b border-white/8 pb-5">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Clasificación del daño
                            </p>
                        </div>

                        <div className="mt-6 grid gap-4 md:grid-cols-3">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Causa</span>
                                <select
                                    value={data.causa_pdr}
                                    onChange={(event) => setData('causa_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                >
                                    {options.causes.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Tipo de daño</span>
                                <select
                                    value={data.tipo_danio_pdr}
                                    onChange={(event) => setData('tipo_danio_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                >
                                    {options.damageTypes.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Gravedad</span>
                                <select
                                    value={data.severidad_pdr}
                                    onChange={(event) => setData('severidad_pdr', event.target.value)}
                                    className="sa-input w-full"
                                    disabled={processing}
                                >
                                    {options.severities.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="flex flex-col gap-3 border-b border-white/8 pb-5 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                    Vinculación opcional con inventario
                                </p>
                                <p className="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                                    Puedes registrar una prenda dañada sin vincularla al inventario. Si seleccionas una variante existente, el sistema podrá descontarla del stock cuando el daño sea confirmado.
                                </p>
                            </div>
                            {selectedInventory ? (
                                <SuperadminBadge tone={data.afecta_inventario_pdr === '1' ? 'warning' : 'neutral'}>
                                    {data.afecta_inventario_pdr === '1' ? 'Descuento pendiente' : 'Vínculo administrativo'}
                                </SuperadminBadge>
                            ) : (
                                <SuperadminBadge tone="neutral">Administrativo</SuperadminBadge>
                            )}
                        </div>

                        <div className="mt-5 grid gap-3 md:grid-cols-2">
                            <div className={[
                                'rounded-[1.2rem] border p-4',
                                selectedInventory
                                    ? 'border-white/8 bg-[#0d141d]'
                                    : 'border-[rgba(126,154,178,0.34)] bg-[rgba(126,154,178,0.12)]',
                            ].join(' ')}>
                                <p className="text-sm font-medium text-white">Registrar daño sin afectar inventario</p>
                                <p className="mt-2 text-xs leading-5 text-stone-400">
                                    Se guarda como reporte administrativo. No mueve stock y no registra Kardex.
                                </p>
                            </div>
                            <div className={[
                                'rounded-[1.2rem] border p-4',
                                selectedInventory
                                    ? 'border-[rgba(201,160,106,0.34)] bg-[rgba(201,160,106,0.12)]'
                                    : 'border-white/8 bg-[#0d141d]',
                            ].join(' ')}>
                                <p className="text-sm font-medium text-white">Vincular con una prenda existente</p>
                                <p className="mt-2 text-xs leading-5 text-stone-400">
                                    Selecciona una variante para identificar visualmente la prenda y decidir si se quitará del stock al confirmar.
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)]">
                            <div className="space-y-4">
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Prenda / variante existente</span>
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

                                <label className={[
                                    'inline-flex w-full items-start gap-3 rounded-[1.2rem] border p-4',
                                    selectedInventory
                                        ? 'border-[rgba(201,160,106,0.28)] bg-[rgba(201,160,106,0.1)]'
                                        : 'border-white/8 bg-[#0d141d] opacity-80',
                                ].join(' ')}>
                                    <input
                                        type="checkbox"
                                        checked={data.afecta_inventario_pdr === '1'}
                                        onChange={(event) => setData('afecta_inventario_pdr', event.target.checked ? '1' : '0')}
                                        disabled={processing || !selectedInventory}
                                        className="mt-1 rounded border-white/20 bg-[#090d13]"
                                    />
                                    <span>
                                        <span className="block text-sm font-medium text-white">Quitar del stock al confirmar el daño</span>
                                        <span className="mt-1 block text-xs leading-5 text-stone-400">
                                            Crear el reporte no mueve inventario. El stock se descontará únicamente si el superadmin confirma el daño.
                                        </span>
                                        {!selectedInventory && (
                                            <span className="mt-2 block text-xs leading-5 text-stone-400">
                                                Selecciona una variante existente para poder descontar stock.
                                            </span>
                                        )}
                                    </span>
                                </label>

                                {selectedInventory && (
                                    <div className="grid gap-3 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 sm:grid-cols-2">
                                        <Detail label="Stock disponible actual" value={`${selectedInventory.availableStock} unidades`} />
                                        <Detail label="Cantidad que se quitará al confirmar" value={data.afecta_inventario_pdr === '1' ? `${quantity} unidades` : 'No se quitará stock'} />
                                        <Detail label="Stock comprometido actual" value={`${selectedInventory.committedStock} unidades`} />
                                        <Detail label="Regla aplicada" value="cdi_inv y cre_inv no se mezclan" />
                                    </div>
                                )}
                            </div>

                            <div className="overflow-hidden rounded-[1.2rem] border border-white/8 bg-[#0d141d]">
                                {selectedInventory ? (
                                    <>
                                        {selectedInventory.imageUrl ? (
                                            <img
                                                src={selectedInventory.imageUrl}
                                                alt={selectedInventory.productName}
                                                className="h-72 w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-72 items-center justify-center bg-[#090d13] px-4 text-center text-sm text-stone-500">
                                                Sin imagen registrada
                                            </div>
                                        )}
                                        <div className="p-4">
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-base font-medium text-white">{selectedInventory.productName}</p>
                                                    <p className="mt-1 text-sm text-stone-400">{selectedInventory.sku}</p>
                                                </div>
                                                <SuperadminBadge tone={selectedInventory.active ? 'success' : 'neutral'}>
                                                    {selectedInventory.statusLabel}
                                                </SuperadminBadge>
                                            </div>
                                            <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                                                <Detail label="Color" value={selectedInventory.color} />
                                                <Detail label="Talla" value={selectedInventory.size} />
                                                <Detail label="Disponible" value={String(selectedInventory.availableStock)} />
                                                <Detail label="Comprometido" value={String(selectedInventory.committedStock)} />
                                                <Detail label="Ubicación" value={selectedInventory.location} />
                                            </dl>
                                        </div>
                                    </>
                                ) : (
                                    <div className="flex min-h-36 items-center justify-center text-center text-sm leading-6 text-stone-400">
                                        Sin imagen registrada. El reporte se guardará como control administrativo sin mover inventario.
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="border-b border-white/8 pb-5">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Evidencia y observaciones
                            </p>
                        </div>

                        <div className="mt-6 grid gap-4 lg:grid-cols-2">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Imágenes</span>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => setData('imagenes', Array.from(event.target.files ?? []))}
                                    className="sa-input w-full file:mr-4 file:rounded-full file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.18em] file:text-stone-200"
                                    disabled={processing}
                                />
                                {data.imagenes.length > 0 && (
                                    <div className="flex flex-wrap gap-2 pt-2">
                                        {data.imagenes.map((file) => (
                                            <SuperadminBadge key={`${file.name}-${file.size}`} tone="accent">
                                                {file.name}
                                            </SuperadminBadge>
                                        ))}
                                    </div>
                                )}
                            </label>

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Observación</span>
                                <textarea
                                    value={data.observacion_pdr}
                                    onChange={(event) => setData('observacion_pdr', event.target.value)}
                                    className="sa-input min-h-32 w-full resize-none"
                                    disabled={processing}
                                />
                            </label>
                        </div>
                    </section>

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Link href={meta.indexHref} className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || Boolean(nameError)}
                                className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {processing ? <ImagePlus size={15} /> : <Save size={15} />}
                                {processing ? 'Guardando' : 'Guardar reporte'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </SuperadminLayout>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-[0.68rem] uppercase tracking-[0.16em] text-stone-400">{label}</dt>
            <dd className="mt-2 break-words text-sm text-white">{value}</dd>
        </div>
    );
}
