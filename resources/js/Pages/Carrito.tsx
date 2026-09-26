import { operationToken, completeOperation, renewOperation } from '@/lib/commercialOperation';
import OperationRecovery from '@/Components/shop/OperationRecovery';
import { reservationPayment, voucherError } from '@/lib/commercialValidation';
import StoreLayout from '@/Components/shop/StoreLayout';
import StorefrontImage from '@/Components/shop/StorefrontImage';
import { ONLINE_PAYMENT_METHOD_OPTIONS, PAYMENT_METHODS } from '@/config/paymentMethods';
import { formatPrice, shopRoutes } from '@/lib/shop';
import type { CartItem } from '@/types';
import type { LandingPageProps } from '@/types/landing';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

const serifStyle = {
    fontFamily: '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

type CheckoutStep = 'items' | 'summary' | 'payment';

type CarritoProps = {
    items: CartItem[];
    subtotal: number;
    initialStep?: CheckoutStep;
};

function CartItemRow({
    item,
    onUpdate,
    onRemove,
    disabled,
}: {
    item: CartItem;
    onUpdate: (varianteId: number, cantidad: number) => void;
    onRemove: (varianteId: number) => void;
    disabled?: boolean;
}) {
    return (
        <div className="flex flex-col gap-4 rounded-[1.4rem] border border-white/8 bg-white/[0.03] p-5 sm:flex-row sm:gap-5">
            <Link href={shopRoutes.product(String(item.prenda_id))} className="shrink-0">
                <StorefrontImage
                    src={item.imagen}
                    alt={item.nombre}
                    className="h-24 w-20 rounded-[1rem] object-cover"
                />
            </Link>

            <div className="flex flex-1 flex-col justify-between">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-[0.65rem] uppercase tracking-[0.22em] text-stone-400">
                            {item.marca}
                        </p>
                        <Link
                            href={shopRoutes.product(String(item.prenda_id))}
                            className="mt-1 block break-words text-xl leading-tight text-white hover:text-stone-200"
                            style={serifStyle}
                        >
                            {item.nombre}
                        </Link>
                        <p className="mt-1.5 text-[0.7rem] uppercase tracking-[0.18em] text-stone-400">
                            {item.talla} / {item.color}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => onRemove(item.variante_id)}
                        className="shrink-0 text-stone-500 transition hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Eliminar"
                        disabled={disabled}
                    >
                        <Trash2 size={15} />
                    </button>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <div className="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-1 py-1">
                        <button
                            type="button"
                            onClick={() => onUpdate(item.variante_id, Math.max(1, item.cantidad - 1))}
                            className="flex h-7 w-7 items-center justify-center rounded-full transition hover:bg-white/[0.08] disabled:cursor-not-allowed disabled:opacity-50"
                            disabled={disabled || item.cantidad <= 1}
                            aria-label="Disminuir cantidad"
                        >
                            <Minus size={12} />
                        </button>
                        <span className="w-5 text-center text-sm text-white">{item.cantidad}</span>
                        <button
                            type="button"
                            onClick={() => onUpdate(item.variante_id, item.cantidad + 1)}
                            className="flex h-7 w-7 items-center justify-center rounded-full transition hover:bg-white/[0.08] disabled:cursor-not-allowed disabled:opacity-50"
                            disabled={disabled || item.cantidad >= item.stock}
                            title="Stock máximo disponible"
                            aria-label="Aumentar cantidad"
                        >
                            <Plus size={12} />
                        </button>
                    </div>
                    <span className="text-sm text-stone-200">
                        {formatPrice(item.precio * item.cantidad)}
                    </span>
                </div>
            </div>
        </div>
    );
}

function SummaryLine({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-sm">
            <span className="text-stone-300">{label}</span>
            <span className="text-right text-white">{value}</span>
        </div>
    );
}

