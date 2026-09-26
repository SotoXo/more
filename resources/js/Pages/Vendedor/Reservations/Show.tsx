import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEvent } from 'react';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';

type Option = {
    label: string;
    value: string;
};

type ReservationDetail = {
    id: number;
    number: string;
    date: string;
    responsible: string;
    customer: string;
    status: VendedorBadge;
    paymentStatus: VendedorBadge;
    logisticsStatus: string;
    expiresAt: string;
    availableAt: string;
    limitAt: string;
    pickupPoint: string;
    stockOrigin: string;
    note: string;
    subtotal: string;
    total: string;
    paid: string;
    balance: string;
    balanceAmount: number;
    hasConfirmedPayments: boolean;
    canAddPayment: boolean;
    canCancel: boolean;
    paymentAction: string;
    cancelAction: string;
};

type ReservationItem = VendedorReadOnlyRecord & {
    sku: string;
    color: string;
    size: string;
    quantity: string;
    unitPrice: string;
    subtotal: string;
};

type Payment = {
    id: number;
    date: string;
    method: string;
    declared: string;
    confirmed: string;
    status: VendedorBadge;
    verifiedBy: string;
    verifiedAt: string;
    voucherUrl: string | null;
    note: string;
};

type Movement = {
    id: number;
    product: string;
    sku: string;
    type: string;
    quantity: string;
    availableBefore: string;
    availableAfter: string;
    committedBefore: string;
    committedAfter: string;
    description: string | null;
};

type ReservationShowProps = {
    reservation: ReservationDetail;
    items: ReservationItem[];
    payments: Payment[];
    movements: Movement[];
    options: {
        paymentMethods: Option[];
    };
};

type PaymentFormData = {
    payment_method: string;
    amount_paid: string;
    voucher: File | null;
    note: string;
};

type CancelFormData = {
    cancellation_reason: string;
};

const itemColumns: VendedorReadOnlyColumn<ReservationItem>[] = [
    { key: 'sku', label: 'SKU', render: (row) => row.sku },
    { key: 'color', label: 'Color', render: (row) => row.color },
    { key: 'size', label: 'Talla', render: (row) => row.size },
    { key: 'quantity', label: 'Cantidad', align: 'right', render: (row) => row.quantity },
    { key: 'unit', label: 'Unitario', align: 'right', render: (row) => row.unitPrice },
    { key: 'subtotal', label: 'Subtotal', align: 'right', render: (row) => row.subtotal },
];

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="sa-panel-soft rounded-2xl p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-2 text-sm font-medium text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

function PaymentVoucher({ url }: { url: string | null }) {
    if (!url) {
        return <span className="text-sm text-[color:var(--admin-muted)]">Sin comprobante</span>;
    }

    if (url.toLowerCase().endsWith('.pdf')) {
        return (
            <a href={url} target="_blank" rel="noreferrer" className="text-sm text-[color:var(--admin-accent)] underline-offset-4 hover:underline">
                Abrir PDF
            </a>
        );
    }

    return <img src={url} alt="Comprobante de pago" className="h-24 max-w-40 rounded-xl border border-[color:var(--admin-border)] object-contain" />;
}

