import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, XCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import type { VendedorBadge } from '../Partials/VendedorReadOnlyTable';

type PaymentDetail = {
    id: string;
    type: string;
    typeLabel: string;
    number: string;
    date: string;
    uploadedAt: string;
    reviewedAt: string;
    customer: string;
    verifier: string;
    method: string;
    declaredAmount: string;
    verifiedAmount: string;
    expectedAmount: string;
    paidAmount: string;
    balanceAmount: string;
    status: VendedorBadge;
    voucherUrl: string | null;
    note: string;
    origin: string;
    canReview: boolean;
    approveAction: string;
    rejectAction: string;
    inventoryNotice: string;
};

type OperationDetail = {
    id: number;
    type: string;
    number: string;
    total: string;
    paid: string;
    balance: string;
    status: VendedorBadge;
    paymentStatus: VendedorBadge;
    logisticsStatus: string;
    href?: string | null;
};

type HistoryPayment = {
    id: string;
    date: string;
    method: string;
    declared: string;
    confirmed: string;
    status: VendedorBadge;
    verifiedAt: string;
    verifiedBy: string;
    note: string;
};

type PaymentShowProps = {
    payment: PaymentDetail;
    operation: OperationDetail;
    history: HistoryPayment[];
};

type RejectFormData = {
    rejection_reason: string;
};

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="sa-panel-soft rounded-2xl p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-2 break-words text-sm font-medium text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

function VoucherPreview({ url }: { url: string | null }) {
    if (!url) {
        return (
            <div className="sa-panel-muted rounded-2xl p-6 text-sm text-[color:var(--admin-muted)]">
                Sin comprobante disponible.
            </div>
        );
    }

    if (url.toLowerCase().endsWith('.pdf')) {
        return (
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className="sa-button-secondary h-11 px-4 text-sm font-semibold"
            >
                Abrir comprobante PDF
            </a>
        );
    }

    return (
        <a href={url} target="_blank" rel="noreferrer" className="block">
            <img
                src={url}
                alt="Comprobante de pago"
                className="max-h-[560px] w-full rounded-2xl border border-[color:var(--admin-border)] object-contain"
            />
        </a>
    );
}

function FormErrors({ errors }: { errors: Partial<Record<string, string>> }) {
    const values = Object.entries(errors).filter((entry): entry is [string, string] => Boolean(entry[1]));

    if (values.length === 0) {
        return null;
    }

    return (
        <div className="rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4 text-sm leading-6 text-[rgb(245,208,210)]">
            {values.map(([field, message]) => (
                <p key={field}>{message}</p>
            ))}
        </div>
    );
}

