import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type { SuperadminTone } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import type { ReactNode } from 'react';

export type VendedorBadge = {
    label: string;
    tone?: SuperadminTone;
};

export type VendedorReadOnlyAction = {
    label: string;
    href: string;
};

export type VendedorReadOnlyRecord = {
    id: string | number;
    primary: string;
    secondary?: string | null;
    image?: string | null;
    status?: VendedorBadge;
    actions?: VendedorReadOnlyAction[];
};

export type VendedorReadOnlyColumn<T> = {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
    render: (row: T) => ReactNode;
};

function ProductImage({ image, alt }: { image?: string | null; alt: string }) {
    if (image) {
        return (
            <img
                src={image}
                alt={alt}
                className="h-14 w-12 shrink-0 rounded-xl object-cover"
            />
        );
    }

    return (
        <span className="flex h-14 w-12 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#090d13] text-center text-[0.55rem] uppercase leading-3 text-[color:var(--admin-muted)]">
            Sin
            <br />
            imagen
        </span>
    );
}

export default function VendedorReadOnlyTable<T extends VendedorReadOnlyRecord>({
    columns,
    rows,
    emptyTitle,
    emptyDescription,
}: {
    columns: VendedorReadOnlyColumn<T>[];
    rows: T[];
    emptyTitle: string;
    emptyDescription: string;
}) {
    if (rows.length === 0) {
        return (
            <section className="sa-panel-muted rounded-2xl p-8 text-center">
                <p className="text-base font-semibold text-[color:var(--admin-text)]">
                    {emptyTitle}
                </p>
                <p className="mx-auto mt-2 max-w-2xl text-sm leading-6 text-[color:var(--admin-muted)]">
                    {emptyDescription}
                </p>
            </section>
        );
    }

    const hasActions = rows.some((row) => (row.actions?.length ?? 0) > 0);

    return (
        <div className="sa-table-wrapper">
            <div className="sa-table-card-grid grid sm:hidden">
                {rows.map((row) => (
                    <article key={row.id} className="sa-table-card p-4">
                        <div className="flex items-start gap-3">
                            <ProductImage image={row.image} alt={row.primary} />
                            <div className="min-w-0 flex-1">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-[color:var(--admin-text)]">
                                            {row.primary}
                                        </p>
                                        {row.secondary && (
                                            <p className="mt-1 line-clamp-2 text-xs text-[color:var(--admin-muted)]">
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
                            </div>
                        </div>

                        <dl className="mt-4">
                            {columns.map((column) => (
                                <div key={`${row.id}-${column.key}`} className="sa-table-card__field">
                                    <dt className="shrink-0 text-xs font-semibold text-[color:var(--admin-muted)]">
                                        {column.label}
                                    </dt>
                                    <dd className="min-w-0 text-right text-sm text-[color:var(--admin-text)]">
                                        {column.render(row)}
                                    </dd>
                                </div>
                            ))}
                        </dl>

                        {(row.actions?.length ?? 0) > 0 && (
                            <div className="mt-4 flex flex-wrap justify-end gap-2">
                                {row.actions?.map((action) => (
                                    <Link
                                        key={action.href}
                                        href={action.href}
                                        className="sa-button-secondary h-10 px-3 text-xs font-semibold"
                                    >
                                        <Eye size={15} />
                                        {action.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </article>
                ))}
            </div>

            <div className="sa-table-scroll hidden sm:block">
                <table className="sa-table min-w-[1060px]">
                    <thead>
                        <tr>
                            <th className="text-left">Registro</th>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}
                                >
                                    {column.label}
                                </th>
                            ))}
                            {hasActions && <th className="text-right">Consulta</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="sa-table-row">
                                <td>
                                    <div className="flex items-center gap-3">
                                        <ProductImage image={row.image} alt={row.primary} />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                                                {row.primary}
                                            </p>
                                            {row.secondary && (
                                                <p className="mt-1 truncate text-sm text-[color:var(--admin-muted)]">
                                                    {row.secondary}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </td>
                                {columns.map((column) => (
                                    <td
                                        key={`${row.id}-${column.key}`}
                                        className={column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'}
                                    >
                                        {column.render(row)}
                                    </td>
                                ))}
                                {hasActions && (
                                    <td>
                                        <div className="flex items-center justify-end gap-2">
                                            {row.status && (
                                                <SuperadminBadge tone={row.status.tone}>
                                                    {row.status.label}
                                                </SuperadminBadge>
                                            )}
                                            {row.actions?.map((action) => (
                                                <Link
                                                    key={action.href}
                                                    href={action.href}
                                                    title={action.label}
                                                    aria-label={`${action.label}: ${row.primary}`}
                                                    className="sa-button-secondary h-10 w-10 p-0"
                                                >
                                                    <Eye size={15} />
                                                </Link>
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
