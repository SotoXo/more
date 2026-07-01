import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminTone } from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardCheck,
    Eye,
    RotateCcw,
    ShieldCheck,
    Trash2,
    Wrench,
    XCircle,
} from 'lucide-react';
import type { FormEvent } from 'react';

type Option = {
    label: string;
    value: string;
};

type ReportAction = {
    key: 'en_revision' | 'confirmar' | 'descartar' | 'reparar' | 'baja_definitiva' | 'resolver';
    label: string;
    url: string;
    tone: SuperadminTone;
    confirm: string;
};

type DamagedReport = {
    id: number;
    code: string;
    name: string;
    description: string;
    cause: string;
    causeLabel: string;
    damageType: string;
    damageTypeLabel: string;
    severity: string;
    severityLabel: string;
    severityTone: SuperadminTone;
    quantity: number;
    status: string;
    statusLabel: string;
    statusTone: SuperadminTone;
    decision: string | null;
    decisionLabel: string;
    observation: string;
    reviewObservation: string;
    reportedAt: string;
    reviewedAt: string;
    affectsInventory: boolean;
    inventoryImpactMessage: string;
    stockActionStatus: string;
    reportedBy: string;
    reviewedBy: string;
    inventory: {
        linked: boolean;
        affectsInventory: boolean;
        inventoryId: number | null;
        variantId: number | null;
        productId: number | null;
        product: string;
        sku: string;
        color: string;
        size: string;
        imageUrl: string | null;
        stock: number | null;
        reserved: number | null;
        availableStock: number | null;
        committedStock: number | null;
        location: string;
        variantStatusLabel: string;
    };
    images: Array<{
        id: number;
        url: string | null;
        path: string;
        description: string;
        order: number;
    }>;
};

type DamagedProductsShowProps = {
    meta: {
        sectionLabel: string;
        title: string;
        description: string;
        indexHref: string;
    };
    breadcrumbs: SuperadminBreadcrumb[];
    report: DamagedReport;
    decisionOptions: Option[];
    actions: ReportAction[];
};

type ReviewForm = {
    decision_pdr: string;
    observacion_revision_pdr: string;
};

function actionClass(tone: SuperadminTone) {
    if (tone === 'danger') {
        return 'border-[rgba(183,108,112,0.32)] bg-[rgba(183,108,112,0.14)] text-[rgb(245,208,210)] hover:bg-[rgba(183,108,112,0.2)]';
    }

    if (tone === 'success') {
        return 'border-[rgba(113,168,148,0.32)] bg-[rgba(113,168,148,0.14)] text-[rgb(210,238,225)] hover:bg-[rgba(113,168,148,0.2)]';
    }

    if (tone === 'warning') {
        return 'border-[rgba(201,160,106,0.32)] bg-[rgba(201,160,106,0.14)] text-[rgb(244,224,194)] hover:bg-[rgba(201,160,106,0.2)]';
    }

    return 'border-[rgba(126,154,178,0.32)] bg-[rgba(126,154,178,0.14)] text-[rgb(210,227,239)] hover:bg-[rgba(126,154,178,0.2)]';
}

function ActionIcon({ action }: { action: ReportAction['key'] }) {
    if (action === 'en_revision') {
        return <Eye size={15} />;
    }

    if (action === 'confirmar') {
        return <CheckCircle2 size={15} />;
    }

    if (action === 'descartar') {
        return <XCircle size={15} />;
    }

    if (action === 'reparar') {
        return <Wrench size={15} />;
    }

    if (action === 'baja_definitiva') {
        return <Trash2 size={15} />;
    }

    return <ShieldCheck size={15} />;
}

