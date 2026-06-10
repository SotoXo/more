import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { SuperadminMetric, SuperadminTone } from '@/types/superadmin';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, DatabaseBackup, Download, Loader2 } from 'lucide-react';
import { useState } from 'react';

type BackupRow = {
    id: number;
    date: string;
    file: string;
    name: string;
    user: string;
    size: string;
    sizeBytes: number | null;
    status: string;
    statusLabel: string;
    tone: SuperadminTone;
    message: string;
    downloadHref: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type BackupsProps = {
    metrics: SuperadminMetric[];
    backups: {
        data: BackupRow[];
        links: PaginationLink[];
        meta: {
            from: number | null;
            to: number | null;
            total: number;
        };
    };
};

function paginationLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente')
        .trim();
}

export default function Backups({ metrics, backups }: BackupsProps) {
    const [generating, setGenerating] = useState(false);

    const generateBackup = () => {
        if (generating) {
            return;
        }

        setGenerating(true);
        router.post('/superadmin/backups/generar', {}, {
            preserveScroll: true,
            onFinish: () => setGenerating(false),
        });
    };

    return (
        <SuperadminLayout title="Mati Boutique | Copias de seguridad">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow="Seguridad"
                    title="Copias de seguridad"
                    description="Administra respaldos manuales de la base de datos del sistema."
                    breadcrumbs={[
                        { label: 'Panel administrativo', href: '/superadmin' },
                        { label: 'Copias de seguridad' },
                    ]}
                    actions={[
                        { label: 'Auditoría crítica', href: '/superadmin/auditoria', kind: 'ghost' },
                    ]}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="sa-panel rounded-[1.6rem] p-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center gap-3">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-[#111922] text-stone-300">
                                <DatabaseBackup size={18} />
                            </span>
                            <div>
                                <p className="text-sm font-medium text-white">Backup manual</p>
                                <p className="mt-1 text-sm text-stone-400">
                                    El archivo se guarda fuera de public y se descarga solo desde una ruta protegida.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={generateBackup}
                            disabled={generating}
                            className="sa-button-secondary inline-flex min-h-11 items-center justify-center gap-2 px-5 text-xs uppercase disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {generating ? <Loader2 size={15} className="animate-spin" /> : <DatabaseBackup size={15} />}
                            {generating ? 'Generando...' : 'Generar backup'}
                        </button>
                    </div>
                </section>

                <section className="sa-panel overflow-hidden rounded-[1.6rem]">
                    <div className="border-b border-white/8 px-5 py-4">
                        <p className="text-sm font-medium text-white">Historial de backups</p>
                    </div>

                    {backups.data.length === 0 ? (
                        <div className="px-5 py-14 text-center">
                            <p className="text-lg text-white">Sin copias de seguridad</p>
                            <p className="mt-2 text-sm text-stone-400">
                                Genera el primer backup manual para iniciar el historial.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="sa-table-scroll hidden sm:block">
                                <table className="sa-table min-w-[1100px]">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Archivo</th>
                                            <th>Usuario</th>
                                            <th>Tamaño</th>
                                            <th>Estado</th>
                                            <th>Mensaje</th>
                                            <th className="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {backups.data.map((backup) => (
                                            <tr key={backup.id} className="align-top text-sm text-stone-300">
                                                <td className="whitespace-nowrap text-white">{backup.date}</td>
                                                <td className="max-w-xs">
                                                    <p className="truncate font-medium text-white">{backup.file}</p>
                                                    <p className="mt-1 text-xs text-stone-400">{backup.name}</p>
                                                </td>
                                                <td>{backup.user}</td>
                                                <td className="whitespace-nowrap">{backup.size}</td>
                                                <td>
                                                    <SuperadminBadge tone={backup.tone}>{backup.statusLabel}</SuperadminBadge>
                                                </td>
                                                <td className="max-w-md leading-6">{backup.message}</td>
                                                <td className="text-right">
                                                    {backup.downloadHref ? (
                                                        <a
                                                            href={backup.downloadHref}
                                                            className="sa-button-ghost inline-flex h-10 w-10 items-center justify-center p-0"
                                                            title="Descargar backup"
                                                        >
                                                            <Download size={16} />
                                                        </a>
                                                    ) : (
                                                        <span className="text-xs text-stone-500">No disponible</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="sa-table-card-grid grid sm:hidden">
                                {backups.data.map((backup) => (
                                    <article key={backup.id} className="sa-table-card space-y-4 p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-white">{backup.file}</p>
                                                <p className="mt-1 text-xs text-stone-400">{backup.date}</p>
                                            </div>
                                            <SuperadminBadge tone={backup.tone}>{backup.statusLabel}</SuperadminBadge>
                                        </div>
                                        <div className="grid gap-2 text-sm text-stone-300">
                                            <span>{backup.user}</span>
                                            <span>{backup.size}</span>
                                            <span className="leading-6">{backup.message}</span>
                                        </div>
                                        {backup.downloadHref && (
                                            <a
                                                href={backup.downloadHref}
                                                className="sa-button-secondary inline-flex min-h-10 items-center gap-2 px-4 text-xs uppercase"
                                            >
                                                <Download size={14} />
                                                Descargar
                                            </a>
                                        )}
                                    </article>
                                ))}
                            </div>
                        </>
                    )}

                    <div className="flex flex-col gap-3 border-t border-white/8 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-stone-400">
                            {backups.meta.from ?? 0}-{backups.meta.to ?? 0} de {backups.meta.total}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {backups.links.map((link, index) => {
                                const label = paginationLabel(link.label);
                                const isEdge = index === 0 || index === backups.links.length - 1;

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
            </div>
        </SuperadminLayout>
    );
}
