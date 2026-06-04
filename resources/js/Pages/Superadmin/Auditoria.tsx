import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { SuperadminMetric, SuperadminTone } from '@/types/superadmin';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, Filter, RotateCcw, Search, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useMemo, useState } from 'react';

type Option = {
    label: string;
    value: string;
};

type AuditFilters = {
    fecha_desde: string;
    fecha_hasta: string;
    usuario: string;
    tabla: string;
    accion: string;
    modulo: string;
    nivel: string;
    buscar: string;
};

type AuditRow = {
    id: number;
    code: string;
    date: string;
    user: string;
    table: string;
    tableLabel: string;
    record: string;
    action: string;
    actionLabel: string;
    module: string;
    moduleLabel: string;
    level: string;
    levelLabel: string;
    tone: SuperadminTone;
    fields: string[];
    fieldsLabel: string;
    description: string;
    detailHref: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type AuditPagination = {
    data: AuditRow[];
    links: PaginationLink[];
    meta: {
        from: number | null;
        to: number | null;
        total: number;
    };
};

type SelectedAuditLog = {
    code: string;
    date: string;
    user: string;
    table: string;
    record: string;
    action: string;
    module: string;
    level: string;
    tone: SuperadminTone;
    description: string;
    fields: string[];
    oldValues: Record<string, unknown>;
    newValues: Record<string, unknown>;
    ip: string;
    route: string;
    userAgent: string;
};

type AuditoriaProps = {
    metrics: SuperadminMetric[];
    auditLogs: AuditPagination;
    filters: AuditFilters;
    options: {
        users: Option[];
        tables: Option[];
        actions: Option[];
        modules: Option[];
        levels: Option[];
    };
    selectedAuditLog: SelectedAuditLog | null;
    closeDetailHref: string;
};

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

function jsonText(value: Record<string, unknown>) {
    return Object.keys(value ?? {}).length > 0
        ? JSON.stringify(value, null, 2)
        : 'Sin valores registrados.';
}

export default function Auditoria({
    metrics,
    auditLogs,
    filters,
    options,
    selectedAuditLog,
    closeDetailHref,
}: AuditoriaProps) {
    const [localFilters, setLocalFilters] = useState<AuditFilters>(filters);
    const oldValuesText = useMemo(
        () => selectedAuditLog ? jsonText(selectedAuditLog.oldValues) : '',
        [selectedAuditLog],
    );
    const newValuesText = useMemo(
        () => selectedAuditLog ? jsonText(selectedAuditLog.newValues) : '',
        [selectedAuditLog],
    );

    useEffect(() => {
        setLocalFilters(filters);
    }, [filters]);

    const updateFilter = (key: keyof AuditFilters, value: string) => {
        setLocalFilters((current) => ({ ...current, [key]: value }));
    };

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get('/superadmin/auditoria', Object.fromEntries(
            Object.entries(localFilters).filter(([, value]) => value !== ''),
        ), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <SuperadminLayout title="Mati Boutique | Auditoría crítica">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Auditoría"
                    title="Auditoría crítica"
                    description="Trazabilidad de cambios sensibles con valores anteriores y nuevos."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Auditoría crítica' },
                    ]}
                    actions={[
                        { label: 'Registro de actividad', href: '/superadmin/logs', kind: 'ghost' },
                    ]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <form onSubmit={applyFilters} className="space-y-5">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-[#111922] text-stone-300">
                                    <Filter size={17} />
                                </span>
                                <div>
                                    <p className="text-sm font-medium text-white">Filtros</p>
                                    <p className="text-xs text-stone-400">
                                        {auditLogs.meta.total} auditorías encontradas
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Link
                                    href="/superadmin/auditoria"
                                    className="sa-button-ghost inline-flex min-h-10 items-center gap-2 px-4 text-xs uppercase"
                                >
                                    <RotateCcw size={14} />
                                    Limpiar
                                </Link>
                                <button
                                    type="submit"
                                    className="sa-button-secondary inline-flex min-h-10 items-center gap-2 px-4 text-xs uppercase"
                                >
                                    <Search size={14} />
                                    Aplicar
                                </button>
                            </div>
                        </div>

                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <FilterField label="Fecha desde">
                                <input
                                    type="date"
                                    value={localFilters.fecha_desde}
                                    onChange={(event) => updateFilter('fecha_desde', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                />
                            </FilterField>
                            <FilterField label="Fecha hasta">
                                <input
                                    type="date"
                                    value={localFilters.fecha_hasta}
                                    onChange={(event) => updateFilter('fecha_hasta', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                />
                            </FilterField>
                            <FilterSelect label="Usuario" value={localFilters.usuario} options={options.users} onChange={(value) => updateFilter('usuario', value)} />
                            <FilterSelect label="Tabla" value={localFilters.tabla} options={options.tables} onChange={(value) => updateFilter('tabla', value)} />
                            <FilterSelect label="Acción" value={localFilters.accion} options={options.actions} onChange={(value) => updateFilter('accion', value)} />
                            <FilterSelect label="Módulo" value={localFilters.modulo} options={options.modules} onChange={(value) => updateFilter('modulo', value)} />
                            <FilterSelect label="Nivel" value={localFilters.nivel} options={options.levels} onChange={(value) => updateFilter('nivel', value)} />
                            <FilterField label="Buscar">
                                <input
                                    type="search"
                                    value={localFilters.buscar}
                                    placeholder="Descripción"
                                    onChange={(event) => updateFilter('buscar', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white placeholder:text-stone-500"
                                />
                            </FilterField>
                        </div>
                    </form>
                </section>

                <section className="sa-panel overflow-hidden rounded-[1.6rem]">
                    <div className="border-b border-white/8 px-5 py-4">
                        <p className="text-sm font-medium text-white">Cambios auditados</p>
                    </div>

                    {auditLogs.data.length === 0 ? (
                        <div className="px-5 py-14 text-center">
                            <p className="text-lg text-white">Sin auditoría crítica para los filtros actuales</p>
                            <p className="mt-2 text-sm text-stone-400">
                                Los cambios críticos aparecerán aquí cuando los observers detecten campos sensibles.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="min-w-full divide-y divide-white/8 text-left">
                                    <thead className="bg-[#0d141d] text-xs uppercase text-stone-400">
                                        <tr>
                                            <th className="px-5 py-4 font-medium">Fecha</th>
                                            <th className="px-5 py-4 font-medium">Usuario</th>
                                            <th className="px-5 py-4 font-medium">Tabla</th>
                                            <th className="px-5 py-4 font-medium">Acción</th>
                                            <th className="px-5 py-4 font-medium">Campos</th>
                                            <th className="px-5 py-4 font-medium">Nivel</th>
                                            <th className="px-5 py-4 font-medium">Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/8">
                                        {auditLogs.data.map((entry) => (
                                            <tr key={entry.id} className="align-top text-sm text-stone-300">
                                                <td className="whitespace-nowrap px-5 py-4 text-white">{entry.date}</td>
                                                <td className="px-5 py-4">{entry.user}</td>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium text-white">{entry.tableLabel}</p>
                                                    <p className="mt-1 text-xs text-stone-400">{entry.record}</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <div className="flex flex-col gap-2">
                                                        <SuperadminBadge tone="accent">{entry.moduleLabel}</SuperadminBadge>
                                                        <span className="text-xs text-stone-400">{entry.actionLabel}</span>
                                                    </div>
                                                </td>
                                                <td className="max-w-md px-5 py-4 leading-6">{entry.fieldsLabel}</td>
                                                <td className="px-5 py-4">
                                                    <SuperadminBadge tone={entry.tone}>{entry.levelLabel}</SuperadminBadge>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <Link
                                                        href={entry.detailHref}
                                                        preserveScroll
                                                        className="sa-button-ghost inline-flex h-10 w-10 items-center justify-center p-0"
                                                        title="Ver detalle"
                                                    >
                                                        <Eye size={16} />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-white/8 lg:hidden">
                                {auditLogs.data.map((entry) => (
                                    <article key={entry.id} className="space-y-4 px-5 py-5">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium text-white">{entry.tableLabel} {entry.record}</p>
                                                <p className="mt-1 text-xs text-stone-400">{entry.date}</p>
                                            </div>
                                            <SuperadminBadge tone={entry.tone}>{entry.levelLabel}</SuperadminBadge>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <SuperadminBadge tone="accent">{entry.moduleLabel}</SuperadminBadge>
                                            <SuperadminBadge tone="neutral">{entry.actionLabel}</SuperadminBadge>
                                        </div>
                                        <p className="text-sm leading-6 text-stone-300">{entry.fieldsLabel}</p>
                                        <p className="text-xs text-stone-400">{entry.user}</p>
                                        <Link
                                            href={entry.detailHref}
                                            preserveScroll
                                            className="sa-button-secondary inline-flex min-h-10 items-center gap-2 px-4 text-xs uppercase"
                                        >
                                            <Eye size={14} />
                                            Ver detalle
                                        </Link>
                                    </article>
                                ))}
                            </div>
                        </>
                    )}

                    <div className="flex flex-col gap-3 border-t border-white/8 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-stone-400">
                            {auditLogs.meta.from ?? 0}-{auditLogs.meta.to ?? 0} de {auditLogs.meta.total}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {auditLogs.links.map((link, index) => {
                                const label = paginationLabel(link.label);
                                const isEdge = index === 0 || index === auditLogs.links.length - 1;

                                if (!link.url) {
                                    return (
                                        <span
                                            key={`${label}-${index}`}
                                            className="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-white/8 px-3 text-sm text-stone-500"
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
                                            'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm transition duration-200',
                                            link.active
                                                ? 'border-[rgba(216,189,143,0.36)] bg-[rgba(216,189,143,0.14)] text-white'
                                                : 'border-white/8 text-stone-300 hover:border-[rgba(126,154,178,0.28)] hover:bg-[#111922] hover:text-white',
                                        ].join(' ')}
                                    >
                                        {index === 0 ? <ChevronLeft size={15} /> : isEdge ? <ChevronRight size={15} /> : label}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {selectedAuditLog && (
                    <div className="fixed inset-0 z-50 flex items-end bg-black/60 p-0 backdrop-blur-sm sm:items-center sm:p-6">
                        <div className="sa-panel max-h-[92vh] w-full overflow-y-auto rounded-t-[1.6rem] p-5 shadow-2xl sm:mx-auto sm:max-w-5xl sm:rounded-[1.6rem] sm:p-6">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <SuperadminBadge tone={selectedAuditLog.tone}>{selectedAuditLog.level}</SuperadminBadge>
                                        <SuperadminBadge tone="neutral">{selectedAuditLog.code}</SuperadminBadge>
                                    </div>
                                    <p className="mt-4 text-xl font-medium text-white">Detalle de auditoría</p>
                                    <p className="mt-2 text-sm leading-6 text-stone-300">
                                        {selectedAuditLog.description}
                                    </p>
                                </div>
                                <Link
                                    href={closeDetailHref}
                                    preserveScroll
                                    className="sa-button-ghost inline-flex h-10 w-10 shrink-0 items-center justify-center p-0"
                                    title="Cerrar"
                                >
                                    <X size={16} />
                                </Link>
                            </div>

                            <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                {[
                                    ['Fecha', selectedAuditLog.date],
                                    ['Usuario', selectedAuditLog.user],
                                    ['Tabla', selectedAuditLog.table],
                                    ['Registro', selectedAuditLog.record],
                                    ['Acción', selectedAuditLog.action],
                                    ['Módulo', selectedAuditLog.module],
                                    ['IP', selectedAuditLog.ip],
                                    ['Ruta', selectedAuditLog.route],
                                ].map(([label, value]) => (
                                    <div key={label} className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                        <p className="text-xs uppercase text-stone-400">{label}</p>
                                        <p className="mt-2 break-words text-sm text-white">{value}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-5 rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-xs uppercase text-stone-400">Campos modificados</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {selectedAuditLog.fields.length > 0 ? selectedAuditLog.fields.map((field) => (
                                        <SuperadminBadge key={field} tone="accent">{field}</SuperadminBadge>
                                    )) : <span className="text-sm text-stone-400">Sin campos registrados.</span>}
                                </div>
                            </div>

                            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                                <JsonPanel title="Valores anteriores" value={oldValuesText} />
                                <JsonPanel title="Valores nuevos" value={newValuesText} />
                            </div>

                            <div className="mt-5 rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-xs uppercase text-stone-400">User agent</p>
                                <p className="mt-2 break-words text-sm text-white">{selectedAuditLog.userAgent}</p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SuperadminLayout>
    );
}

function FilterField({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="space-y-2 text-xs uppercase text-stone-400">
            {label}
            {children}
        </label>
    );
}

function FilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <FilterField label={label}>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
            >
                <option value="">Todos</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FilterField>
    );
}

function JsonPanel({ title, value }: { title: string; value: string }) {
    return (
        <div className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
            <p className="text-xs uppercase text-stone-400">{title}</p>
            <pre className="mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-stone-200">
                {value}
            </pre>
        </div>
    );
}
