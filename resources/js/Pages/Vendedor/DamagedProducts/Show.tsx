import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminTone } from '@/types/superadmin';
import { Link, usePage } from '@inertiajs/react';
import { RotateCcw, ShieldAlert } from 'lucide-react';

type DamagedReport = {
    id: number;
    code: string;
    name: string;
    description: string;
    causeLabel: string;
    damageTypeLabel: string;
    severityLabel: string;
    severityTone: SuperadminTone;
    quantity: number;
    statusLabel: string;
    statusTone: SuperadminTone;
    observation: string;
    reviewObservation: string;
    reportedAt: string;
    reviewedAt: string;
    reportedBy: string;
    reviewedBy: string;
    affectsInventory: boolean;
    inventory: {
        linked: boolean;
        inventoryId: number | null;
        variantId: number | null;
        productId: number | null;
        product: string;
        sku: string;
        color: string;
        size: string;
        imageUrl: string | null;
        availableStock: number | null;
        committedStock: number | null;
        location: string;
        variantStatusLabel: string;
    };
    images: Array<{
        id: number;
        url: string | null;
        description: string;
        order: number;
    }>;
};

type DamagedProductsShowProps = {
    report: DamagedReport;
    indexHref: string;
    scopeLabel: string;
};

export default function Show({ report, indexHref, scopeLabel }: DamagedProductsShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    return (
        <VendedorLayout title={`Mati Boutique | ${report.code}`}>
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            Inventario
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            {report.code}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {scopeLabel}
                        </p>
                    </div>
                    <Link href={indexHref} className="sa-button-ghost inline-flex min-h-11 items-center justify-center gap-2 px-4 text-xs font-semibold">
                        <RotateCcw size={15} />
                        Volver
                    </Link>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard label="Estado" value={report.statusLabel} tone={report.statusTone} />
                    <SummaryCard label="Severidad" value={report.severityLabel} tone={report.severityTone} />
                    <SummaryCard label="Cantidad" value={String(report.quantity)} />
                    <SummaryCard label="Stock" value="No modificado" tone="success" />
                </section>

                <section className="rounded-2xl border border-[rgba(126,154,178,0.24)] bg-[rgba(126,154,178,0.1)] px-5 py-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-start gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] text-[color:var(--admin-text)]">
                                <ShieldAlert size={17} />
                            </span>
                            <div>
                                <p className="text-sm font-medium text-[color:var(--admin-text)]">
                                    Este reporte esta pendiente de revision administrativa.
                                </p>
                                <p className="mt-1 text-xs leading-5 text-[color:var(--admin-muted)]">
                                    Desde vendedor no se puede confirmar dano, quitar stock, reparar, dar baja definitiva ni resolver reportes.
                                </p>
                            </div>
                        </div>
                        <SuperadminBadge tone={report.affectsInventory ? 'warning' : 'success'}>
                            {report.affectsInventory ? 'Revisar en superadmin' : 'No afecta inventario'}
                        </SuperadminBadge>
                    </div>
                </section>

                <section className="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div className="space-y-5">
                        <div className="sa-panel rounded-2xl p-5">
                            <div className="flex flex-wrap items-center gap-2 border-b border-[color:var(--admin-border)] pb-4">
                                <SuperadminBadge tone={report.statusTone}>{report.statusLabel}</SuperadminBadge>
                                <SuperadminBadge tone={report.severityTone}>{report.severityLabel}</SuperadminBadge>
                                <SuperadminBadge tone="accent">{report.code}</SuperadminBadge>
                            </div>

                            <div className="mt-5 grid gap-4 md:grid-cols-2">
                                <Detail label="Prenda reportada" value={report.name} />
                                <Detail label="Fecha reporte" value={report.reportedAt} />
                                <Detail label="Causa" value={report.causeLabel} />
                                <Detail label="Tipo de dano" value={report.damageTypeLabel} />
                                <Detail label="Reportado por" value={report.reportedBy} />
                                <Detail label="Revisado por" value={report.reviewedBy} />
                                <Detail label="Fecha revision" value={report.reviewedAt} />
                                <Detail label="Afecta inventario" value={report.affectsInventory ? 'Revisar decision administrativa' : 'No modifica stock'} />
                            </div>

                            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                <TextBlock label="Descripcion" value={report.description} />
                                <TextBlock label="Observacion inicial" value={report.observation} />
                                <TextBlock label="Observacion administrativa" value={report.reviewObservation} />
                            </div>
                        </div>

                        <div className="sa-panel rounded-2xl p-5">
                            <div className="flex items-center justify-between gap-3 border-b border-[color:var(--admin-border)] pb-4">
                                <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">Inventario vinculado</p>
                                <SuperadminBadge tone={report.inventory.linked ? 'accent' : 'neutral'}>
                                    {report.inventory.linked ? 'Vinculado' : 'Sin vinculo'}
                                </SuperadminBadge>
                            </div>

                            <div className="mt-5 grid gap-5 xl:grid-cols-[18rem_minmax(0,1fr)]">
                                <div className="overflow-hidden rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)]">
                                    {report.inventory.imageUrl ? (
                                        <img src={report.inventory.imageUrl} alt={report.inventory.product} className="h-72 w-full object-cover" />
                                    ) : (
                                        <div className="flex h-72 items-center justify-center px-4 text-center text-sm text-[color:var(--admin-muted)]">
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
                                    <Detail label="Ubicacion" value={report.inventory.location} />
                                    <Detail label="Estado variante" value={report.inventory.variantStatusLabel} />
                                    <Detail label="Inventario" value={report.inventory.inventoryId ? `#${report.inventory.inventoryId}` : 'Sin inventario'} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-5">
                        <div className="sa-panel rounded-2xl p-5">
                            <div className="flex items-center justify-between gap-3 border-b border-[color:var(--admin-border)] pb-4">
                                <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">Evidencia</p>
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
                                            className="block overflow-hidden rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-3 transition hover:border-[rgba(126,154,178,0.3)]"
                                        >
                                            {image.url ? (
                                                <img src={image.url} alt={image.description} className="h-56 w-full rounded-xl object-cover" />
                                            ) : (
                                                <div className="flex h-56 items-center justify-center rounded-xl text-[color:var(--admin-muted)]">
                                                    Sin vista previa
                                                </div>
                                            )}
                                            <p className="mt-3 text-sm text-[color:var(--admin-text)]">{image.description}</p>
                                            <p className="mt-1 text-xs text-[color:var(--admin-muted)]">Orden {image.order}</p>
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <div className="mt-5 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] px-4 py-12 text-center text-sm text-[color:var(--admin-muted)]">
                                    Sin evidencia fotografica.
                                </div>
                            )}
                        </div>

                        <div className="sa-panel rounded-2xl p-5">
                            <div className="border-b border-[color:var(--admin-border)] pb-4">
                                <p className="text-xs font-semibold uppercase text-[color:var(--admin-muted)]">Acciones administrativas</p>
                            </div>
                            <p className="mt-5 text-sm leading-6 text-[color:var(--admin-muted)]">
                                No hay acciones disponibles en el panel vendedor. La revision, confirmacion, reparacion, baja definitiva o resolucion se mantiene en el modulo superadmin.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </VendedorLayout>
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
        <div className="sa-panel rounded-2xl p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
                    <p className="mt-3 text-xl text-[color:var(--admin-text)]">{value}</p>
                </div>
                <SuperadminBadge tone={tone}>{label}</SuperadminBadge>
            </div>
        </div>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-3 break-words text-sm leading-6 text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

function TextBlock({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}
