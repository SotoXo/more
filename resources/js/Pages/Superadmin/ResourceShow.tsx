import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminDataTable from '@/Components/Superadmin/SuperadminDataTable';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import { PAYMENT_METHODS } from '@/config/paymentMethods';
import type { LandingPageProps } from '@/types/landing';
import type {
    SuperadminBreadcrumb,
    SuperadminDetailSection,
    SuperadminMetric,
    SuperadminResourceMeta,
    SuperadminTableColumn,
    SuperadminTableRow,
    SuperadminTone,
    SuperadminTimelineItem,
} from '@/types/superadmin';
import { router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { FileText } from 'lucide-react';
import { useEffect, useState } from 'react';

type RecordAction = {
    label: string;
    action: string;
    method?: 'put' | 'patch' | 'post';
    data?: Record<string, string>;
    promptFields?: { key: string; label: string; optional?: boolean }[];
    confirm?: string;
    kind?: 'primary' | 'secondary' | 'ghost' | 'danger';
};

type ResourceShowProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    recordLabel: string;
    summaryMetrics: SuperadminMetric[];
    detailSections: SuperadminDetailSection[];
    timeline: SuperadminTimelineItem[];
    relatedColumns?: SuperadminTableColumn[];
    relatedRows?: SuperadminTableRow[];
    editHref?: string | null;
    recordActions?: RecordAction[];
    paymentForm?: {
        action: string;
        canAdd: boolean;
        saldo: string;
        methods: { label: string; value: string }[];
    } | null;
    receiptPanel?: {
        title: string;
        description: string;
        links: { label: string; href: string }[];
        notice?: string | null;
    } | null;
};

function DetailFieldValue({ field }: { field: SuperadminDetailSection['fields'][number] }) {
    const value = String(field.value);
    const isFileUrl = value.startsWith('/storage/') || value.startsWith('http');
    const isVoucher = field.label.toLowerCase().includes('comprobante') && isFileUrl;

    if (isVoucher) {
        return (
            <div className="space-y-3">
                <img
                    src={value}
                    alt="Comprobante de pago"
                    className="max-h-72 w-full rounded-[1rem] border border-white/10 object-contain"
                />
                <a
                    href={value}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex text-sm text-[rgb(194,214,232)] underline-offset-4 hover:underline"
                >
                    Abrir comprobante en nueva pestana
                </a>
            </div>
        );
    }

    if (isFileUrl) {
        return (
            <a href={value} target="_blank" rel="noreferrer" className="text-[rgb(194,214,232)] underline-offset-4 hover:underline">
                Abrir comprobante
            </a>
        );
    }

    return <>{field.value}</>;
}

function parseMoney(value: string): number {
    const normalized = value.replace(/[^\d.,-]/g, '').replace(/,/g, '');

    return Number(normalized || 0);
}