export default function Show({ payment, operation, history }: PaymentShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const approveForm = useForm({});
    const rejectForm = useForm<RejectFormData>({
        rejection_reason: '',
    });

    const submitApprove = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!window.confirm('¿Confirmar que el comprobante es correcto?')) {
            return;
        }

        approveForm.post(payment.approveAction, {
            preserveScroll: true,
        });
    };

    const submitReject = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        rejectForm.post(payment.rejectAction, {
            preserveScroll: true,
        });
    };

    return (
        <VendedorLayout title={`Mati Boutique | ${payment.number}`}>
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <Link href="/vendedor/pagos" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                        <ArrowLeft size={15} />
                        Pagos
                    </Link>
                    <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                        Revision de comprobante
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        {payment.number}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        {payment.inventoryNotice}
                    </p>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="sa-panel rounded-2xl p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <SuperadminBadge tone={payment.status.tone}>{payment.status.label}</SuperadminBadge>
                        <SuperadminBadge tone={operation.status.tone}>{operation.status.label}</SuperadminBadge>
                        <SuperadminBadge tone={operation.paymentStatus.tone}>{operation.paymentStatus.label}</SuperadminBadge>
                    </div>
                    <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Tipo" value={payment.typeLabel} />
                        <Detail label="Cliente" value={payment.customer} />
                        <Detail label="Metodo" value={payment.method} />
                        <Detail label="Origen" value={payment.origin} />
                        <Detail label="Monto declarado" value={payment.declaredAmount} />
                        <Detail label="Monto esperado" value={payment.expectedAmount} />
                        <Detail label="Monto pagado" value={payment.paidAmount} />
                        <Detail label="Saldo" value={payment.balanceAmount} />
                        <Detail label="Subido" value={payment.uploadedAt} />
                        <Detail label="Revisado" value={payment.reviewedAt} />
                        <Detail label="Verificador" value={payment.verifier} />
                        <Detail label="Estado logistico" value={operation.logisticsStatus} />
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(340px,0.8fr)]">
                    <div className="sa-panel rounded-2xl p-5">
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                            Comprobante
                        </p>
                        <div className="mt-4">
                            <VoucherPreview url={payment.voucherUrl} />
                        </div>
                    </div>

                    <div className="space-y-6">
                        <section className="sa-panel rounded-2xl p-5">
                            <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                                Operacion
                            </p>
                            <div className="mt-4 grid gap-3">
                                <Detail label="Numero" value={operation.number} />
                                <Detail label="Total" value={operation.total} />
                                <Detail label="Pagado" value={operation.paid} />
                                <Detail label="Saldo" value={operation.balance} />
                                <Detail label="Observacion" value={payment.note} />
                            </div>
                        </section>

                        {payment.canReview ? (
                            <section className="sa-panel rounded-2xl p-5">
                                <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                                    Acciones de revision
                                </p>
                                <p className="mt-1 text-sm leading-6 text-[color:var(--admin-muted)]">
                                    Revisa el comprobante contra el monto declarado antes de aprobarlo o rechazarlo.
                                </p>

                                <div className="mt-4 space-y-4">
                                    <form onSubmit={submitApprove}>
                                        <FormErrors errors={approveForm.errors} />
                                        <button
                                            type="submit"
                                            disabled={approveForm.processing || rejectForm.processing}
                                            className="sa-button-primary mt-3 h-11 w-full text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <CheckCircle2 size={16} />
                                            {approveForm.processing ? 'Aprobando' : 'Aprobar comprobante'}
                                        </button>
                                    </form>

                                    <form onSubmit={submitReject} className="space-y-3">
                                        <FormErrors errors={rejectForm.errors} />
                                        <label className="block space-y-2">
                                            <span className="text-xs font-semibold text-[color:var(--admin-muted)]">
                                                Motivo del rechazo
                                            </span>
                                            <textarea
                                                value={rejectForm.data.rejection_reason}
                                                onChange={(event) => rejectForm.setData('rejection_reason', event.target.value)}
                                                className="sa-input min-h-28 w-full px-3 py-3 text-sm"
                                                placeholder="Describe por que el comprobante no corresponde."
                                            />
                                        </label>
                                        <button
                                            type="submit"
                                            disabled={rejectForm.processing || approveForm.processing || rejectForm.data.rejection_reason.trim().length < 5}
                                            className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full border border-[rgba(var(--admin-danger-rgb),0.42)] bg-[rgba(var(--admin-danger-rgb),0.16)] px-4 text-sm font-semibold text-[color:var(--admin-danger)] transition hover:bg-[rgba(var(--admin-danger-rgb),0.22)] disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <XCircle size={16} />
                                            {rejectForm.processing ? 'Rechazando' : 'Rechazar comprobante'}
                                        </button>
                                    </form>
                                </div>
                            </section>
                        ) : (
                            <section className="sa-panel-muted rounded-2xl p-5">
                                <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                                    Comprobante ya revisado
                                </p>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                    Este registro queda en modo consulta. No hay acciones pendientes para este comprobante.
                                </p>
                            </section>
                        )}
                    </div>
                </section>

                <section className="sa-panel rounded-2xl p-5">
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">Historial basico</p>
                    <div className="mt-4 grid gap-3">
                        {history.length === 0 && (
                            <div className="sa-panel-muted rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                                Sin pagos asociados.
                            </div>
                        )}
                        {history.map((item) => (
                            <div key={item.id} className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 md:grid-cols-3 xl:grid-cols-6">
                                <Detail label="Fecha" value={item.date} />
                                <Detail label="Metodo" value={item.method} />
                                <Detail label="Declarado" value={item.declared} />
                                <Detail label="Confirmado" value={item.confirmed} />
                                <div className="sa-panel-soft rounded-2xl p-4">
                                    <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Estado</p>
                                    <div className="mt-2">
                                        <SuperadminBadge tone={item.status.tone}>{item.status.label}</SuperadminBadge>
                                    </div>
                                </div>
                                <Detail label="Verificado" value={item.verifiedAt} />
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </VendedorLayout>
    );
}
