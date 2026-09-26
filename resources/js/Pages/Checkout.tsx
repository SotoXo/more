import { decimalAmount } from '@/lib/commercialValidation';
import { operationToken, completeOperation, renewOperation } from '@/lib/commercialOperation';
import OperationRecovery from '@/Components/shop/OperationRecovery';
import StoreLayout from '@/Components/shop/StoreLayout';
import { ONLINE_PAYMENT_METHOD_OPTIONS, PAYMENT_METHODS } from '@/config/paymentMethods';
import {
    clearPurchaseCart,
    PurchaseCartItem,
    purchaseCartTotal,
    readPurchaseCart,
} from '@/lib/purchaseCart';
import { formatPrice, shopRoutes } from '@/lib/shop';
import { Link, router, usePage } from '@inertiajs/react';
import { ShoppingBag, UploadCloud } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

export default function Checkout() {
    const page = usePage();
    const [operationId, setOperationId] = useState(operationToken);
    const [operationErrorDismissed, setOperationErrorDismissed] = useState(false);
    const serverErrors = (page.props.errors ?? {}) as Record<string, string>;
    const [items, setItems] = useState<PurchaseCartItem[]>([]);
    const [method, setMethod] = useState<'qr' | 'transferencia_bancaria'>('qr');
    const [declaredAmount, setDeclaredAmount] = useState('');
    const [voucher, setVoucher] = useState<File | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const total = useMemo(() => Number(purchaseCartTotal(items).toFixed(2)), [items]);

    useEffect(() => {
        const cartItems = readPurchaseCart();
        setItems(cartItems);
        setDeclaredAmount(purchaseCartTotal(cartItems).toFixed(2));
    }, []);

    useEffect(() => {
        setDeclaredAmount(total > 0 ? total.toFixed(2) : '');
    }, [total]);

    function validateCheckout() {
        const nextErrors: Record<string, string> = {};
        const amount = decimalAmount(declaredAmount);

        if (items.length === 0 || items.some(item => !Number.isSafeInteger(item.quantity) || item.quantity < 1 || item.quantity > item.stock)) {
            nextErrors.sale_items = 'Agrega al menos una prenda al carrito.';
        }

        if (!Number.isFinite(amount) || amount <= 0) {
            nextErrors.monto_declarado = 'Para completar la compra debes pagar el 100% del total.';
        } else if (amount < total) {
            nextErrors.monto_declarado = 'El monto pagado no puede ser menor al total de la compra.';
        } else if (amount > total) {
            nextErrors.monto_declarado = 'El monto pagado no puede ser mayor al total de la compra.';
        }

        if (!method) {
            nextErrors.metodo_pago = 'Selecciona el método de pago.';
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!voucher) {
            nextErrors.comprobante = 'Sube el comprobante de pago.';
        } else if (!allowedTypes.includes(voucher.type)) {
            nextErrors.comprobante = 'El comprobante debe ser una imagen JPG, PNG o WEBP.';
        } else if (voucher.size > 4 * 1024 * 1024) {
            nextErrors.comprobante = 'El comprobante no debe superar 4MB.';
        }

        return nextErrors;
    }

    function submitCheckout() {
        const nextErrors = validateCheckout();
        setErrors(nextErrors);

        if (Object.keys(nextErrors).length > 0 || !voucher) {
            return;
        }

        const payload: Record<string, string | number | File> = {
            operation_id: operationId,
            metodo_pago: method,
            monto_declarado: declaredAmount,
            comprobante: voucher,
        };

        items.forEach((item, index) => {
            payload[`sale_items[${index}][variante_id]`] = item.variantId;
            payload[`sale_items[${index}][cantidad]`] = item.quantity;
        });

        router.post('/compras/online', payload, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onError: (server) => { setOperationErrorDismissed(false); setErrors(server as Record<string, string>); },
            onSuccess: () => { completeOperation(operationId); clearPurchaseCart(); },
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <StoreLayout title="Mati Boutique | Checkout">
            <section className="px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-6xl">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                                Checkout
                            </p>
                            <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                                Pago de compra online
                            </h1>
                        </div>
                        <Link
                            href={shopRoutes.cart}
                            className="text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                        >
                            Volver al carrito
                        </Link>
                    </div>

                    {items.length === 0 ? (
                        <div className="mt-12 rounded-[2rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <ShoppingBag size={34} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                No hay prendas para pagar.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950"
                            >
                                Explorar catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_24rem]">
                            <div className="space-y-4">
                                <section className="rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-6">
                                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                        Resumen de compra
                                    </p>
                                    <div className="mt-5 space-y-3">
                                        {items.map((item) => (
                                            <div key={item.variantId} className="flex items-center justify-between gap-4 border-b border-white/8 pb-3 text-sm last:border-b-0 last:pb-0">
                                                <div>
                                                    <p className="text-white">{item.name}</p>
                                                    <p className="mt-1 text-stone-500">
                                                        {item.color} / {item.size} / x{item.quantity}
                                                    </p>
                                                </div>
                                                <span className="text-stone-200">{formatPrice(item.price * item.quantity)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </section>

                                <section className="rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-6">
                                    <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                        Método de pago
                                    </p>
                                    <p className="mt-2 text-sm leading-6 text-stone-300">
                                        Para completar la compra debes pagar el 100% del total.
                                    </p>

                                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                        {ONLINE_PAYMENT_METHOD_OPTIONS.map((option) => (
                                            <button
                                                key={option.value}
                                                type="button"
                                                onClick={() => {
                                                    setMethod(option.value);
                                                    setErrors((current) => ({ ...current, metodo_pago: '' }));
                                                }}
                                                disabled={processing}
                                                className={
                                                    method === option.value
                                                        ? 'rounded-[1rem] border border-[rgba(223,184,166,0.42)] bg-[rgba(223,184,166,0.14)] px-4 py-3 text-left text-sm font-medium text-white'
                                                        : 'rounded-[1rem] border border-white/8 bg-black/10 px-4 py-3 text-left text-sm text-stone-300 transition hover:border-white/18 hover:text-white'
                                                }
                                            >
                                                {option.label}
                                            </button>
                                        ))}
                                    </div>
                                    {(errors.metodo_pago || serverErrors.metodo_pago) && (
                                        <p className="mt-2 text-sm text-red-200">{errors.metodo_pago || serverErrors.metodo_pago}</p>
                                    )}

                                    {method === 'qr' ? (
                                        <div className="mt-5 grid gap-4 rounded-[1rem] border border-white/8 bg-black/10 p-4 sm:grid-cols-[9rem_1fr]">
                                            <img
                                                src={PAYMENT_METHODS.qr.qrUrl}
                                                alt="QR de pago Mati Boutique"
                                                className="h-36 w-36 rounded-[1rem] border border-white/10 object-contain"
                                            />
                                            <div className="text-sm leading-6 text-stone-300">
                                                <p className="font-medium text-white">QR Mati Boutique</p>
                                                <p className="mt-1">{PAYMENT_METHODS.qr.instructions}</p>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="mt-5 rounded-[1rem] border border-white/8 bg-black/10 p-4 text-sm leading-6 text-stone-300">
                                            <p className="font-medium text-white">{PAYMENT_METHODS.bankTransfer.accountLabel}</p>
                                            <p className="mt-1">Titular: {PAYMENT_METHODS.bankTransfer.accountHolder}</p>
                                            <p>Nro. de cuenta: {PAYMENT_METHODS.bankTransfer.accountNumber}</p>
                                            <p className="mt-2">{PAYMENT_METHODS.bankTransfer.instructions}</p>
                                        </div>
                                    )}
                                </section>
                            </div>

                            <aside className="h-fit rounded-[1.6rem] border border-white/10 bg-white/[0.03] p-6">
                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                    Confirmacion
                                </p>
                                <div className="mt-5 flex items-center justify-between border-b border-white/10 pb-5">
                                    <span className="text-stone-300">Total a pagar</span>
                                    <span className="text-xl font-semibold text-white">{formatPrice(total)}</span>
                                </div>

                                <label className="mt-5 block space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                        Monto pagado
                                    </span>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={declaredAmount}
                                        onChange={(event) => {
                                            setDeclaredAmount(event.target.value);
                                            setErrors((current) => ({ ...current, monto_declarado: '' }));
                                        }}
                                        disabled={processing}
                                        className="w-full rounded-[1rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-white outline-none transition focus:border-[rgb(223,184,166)]"
                                    />
                                    {(errors.monto_declarado || serverErrors.monto_declarado) && (
                                        <p className="text-sm text-red-200">{errors.monto_declarado || serverErrors.monto_declarado}</p>
                                    )}
                                </label>

                                <label className="mt-5 block space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                        Sube tu comprobante
                                    </span>
                                    <div className="rounded-[1rem] border border-dashed border-white/14 bg-black/10 p-4">
                                        <div className="flex items-center gap-3 text-sm text-stone-300">
                                            <UploadCloud size={18} />
                                            <span>{voucher ? voucher.name : 'JPG, PNG o WEBP hasta 4MB.'}</span>
                                        </div>
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={(event) => {
                                                setVoucher(event.target.files?.[0] ?? null);
                                                setErrors((current) => ({ ...current, comprobante: '' }));
                                            }}
                                            disabled={processing}
                                            className="mt-3 block w-full text-sm text-stone-300 file:mr-4 file:rounded-full file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-950"
                                        />
                                    </div>
                                    {(errors.comprobante || serverErrors.comprobante) && (
                                        <p className="text-sm text-red-200">{errors.comprobante || serverErrors.comprobante}</p>
                                    )}
                                </label>

                                <OperationRecovery error={operationErrorDismissed ? undefined : (errors.operation_id || serverErrors.operation_id)} onRenew={() => { setOperationId(renewOperation(operationId)); setOperationErrorDismissed(true); setErrors(current => { const next = { ...current }; delete next.operation_id; return next; }); }} />
                                {(errors.sale_items || serverErrors.sale_items) && (
                                    <p className="mt-4 rounded-[1rem] border border-red-300/20 bg-red-950/20 p-4 text-sm text-red-100">
                                        {errors.sale_items || serverErrors.sale_items}
                                    </p>
                                )}

                                <button
                                    type="button"
                                    onClick={submitCheckout}
                                    disabled={processing || Object.keys(validateCheckout()).length > 0}
                                    className="mt-6 inline-flex w-full justify-center rounded-full bg-white px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 disabled:opacity-60"
                                >
                                    {processing ? 'Enviando...' : 'Enviar compra a revision'}
                                </button>
                            </aside>
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