export default function Carrito({ items, initialStep = 'items' }: CarritoProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const [cartItems, setCartItems] = useState(items);
    const [step, setStep] = useState<CheckoutStep>(initialStep);
    const [cartPending, setCartPending] = useState(false);
    const [cartError, setCartError] = useState<string | null>(null);
    const [clientError, setClientError] = useState<string | null>(null);

    const total = cartItems.reduce((sum, item) => sum + item.precio * item.cantidad, 0);

    const { data, setData, post, processing, errors, clearErrors, reset } = useForm<{
        operation_id: string;
        monto_declarado_rpg: string;
        metodo_pago_rpg: string;
        comprobante_rpg: File | null;
    }>({
        operation_id: operationToken(),
        monto_declarado_rpg: '',
        metodo_pago_rpg: PAYMENT_METHODS.qr.value,
        comprobante_rpg: null,
    });
    const payment = reservationPayment(data.monto_declarado_rpg, total);
    const { minimumPayment: minimum, maximumPayment: maximum } = payment;

    function visibleErrors() {
        return [
            clientError,
            cartError,
            flash?.error,
            ...Object.values(errors),
        ].filter(Boolean) as string[];
    }

    function goToSummary() {
        setCartError(null);
        setClientError(null);
        if (cartItems.length === 0) {
            setCartError('Selecciona al menos una prenda.');
            return;
        }
        setStep('summary');
    }

    function goToPayment() {
        setCartError(null);
        setClientError(null);
        if (cartItems.length === 0) {
            setCartError('Selecciona al menos una prenda.');
            setStep('items');
            return;
        }
        router.get('/bolsa/pago', {}, { preserveState: false });
    }

    function validatePaymentStep() {
        if (!payment.isPaymentValid) return payment.message;

        if (!ONLINE_PAYMENT_METHOD_OPTIONS.some((method) => method.value === data.metodo_pago_rpg)) {
            return 'Selecciona un método de pago válido.';
        }

        if (voucherError(data.comprobante_rpg)) return voucherError(data.comprobante_rpg);

        return null;
    }

    function handleConfirm(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setClientError(null);
        setCartError(null);

        const validationMessage = validatePaymentStep();
        if (validationMessage) {
            setClientError(validationMessage);
            return;
        }

        post('/bolsa/confirmar', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { completeOperation(data.operation_id); reset('monto_declarado_rpg', 'comprobante_rpg'); },
            onError: () => setStep('payment'),
        });
    }

    async function handleUpdate(varianteId: number, cantidad: number) {
        if (cartPending) {
            return;
        }

        setCartPending(true);
        setCartError(null);
        setClientError(null);

        try {
            const response = await axios.patch(`/api/carrito/${varianteId}`, { cantidad });
            window.dispatchEvent(
                new CustomEvent('mati-boutique:reservation-bag-updated', {
                    detail: { count: response.data?.cart_count },
                }),
            );
            setCartItems((prev) =>
                prev.map((item) => (item.variante_id === varianteId ? { ...item, cantidad } : item)),
            );
        } catch (error) {
            setCartError(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'No se pudo actualizar la prenda.')
                    : 'No se pudo actualizar la prenda.',
            );
        } finally {
            setCartPending(false);
        }
    }

    async function handleRemove(varianteId: number) {
        if (cartPending) {
            return;
        }

        setCartPending(true);
        setCartError(null);
        setClientError(null);

        try {
            const response = await axios.delete(`/api/carrito/${varianteId}`);
            window.dispatchEvent(
                new CustomEvent('mati-boutique:reservation-bag-updated', {
                    detail: { count: response.data?.cart_count },
                }),
            );
            setCartItems((prev) => {
                const next = prev.filter((item) => item.variante_id !== varianteId);
                if (next.length === 0) {
                    setStep('items');
                }
                return next;
            });
        } catch (error) {
            setCartError(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'No se pudo quitar la prenda.')
                    : 'No se pudo quitar la prenda.',
            );
        } finally {
            setCartPending(false);
        }
    }

    const selectedPayment =
        data.metodo_pago_rpg === PAYMENT_METHODS.bankTransfer.value
            ? PAYMENT_METHODS.bankTransfer
            : PAYMENT_METHODS.qr;
    const errorMessages = visibleErrors();

    return (
        <StoreLayout title="Mati Boutique | Bolsa de reservas">
            <section className="px-3 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl">
                    <p className="text-[0.68rem] uppercase tracking-[0.28em] text-stone-400">
                        Mati Boutique
                    </p>
                    <h1 className="mt-3 text-5xl leading-none text-white sm:text-6xl" style={serifStyle}>
                        Tus prendas para reservar
                    </h1>

                    {cartItems.length === 0 ? (
                        <div className="mt-14 rounded-[1.6rem] border border-white/10 bg-white/[0.03] px-6 py-16 text-center">
                            <ShoppingBag size={32} className="mx-auto text-stone-600" />
                            <p className="mt-6 text-3xl text-white" style={serifStyle}>
                                Aun no agregaste prendas para reservar.
                            </p>
                            <p className="mt-3 text-sm text-stone-400">
                                Revisa el catálogo y agrega tus prendas favoritas.
                            </p>
                            <Link
                                href={shopRoutes.catalog}
                                className="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200"
                            >
                                Ver catálogo
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_380px]">
                            <div>
                                <div className="mb-6 grid gap-3 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400 sm:grid-cols-3">
                                    <span className={step === 'items' ? 'text-white' : ''}>1. Selección</span>
                                    <span className={step === 'summary' ? 'text-white' : ''}>2. Resumen</span>
                                    <span className={step === 'payment' ? 'text-white' : ''}>3. Pago</span>
                                </div>

                                {errorMessages.length > 0 && (
                                    <div className="mb-5 rounded-[1rem] border border-red-300/20 bg-red-950/30 p-4 text-sm leading-6 text-red-100">
                                        {errorMessages.map((message, index) => (
                                            <p key={`${message}-${index}`}>{message}</p>
                                        ))}
                                    </div>
                                )}

                                {flash?.success && (
                                    <div className="mb-5 rounded-[1rem] border border-emerald-300/20 bg-emerald-950/30 p-4 text-sm text-emerald-100">
                                        {flash.success}
                                    </div>
                                )}

                                {step === 'items' && (
                                    <div className="space-y-4">
                                        {cartItems.map((item) => (
                                            <CartItemRow
                                                key={item.variante_id}
                                                item={item}
                                                onUpdate={handleUpdate}
                                                onRemove={handleRemove}
                                                disabled={cartPending || processing}
                                            />
                                        ))}
                                    </div>
                                )}

                                {step === 'summary' && (
                                    <div className="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-5">
                                        <h2 className="text-3xl text-white" style={serifStyle}>
                                            Resumen de reserva
                                        </h2>
                                        <div className="mt-6 space-y-4">
                                            {cartItems.map((item) => (
                                                <div key={item.variante_id} className="grid gap-4 border-b border-white/10 pb-4 last:border-b-0 last:pb-0 sm:grid-cols-[64px_1fr_auto]">
                                                    <StorefrontImage
                                                        src={item.imagen}
                                                        alt={item.nombre}
                                                        className="h-20 w-16 rounded-[0.9rem] object-cover"
                                                    />
                                                    <div>
                                                        <p className="break-words text-lg text-white" style={serifStyle}>
                                                            {item.nombre}
                                                        </p>
                                                        <p className="mt-1 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                            {item.talla} / {item.color} - Cantidad {item.cantidad}
                                                        </p>
                                                        <p className="mt-2 text-sm text-stone-300">
                                                            Precio unitario: {formatPrice(item.precio)}
                                                        </p>
                                                    </div>
                                                    <p className="text-sm text-white sm:text-right">
                                                        {formatPrice(item.precio * item.cantidad)}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {step === 'payment' && (
                                    <div><OperationRecovery error={errors.operation_id} onRenew={() => { setData('operation_id', renewOperation(data.operation_id)); clearErrors('operation_id'); }} /><form onSubmit={handleConfirm} className="rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-5">
                                        <h2 className="text-3xl text-white" style={serifStyle}>
                                            Pago de reserva
                                        </h2>
                                        <p className="mt-2 text-sm leading-6 text-stone-300">
                                            Puedes reservar pagando desde el 50% hasta el 100% del total.
                                        </p>

                                        <div className="mt-6 grid gap-4">
                                            <label className="block space-y-2">
                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                    Método de pago
                                                </span>
                                                <select
                                                    value={data.metodo_pago_rpg}
                                                    onChange={(event) => {
                                                        setClientError(null);
                                                        setData('metodo_pago_rpg', event.target.value);
                                                    }}
                                                    className="w-full rounded-[1rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-white outline-none transition focus:border-white/30"
                                                    disabled={processing}
                                                >
                                                    {ONLINE_PAYMENT_METHOD_OPTIONS.map((method) => (
                                                        <option key={method.value} value={method.value}>
                                                            {method.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </label>

                                            <div className="rounded-[1.2rem] border border-white/10 bg-black/20 p-4">
                                                {selectedPayment.value === PAYMENT_METHODS.qr.value ? (
                                                    <div className="grid gap-4 sm:grid-cols-[150px_1fr]">
                                                        <img
                                                            src={PAYMENT_METHODS.qr.qrUrl}
                                                            alt="QR de pago Mati Boutique"
                                                            className="h-36 w-36 rounded-[1rem] bg-white object-contain p-2"
                                                        />
                                                        <div>
                                                            <p className="text-sm font-medium text-white">QR Mati Boutique</p>
                                                            <p className="mt-2 text-sm leading-6 text-stone-300">
                                                                {PAYMENT_METHODS.qr.instructions}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <p className="text-sm font-medium text-white">
                                                            {PAYMENT_METHODS.bankTransfer.accountLabel}
                                                        </p>
                                                        <p className="mt-3 text-sm text-stone-300">
                                                            Titular: {PAYMENT_METHODS.bankTransfer.accountHolder}
                                                        </p>
                                                        <p className="mt-1 text-sm text-stone-300">
                                                            Nro. de cuenta: {PAYMENT_METHODS.bankTransfer.accountNumber}
                                                        </p>
                                                        <p className="mt-3 text-sm leading-6 text-stone-400">
                                                            {PAYMENT_METHODS.bankTransfer.instructions}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>

                                            <label className="block space-y-2">
                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                    Monto a depositar
                                                </span>
                                                <input
                                                    type="number"
                                                    min={minimum}
                                                    aria-invalid={!payment.isPaymentValid}
                                                    max={maximum}
                                                    step="0.01"
                                                    value={data.monto_declarado_rpg}
                                                    onChange={(event) => {
                                                        setClientError(null);
                                                        setData('monto_declarado_rpg', event.target.value);
                                                    }}
                                                    className="w-full rounded-[1rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-white outline-none transition focus:border-white/30"
                                                    disabled={processing}
                                                />
                                                {payment.message && <p className="mt-2 text-xs text-red-300" role="status">{payment.message}</p>}
                                            </label>

                                            <label className="block space-y-2">
                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                    Sube tu comprobante
                                                </span>
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    onChange={(event) => {
                                                        setClientError(null);
                                                        setData('comprobante_rpg', event.target.files?.[0] ?? null);
                                                    }}
                                                    className="w-full rounded-[1rem] border border-white/10 bg-black/20 px-4 py-3 text-sm text-stone-200 outline-none file:mr-4 file:rounded-full file:border-0 file:bg-white file:px-4 file:py-2 file:text-[0.68rem] file:uppercase file:tracking-[0.18em] file:text-slate-950"
                                                    disabled={processing}
                                                />
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={processing || cartPending || Boolean(validatePaymentStep())}
                                            className="mt-8 w-full rounded-full bg-white py-4 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {processing ? 'Procesando reserva...' : 'Enviar reserva a revisión'}
                                        </button>
                                    </form></div>
                                )}
                            </div>

                            <aside className="rounded-[1.4rem] border border-white/10 bg-white/[0.04] p-7 lg:self-start lg:sticky lg:top-28">
                                <p className="text-[0.68rem] uppercase tracking-[0.26em] text-stone-400">
                                    Resumen
                                </p>

                                <div className="mt-6 space-y-3">
                                    <SummaryLine label="Subtotal" value={formatPrice(total)} />
                                    <SummaryLine label="Total" value={formatPrice(total)} />
                                    <SummaryLine label="Anticipo mínimo requerido" value={formatPrice(minimum)} />
                                    <SummaryLine label="Monto máximo permitido" value={formatPrice(maximum)} />
                                </div>

                                <div className="mt-6 border-t border-white/10 pt-6">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm uppercase tracking-[0.18em] text-stone-200">
                                            Total reserva
                                        </span>
                                        <span className="text-2xl text-white" style={serifStyle}>
                                            {formatPrice(total)}
                                        </span>
                                    </div>
                                </div>

                                {step === 'items' && (
                                    <button
                                        type="button"
                                        onClick={goToSummary}
                                        disabled={cartPending || processing}
                                        className="mt-8 w-full rounded-full bg-white py-4 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Continuar con la reserva
                                    </button>
                                )}

                                {step === 'summary' && (
                                    <button
                                        type="button"
                                        onClick={goToPayment}
                                        disabled={cartPending || processing}
                                        className="mt-8 w-full rounded-full bg-white py-4 text-[0.72rem] uppercase tracking-[0.22em] text-slate-950 transition hover:-translate-y-px hover:bg-stone-200 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Continuar al pago
                                    </button>
                                )}

                                {step !== 'items' && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setClientError(null);
                                            setCartError(null);
                                            setStep(step === 'payment' ? 'summary' : 'items');
                                        }}
                                        disabled={processing}
                                        className="mt-4 flex w-full items-center justify-center gap-2 rounded-full border border-white/10 py-3 text-[0.7rem] uppercase tracking-[0.2em] text-stone-300 transition hover:border-white/30 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <ArrowLeft size={14} />
                                        Volver
                                    </button>
                                )}

                                <Link
                                    href={shopRoutes.catalog}
                                    className="mt-4 block text-center text-[0.7rem] uppercase tracking-[0.2em] text-stone-400 transition hover:text-white"
                                >
                                    Seguir viendo catálogo
                                </Link>
                            </aside>
                        </div>
                    )}
                </div>
            </section>
        </StoreLayout>
    );
}
