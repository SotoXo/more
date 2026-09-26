import { operationToken, completeOperation, renewOperation } from '@/lib/commercialOperation';
import OperationRecovery from '@/Components/shop/OperationRecovery';
import { commercialItemErrors, decimalAmount, discountError, reservationPayment, voucherError } from '@/lib/commercialValidation';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminResourceMeta } from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import { CalendarDays, Plus, Receipt, Trash2 } from 'lucide-react';
import { useMemo, type FormEvent } from 'react';

type Option = { label: string; value: string };
type VariantOption = Option & {
    stock: number;
    reserved: number;
    price: number;
    location: string;
    hasInventory: boolean;
};
type ReservationItem = {
    cod_prenda_variantes_dre: string;
    can_dre: string;
};
type ReservationFormData = {
    operation_id: string;
    cod_usuario_cliente_res: string;
    des_res: string;
    monto_declarado_rpg: string;
    metodo_pago_rpg: string;
    comprobante_rpg: File | null;
    obs_res: string;
    reservation_items: ReservationItem[];
};
type ReservationCreateProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    options: {
        customers: Option[];
        responsible: { id: string; name: string };
        paymentMethods: Option[];
        variants: VariantOption[];
    };
    submitAction: string;
};

function blankItem(): ReservationItem {
    return { cod_prenda_variantes_dre: '', can_dre: '1' };
}

