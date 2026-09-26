import { operationToken, completeOperation, renewOperation } from '@/lib/commercialOperation';
import OperationRecovery from '@/Components/shop/OperationRecovery';
import { commercialItemErrors, decimalAmount, discountError, reservationPayment, voucherError } from '@/lib/commercialValidation';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import { ONLINE_PAYMENT_METHOD_OPTIONS } from '@/config/paymentMethods';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminResourceMeta } from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, type FormEvent } from 'react';

type Option = {
    label: string;
    value: string;
};

type VariantOption = Option & {
    price: number;
    stock: number;
    hasInventory: boolean;
};

type SaleItem = {
    cod_prenda_variantes_dve: string;
    can_dve: string;
};

type SaleFormData = {
    operation_id: string;
    cod_usuario_cliente_ven: string;
    tiv_ven: string;
    mpa_ven: string;
    des_ven: string;
    sale_items: SaleItem[];
};

type SaleCreateProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    options: {
        customers: Option[];
        seller: { id: string; name: string };
        types: Option[];
        paymentMethods: Option[];
        variants: VariantOption[];
    };
    submitAction: string;
};

const money = new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
});

function blankItem(): SaleItem {
    return { cod_prenda_variantes_dve: '', can_dve: '1' };
}

export default function SaleCreate({ meta, breadcrumbs, options, submitAction }: SaleCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const { data, setData, post, processing, errors, clearErrors } = useForm<SaleFormData>({
        operation_id: operationToken(),
        cod_usuario_cliente_ven: '',
        tiv_ven: 'fisica',
        mpa_ven: 'efectivo',
        des_ven: '0',
        sale_items: [blankItem()],
    });

    const variantsById = useMemo(
        () => new Map(options.variants.map((variant) => [variant.value, variant])),
        [options.variants],
    );
    const visiblePaymentMethods = data.tiv_ven === 'online'
        ? ONLINE_PAYMENT_METHOD_OPTIONS
        : options.paymentMethods;
    const totals = useMemo(() => {
        const subtotal = Math.round(data.sale_items.reduce((sum, item) => {
            const variant = variantsById.get(item.cod_prenda_variantes_dve);
            const quantity = Number(item.can_dve) || 0;

            return sum + ((variant?.price ?? 0) * quantity);
        }, 0) * 100) / 100;
        const discount = decimalAmount(data.des_ven);

        return {
            subtotal,
            discount,
            total: Math.max(0, subtotal - discount),
        };
    }, [data.des_ven, data.sale_items, variantsById]);

    const itemErrors = commercialItemErrors(data.sale_items.map(item => ({ variantId: item.cod_prenda_variantes_dve, quantity: item.can_dve })), variantsById);
    const discountMessage = discountError(data.des_ven, totals.subtotal);
    const formInvalid = itemErrors.some(Boolean) || Boolean(discountMessage) || !options.types.some(option => option.value === data.tiv_ven) || !visiblePaymentMethods.some(option => option.value === data.mpa_ven);

    const updateItem = (index: number, field: keyof SaleItem, value: string) => {
        setData('sale_items', data.sale_items.map((item, currentIndex) => (
            currentIndex === index ? { ...item, [field]: value } : item
        )));
    };

    const removeItem = (index: number) => {
        if (data.sale_items.length === 1) {
            setData('sale_items', [blankItem()]);
            return;
        }

        setData('sale_items', data.sale_items.filter((_, currentIndex) => currentIndex !== index));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (formInvalid) return;
        post(submitAction, { onSuccess: () => completeOperation(data.operation_id),
            preserveScroll: true,
        });
    };

    return (
        <SuperadminLayout title="Mati Boutique | Nueva venta">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title="Nueva venta"
                    description="Registra ventas presenciales con pago confirmado y descuento de inventario."
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {Object.keys(errors).length > 0 && (
                    <section className="rounded-[1.4rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[rgb(245,208,210)]">
                            Revisa los datos antes de registrar la venta.
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
                                Datos comerciales
                            </p>
                        </div>
                        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cliente</span>
                                <select
                                    className="sa-input w-full"
                                    value={data.cod_usuario_cliente_ven}
                                    onChange={(event) => setData('cod_usuario_cliente_ven', event.target.value)}
                                    disabled={processing}
                                >
                                    <option value="">Venta sin cliente</option>
                                    {options.customers.map((customer) => (
                                        <option key={customer.value} value={customer.value}>{customer.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Vendedor</span>
                                <input className="sa-input w-full" value={options.seller.name} disabled />
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Tipo</span>
                                <select
                                    className="sa-input w-full"
                                    value={data.tiv_ven}
                                    onChange={(event) => {
                                        const nextType = event.target.value;
                                        setData('tiv_ven', nextType);
                                        if (nextType === 'online') {
                                            setData('mpa_ven', ONLINE_PAYMENT_METHOD_OPTIONS[0].value);
                                        }
                                    }}
                                    disabled={processing}
                                >
                                    {options.types.map((type) => (
                                        <option key={type.value} value={type.value}>{type.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Método de pago</span>
                                <select className="sa-input w-full" value={data.mpa_ven} onChange={(event) => setData('mpa_ven', event.target.value)} disabled={processing}>
                                    {visiblePaymentMethods.map((method) => (
                                        <option key={method.value} value={method.value}>{method.label}</option>
                                    ))}
                                </select>
                                {data.tiv_ven === 'online' && (
                                    <p className="text-xs leading-5 text-stone-400">
                                        La venta online queda en revisión y no descuenta inventario hasta confirmarse.
                                    </p>
                                )}
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="flex flex-col gap-4 border-b border-white/8 pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Items vendidos
                            </p>
                            <button
                                type="button"
                                className="sa-button-secondary inline-flex items-center gap-2 px-4 py-2 text-[0.72rem] uppercase tracking-[0.18em]"
                                onClick={() => setData('sale_items', [...data.sale_items, blankItem()])}
                                disabled={processing}
                            >
                                <Plus size={14} />
                                Agregar
                            </button>
                        </div>

                        <div className="mt-5 space-y-3">
                            {data.sale_items.map((item, index) => {
                                const variant = variantsById.get(item.cod_prenda_variantes_dve);
                                const quantity = Number(item.can_dve) || 0;
                                const lineTotal = (variant?.price ?? 0) * quantity;
                                const stockWarning = itemErrors[index];

                                return (
                                    <div key={index} className="grid gap-3 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 lg:grid-cols-[minmax(0,1fr)_8rem_8rem_8rem_3rem] lg:items-end">
                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Variante</span>
                                            <select
                                                className="sa-input w-full"
                                                value={item.cod_prenda_variantes_dve}
                                                onChange={(event) => updateItem(index, 'cod_prenda_variantes_dve', event.target.value)}
                                                disabled={processing}
                                            >
                                                <option value="">Seleccionar variante</option>
                                                {options.variants.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label} - {money.format(option.price)} - Stock {option.stock}
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
                                                value={item.can_dve}
                                                onChange={(event) => updateItem(index, 'can_dve', event.target.value)}
                                                disabled={processing}
                                            />
                                        </label>
                                        <div className="space-y-2">
                                            <span className="block text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Unitario</span>
                                            <p className="sa-input flex items-center">{money.format(variant?.price ?? 0)}</p>
                                        </div>
                                        <div className="space-y-2">
                                            <span className="block text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Subtotal</span>
                                            <p className="sa-input flex items-center">{money.format(lineTotal)}</p>
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
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Subtotal</p>
                                <p className="mt-3 text-2xl text-white">{money.format(totals.subtotal)}</p>
                            </div>
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descuento general</span>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    className="sa-input w-full"
                                    max={totals.subtotal} aria-invalid={Boolean(discountMessage)} value={data.des_ven}
                                    onChange={(event) => setData('des_ven', event.target.value)}
                                    disabled={processing}
                                />
                                <p className="text-xs text-stone-300">Máximo: Bs {totals.subtotal.toFixed(2)}</p>
                                {discountMessage && <p className="text-xs text-red-300" role="status">{discountMessage}</p>}
                            </label>
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descuento aplicado</p>
                                <p className="mt-3 text-2xl text-white">{money.format(totals.discount)}</p>
                            </div>
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Total</p>
                                <p className="mt-3 text-2xl text-white">{money.format(totals.total)}</p>
                            </div>
                        </div>
                    </section>

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Link href={meta.indexHref} className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]">
                                Cancelar
                            </Link>
                            <button type="submit" disabled={processing || formInvalid} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">
                                {processing ? 'Registrando' : 'Registrar venta'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </SuperadminLayout>
    );
}