export default function DamagedProductsShow({
    meta,
    breadcrumbs,
    report,
    decisionOptions,
    actions,
}: DamagedProductsShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<ReviewForm>({
        decision_pdr: report.decision ?? '',
        observacion_revision_pdr: '',
    });
    const errorEntries = Object.entries(errors as Record<string, string>);
    const motivoRequeridoActions = ['descartar', 'baja_definitiva'];

    const submitAction = (event: FormEvent<HTMLFormElement>, action: ReportAction) => {
        event.preventDefault();

        if (motivoRequeridoActions.includes(action.key) && data.observacion_revision_pdr.trim().length < 5) {
            setError('observacion_revision_pdr', 'Ingresa el motivo de la acción (mínimo 5 caracteres).');
            return;
        }

        clearErrors('observacion_revision_pdr');

        if (!window.confirm(action.confirm)) {
            return;
        }

        post(action.url, {
            preserveScroll: true,
        });
    };

    return (
        <SuperadminLayout title={`Mati Boutique | ${report.code}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={report.code}
                    description={meta.description}
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                {errorEntries.length > 0 && (
                    <section className="rounded-[1.4rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[rgb(245,208,210)]">
                            No se pudo completar la acción.
                        </p>
                        <ul className="mt-3 space-y-1 text-sm text-[rgb(245,208,210)]">
                            {errorEntries.map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard label="Estado" value={report.statusLabel} tone={report.statusTone} />
                    <SummaryCard label="Gravedad" value={report.severityLabel} tone={report.severityTone} />
                    <SummaryCard label="Cantidad" value={String(report.quantity)} />
                    <SummaryCard label="Decisión" value={report.decisionLabel} />
                </section>

                <section className={[
                    'rounded-[1.4rem] border px-5 py-4',
                    report.affectsInventory
                        ? 'border-[rgba(201,160,106,0.28)] bg-[rgba(201,160,106,0.1)]'
                        : 'border-[rgba(126,154,178,0.24)] bg-[rgba(126,154,178,0.1)]',
                ].join(' ')}>
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-medium text-white">{report.inventoryImpactMessage}</p>
                            <p className="mt-1 text-xs leading-5 text-stone-400">{report.stockActionStatus}</p>
                        </div>
                        <SuperadminBadge tone={report.affectsInventory ? 'warning' : 'neutral'}>
                            {report.affectsInventory ? 'Quita stock al confirmar' : 'Solo administrativo'}
                        </SuperadminBadge>
                    </div>
                </section>

                <section className="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div className="space-y-5">
                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex flex-wrap items-center gap-2 border-b border-white/8 pb-5">
                                <SuperadminBadge tone={report.statusTone}>{report.statusLabel}</SuperadminBadge>
                                <SuperadminBadge tone={report.severityTone}>{report.severityLabel}</SuperadminBadge>
                                <SuperadminBadge tone="accent">{report.code}</SuperadminBadge>
                            </div>

                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                <Detail label="Prenda reportada" value={report.name} />
                                <Detail label="Fecha reporte" value={report.reportedAt} />
                                <Detail label="Causa" value={report.causeLabel} />
                                <Detail label="Tipo de daño" value={report.damageTypeLabel} />
                                <Detail label="Reportado por" value={report.reportedBy} />
                                <Detail label="Revisado por" value={report.reviewedBy} />
                                <Detail label="Fecha revisión" value={report.reviewedAt} />
                                <Detail label="Afecta inventario" value={report.affectsInventory ? 'Quita stock al confirmar el daño' : 'No modifica stock'} />
                            </div>

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <TextBlock label="Descripción" value={report.description} />
                                <TextBlock label="Observación inicial" value={report.observation} />
                                <TextBlock label="Observación de revisión" value={report.reviewObservation} />
                            </div>
                        </div>

                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex items-center justify-between gap-3 border-b border-white/8 pb-5">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Inventario vinculado</p>
                                <SuperadminBadge tone={report.inventory.linked ? 'accent' : 'neutral'}>
                                    {report.inventory.linked ? 'Vinculado' : 'Sin vínculo'}
                                </SuperadminBadge>
                            </div>

                            <div className="mt-6 grid gap-5 xl:grid-cols-[18rem_minmax(0,1fr)]">
                                <div className="overflow-hidden rounded-[1.2rem] border border-white/8 bg-[#0d141d]">
                                    {report.inventory.imageUrl ? (
                                        <img src={report.inventory.imageUrl} alt={report.inventory.product} className="h-72 w-full object-cover" />
                                    ) : (
                                        <div className="flex h-72 items-center justify-center bg-[#090d13] px-4 text-center text-sm text-stone-500">
                                            Sin imagen registrada
                                        </div>
                                    )}
                                </div>
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <Detail label="Prenda" value={report.inventory.product} />
                                    <Detail label="SKU" value={report.inventory.sku} />
                                    <Detail label="Color" value={report.inventory.color} />
                                    <Detail label="Talla" value={report.inventory.size} />
                                    <Detail label="Disponible cdi_inv" value={report.inventory.availableStock === null ? 'Sin inventario' : String(report.inventory.availableStock)} />
                                    <Detail label="Comprometido cre_inv" value={report.inventory.committedStock === null ? 'Sin inventario' : String(report.inventory.committedStock)} />
                                    <Detail label="Ubicación" value={report.inventory.location} />
                                    <Detail label="Estado variante" value={report.inventory.variantStatusLabel} />
                                    <Detail label="Inventario" value={report.inventory.inventoryId ? `#${report.inventory.inventoryId}` : 'Sin inventario'} />
                                    <Detail label="Variante" value={report.inventory.variantId ? `#${report.inventory.variantId}` : 'Sin variante'} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-5">
                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex items-center justify-between gap-3 border-b border-white/8 pb-5">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Evidencia</p>
                                <SuperadminBadge tone="neutral">{report.images.length} imagen{report.images.length === 1 ? '' : 'es'}</SuperadminBadge>
                            </div>

                            {report.images.length > 0 ? (
                                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                    {report.images.map((image) => (
                                        <a
                                            key={image.id}
                                            href={image.url ?? '#'}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="block overflow-hidden rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-3 transition hover:border-[rgba(126,154,178,0.3)]"
                                        >
                                            {image.url ? (
                                                <img src={image.url} alt={image.description} className="h-56 w-full rounded-[1rem] object-cover" />
                                            ) : (
                                                <div className="flex h-56 items-center justify-center rounded-[1rem] bg-[#090d13] text-stone-500">
                                                    Sin vista previa
                                                </div>
                                            )}
                                            <p className="mt-3 text-sm text-white">{image.description}</p>
                                            <p className="mt-1 text-xs text-stone-400">Orden {image.order}</p>
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <div className="mt-5 rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-12 text-center text-sm text-stone-400">
                                    Sin evidencia fotográfica.
                                </div>
                            )}
                        </div>

                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex items-center gap-3 border-b border-white/8 pb-5">
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl border border-white/8 bg-[#111922] text-stone-300">
                                    <ClipboardCheck size={17} />
                                </span>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Revisión</p>
                            </div>

                            <div className="mt-5 space-y-4">
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Decisión</span>
                                    <select
                                        value={data.decision_pdr}
                                        onChange={(event) => setData('decision_pdr', event.target.value)}
                                        className="sa-input w-full"
                                        disabled={processing || actions.length === 0}
                                    >
                                        <option value="">Sin decisión</option>
                                        {decisionOptions.map((option) => (
                                            <option key={option.value} value={option.value}>{option.label}</option>
                                        ))}
                                    </select>
                                </label>

                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Observación de revisión (obligatoria para descartar o dar de baja)</span>
                                    <textarea
                                        value={data.observacion_revision_pdr}
                                        onChange={(event) => setData('observacion_revision_pdr', event.target.value)}
                                        className="sa-input min-h-28 w-full resize-none"
                                        disabled={processing || actions.length === 0}
                                    />
                                </label>

                                {actions.length > 0 ? (
                                    <div className="grid gap-3">
                                        {actions.map((action) => (
                                            <form key={action.key} onSubmit={(event) => submitAction(event, action)}>
                                                <button
                                                    type="submit"
                                                    disabled={processing}
                                                    className={`inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full border px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em] transition disabled:cursor-not-allowed disabled:opacity-70 ${actionClass(action.tone)}`}
                                                >
                                                    <ActionIcon action={action.key} />
                                                    {action.label}
                                                </button>
                                            </form>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-8 text-center text-sm text-stone-400">
                                        Este reporte no tiene acciones pendientes.
                                    </div>
                                )}
                            </div>
                        </div>

                        <Link
                            href={meta.indexHref}
                            className="sa-button-ghost inline-flex min-h-11 w-full items-center justify-center gap-2 px-4 text-[0.72rem] uppercase tracking-[0.18em]"
                        >
                            <RotateCcw size={15} />
                            Volver al listado
                        </Link>
                    </div>
                </section>
            </div>
        </SuperadminLayout>
    );
}

function SummaryCard({
    label,
    value,
    tone = 'neutral',
}: {
    label: string;
    value: string;
    tone?: SuperadminTone;
}) {
    return (
        <div className="sa-panel rounded-[1.6rem] p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">{label}</p>
                    <p className="mt-3 text-xl text-white">{value}</p>
                </div>
                <SuperadminBadge tone={tone}>{label}</SuperadminBadge>
            </div>
        </div>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
            <p className="text-[0.68rem] uppercase tracking-[0.16em] text-stone-400">{label}</p>
            <p className="mt-3 break-words text-sm leading-6 text-white">{value}</p>
        </div>
    );
}

function TextBlock({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
            <p className="text-[0.68rem] uppercase tracking-[0.16em] text-stone-400">{label}</p>
            <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-stone-200">{value}</p>
        </div>
    );
}
