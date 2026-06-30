import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import type { SuperadminTone } from '@/types/superadmin';
import { Head, Link } from '@inertiajs/react';
import { Printer, RotateCcw } from 'lucide-react';

type Badge = {
    label: string;
    tone?: SuperadminTone;
};

type SummaryCard = {
    label: string;
    value: string;
    note: string;
    tone?: SuperadminTone;
};

type PrintRow = Record<string, string | number | Badge | null | undefined>;

type PrintProps = {
    filters: {
        date_from: string;
        date_to: string;
    };
    generatedAt: string;
    scopeLabel: string;
    summary: SummaryCard[];
    tables: {
        sales: PrintRow[];
        reservations: PrintRow[];
        payments: PrintRow[];
        pickups: PrintRow[];
        damagedProducts: PrintRow[];
    };
    backHref: string;
};

function valueText(value: unknown) {
    if (!value) return '';

    if (typeof value === 'object' && 'label' in (value as Record<string, unknown>)) {
        return String((value as Badge).label ?? '');
    }

    return String(value);
}

function PrintTable({
    title,
    rows,
    columns,
}: {
    title: string;
    rows: PrintRow[];
    columns: Array<{ key: string; label: string }>;
}) {
    return (
        <section className="print-section">
            <h2>{title}</h2>
            {rows.length > 0 ? (
                <table>
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key}>{column.label}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={String(row.id ?? index)}>
                                {columns.map((column) => (
                                    <td key={column.key}>{valueText(row[column.key])}</td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <p className="empty">Sin registros en el periodo.</p>
            )}
        </section>
    );
}

export default function Print({
    filters,
    generatedAt,
    scopeLabel,
    summary,
    tables,
    backHref,
}: PrintProps) {
    return (
        <>
            <Head title="Mati Boutique | Imprimir reporte vendedor" />
            <div className="print-shell">
                <style>{`
                    body { background: #f7f3ec; color: #1f2933; }
                    .print-shell { max-width: 1180px; margin: 0 auto; padding: 24px; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
                    .toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px; }
                    .btn { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #c9b99c; border-radius: 999px; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #1f2933; text-decoration: none; background: #fffaf2; }
                    .btn.primary { background: #1f2933; color: white; border-color: #1f2933; }
                    header { border-bottom: 2px solid #1f2933; padding-bottom: 18px; margin-bottom: 22px; }
                    h1 { margin: 0; font-size: 28px; }
                    .brand { font-size: 13px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #8f6f3d; }
                    .meta { margin-top: 10px; color: #5b6470; font-size: 13px; line-height: 1.6; }
                    .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 24px; }
                    .summary-card { border: 1px solid #d9cab0; background: white; border-radius: 14px; padding: 14px; }
                    .summary-card .label { font-size: 11px; text-transform: uppercase; color: #66717c; font-weight: 800; }
                    .summary-card .value { margin-top: 8px; font-size: 22px; font-weight: 800; }
                    .summary-card .note { margin-top: 6px; font-size: 12px; color: #66717c; }
                    .print-section { margin-top: 22px; page-break-inside: avoid; }
                    h2 { font-size: 16px; margin: 0 0 10px; }
                    table { width: 100%; border-collapse: collapse; background: white; }
                    th, td { border: 1px solid #d9cab0; padding: 8px 10px; font-size: 12px; text-align: left; vertical-align: top; }
                    th { background: #efe6d6; font-size: 11px; text-transform: uppercase; }
                    .empty { border: 1px solid #d9cab0; background: white; padding: 14px; color: #66717c; }
                    @media print {
                        .toolbar { display: none; }
                        .print-shell { max-width: none; padding: 0; }
                        body { background: white; }
                    }
                `}</style>

                <div className="toolbar">
                    <Link href={backHref} className="btn">
                        <RotateCcw size={15} />
                        Volver
                    </Link>
                    <button type="button" onClick={() => window.print()} className="btn primary">
                        <Printer size={15} />
                        Imprimir
                    </button>
                </div>

                <header>
                    <div className="brand">Mati Boutique</div>
                    <h1>Mis reportes</h1>
                    <p className="meta">
                        Periodo {filters.date_from} al {filters.date_to}. Generado {generatedAt}.
                        <br />
                        {scopeLabel}
                    </p>
                </header>

                <section className="summary">
                    {summary.map((item) => (
                        <article key={item.label} className="summary-card">
                            <div className="label">{item.label}</div>
                            <div className="value">{item.value}</div>
                            <div className="note">{item.note}</div>
                            <div style={{ marginTop: 8 }}>
                                <SuperadminBadge tone={item.tone ?? 'neutral'}>Periodo</SuperadminBadge>
                            </div>
                        </article>
                    ))}
                </section>

                <PrintTable
                    title="Ventas"
                    rows={tables.sales}
                    columns={[
                        { key: 'date', label: 'Fecha' },
                        { key: 'number', label: 'Venta' },
                        { key: 'customer', label: 'Cliente' },
                        { key: 'total', label: 'Total' },
                        { key: 'paymentMethod', label: 'Metodo' },
                        { key: 'status', label: 'Estado' },
                        { key: 'delivery', label: 'Entrega' },
                    ]}
                />
                <PrintTable
                    title="Reservas"
                    rows={tables.reservations}
                    columns={[
                        { key: 'date', label: 'Fecha' },
                        { key: 'number', label: 'Reserva' },
                        { key: 'customer', label: 'Cliente' },
                        { key: 'total', label: 'Total' },
                        { key: 'paid', label: 'Pagado' },
                        { key: 'balance', label: 'Saldo' },
                        { key: 'status', label: 'Estado' },
                    ]}
                />
                <PrintTable
                    title="Pagos gestionados"
                    rows={tables.payments}
                    columns={[
                        { key: 'date', label: 'Fecha' },
                        { key: 'type', label: 'Tipo' },
                        { key: 'operation', label: 'Operacion' },
                        { key: 'method', label: 'Metodo' },
                        { key: 'amount', label: 'Monto' },
                        { key: 'status', label: 'Estado' },
                        { key: 'action', label: 'Accion' },
                    ]}
                />
                <PrintTable
                    title="Recojos y entregas"
                    rows={tables.pickups}
                    columns={[
                        { key: 'date', label: 'Fecha' },
                        { key: 'type', label: 'Tipo' },
                        { key: 'number', label: 'Numero' },
                        { key: 'customer', label: 'Cliente' },
                        { key: 'status', label: 'Estado' },
                    ]}
                />
                <PrintTable
                    title="Prendas danadas"
                    rows={tables.damagedProducts}
                    columns={[
                        { key: 'date', label: 'Fecha' },
                        { key: 'code', label: 'Codigo' },
                        { key: 'name', label: 'Prenda' },
                        { key: 'cause', label: 'Causa' },
                        { key: 'damageType', label: 'Tipo' },
                        { key: 'status', label: 'Estado' },
                    ]}
                />
            </div>
        </>
    );
}
