import type { SuperadminTableCell, SuperadminTableColumn, SuperadminTableRow, SuperadminTableSwatchCell } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { Eye, FileText, PenSquare } from 'lucide-react';
import SuperadminBadge from './SuperadminBadge';

function CellContent({ value }: { value: SuperadminTableCell | SuperadminTableSwatchCell }) {
    if (typeof value === 'object' && value !== null) {
        if ('swatch' in value) {
            return (
                <span className="inline-flex items-center gap-2">
                    <span className="h-5 w-5 rounded-full border border-white/15" style={{ backgroundColor: value.swatch }} />
                    <span>{value.label}</span>
                    {value.value && <span className="text-stone-500">{value.value}</span>}
                </span>
            );
        }

        return <SuperadminBadge tone={value.tone}>{value.label}</SuperadminBadge>;
    }
    return <>{value}</>;
}

export default function SuperadminDataTable({
    columns,
    rows,
}: {
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
}) {
    const hasEditActions = rows.some((row) => row.editHref);
    const hasShowActions = rows.some((row) => row.showHref);
    const hasExtraActions = rows.some((row) => (row.extraActions?.length ?? 0) > 0);
    const hasActions = hasEditActions || hasShowActions || hasExtraActions;

    return (
        <div className="sa-table-wrapper">
            <div className="sa-table-card-grid grid sm:hidden">
                {rows.map((row) => (
                    <article
                        key={row.id}
                        className="sa-table-card p-4"
                    >
                        {'image' in row && (
                            row.image ? (
                                <img
                                    src={row.image}
                                    alt={row.primary}
                                    className="mb-4 h-36 w-full rounded-xl object-cover"
                                />
                            ) : (
                                <div className="mb-4 flex h-36 w-full items-center justify-center rounded-xl border border-white/8 bg-[#090d13] text-xs uppercase tracking-[0.16em] text-stone-500">
                                    Sin imagen
                                </div>
                            )
                        )}
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold text-white">
                                    {row.primary}
                                </p>
                                {row.secondary && (
                                    <p className="mt-1 line-clamp-2 text-xs text-stone-400">
                                        {row.secondary}
                                    </p>
                                )}
                            </div>
                            {row.status && (
                                <SuperadminBadge tone={row.status.tone}>
                                    {row.status.label}
                                </SuperadminBadge>
                            )}
                        </div>

                        <dl className="mt-4">
                            {columns.map((column) => {
                                const cellValue = row.cells[column.key] ?? '-';

                                return (
                                    <div key={`${row.id}-${column.key}`} className="sa-table-card__field">
                                        <dt className="shrink-0 text-[0.64rem] uppercase tracking-[0.18em] text-stone-500">
                                            {column.label}
                                        </dt>
                                        <dd className="min-w-0 text-right text-sm text-stone-200">
                                            <CellContent value={cellValue} />
                                        </dd>
                                    </div>
                                );
                            })}
                        </dl>

                        {hasActions && (
                            <div className="mt-4 flex flex-wrap justify-end gap-2">
                                {row.editHref && (
                                    <Link
                                        href={row.editHref}
                                        className="sa-button-ghost h-10 px-3 text-[0.7rem] uppercase tracking-[0.16em]"
                                    >
                                        <PenSquare size={14} />
                                        Editar
                                    </Link>
                                )}
                                {row.showHref && (
                                    row.showHref.startsWith('http') || row.showHref.startsWith('/storage/') ? (
                                        <a
                                            href={row.showHref}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="sa-button-secondary h-10 px-3 text-[0.7rem] uppercase tracking-[0.16em]"
                                        >
                                            <Eye size={15} />
                                            Ver detalle
                                        </a>
                                    ) : (
                                        <Link
                                            href={row.showHref}
                                            className="sa-button-secondary h-10 px-3 text-[0.7rem] uppercase tracking-[0.16em]"
                                        >
                                            <Eye size={15} />
                                            Ver detalle
                                        </Link>
                                    )
                                )}
                                {row.extraActions?.map((action) => (
                                    <a
                                        key={action.href}
                                        href={action.href}
                                        className="sa-button-secondary h-10 px-3 text-[0.7rem] uppercase tracking-[0.16em]"
                                    >
                                        <FileText size={15} />
                                        {action.label}
                                    </a>
                                ))}
                            </div>
                        )}
                    </article>
                ))}
            </div>

            <div className="sa-table-scroll hidden sm:block">
                <table className="sa-table min-w-[920px]">
                    <thead>
                        <tr>
                            <th className="text-left">
                                Registro
                            </th>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}
                                >
                                    {column.label}
                                </th>
                            ))}
                            {hasActions && (
                                <th className="text-right">
                                    {hasEditActions ? 'Acciones' : 'Consulta'}
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="sa-table-row">
                                <td>
                                    <div className="flex items-center gap-3">
                                        {'image' in row && (
                                            row.image ? (
                                            <img
                                                src={row.image}
                                                alt={row.primary}
                                                className="h-12 w-10 shrink-0 rounded-xl object-cover"
                                            />
                                            ) : (
                                                <span className="flex h-12 w-10 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#090d13] text-[0.55rem] uppercase leading-3 text-stone-500">
                                                    Sin
                                                    <br />
                                                    img
                                                </span>
                                            )
                                        )}
                                        <div>
                                            <p className="text-sm font-medium text-white">{row.primary}</p>
                                            {row.secondary && (
                                                <p className="mt-1 text-sm text-stone-400">
                                                    {row.secondary}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </td>
                                {columns.map((column) => {
                                    const cellValue = row.cells[column.key] ?? '-';

                                    return (
                                        <td
                                            key={`${row.id}-${column.key}`}
                                            className={column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}
                                            title={typeof cellValue === 'string' ? cellValue : undefined}
                                        >
                                            <CellContent value={cellValue} />
                                        </td>
                                    );
                                })}
                                {hasActions && (
                                    <td>
                                        <div className="flex items-center justify-end gap-2">
                                            {row.status && (
                                                <SuperadminBadge
                                                    tone={row.status.tone}
                                                    className="hidden sm:inline-flex"
                                                >
                                                    {row.status.label}
                                                </SuperadminBadge>
                                            )}
                                            {row.editHref && (
                                                <Link
                                                    href={row.editHref}
                                                    className="sa-button-ghost h-10 w-10 p-0"
                                                    aria-label={`Editar ${row.primary}`}
                                                >
                                                    <PenSquare size={14} />
                                                </Link>
                                            )}
                                            {row.showHref && (
                                                row.showHref.startsWith('http') || row.showHref.startsWith('/storage/') ? (
                                                    <a
                                                        href={row.showHref}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="sa-button-secondary h-10 w-10 p-0"
                                                        aria-label={`Abrir ${row.primary}`}
                                                    >
                                                        <Eye size={15} />
                                                    </a>
                                                ) : (
                                                    <Link
                                                        href={row.showHref}
                                                        className="sa-button-secondary h-10 w-10 p-0"
                                                        aria-label={`${hasEditActions ? 'Ver' : 'Consultar'} ${row.primary}`}
                                                    >
                                                        <Eye size={15} />
                                                    </Link>
                                                )
                                            )}
                                            {row.extraActions?.map((action) => (
                                                <a
                                                    key={action.href}
                                                    href={action.href}
                                                    className="sa-button-secondary h-10 w-10 p-0"
                                                    aria-label={action.label}
                                                >
                                                    <FileText size={15} />
                                                </a>
                                            ))}
                                        </div>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
