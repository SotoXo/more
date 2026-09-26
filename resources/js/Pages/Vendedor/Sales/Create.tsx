import { commercialItemErrors, decimalAmount, reservationPayment } from '@/lib/commercialValidation';
import { operationToken, completeOperation, renewOperation } from '@/lib/commercialOperation';
import OperationRecovery from '@/Components/shop/OperationRecovery';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';

type Option = {
    label: string;
    value: string;
};

type VariantOption = {
    id: string;
    label: string;
    productName: string;
    sku: string;
    color: string;
    size: string;
    price: number;
    stock: number;
    committed: number;
    location: string;
    image?: string | null;
    hasInventory: boolean;
};

type SaleItem = {
    variant_id: string;
    quantity: string;
};

type SaleFormData = {
    operation_id: string;
    customer_id: string;
    payment_method: string;
    amount_paid: string;
    voucher: File | null;
    sale_items: SaleItem[];
};

type SaleCreateProps = {
    options: {
        seller: { id: string; name: string };
        customers: Option[];
        paymentMethods: Option[];
        variants: VariantOption[];
    };
    submitAction: string;
};

const money = new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
});

function variantMatches(variant: VariantOption, search: string) {
    const needle = search.trim().toLowerCase();

    if (!needle) {
        return true;
    }

    return [
        variant.productName,
        variant.sku,
        variant.color,
        variant.size,
        variant.location,
    ].some((value) => value.toLowerCase().includes(needle));
}

function ImageBox({ image, alt }: { image?: string | null; alt: string }) {
    if (image) {
        return <img src={image} alt={alt} className="h-16 w-14 shrink-0 rounded-xl object-cover" />;
    }

    return (
        <span className="flex h-16 w-14 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#090d13] text-center text-[0.55rem] uppercase leading-3 text-[color:var(--admin-muted)]">
            Sin
            <br />
            imagen
        </span>
    );
}