export default function ReservationCreate({ meta, breadcrumbs, options, submitAction }: ReservationCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const { data, setData, post, processing, errors, clearErrors } = useForm<ReservationFormData>({
        operation_id: operationToken(),
        cod_usuario_cliente_res: '',
        des_res: '0',
        monto_declarado_rpg: '',
        metodo_pago_rpg: options.paymentMethods[0]?.value ?? 'qr',
        comprobante_rpg: null,
        obs_res: '',
        reservation_items: [blankItem()],
    });
    const variantsById = useMemo(
        () => new Map(options.variants.map((variant) => [variant.value, variant])),
        [options.variants],
    );
    const totalUnits = data.reservation_items.reduce((sum, item) => sum + (Number(item.can_dre) || 0), 0);
    const subtotal = Math.round(data.reservation_items.reduce((sum, item) => {
        const variant = variantsById.get(item.cod_prenda_variantes_dre);
        return sum + ((variant?.price ?? 0) * (Number(item.can_dre) || 0));
    }, 0) * 100) / 100;
    const discount = decimalAmount(data.des_res);
    const total = Math.max(0, subtotal - discount);
    const payment = reservationPayment(data.monto_declarado_rpg, total);
    const minimum = payment.minimumPayment;
    const hasDeposit = data.reservation_items.some((item) => variantsById.get(item.cod_prenda_variantes_dre)?.location === 'DEPOSITO');
    const pickupLabel = hasDeposit ? 'Disponible en 3 días' : 'Disponible hoy';
    const money = (value: number) => new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(value).replace('BOB', 'Bs');

    const itemErrors = commercialItemErrors(data.reservation_items.map(item => ({ variantId: item.cod_prenda_variantes_dre, quantity: item.can_dre })), variantsById);
    const discountMessage = discountError(data.des_res, subtotal);
    const proofMessage = voucherError(data.comprobante_rpg);
    const formInvalid = itemErrors.some(Boolean) || Boolean(discountMessage) || !payment.isPaymentValid || Boolean(proofMessage) || !options.customers.some(option => option.value === data.cod_usuario_cliente_res) || !options.paymentMethods.some(option => option.value === data.metodo_pago_rpg);

    const updateItem = (index: number, field: keyof ReservationItem, value: string) => {
        setData('reservation_items', data.reservation_items.map((item, currentIndex) => (
            currentIndex === index ? { ...item, [field]: value } : item
        )));
    };

    const removeItem = (index: number) => {
        if (data.reservation_items.length === 1) {
            setData('reservation_items', [blankItem()]);
            return;
        }

        setData('reservation_items', data.reservation_items.filter((_, currentIndex) => currentIndex !== index));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (formInvalid) return;
        post(submitAction, { onSuccess: () => completeOperation(data.operation_id), preserveScroll: true, forceFormData: true });
    };

    return (
        <SuperadminLayout title="Mati Boutique | Nueva reserva">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title="Nueva reserva"
                    description="Separa stock temporalmente moviendo disponible a reservado."
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {Object.keys(errors).length > 0 && (
                    <section className="rounded-[1.4rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[rgb(245,208,210)]">
                            Revisa los datos antes de registrar la reserva.
                        </p>
                        <ul className="mt-3 space-y-1 text-sm text-[rgb(245,208,210)]">
                            {Object.entries(errors).map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </section>
                )}

                <OperationRecovery error={errors.operation_id} onRenew={() => { setData('operation_id', renewOperation(data.operation_id)); clearErrors('operation_id'); }} />

                <form onSubmit={submit} className="space-y-5">
                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="border-b border-white/8 pb-5">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Cabecera
                            </p>
                        </div>
                        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cliente</span>
                                <select
                                    className="sa-input w-full"
                                    value={data.cod_usuario_cliente_res}
                                    onChange={(event) => setData('cod_usuario_cliente_res', event.target.value)}
                                    disabled={processing}
                                >
                                    <option value="">Seleccionar cliente</option>
                                    {options.customers.map((customer) => (
                                        <option key={customer.value} value={customer.value}>{customer.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Responsable</span>
                                <input className="sa-input w-full" value={options.responsible.name} disabled />
                            </label>
                            <div className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Fecha estimada de recojo</span>
                                <div className="flex items-center gap-2 rounded-[1rem] border border-white/8 bg-[#0d141d] px-4 py-3 text-sm text-white">
                                    <CalendarDays size={15} />
                                    {pickupLabel}
                                </div>
                            </div>
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Unidades</p>
                                <p className="mt-3 text-2xl text-white">{totalUnits}</p>
                            </div>
                        </div>
                        <label className="mt-4 block space-y-2">
                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Observacion</span>
                            <textarea
                                className="sa-input min-h-[120px] w-full px-4 py-3"
                                value={data.obs_res}
                                onChange={(event) => setData('obs_res', event.target.value)}
                                disabled={processing}
                                placeholder="Detalle opcional para seguimiento interno"
                            />
                        </label>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="flex flex-col gap-4 border-b border-white/8 pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Items reservados
                            </p>
                            <button
                                type="button"
                                className="sa-button-secondary inline-flex items-center gap-2 px-4 py-2 text-[0.72rem] uppercase tracking-[0.18em]"
                                onClick={() => setData('reservation_items', [...data.reservation_items, blankItem()])}
                                disabled={processing}
                            >
                                <Plus size={14} />
                                Agregar
                            </button>
                        </div>

                        <div className="mt-5 space-y-3">
                            {data.reservation_items.map((item, index) => {
                                const variant = variantsById.get(item.cod_prenda_variantes_dre);
                                const quantity = Number(item.can_dre) || 0;
                                const stockWarning = itemErrors[index];

                                return (
                                    <div key={index} className="grid gap-3 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 lg:grid-cols-[minmax(0,1fr)_8rem_8rem_8rem_3rem] lg:items-end">
                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Variante</span>
                                            <select
                                                className="sa-input w-full"
                                                value={item.cod_prenda_variantes_dre}
                                                onChange={(event) => updateItem(index, 'cod_prenda_variantes_dre', event.target.value)}
                                                disabled={processing}
                                            >
                                                <option value="">Seleccionar variante</option>
                                                {options.variants.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label} - Bs {option.price.toFixed(2)} - Disponible {option.stock} - {option.location}
                                                    </option>
                                                ))}
                                            </select>
                                            {stockWarning && (
                                                <p className="text-xs text-[rgb(245,208,210)]">
                                                    {stockWarning}
                                                </p>
                                            )}
                                        </label>
                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cantidad</span>
                                            <input
                                                type="number"
                                                min="1"
                                                max={variant?.stock ?? 0}
                                                aria-invalid={Boolean(itemErrors[index])}
                                                step="1"
                                                className="sa-input w-full"
                                                value={item.can_dre}
                                                onChange={(event) => updateItem(index, 'can_dre', event.target.value)}
                                                disabled={processing}
                                            />
                                        </label>
                                        <div>
                                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Disponible</p>
                                            <p className="mt-3 text-lg text-white">{variant?.stock ?? 0}</p>
                                        </div>
                                        <div>
                                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Subtotal</p>
                                            <p className="mt-3 text-lg text-white">{money((variant?.price ?? 0) * quantity)}</p>
                                        </div>
                                        <button
                                            type="button"
                                            className="sa-button-ghost h-11 w-11 p-0"
                                            onClick={() => removeItem(index)}
                                            disabled={processing}
                                            aria-label="Quitar item"
                                        >
                                            <Trash2 size={15} />
                                        </button>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="flex items-center gap-3 border-b border-white/8 pb-5">
                            <Receipt size={18} className="text-[rgb(194,214,232)]" />
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Comprobante y anticipo</p>
                        </div>
                        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Subtotal</p>
                                <p className="mt-3 text-xl text-white">{money(subtotal)}</p>
                            </div>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descuento</span>
                                <input type="number" min="0" step="0.01" className="sa-input w-full" max={subtotal} aria-invalid={Boolean(discountMessage)} value={data.des_res} onChange={(event) => setData('des_res', event.target.value)} disabled={processing} />
                                <p className="text-xs text-stone-300">Máximo: Bs {subtotal.toFixed(2)}</p>
                                {discountMessage && <p className="text-xs text-red-300" role="status">{discountMessage}</p>}
                            </label>
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Total</p>
                                <p className="mt-3 text-xl text-white">{money(total)}</p>
                            </div>
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Anticipo mínimo requerido</p>
                                <p className="mt-3 text-xl text-white">{money(minimum)}</p>
                            </div>
                        </div>
                        <div className="mt-5 grid gap-4 md:grid-cols-3">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Monto declarado</span>
                                <input type="number" min={payment.minimumPayment} max={payment.maximumPayment} aria-invalid={!payment.isPaymentValid} step="0.01" className="sa-input w-full" value={data.monto_declarado_rpg} onChange={(event) => setData('monto_declarado_rpg', event.target.value)} disabled={processing} />
                                {payment.message && <p className="text-xs text-red-300" role="status">{payment.message}</p>}
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Método de pago</span>
                                <select className="sa-input w-full" value={data.metodo_pago_rpg} onChange={(event) => setData('metodo_pago_rpg', event.target.value)} disabled={processing}>
                                    {options.paymentMethods.map((method) => (
                                        <option key={method.value} value={method.value}>{method.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Comprobante de pago</span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" className="sa-input w-full" onChange={(event) => setData('comprobante_rpg', event.target.files?.[0] ?? null)} disabled={processing} />
                                {proofMessage && <p className="text-xs text-red-300">{proofMessage}</p>}
                            </label>
                        </div>
                        <div className="mt-5 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 text-sm text-stone-300">
                            <p>Punto de recojo: Recojo en tienda Mati Boutique.</p>
                            <p className="mt-1">Si alguna prenda está en depósito, toda la reserva queda disponible en 3 días y el límite se calcula 30 días después.</p>
                        </div>
                    </section>

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Link href={meta.indexHref} className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]">
                                Cancelar
                            </Link>
                            <button type="submit" disabled={processing || formInvalid} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">
                                {processing ? 'Registrando' : 'Registrar reserva'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </SuperadminLayout>
    );
}
