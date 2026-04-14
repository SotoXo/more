import type { SuperadminTableCell, SuperadminTableColumn, SuperadminTableRow } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { ChevronRight, PenSquare } from 'lucide-react';
import SuperadminBadge from './SuperadminBadge';

function CellContent({ value }: { value: SuperadminTableCell }) {
    if (typeof value === 'object' && value !== null) {
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
    const hasActions = rows.some((row) => row.editHref || row.showHref);

    return (
        <div className="sa-panel overflow-hidden rounded-[1.6rem]">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-white/8">
                    <thead>
                        <tr className="bg-[#0d141d]">
                            <th className="px-6 py-4 text-left text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                Registro
                            </th>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={`px-6 py-4 text-[0.72rem] uppercase tracking-[0.18em] text-stone-300 ${column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}`}
                                >
                                    {column.label}
                                </th>
                            ))}
                            {hasActions && (
                                <th className="px-6 py-4 text-right text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                                    Acciones
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-white/6">
                        {rows.map((row) => (
                            <tr key={row.id} className="sa-table-row">
                                <td className="px-6 py-5">
                                    <div>
                                        <p className="text-sm font-medium text-white">{row.primary}</p>
                                        {row.secondary && (
                                            <p className="mt-1 text-sm text-stone-400">
                                                {row.secondary}
                                            </p>
                                        )}
                                    </div>
                                </td>
                                {columns.map((column) => {
                                    const cellValue = row.cells[column.key] ?? '-';

                                    return (
                                        <td
                                            key={`${row.id}-${column.key}`}
                                            className={`px-6 py-5 text-sm text-stone-300 ${column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}`}
                                        >
                                            <CellContent value={cellValue} />
                                        </td>
                                    );
                                })}
                                {hasActions && (
                                    <td className="px-6 py-5">
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
                                                <Link
                                                    href={row.showHref}
                                                    className="sa-button-secondary h-10 w-10 p-0"
                                                    aria-label={`Ver ${row.primary}`}
                                                >
                                                    <ChevronRight size={15} />
                                                </Link>
                                            )}
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