function formatMoney(value: number): string {
    return `Bs ${value.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

export default function ResourceShow({
    meta,
    breadcrumbs,
    recordLabel,
    summaryMetrics,
    detailSections,
    timeline,
    relatedColumns = [],
    relatedRows = [],
    editHref = null,
    recordActions = [],
    paymentForm = null,
    receiptPanel = null,
}: ResourceShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const serverErrors = (page.props.errors ?? {}) as Record<string, string>;
    const [activeAction, setActiveAction] = useState<RecordAction | null>(null);
    const [actionValues, setActionValues] = useState<Record<string, string>>({});
    const [actionErrors, setActionErrors] = useState<Record<string, string>>({});
    const [actionProcessing, setActionProcessing] = useState(false);
    const [finalPaymentData, setFinalPaymentData] = useState<{
        monto_pago_final: string;
        metodo_pago_final: string;
        obs_res: string;
        comprobante_pago_final: File | null;
    }>({
        monto_pago_final: '',
        metodo_pago_final: 'efectivo',
        obs_res: '',
        comprobante_pago_final: null,
    });
    const [finalPaymentAmountMode, setFinalPaymentAmountMode] = useState<'balance' | 'custom'>('balance');
    const [finalPaymentErrors, setFinalPaymentErrors] = useState<Record<string, string>>({});
    const [finalPaymentProcessing, setFinalPaymentProcessing] = useState(false);
    const moduleStatusTone: SuperadminTone =
        meta.moduleStatus === 'crud'
            ? 'success'
            : meta.moduleStatus === 'not_ready'
            ? 'warning'
            : 'accent';
    const reservationSummary = (() => {
        const fields = detailSections.flatMap((section) => section.fields);
        const valueFor = (labels: string[]) =>
            fields.find((field) => labels.some((label) => field.label.toLowerCase() === label.toLowerCase()))?.value
            ?? summaryMetrics.find((metric) => labels.some((label) => metric.label.toLowerCase() === label.toLowerCase()))?.value
            ?? 'Bs 0.00';
        const total = String(valueFor(['Total de reserva', 'Total']));
        const confirmed = String(valueFor(['Monto confirmado', 'Monto pagado confirmado', 'Pagado']));
        const totalAmount = parseMoney(total);
        const confirmedAmount = parseMoney(confirmed);
        const calculatedBalance = Math.max(0, Number((totalAmount - confirmedAmount).toFixed(2)));
        const balance = formatMoney(calculatedBalance);
        const paymentStatus = String(valueFor(['Estado de pago', 'Pago']));
        const reservationStatus = String(valueFor(['Estado de reserva', 'Estado']));

        return {
            total,
            confirmed,
            balance,
            paymentStatus,
            reservationStatus,
            balanceAmount: calculatedBalance,
        };
    })();
    const finalizationAction = recordActions.find((action) =>
        ['complete', 'settle_completed'].includes(String(action.data?.reservation_action ?? '')),
    );
    const secondaryRecordActions = recordActions.filter((action) => action !== finalizationAction);
    const finalPaymentMethods = [
        { label: PAYMENT_METHODS.cash.label, value: PAYMENT_METHODS.cash.value },
        { label: PAYMENT_METHODS.qr.label, value: PAYMENT_METHODS.qr.value },
        { label: PAYMENT_METHODS.bankTransfer.label, value: PAYMENT_METHODS.bankTransfer.value },
    ];
    const requiresFinalVoucher = ['qr', 'transferencia_bancaria'].includes(finalPaymentData.metodo_pago_final);

    useEffect(() => {
        setFinalPaymentData((current) => ({
            ...current,
            monto_pago_final: reservationSummary.balanceAmount > 0 && finalPaymentAmountMode === 'balance'
                ? reservationSummary.balanceAmount.toFixed(2)
                : current.monto_pago_final,
            metodo_pago_final: current.metodo_pago_final || 'efectivo',
        }));
    }, [finalPaymentAmountMode, reservationSummary.balanceAmount]);

    function openRecordAction(action: RecordAction) {
        const initialValues = { ...(action.data ?? {}) };

        for (const field of action.promptFields ?? []) {
            initialValues[field.key] = field.key === 'metodo_pago_final'
                ? (paymentForm?.methods[0]?.value ?? 'qr')
                : '';
        }

        setActionValues(initialValues);
        setActionErrors({});
        setActiveAction(action);
    }

    function closeRecordAction() {
        if (actionProcessing) {
            return;
        }

        setActiveAction(null);
        setActionValues({});
        setActionErrors({});
    }

    function validateAction(action: RecordAction) {
        const nextErrors: Record<string, string> = {};
        const fields = action.promptFields ?? [];

        for (const field of fields) {
            const value = actionValues[field.key]?.trim() ?? '';

            if (!field.optional && value === '') {
                nextErrors[field.key] = field.key === 'monto_pago_final'
                    ? 'Ingresa el monto recibido.'
                    : field.key.includes('metodo')
                    ? 'Selecciona el metodo de pago.'
                    : 'Completa este campo.';
            }
        }

        if (fields.some((field) => field.key === 'monto_pago_final')) {
            const amount = Number(actionValues.monto_pago_final || 0);
            const balance = reservationSummary.balanceAmount;

            if (!actionValues.monto_pago_final || amount <= 0) {
                nextErrors.monto_pago_final = 'Ingresa el monto recibido.';
            } else if (amount < balance) {
                nextErrors.monto_pago_final = 'El monto recibido debe completar el saldo pendiente.';
            } else if (amount > balance) {
                nextErrors.monto_pago_final = 'El monto no puede ser mayor al saldo pendiente.';
            }

            if (!actionValues.metodo_pago_final) {
                nextErrors.metodo_pago_final = 'Selecciona el metodo de pago.';
            }
        }

        return nextErrors;
    }

    function submitRecordAction() {
        if (!activeAction) {
            return;
        }

        const nextErrors = validateAction(activeAction);
        setActionErrors(nextErrors);

        if (Object.keys(nextErrors).length > 0) {
            return;
        }

        const payload = { ...(activeAction.data ?? {}) };

        for (const field of activeAction.promptFields ?? []) {
            const value = actionValues[field.key]?.trim();

            if (value) {
                payload[field.key] = value;
            }
        }

        router[activeAction.method ?? 'put'](activeAction.action, payload, {
            preserveScroll: true,
            onStart: () => setActionProcessing(true),
            onFinish: () => setActionProcessing(false),
            onSuccess: () => {
                setActiveAction(null);
                setActionValues({});
                setActionErrors({});
            },
        });
    }

    function validateFinalPayment() {
        const nextErrors: Record<string, string> = {};
        const balance = reservationSummary.balanceAmount;
        const amount = Number(finalPaymentData.monto_pago_final || 0);

        if (balance > 0) {
            if (!finalPaymentData.monto_pago_final || amount <= 0) {
                nextErrors.monto_pago_final = 'Ingresa el monto recibido.';
            } else if (amount < balance) {
                nextErrors.monto_pago_final = 'El monto recibido debe completar el saldo pendiente.';
            } else if (amount > balance) {
                nextErrors.monto_pago_final = 'El monto no puede ser mayor al saldo pendiente.';
            }

            if (!finalPaymentData.metodo_pago_final) {
                nextErrors.metodo_pago_final = 'Selecciona el metodo de pago.';
            }

            if (requiresFinalVoucher) {
                const file = finalPaymentData.comprobante_pago_final;
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!file) {
                    nextErrors.comprobante_pago_final = 'Sube el comprobante de pago.';
                } else if (!allowedTypes.includes(file.type)) {
                    nextErrors.comprobante_pago_final = 'El comprobante debe ser una imagen JPG, PNG o WEBP.';
                } else if (file.size > 4 * 1024 * 1024) {
                    nextErrors.comprobante_pago_final = 'El comprobante no debe superar 4MB.';
                }
            }
        }

        return nextErrors;
    }

    function submitFinalPayment() {
        if (!finalizationAction) {
            return;
        }

        const nextErrors = validateFinalPayment();
        setFinalPaymentErrors(nextErrors);

        if (Object.keys(nextErrors).length > 0) {
            return;
        }

        const payload: Record<string, string | File> = { ...(finalizationAction.data ?? {}) };
        const actionMethod = finalizationAction.method ?? 'put';

        if (actionMethod !== 'post') {
            payload._method = actionMethod;
        }

        if (reservationSummary.balanceAmount > 0) {
            payload.monto_pago_final = finalPaymentData.monto_pago_final;
            payload.metodo_pago_final = finalPaymentData.metodo_pago_final;

            if (finalPaymentData.obs_res.trim()) {
                payload.obs_res = finalPaymentData.obs_res.trim();
            }

            if (requiresFinalVoucher && finalPaymentData.comprobante_pago_final) {
                payload.comprobante_pago_final = finalPaymentData.comprobante_pago_final;
            }
        }

        router.post(finalizationAction.action, payload, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setFinalPaymentProcessing(true),
            onFinish: () => setFinalPaymentProcessing(false),
            onSuccess: () => {
                setFinalPaymentErrors({});
                setFinalPaymentData((current) => ({
                    ...current,
                    obs_res: '',
                    comprobante_pago_final: null,
                }));
            },
        });
    }

    const activePromptFields = activeAction?.promptFields ?? [];
    const isPaymentRemainingAction = activePromptFields.some((field) => field.key === 'monto_pago_final');

    return (
        <SuperadminLayout title={`Mati Boutique | ${recordLabel}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={recordLabel}
                    description={`Detalle de ${meta.singular}.`}
                    breadcrumbs={breadcrumbs}
                    actions={[
                        { label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' },
                        ...(editHref ? [{ label: 'Editar', href: editHref, kind: 'secondary' as const }] : []),
                    ]}
                />

                {receiptPanel && (
                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                    Mati Boutique
                                </p>
                                <h2 className="mt-2 text-xl font-semibold text-white">
                                    {receiptPanel.title}
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                    {receiptPanel.description}
                                </p>
                                {receiptPanel.notice && (
                                    <p className="mt-3 rounded-[1rem] border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-stone-300">
                                        {receiptPanel.notice}
                                    </p>
                                )}
                            </div>
                            {receiptPanel.links.length > 0 && (
                                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap lg:justify-end">
                                    {receiptPanel.links.map((link) => (
                                        <a
                                            key={link.href}
                                            href={link.href}
                                            className="sa-button-secondary px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]"
                                        >
                                            {link.label}
                                        </a>
                                    ))}
                                </div>
                            )}
                        </div>
                    </section>
                )}

                {finalizationAction && (
                    <section className="sa-panel rounded-[1.6rem] p-6">
                        <div className="grid gap-6 xl:grid-cols-[1fr_1.1fr]">
                            <div>
                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                    Mati Boutique
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-white">
                                    Finalizar reserva
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                    Registra el pago pendiente y marca la prenda como recogida para generar la venta.
                                </p>

                                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                    {[
                                        ['Total de la reserva', reservationSummary.total],
                                        ['Monto ya confirmado', reservationSummary.confirmed],
                                        ['Saldo pendiente', reservationSummary.balance],
                                        ['Estado de pago', reservationSummary.paymentStatus],
                                        ['Estado de reserva', reservationSummary.reservationStatus],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-[1rem] border border-white/8 bg-[#0d141d] p-4">
                                            <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                {label}
                                            </p>
                                            <p className="mt-2 text-base text-white">{value}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    submitFinalPayment();
                                }}
                                className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-5"
                            >
                                {reservationSummary.balanceAmount > 0 ? (
                                    <div className="space-y-4">
                                        <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4">
                                            <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                Saldo pendiente
                                            </p>
                                            <p className="mt-2 text-xl font-semibold text-white">{reservationSummary.balance}</p>
                                        </div>

                                        <div className="space-y-2">
                                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Opcion de monto
                                            </p>
                                            <div className="grid gap-2 sm:grid-cols-2">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setFinalPaymentAmountMode('balance');
                                                        setFinalPaymentData((current) => ({
                                                            ...current,
                                                            monto_pago_final: reservationSummary.balanceAmount.toFixed(2),
                                                        }));
                                                        setFinalPaymentErrors((current) => ({ ...current, monto_pago_final: '' }));
                                                    }}
                                                    disabled={finalPaymentProcessing}
                                                    className={
                                                        finalPaymentAmountMode === 'balance'
                                                            ? 'rounded-[1rem] border border-[rgba(194,214,232,0.45)] bg-[rgba(194,214,232,0.12)] px-4 py-3 text-left text-sm font-medium text-white transition'
                                                            : 'rounded-[1rem] border border-white/8 bg-black/10 px-4 py-3 text-left text-sm text-stone-300 transition hover:border-white/18 hover:text-white'
                                                    }
                                                >
                                                    <span className="block">Cobrar saldo pendiente</span>
                                                    <span className="mt-1 block text-xs text-stone-400">{reservationSummary.balance}</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setFinalPaymentAmountMode('custom')}
                                                    disabled={finalPaymentProcessing}
                                                    className={
                                                        finalPaymentAmountMode === 'custom'
                                                            ? 'rounded-[1rem] border border-[rgba(194,214,232,0.45)] bg-[rgba(194,214,232,0.12)] px-4 py-3 text-left text-sm font-medium text-white transition'
                                                            : 'rounded-[1rem] border border-white/8 bg-black/10 px-4 py-3 text-left text-sm text-stone-300 transition hover:border-white/18 hover:text-white'
                                                    }
                                                >
                                                    <span className="block">Ingresar monto especifico</span>
                                                    <span className="mt-1 block text-xs text-stone-400">Debe completar el saldo exacto.</span>
                                                </button>
                                            </div>
                                        </div>

                                        <label className="block space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Monto recibido ahora
                                            </span>
                                            <input
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                className="sa-input w-full"
                                                value={finalPaymentData.monto_pago_final}
                                                onChange={(event) => {
                                                    setFinalPaymentData((current) => ({ ...current, monto_pago_final: event.target.value }));
                                                    setFinalPaymentErrors((current) => ({ ...current, monto_pago_final: '' }));
                                                    setFinalPaymentAmountMode('custom');
                                                }}
                                                readOnly={finalPaymentAmountMode === 'balance'}
                                                disabled={finalPaymentProcessing}
                                            />
                                            {finalPaymentErrors.monto_pago_final && (
                                                <p className="text-sm text-[rgb(245,208,210)]">{finalPaymentErrors.monto_pago_final}</p>
                                            )}
                                        </label>

                                        <div className="space-y-2">
                                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Metodo de pago
                                            </p>
                                            <div className="grid gap-2 sm:grid-cols-3">
                                                {finalPaymentMethods.map((method) => (
                                                    <button
                                                        key={method.value}
                                                        type="button"
                                                        onClick={() => {
                                                            setFinalPaymentData((current) => ({
                                                                ...current,
                                                                metodo_pago_final: method.value,
                                                                comprobante_pago_final: method.value === 'efectivo' ? null : current.comprobante_pago_final,
                                                            }));
                                                            setFinalPaymentErrors((current) => ({ ...current, metodo_pago_final: '', comprobante_pago_final: '' }));
                                                        }}
                                                        disabled={finalPaymentProcessing}
                                                        className={
                                                            finalPaymentData.metodo_pago_final === method.value
                                                                ? 'rounded-[1rem] border border-[rgba(194,214,232,0.45)] bg-[rgba(194,214,232,0.12)] px-4 py-3 text-sm font-medium text-white transition'
                                                                : 'rounded-[1rem] border border-white/8 bg-black/10 px-4 py-3 text-sm text-stone-300 transition hover:border-white/18 hover:text-white'
                                                        }
                                                    >
                                                        {method.label}
                                                    </button>
                                                ))}
                                            </div>
                                            {finalPaymentErrors.metodo_pago_final && (
                                                <p className="text-sm text-[rgb(245,208,210)]">{finalPaymentErrors.metodo_pago_final}</p>
                                            )}
                                        </div>

                                        {finalPaymentData.metodo_pago_final === 'efectivo' && (
                                            <div className="rounded-[1rem] border border-[rgba(194,214,232,0.18)] bg-[rgba(194,214,232,0.08)] p-4 text-sm leading-6 text-stone-300">
                                                {PAYMENT_METHODS.cash.instructions}
                                            </div>
                                        )}

                                        {finalPaymentData.metodo_pago_final === 'qr' && (
                                            <div className="grid gap-4 rounded-[1rem] border border-white/8 bg-black/10 p-4 sm:grid-cols-[8rem_1fr]">
                                                <img
                                                    src={PAYMENT_METHODS.qr.qrUrl}
                                                    alt="QR de pago Mati Boutique"
                                                    className="aspect-square w-full rounded-[0.8rem] border border-white/8 bg-white object-contain p-2"
                                                />
                                                <div className="text-sm leading-6 text-stone-300">
                                                    <p className="font-medium text-white">{PAYMENT_METHODS.qr.label}</p>
                                                    <p className="mt-1">{PAYMENT_METHODS.qr.instructions}</p>
                                                </div>
                                            </div>
                                        )}

                                        {finalPaymentData.metodo_pago_final === 'transferencia_bancaria' && (
                                            <div className="rounded-[1rem] border border-white/8 bg-black/10 p-4 text-sm leading-6 text-stone-300">
                                                <p className="font-medium text-white">{PAYMENT_METHODS.bankTransfer.accountLabel}</p>
                                                <p className="mt-1">Titular: {PAYMENT_METHODS.bankTransfer.accountHolder}</p>
                                                <p>Nro. de cuenta: {PAYMENT_METHODS.bankTransfer.accountNumber}</p>
                                            </div>
                                        )}

                                        {requiresFinalVoucher && (
                                            <label className="block space-y-2">
                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                    Comprobante de pago
                                                </span>
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    className="sa-input w-full"
                                                    onChange={(event) => {
                                                        setFinalPaymentData((current) => ({ ...current, comprobante_pago_final: event.target.files?.[0] ?? null }));
                                                        setFinalPaymentErrors((current) => ({ ...current, comprobante_pago_final: '' }));
                                                    }}
                                                    disabled={finalPaymentProcessing}
                                                />
                                                {finalPaymentErrors.comprobante_pago_final && (
                                                    <p className="text-sm text-[rgb(245,208,210)]">{finalPaymentErrors.comprobante_pago_final}</p>
                                                )}
                                            </label>
                                        )}

                                        <label className="block space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                                Observacion
                                            </span>
                                            <textarea
                                                className="sa-input min-h-24 w-full"
                                                value={finalPaymentData.obs_res}
                                                onChange={(event) => setFinalPaymentData((current) => ({ ...current, obs_res: event.target.value }))}
                                                disabled={finalPaymentProcessing}
                                            />
                                        </label>
                                    </div>
                                ) : (
                                    <div className="rounded-[1rem] border border-[rgba(194,214,232,0.18)] bg-[rgba(194,214,232,0.08)] p-4 text-sm leading-6 text-stone-300">
                                        Esta reserva ya no tiene saldo pendiente. Puedes marcarla como recogida para registrar la venta.
                                    </div>
                                )}

                                {Object.values(finalPaymentErrors).some(Boolean) && (
                                    <div className="mt-5 rounded-[1rem] border border-[rgba(183,108,112,0.25)] bg-[rgba(183,108,112,0.10)] p-4 text-sm text-[rgb(245,208,210)]">
                                        Revisa los datos del cierre antes de continuar.
                                    </div>
                                )}

                                <div className="mt-5 flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={finalPaymentProcessing}
                                        className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:opacity-60"
                                    >
                                        {finalPaymentProcessing ? 'Procesando...' : 'Registrar pago y finalizar venta'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                )}

                {secondaryRecordActions.length > 0 && (
                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
                            {secondaryRecordActions.map((action) => (
                                <button
                                    key={action.label}
                                    type="button"
                                    onClick={() => openRecordAction(action)}
                                    className={
                                        action.kind === 'danger'
                                            ? 'rounded-full border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.12)] px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-[rgb(245,208,210)] transition hover:bg-[rgba(183,108,112,0.18)]'
                                            : action.kind === 'primary'
                                            ? 'sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]'
                                            : 'sa-button-secondary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]'
                                    }
                                >
                                    {action.label}
                                </button>
                            ))}
                        </div>
                    </section>
                )}

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {Object.keys(serverErrors).length > 0 && (
                    <section className="sa-panel rounded-[1.6rem] border border-[rgba(183,108,112,0.25)] p-5">
                        <div className="space-y-2 text-sm leading-6 text-[rgb(245,208,210)]">
                            {Object.values(serverErrors).map((message) => (
                                <p key={message}>{message}</p>
                            ))}
                        </div>
                    </section>
                )}

                {(meta.moduleStatusLabel || meta.moduleStatusDescription) && (
                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                {meta.moduleStatusLabel && (
                                    <SuperadminBadge tone={moduleStatusTone}>
                                        {meta.moduleStatusLabel}
                                    </SuperadminBadge>
                                )}
                                {meta.moduleIsSecondary && (
                                    <SuperadminBadge tone="neutral">
                                        Secundario
                                    </SuperadminBadge>
                                )}
                            </div>
                            {meta.moduleStatusDescription && (
                                <p className="max-w-3xl text-sm leading-6 text-stone-300">
                                    {meta.moduleStatusDescription}
                                </p>
                            )}
                        </div>
                    </section>
                )}

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {summaryMetrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="space-y-4">
                    {detailSections.map((section) => (
                        <div key={section.title} className="sa-panel rounded-[1.6rem] p-6">
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
                                {section.fields.map((field) => (
                                    <div
                                        key={`${section.title}-${field.label}`}
                                        className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                    >
                                        <p className="text-[0.72rem] uppercase tracking-[0.16em] text-stone-400">
                                            {field.label}
                                        </p>
                                        <div className="mt-3 text-base leading-7 text-white">
                                            <DetailFieldValue field={field} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}

                    {relatedColumns.length > 0 && relatedRows.length > 0 && (
                        <div className="space-y-4">
                            <div className="sa-panel rounded-[1.6rem] p-5">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                    Registros relacionados
                                </p>
                            </div>
                            <SuperadminDataTable columns={relatedColumns} rows={relatedRows} />
                        </div>
                    )}

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-[#0d141d] text-[rgb(194,214,232)]">
                                <FileText size={18} />
                            </div>
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Historial
                            </p>
                        </div>

                        <div className="mt-5 grid gap-3 lg:grid-cols-3">
                            {timeline.map((item, index) => (
                                <motion.div
                                    key={`${item.title}-${item.date}`}
                                    initial={{ opacity: 0, y: 16 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.28,
                                        delay: index * 0.04,
                                        ease: [0.22, 1, 0.36, 1],
                                    }}
                                    className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="text-sm font-medium text-white">{item.title}</p>
                                        <SuperadminBadge tone={item.tone}>{item.date}</SuperadminBadge>
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-stone-400">
                                        {item.note}
                                    </p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {activeAction && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm">
                        <div className="sa-panel max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[1.6rem] p-6 shadow-2xl">
                            <div className="border-b border-white/8 pb-5">
                                <p className="text-[0.68rem] uppercase tracking-[0.22em] text-stone-400">
                                    Mati Boutique
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-white">
                                    {isPaymentRemainingAction ? 'Registrar pago restante' : activeAction.label}
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-stone-400">
                                    {isPaymentRemainingAction
                                        ? 'Para marcar la reserva como recogida, primero debes completar el saldo pendiente.'
                                        : activeAction.confirm ?? 'Confirma la accion para continuar.'}
                                </p>
                            </div>

                            {isPaymentRemainingAction && (
                                <div className="mt-5 grid gap-3 sm:grid-cols-3">
                                    {[
                                        ['Total de la reserva', reservationSummary.total],
                                        ['Monto ya confirmado', reservationSummary.confirmed],
                                        ['Saldo pendiente', reservationSummary.balance],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-[1rem] border border-white/8 bg-[#0d141d] p-4">
                                            <p className="text-[0.62rem] uppercase tracking-[0.16em] text-stone-500">
                                                {label}
                                            </p>
                                            <p className="mt-2 text-base text-white">{value}</p>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="mt-6 space-y-4">
                                {activePromptFields.map((field) => (
                                    <label key={field.key} className="block space-y-2">
                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                            {field.key === 'monto_pago_final'
                                                ? 'Monto recibido ahora'
                                                : field.key === 'metodo_pago_final'
                                                ? 'Metodo de pago final'
                                                : field.label}
                                        </span>

                                        {field.key === 'metodo_pago_final' ? (
                                            <select
                                                className="sa-input w-full"
                                                value={actionValues[field.key] ?? ''}
                                                onChange={(event) => {
                                                    setActionValues((current) => ({ ...current, [field.key]: event.target.value }));
                                                    setActionErrors((current) => ({ ...current, [field.key]: '' }));
                                                }}
                                                disabled={actionProcessing}
                                            >
                                                {(paymentForm?.methods ?? [
                                                    { label: 'QR', value: 'qr' },
                                                    { label: 'Transferencia bancaria', value: 'transferencia_bancaria' },
                                                ]).map((method) => (
                                                    <option key={method.value} value={method.value}>
                                                        {method.label}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : field.key.includes('obs') ? (
                                            <textarea
                                                className="sa-input min-h-28 w-full"
                                                value={actionValues[field.key] ?? ''}
                                                onChange={(event) => {
                                                    setActionValues((current) => ({ ...current, [field.key]: event.target.value }));
                                                    setActionErrors((current) => ({ ...current, [field.key]: '' }));
                                                }}
                                                disabled={actionProcessing}
                                            />
                                        ) : (
                                            <input
                                                type={field.key.includes('monto') ? 'number' : 'text'}
                                                min={field.key.includes('monto') ? '0.01' : undefined}
                                                step={field.key.includes('monto') ? '0.01' : undefined}
                                                className="sa-input w-full"
                                                value={actionValues[field.key] ?? ''}
                                                onChange={(event) => {
                                                    setActionValues((current) => ({ ...current, [field.key]: event.target.value }));
                                                    setActionErrors((current) => ({ ...current, [field.key]: '' }));
                                                }}
                                                disabled={actionProcessing}
                                            />
                                        )}

                                        {actionErrors[field.key] && (
                                            <p className="text-sm text-[rgb(245,208,210)]">{actionErrors[field.key]}</p>
                                        )}
                                    </label>
                                ))}
                            </div>

                            {Object.values(actionErrors).some(Boolean) && (
                                <div className="mt-5 rounded-[1rem] border border-[rgba(183,108,112,0.25)] bg-[rgba(183,108,112,0.10)] p-4 text-sm text-[rgb(245,208,210)]">
                                    Revisa los datos del formulario antes de continuar.
                                </div>
                            )}

                            <div className="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onClick={closeRecordAction}
                                    disabled={actionProcessing}
                                    className="sa-button-secondary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:opacity-60"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    onClick={submitRecordAction}
                                    disabled={actionProcessing}
                                    className={
                                        activeAction.kind === 'danger'
                                            ? 'rounded-full border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.16)] px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-[rgb(245,208,210)] transition hover:bg-[rgba(183,108,112,0.22)] disabled:opacity-60'
                                            : 'sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:opacity-60'
                                    }
                                >
                                    {actionProcessing
                                        ? 'Procesando...'
                                        : isPaymentRemainingAction
                                        ? 'Registrar pago y finalizar venta'
                                        : 'Confirmar'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SuperadminLayout>
    );
}