export default function Create({ options, submitAction }: SaleCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const [search, setSearch] = useState('');
    const [clientNotice, setClientNotice] = useState<string | null>(null);
    const { data, setData, post, processing, errors, clearErrors } = useForm<SaleFormData>({
        operation_id: operationToken(),
        customer_id: '',
        payment_method: 'efectivo',
        amount_paid: '0.00',
        voucher: null,
        sale_items: [],
    });

    const variantsById = useMemo(
        () => new Map(options.variants.map((variant) => [variant.id, variant])),
        [options.variants],
    );

    const filteredVariants = useMemo(
        () => options.variants.filter((variant) => variantMatches(variant, search)).slice(0, 18),
        [options.variants, search],
    );

    const totals = useMemo(() => {
        const subtotal = data.sale_items.reduce((sum, item) => {
            const variant = variantsById.get(item.variant_id);
            const quantity = Number(item.quantity) || 0;

            return sum + ((variant?.price ?? 0) * quantity);
        }, 0);

        return {
            subtotal,
            total: subtotal,
        };
    }, [data.sale_items, variantsById]);

    const requiresVoucher = ['qr', 'transferencia_bancaria'].includes(data.payment_method);
    const itemErrors = commercialItemErrors(data.sale_items.map(item => ({ variantId: item.variant_id, quantity: item.quantity })), variantsById);
    const hasInvalidItems = itemErrors.some(Boolean);

    const hasInvalidPayment = !Number.isFinite(decimalAmount(data.amount_paid)) || decimalAmount(data.amount_paid) !== totals.total || totals.total <= 0;

    useEffect(() => {
        setData('amount_paid', totals.total.toFixed(2));
    }, [totals.total]);

    const addVariant = (variant: VariantOption) => {
        if (variant.stock <= 0) {
            setClientNotice('Esta variante no tiene stock disponible.');
            return;
        }

        const existing = data.sale_items.find((item) => item.variant_id === variant.id);

        if (existing) {
            const nextQuantity = Math.min(Number(existing.quantity || 0) + 1, variant.stock);
            setData('sale_items', data.sale_items.map((item) => (
                item.variant_id === variant.id ? { ...item, quantity: String(nextQuantity) } : item
            )));
            setClientNotice(null);
            return;
        }

        setData('sale_items', [...data.sale_items, { variant_id: variant.id, quantity: '1' }]);
        setClientNotice(null);
    };

    const updateQuantity = (variantId: string, quantity: string) => {
        setData('sale_items', data.sale_items.map((item) => (
            item.variant_id === variantId ? { ...item, quantity } : item
        )));
    };

    const removeItem = (variantId: string) => {
        setData('sale_items', data.sale_items.filter((item) => item.variant_id !== variantId));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (data.sale_items.length === 0) {
            setClientNotice('Agrega al menos un producto a la venta.');
            return;
        }

        if (hasInvalidItems || hasInvalidPayment) {
            setClientNotice('Revisa las cantidades. No pueden superar el stock disponible.');
            return;
        }

        if (requiresVoucher && !data.voucher) {
            setClientNotice('Sube el comprobante para QR o transferencia.');
            return;
        }

        post(submitAction, { onSuccess: () => completeOperation(data.operation_id),
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <VendedorLayout title="Mati Boutique | Nueva venta">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="max-w-4xl">
                        <Link href="/vendedor/ventas" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                            <ArrowLeft size={15} />
                            Ventas
                        </Link>
                        <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                            Venta directa
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Nueva venta
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            Los pagos registrados por vendedor se confirmarán automáticamente.
                        </p>
                    </div>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {(clientNotice || Object.keys(errors).length > 0) && (
                    <section className="rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4 text-sm leading-6 text-[rgb(245,208,210)]">
                        {clientNotice && <p>{clientNotice}</p>}
                        {Object.entries(errors).map(([field, message]) => (
                            <p key={field}>{message}</p>
                        ))}
                    </section>
                )}

                <OperationRecovery error={errors.operation_id} onRenew={() => { setData('operation_id', renewOperation(data.operation_id)); clearErrors('operation_id'); }} />

                <form onSubmit={submit} className="space-y-6">
                    <section className="sa-panel rounded-2xl p-5">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Cliente</span>
                                <select
                                    className="sa-input min-h-11 px-3 text-sm"
                                    value={data.customer_id}
                                    onChange={(event) => setData('customer_id', event.target.value)}
                                    disabled={processing}
                                >
                                    <option value="">Venta sin cliente</option>
                                    {options.customers.map((customer) => (
                                        <option key={customer.value} value={customer.value}>{customer.label}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Vendedor</span>
                                <input className="sa-input min-h-11 px-3 text-sm" value={options.seller.name} disabled />
                            </label>
                            <label className="space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Método de pago</span>
                                <select
                                    className="sa-input min-h-11 px-3 text-sm"
                                    value={data.payment_method}
                                    onChange={(event) => {
                                        setData('payment_method', event.target.value);
                                        setData('voucher', null);
                                    }}
                                    disabled={processing}
                                >
                                    {options.paymentMethods.map((method) => (
                                        <option key={method.value} value={method.value}>{method.label}</option>
                                    ))}
                                </select>
                            </label>
                        </div>
                    </section>

                    <section className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)]">
                        <div className="space-y-4">
                            <div className="sa-panel rounded-2xl p-4">
                                <label className="flex items-center gap-3">
                                    <Search size={18} className="text-[color:var(--admin-muted)]" />
                                    <input
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Buscar por prenda, SKU, color, talla o ubicacion"
                                        className="min-h-11 flex-1 border-0 bg-transparent text-sm text-[color:var(--admin-text)] outline-none placeholder:text-[color:var(--admin-muted)]"
                                    />
                                </label>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {filteredVariants.map((variant) => (
                                    <article key={variant.id} className="sa-dashboard-card rounded-2xl">
                                        <div className="flex gap-3">
                                            <ImageBox image={variant.image} alt={variant.productName} />
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0">
                                                        <p className="line-clamp-2 text-sm font-semibold text-[color:var(--admin-text)]">{variant.productName}</p>
                                                        <p className="mt-1 text-xs text-[color:var(--admin-muted)]">{variant.sku}</p>
                                                    </div>
                                                    <SuperadminBadge tone={variant.stock > 0 ? 'success' : 'danger'}>
                                                        {variant.stock}
                                                    </SuperadminBadge>
                                                </div>
                                                <p className="mt-2 text-xs text-[color:var(--admin-muted)]">
                                                    {variant.color} / {variant.size} / {variant.location}
                                                </p>
                                                <div className="mt-3 flex items-center justify-between gap-3">
                                                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">{money.format(variant.price)}</p>
                                                    <button
                                                        type="button"
                                                        onClick={() => addVariant(variant)}
                                                        disabled={processing || variant.stock <= 0 || Number(data.sale_items.find(item => item.variant_id === variant.id)?.quantity ?? 0) >= variant.stock}
                                                        className="sa-button-secondary h-9 px-3 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        <Plus size={14} />
                                                        Agregar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </div>

                        <aside className="space-y-4">
                            <section className="sa-panel rounded-2xl p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">Carrito de venta</p>
                                        <p className="mt-1 text-xs text-[color:var(--admin-muted)]">Stock mostrado = stock disponible.</p>
                                    </div>
                                    <SuperadminBadge tone={data.sale_items.length > 0 ? 'accent' : 'neutral'}>
                                        {data.sale_items.length} items
                                    </SuperadminBadge>
                                </div>

                                <div className="mt-4 space-y-3">
                                    {data.sale_items.length === 0 && (
                                        <div className="rounded-2xl border border-dashed border-[color:var(--admin-border)] p-4 text-sm text-[color:var(--admin-muted)]">
                                            Agrega productos desde el buscador.
                                        </div>
                                    )}
                                    {data.sale_items.map((item) => {
                                        const variant = variantsById.get(item.variant_id);
                                        const quantity = Number(item.quantity) || 0;
                                        const lineTotal = (variant?.price ?? 0) * quantity;
                                        const invalid = !variant || !/^\d+$/.test(item.quantity) || !Number.isSafeInteger(quantity) || quantity <= 0 || quantity > variant.stock;

                                        return (
                                            <div key={item.variant_id} className="rounded-2xl border border-white/8 bg-[#0d141d] p-4">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="line-clamp-2 text-sm font-semibold text-[color:var(--admin-text)]">{variant?.productName}</p>
                                                        <p className="mt-1 text-xs text-[color:var(--admin-muted)]">{variant?.sku} / stock {variant?.stock ?? 0}</p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem(item.variant_id)}
                                                        className="sa-button-ghost h-9 w-9 p-0"
                                                        aria-label="Quitar producto"
                                                    >
                                                        <Trash2 size={14} />
                                                    </button>
                                                </div>
                                                <div className="mt-3 grid grid-cols-[1fr_1fr] gap-3">
                                                    <label className="space-y-1">
                                                        <span className="text-xs text-[color:var(--admin-muted)]">Cantidad</span>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            aria-invalid={invalid}
                                                            max={variant?.stock ?? 1}
                                                            value={item.quantity}
                                                            onChange={(event) => updateQuantity(item.variant_id, event.target.value)}
                                                            className="sa-input min-h-10 px-3 text-sm"
                                                        />
                                                    </label>
                                                    <div>
                                                        <p className="text-xs text-[color:var(--admin-muted)]">Subtotal</p>
                                                        <p className="mt-2 text-sm font-semibold text-[color:var(--admin-text)]">{money.format(lineTotal)}</p>
                                                    </div>
                                                </div>
                                                {invalid && (
                                                    <p className="mt-2 text-xs text-[rgb(245,208,210)]">
                                                        Cantidad invalida o mayor al stock disponible.
                                                    </p>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </section>

                            <section className="sa-panel rounded-2xl p-5">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-sm text-[color:var(--admin-muted)]">Total</span>
                                        <span className="text-2xl font-semibold text-[color:var(--admin-text)]">{money.format(totals.total)}</span>
                                    </div>
                                    <label className="space-y-2">
                                        <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Monto pagado</span>
                                        <input
                                            type="number"
                                            min={totals.total} max={totals.total} aria-invalid={hasInvalidPayment}
                                            step="0.01"
                                            value={data.amount_paid}
                                            onChange={(event) => setData('amount_paid', event.target.value)}
                                            className="sa-input min-h-11 px-3 text-sm"
                                        />
                                        {hasInvalidPayment && <p className="text-xs text-red-300" role="status">El pago debe coincidir con el total y tener hasta dos decimales.</p>}
                                    </label>
                                    {requiresVoucher && (
                                        <label className="space-y-2">
                                            <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Comprobante</span>
                                            <input
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                                onChange={(event) => setData('voucher', event.target.files?.[0] ?? null)}
                                                className="sa-input min-h-11 px-3 py-2 text-sm"
                                            />
                                        </label>
                                    )}
                                    <div className="rounded-2xl border border-[rgba(143,200,173,0.28)] bg-[rgba(143,200,173,0.1)] p-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                                        El pago registrado por vendedor quedará confirmado automáticamente. QR y transferencia no pasan por revisión admin en esta fase.
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={processing || data.sale_items.length === 0 || hasInvalidItems || hasInvalidPayment || (requiresVoucher && !data.voucher)}
                                        className="sa-button-primary h-12 w-full text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Registrando venta' : 'Guardar venta'}
                                    </button>
                                </div>
                            </section>
                        </aside>
                    </section>
                </form>
            </div>
        </VendedorLayout>
    );
}
