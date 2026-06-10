import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { SuperadminMetric, SuperadminTone } from '@/types/superadmin';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, Filter, RotateCcw, Search, X } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';

type Option = {
    label: string;
    value: string;
};

type LogFilters = {
    fecha_desde: string;
    fecha_hasta: string;
    usuario: string;
    modulo: string;
    accion: string;
    nivel: string;
    buscar: string;
};

type LogRow = {
    id: number;
    code: string;
    date: string;
    user: string;
    role: string;
    module: string;
    moduleLabel: string;
    action: string;
    actionLabel: string;
    description: string;
    entity: string;
    level: string;
    levelLabel: string;
    tone: SuperadminTone;
    ip: string;
    route: string;
    detailHref: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type LogPagination = {
    data: LogRow[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};

type SelectedLog = {
    code: string;
    date: string;
    user: string;
    role: string;
    module: string;
    action: string;
    description: string;
    entity: string;
    level: string;
    tone: SuperadminTone;
    ip: string;
    route: string;
    method: string;
    userAgent: string;
    metadata: Record<string, unknown> | unknown[];
};

type LogsProps = {
    metrics: SuperadminMetric[];
    logs: LogPagination;
    filters: LogFilters;
    options: {
        users: Option[];
        modules: Option[];
        actions: Option[];
        levels: Option[];
    };
    selectedLog: SelectedLog | null;
    closeDetailHref: string;
};

const emptyFilters: LogFilters = {
    fecha_desde: '',
    fecha_hasta: '',
    usuario: '',
    modulo: '',
    accion: '',
    nivel: '',
    buscar: '',
};

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

function hasMetadata(metadata: SelectedLog['metadata']) {
    return Array.isArray(metadata) ? metadata.length > 0 : Object.keys(metadata ?? {}).length > 0;
}

export default function Logs({
    metrics,
    logs,
    filters,
    options,
    selectedLog,
    closeDetailHref,
}: LogsProps) {
    const [localFilters, setLocalFilters] = useState<LogFilters>(filters);
    const metadataText = useMemo(
        () => selectedLog && hasMetadata(selectedLog.metadata)
            ? JSON.stringify(selectedLog.metadata, null, 2)
            : 'Sin metadata registrada.',
        [selectedLog],
    );

    useEffect(() => {
        setLocalFilters(filters);
    }, [filters]);

    const updateFilter = (key: keyof LogFilters, value: string) => {
        setLocalFilters((current) => ({ ...current, [key]: value }));
    };

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const query = Object.fromEntries(
            Object.entries(localFilters).filter(([, value]) => value !== ''),
        );

        router.get('/superadmin/logs', query, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <SuperadminLayout title="Mati Boutique | Registro de actividad">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Actividad"
                    title="Registro de actividad"
                    description="Logs persistentes de acciones administrativas, comerciales y de clientes."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Registro de actividad' },
                    ]}
                    actions={[
                        { label: 'Volver al panel', href: '/superadmin', kind: 'ghost' },
                    ]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
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
                                        {logs.meta.total} registros encontrados
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Link
                                    href="/superadmin/logs"
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
                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Fecha desde
                                <input
                                    type="date"
                                    value={localFilters.fecha_desde}
                                    onChange={(event) => updateFilter('fecha_desde', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                />
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Fecha hasta
                                <input
                                    type="date"
                                    value={localFilters.fecha_hasta}
                                    onChange={(event) => updateFilter('fecha_hasta', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                />
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Usuario
                                <select
                                    value={localFilters.usuario}
                                    onChange={(event) => updateFilter('usuario', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                >
                                    <option value="">Todos</option>
                                    {options.users.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Nivel
                                <select
                                    value={localFilters.nivel}
                                    onChange={(event) => updateFilter('nivel', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                >
                                    <option value="">Todos</option>
                                    {options.levels.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Modulo
                                <select
                                    value={localFilters.modulo}
                                    onChange={(event) => updateFilter('modulo', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                >
                                    <option value="">Todos</option>
                                    {options.modules.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400">
                                Accion
                                <select
                                    value={localFilters.accion}
                                    onChange={(event) => updateFilter('accion', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white"
                                >
                                    <option value="">Todas</option>
                                    {options.actions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="space-y-2 text-xs uppercase text-stone-400 md:col-span-2">
                                Buscar
                                <input
                                    type="search"
                                    value={localFilters.buscar}
                                    placeholder="Descripcion"
                                    onChange={(event) => updateFilter('buscar', event.target.value)}
                                    className="w-full rounded-xl border border-white/10 bg-[#0d141d] px-3 py-3 text-sm normal-case text-white placeholder:text-stone-500"
                                />
                            </label>
                        </div>
                    </form>
                </section>

                <section className="sa-panel overflow-hidden rounded-[1.6rem]">
                    <div className="border-b border-white/8 px-5 py-4">
                        <p className="text-sm font-medium text-white">Actividad registrada</p>
                    </div>

                    {logs.data.length === 0 ? (
                        <div className="px-5 py-14 text-center">
                            <p className="text-lg text-white">Sin logs para los filtros actuales</p>
                            <p className="mt-2 text-sm text-stone-400">
                                Limpia los filtros o espera a que se registren nuevas acciones.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="sa-table-scroll hidden sm:block">
                                <table className="sa-table min-w-[1180px]">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Modulo</th>
                                            <th>Descripcion</th>
                                            <th>Entidad</th>
                                            <th>Nivel</th>
                                            <th>IP / ruta</th>
                                            <th className="text-right">Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logs.data.map((entry) => (
                                            <tr key={entry.id} className="align-top text-sm text-stone-300">
                                                <td className="whitespace-nowrap text-white">
                                                    {entry.date}
                                                </td>
                                                <td>
                                                    <p className="font-medium text-white">{entry.user}</p>
                                                    <p className="mt-1 text-xs text-stone-400">{entry.role}</p>
                                                </td>
                                                <td>
                                                    <div className="flex flex-col gap-2">
                                                        <SuperadminBadge tone="accent">{entry.moduleLabel}</SuperadminBadge>
                                                        <span className="text-xs text-stone-400">{entry.actionLabel}</span>
                                                    </div>
                                                </td>
                                                <td className="max-w-md leading-6">
                                                    {entry.description}
                                                </td>
                                                <td>{entry.entity}</td>
                                                <td>
                                                    <SuperadminBadge tone={entry.tone}>{entry.levelLabel}</SuperadminBadge>
                                                </td>
                                                <td>
                                                    <p className="font-medium text-white">{entry.ip}</p>
                                                    <p className="mt-1 max-w-[15rem] truncate text-xs text-stone-400">
                                                        {entry.route}
                                                    </p>
                                                </td>
                                                <td className="text-right">
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

                            <div className="sa-table-card-grid grid sm:hidden">
                                {logs.data.map((entry) => (
                                    <article key={entry.id} className="sa-table-card space-y-4 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium text-white">{entry.user}</p>
                                                <p className="mt-1 text-xs text-stone-400">{entry.date}</p>
                                            </div>
                                            <SuperadminBadge tone={entry.tone}>{entry.levelLabel}</SuperadminBadge>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <SuperadminBadge tone="accent">{entry.moduleLabel}</SuperadminBadge>
                                            <SuperadminBadge tone="neutral">{entry.actionLabel}</SuperadminBadge>
                                        </div>
                                        <p className="text-sm leading-6 text-stone-300">{entry.description}</p>
                                        <div className="grid gap-2 text-xs text-stone-400">
                                            <span>{entry.entity}</span>
                                            <span>{entry.ip}</span>
                                            <span className="truncate">{entry.route}</span>
                                        </div>
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
                            {logs.meta.from ?? 0}-{logs.meta.to ?? 0} de {logs.meta.total}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {logs.links.map((link, index) => {
                                const label = paginationLabel(link.label);
                                const isEdge = index === 0 || index === logs.links.length - 1;

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

                {selectedLog && (
                    <div className="fixed inset-0 z-50 flex items-end bg-black/60 p-0 backdrop-blur-sm sm:items-center sm:p-6">
                        <div className="sa-panel max-h-[92vh] w-full overflow-y-auto rounded-t-[1.6rem] p-5 shadow-2xl sm:mx-auto sm:max-w-3xl sm:rounded-[1.6rem] sm:p-6">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <SuperadminBadge tone={selectedLog.tone}>{selectedLog.level}</SuperadminBadge>
                                        <SuperadminBadge tone="neutral">{selectedLog.code}</SuperadminBadge>
                                    </div>
                                    <p className="mt-4 text-xl font-medium text-white">Detalle de log</p>
                                    <p className="mt-2 text-sm leading-6 text-stone-300">
                                        {selectedLog.description}
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

                            <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                {[
                                    ['Fecha', selectedLog.date],
                                    ['Usuario', selectedLog.user],
                                    ['Rol', selectedLog.role],
                                    ['Modulo', selectedLog.module],
                                    ['Accion', selectedLog.action],
                                    ['Entidad', selectedLog.entity],
                                    ['IP', selectedLog.ip],
                                    ['Metodo', selectedLog.method],
                                    ['Ruta', selectedLog.route],
                                    ['User agent', selectedLog.userAgent],
                                ].map(([label, value]) => (
                                    <div key={label} className="rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                        <p className="text-xs uppercase text-stone-400">{label}</p>
                                        <p className="mt-2 break-words text-sm text-white">{value}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-5 rounded-xl border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-xs uppercase text-stone-400">Metadata</p>
                                <pre className="mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-stone-200">
                                    {metadataText}
                                </pre>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </SuperadminLayout>
    );
}
