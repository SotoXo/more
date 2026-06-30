import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import VendedorFilterPanel, { type VendedorFilters, type VendedorOption } from '../Partials/VendedorFilterPanel';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyAction, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';
import VendedorSummaryGrid, { type VendedorSummaryItem } from '../Partials/VendedorSummaryGrid';

type DamagedReportRow = VendedorReadOnlyRecord & {
    code: string;
    date: string;
    name: string;
    cause: string;
    damageType: string;
    severity: VendedorBadge;
    quantity: string;
    linked: VendedorBadge;
    stockImpact: VendedorBadge;
    reportedBy: string;
    showHref: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type DamagedProductsIndexProps = {
    reports: DamagedReportRow[];
    summary: VendedorSummaryItem[];
    filters: VendedorFilters;
    options: {
        states: VendedorOption[];
        causes: VendedorOption[];
        damageTypes: VendedorOption[];
        severities: VendedorOption[];
    };
    pagination: {
        links: PaginationLink[];
        meta: {
            from: number | null;
            to: number | null;
            total: number;
        };
    };
    createHref: string;
    scopeLabel: string;
};

const columns: VendedorReadOnlyColumn<DamagedReportRow>[] = [
    { key: 'code', label: 'Codigo', render: (row) => row.code },
    { key: 'date', label: 'Fecha', render: (row) => row.date },
    { key: 'name', label: 'Prenda', render: (row) => row.name },
    { key: 'cause', label: 'Causa', render: (row) => row.cause },
    { key: 'damageType', label: 'Tipo', render: (row) => row.damageType },
    {
        key: 'severity',
        label: 'Severidad',
        render: (row) => (
            <SuperadminBadge tone={row.severity.tone}>
                {row.severity.label}
            </SuperadminBadge>
        ),
    },
    { key: 'quantity', label: 'Cantidad', align: 'right', render: (row) => row.quantity },
    {
        key: 'linked',
        label: 'Vinculo',
        render: (row) => (
            <SuperadminBadge tone={row.linked.tone}>
                {row.linked.label}
            </SuperadminBadge>
        ),
    },
    {
        key: 'stockImpact',
        label: 'Stock',
        render: (row) => (
            <SuperadminBadge tone={row.stockImpact.tone}>
                {row.stockImpact.label}
            </SuperadminBadge>
        ),
    },
    { key: 'reportedBy', label: 'Reportado por', render: (row) => row.reportedBy },
];

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

function Pagination({ links, meta }: { links: PaginationLink[]; meta: DamagedProductsIndexProps['pagination']['meta'] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 rounded-2xl border border-[color:var(--admin-border)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-[color:var(--admin-muted)]">
                {meta.from ?? 0}-{meta.to ?? 0} de {meta.total}
            </p>
            <div className="flex flex-wrap gap-2">
                {links.map((link, index) => {
                    const label = paginationLabel(link.label);
                    const isEdge = index === 0 || index === links.length - 1;

                    if (!link.url) {
                        return (
                            <span
                                key={`${label}-${index}`}
                                className="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-[color:var(--admin-border)] px-3 text-sm text-[color:var(--admin-muted)]"
                            >
                                {index === 0 ? <ChevronLeft size={15} /> : isEdge ? <ChevronRight size={15} /> : label}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={`${label}-${index}`}
                            href={link.url}
                            preserveScroll
                            className={[
                                'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm transition',
                                link.active
                                    ? 'border-[rgba(216,189,143,0.36)] bg-[rgba(216,189,143,0.14)] text-[color:var(--admin-text)]'
                                    : 'border-[color:var(--admin-border)] text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]',
                            ].join(' ')}
                        >
                            {index === 0 ? <ChevronLeft size={15} /> : isEdge ? <ChevronRight size={15} /> : label}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}

export default function Index({
    reports,
    summary,
    filters,
    options,
    pagination,
    createHref,
    scopeLabel,
}: DamagedProductsIndexProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const rows = reports.map((report) => ({
        ...report,
        actions: [{ label: 'Ver detalle', href: report.showHref }] satisfies VendedorReadOnlyAction[],
    }));

    return (
        <VendedorLayout title="Mati Boutique | Prendas danadas">
            <div className="space-y-6">
                <header className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-4xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            Inventario
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            Reportar prendas danadas
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {scopeLabel}
                        </p>
                    </div>
                    <Link
                        href={createHref}
                        className="sa-button-primary inline-flex min-h-11 items-center justify-center gap-2 px-4 text-xs font-semibold"
                    >
                        <Plus size={15} />
                        Nuevo reporte
                    </Link>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />
                <VendedorSummaryGrid items={summary} />

                <VendedorFilterPanel
                    action="/vendedor/prendas-daniadas"
                    resetHref="/vendedor/prendas-daniadas"
                    filters={filters}
                    fields={[
                        { name: 'search', label: 'Busqueda', placeholder: 'Codigo, nombre u observacion' },
                        { name: 'estado', label: 'Estado', type: 'select', options: options.states },
                        { name: 'causa', label: 'Causa', type: 'select', options: options.causes },
                        { name: 'tipo_danio', label: 'Tipo de dano', type: 'select', options: options.damageTypes },
                        { name: 'severidad', label: 'Severidad', type: 'select', options: options.severities },
                        { name: 'fecha_desde', label: 'Desde', type: 'date' },
                        { name: 'fecha_hasta', label: 'Hasta', type: 'date' },
                    ]}
                />

                <section className="sa-panel-muted rounded-2xl p-4 text-sm leading-6 text-[color:var(--admin-muted)]">
                    El vendedor solo reporta. La creacion queda en estado reportado, no modifica cdi_inv, no modifica cre_inv y no registra Kardex.
                </section>

                <VendedorReadOnlyTable
                    rows={rows}
                    columns={columns}
                    emptyTitle="Sin reportes para mostrar"
                    emptyDescription="No hay reportes de prendas danadas con los filtros aplicados."
                />

                <Pagination links={pagination.links} meta={pagination.meta} />
            </div>
        </VendedorLayout>
    );
}