export default function Show({ reservation, items, payments, movements, options }: ReservationShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const paymentForm = useForm<PaymentFormData>({
        payment_method: 'efectivo',
        amount_paid: reservation.balanceAmount > 0 ? reservation.balanceAmount.toFixed(2) : '0.00',
        voucher: null,
        note: '',
    });
    const cancelForm = useForm<CancelFormData>({
        cancellation_reason: '',
    });
    const paymentRequiresVoucher = ['qr', 'transferencia_bancaria'].includes(paymentForm.data.payment_method);

    const submitPayment = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        paymentForm.post(reservation.paymentAction, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitCancel = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        cancelForm.post(reservation.cancelAction, {
            preserveScroll: true,
        });
    };

    return (
        <VendedorLayout title={`Mati Boutique | ${reservation.number}`}>
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <Link href="/vendedor/reservas" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                        <ArrowLeft size={15} />
                        Reservas
                    </Link>
                    <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                        Detalle de reserva
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        {reservation.number}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Reserva presencial registrada desde el panel vendedor.
                    </p>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="sa-panel rounded-2xl p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <SuperadminBadge tone={reservation.status.tone}>{reservation.status.label}</SuperadminBadge>
                        <SuperadminBadge tone={reservation.paymentStatus.tone}>{reservation.paymentStatus.label}</SuperadminBadge>
                    </div>
                    <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Fecha" value={reservation.date} />
                        <Detail label="Responsable" value={reservation.responsible} />
                        <Detail label="Cliente" value={reservation.customer} />
                        <Detail label="Estado logistico" value={reservation.logisticsStatus} />
                        <Detail label="Vence" value={reservation.expiresAt} />
                        <Detail label="Disponible" value={reservation.availableAt} />
                        <Detail label="Límite recojo" value={reservation.limitAt} />
                        <Detail label="Origen stock" value={reservation.stockOrigin} />
                        <Detail label="Total" value={reservation.total} />
                        <Detail label="Pagado" value={reservation.paid} />
                        <Detail label="Saldo" value={reservation.balance} />
                        <Detail label="Observacion" value={reservation.note} />
                    </div>
                </section>

                <section className="space-y-3">
                    <div>
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">Productos reservados</p>
                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">Detalle en modo lectura. No hay edición de productos en esta fase.</p>
                    </div>
                    <VendedorReadOnlyTable
                        rows={items}
                        columns={itemColumns}
                        emptyTitle="Sin productos"
                        emptyDescription="La reserva no tiene productos asociados."
                    />
                </section>

                <section className="sa-panel rounded-2xl p-5">
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">Pagos registrados</p>
                    <div className="mt-4 grid gap-3">
                        {payments.length === 0 && (
                            <div className="sa-panel-muted rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                                Sin pagos registrados.
                            </div>
                        )}
                        {payments.map((payment) => (
                            <div key={payment.id} className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 lg:grid-cols-[1fr_1fr_1fr_1fr_1fr]">
                                <Detail label="Fecha" value={payment.date} />
                                <Detail label="Método" value={payment.method} />
                                <Detail label="Confirmado" value={payment.confirmed} />
                                <div className="sa-panel-soft rounded-2xl p-4">
                                    <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Estado</p>
                                    <div className="mt-2">
                                        <SuperadminBadge tone={payment.status.tone}>{payment.status.label}</SuperadminBadge>
                                    </div>
                                </div>
                                <div className="sa-panel-soft rounded-2xl p-4">
                                    <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Comprobante</p>
                                    <div className="mt-2">
                                        <PaymentVoucher url={payment.voucherUrl} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    {reservation.canAddPayment && (
                        <form onSubmit={submitPayment} className="sa-panel rounded-2xl p-5">
                            <p className="text-sm font-semibold text-[color:var(--admin-text)]">Registrar pago adicional</p>
                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">No modifica stock. El pago queda confirmado automaticamente.</p>
                            {Object.keys(paymentForm.errors).length > 0 && (
                                <div className="mt-4 rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4 text-sm leading-6 text-[rgb(245,208,210)]">
                                    {Object.entries(paymentForm.errors).map(([field, message]) => (
                                        <p key={field}>{message}</p>
                                    ))}
                                </div>
                            )}
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <label className="space-y-2">
                                    <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Método de pago</span>
                                    <select
                                        value={paymentForm.data.payment_method}
                                        onChange={(event) => {
                                            paymentForm.setData('payment_method', event.target.value);
                                            paymentForm.setData('voucher', null);
                                        }}
                                        className="sa-input min-h-11 px-3 text-sm"
                                    >
                                        {options.paymentMethods.map((method) => (
                                            <option key={method.value} value={method.value}>{method.label}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="space-y-2">
                                    <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Monto</span>
                                    <input
                                        type="number"
                                        min="0.01"
                                        max={reservation.balanceAmount}
                                        step="0.01"
                                        value={paymentForm.data.amount_paid}
                                        onChange={(event) => paymentForm.setData('amount_paid', event.target.value)}
                                        className="sa-input min-h-11 px-3 text-sm"
                                    />
                                </label>
                            </div>
                            {paymentRequiresVoucher && (
                                <label className="mt-4 block space-y-2">
                                    <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Comprobante</span>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,application/pdf"
                                        onChange={(event) => paymentForm.setData('voucher', event.target.files?.[0] ?? null)}
                                        className="sa-input min-h-11 px-3 py-2 text-sm"
                                    />
                                </label>
                            )}
                            <label className="mt-4 block space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Observacion</span>
                                <textarea
                                    value={paymentForm.data.note}
                                    onChange={(event) => paymentForm.setData('note', event.target.value)}
                                    className="sa-input min-h-24 w-full px-3 py-3 text-sm"
                                />
                            </label>
                            <button
                                type="submit"
                                disabled={paymentForm.processing || (paymentRequiresVoucher && !paymentForm.data.voucher)}
                                className="sa-button-primary mt-4 h-11 w-full text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {paymentForm.processing ? 'Registrando pago' : 'Registrar pago'}
                            </button>
                        </form>
                    )}

                    {reservation.canCancel && (
                        <form onSubmit={submitCancel} className="sa-panel rounded-2xl p-5">
                            <p className="text-sm font-semibold text-[color:var(--admin-text)]">Cancelar reserva</p>
                            <p className="mt-1 text-sm text-[color:var(--admin-muted)]">La cancelación libera stock: el stock disponible sube y el comprometido baja.</p>
                            {reservation.hasConfirmedPayments && (
                                <div className="mt-4 rounded-2xl border border-[rgba(244,196,117,0.28)] bg-[rgba(244,196,117,0.1)] p-4 text-sm leading-6 text-[rgb(250,222,179)]">
                                    La reserva tiene pagos registrados. La cancelación liberará stock, pero la gestión de devolución queda fuera de esta fase.
                                </div>
                            )}
                            {Object.keys(cancelForm.errors).length > 0 && (
                                <div className="mt-4 rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4 text-sm leading-6 text-[rgb(245,208,210)]">
                                    {Object.entries(cancelForm.errors).map(([field, message]) => (
                                        <p key={field}>{message}</p>
                                    ))}
                                </div>
                            )}
                            <label className="mt-4 block space-y-2">
                                <span className="text-xs font-semibold text-[color:var(--admin-muted)]">Motivo de cancelación</span>
                                <textarea
                                    value={cancelForm.data.cancellation_reason}
                                    onChange={(event) => cancelForm.setData('cancellation_reason', event.target.value)}
                                    className="sa-input min-h-28 w-full px-3 py-3 text-sm"
                                    placeholder="Motivo obligatorio"
                                />
                            </label>
                            <button
                                type="submit"
                                disabled={cancelForm.processing || cancelForm.data.cancellation_reason.trim().length < 5}
                                className="sa-button-secondary mt-4 h-11 w-full text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {cancelForm.processing ? 'Cancelando reserva' : 'Cancelar reserva'}
                            </button>
                        </form>
                    )}
                </section>

                <section className="sa-panel rounded-2xl p-5">
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">Resumen de Kardex</p>
                    <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                        Disponible corresponde al stock disponible y comprometido al stock comprometido.
                    </p>
                    <div className="mt-4 grid gap-3">
                        {movements.length === 0 && (
                            <div className="sa-panel-muted rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                                Sin movimientos asociados.
                            </div>
                        )}
                        {movements.map((movement) => (
                            <div key={movement.id} className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 md:grid-cols-6">
                                <Detail label="Tipo" value={movement.type} />
                                <Detail label="Producto" value={movement.product} />
                                <Detail label="SKU" value={movement.sku} />
                                <Detail label="Cantidad" value={movement.quantity} />
                                <Detail label="Stock disponible" value={`${movement.availableBefore} -> ${movement.availableAfter}`} />
                                <Detail label="Stock comprometido" value={`${movement.committedBefore} -> ${movement.committedAfter}`} />
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </VendedorLayout>
    );
}
