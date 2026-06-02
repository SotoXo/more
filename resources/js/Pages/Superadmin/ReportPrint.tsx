import { Head, Link } from '@inertiajs/react';

type PrintColumn = {
    key: string;
    label: string;
};

type PrintSummary = {
    label: string;
    value: string;
};

type PrintFilter = {
    label: string;
    value: string;
};

type PrintReport = {
    storeName: string;
    title: string;
    dateRangeLabel: string;
    generatedAt: string;
    generatedBy: string;
    appliedFilters: PrintFilter[];
    summary: PrintSummary[];
    columns: PrintColumn[];
    rows: Record<string, string>[];
    footer: string;
    backHref: string;
};

type ReportPrintProps = {
    report: PrintReport;
};

export default function ReportPrint({ report }: ReportPrintProps) {
    const printPage = () => {
        window.print();
    };

    return (
        <>
            <Head title={`${report.storeName} | ${report.title}`} />

            <style>
                {`
                    @media print {
                        body {
                            background: #ffffff !important;
                        }

                        .print-actions {
                            display: none !important;
                        }

                        .print-page {
                            box-shadow: none !important;
                            margin: 0 !important;
                            max-width: none !important;
                            min-height: auto !important;
                            padding: 0 !important;
                        }

                        .print-table {
                            page-break-inside: auto;
                        }

                        .print-table tr {
                            page-break-inside: avoid;
                            page-break-after: auto;
                        }
                    }
                `}
            </style>

            <main className="min-h-screen bg-[#f4f1ed] px-4 py-6 text-[#1f1a17] sm:px-6 lg:px-8">
                <div className="print-page mx-auto min-h-[calc(100vh-3rem)] max-w-6xl rounded-2xl bg-white p-6 shadow-xl sm:p-8">
                    <div className="print-actions mb-6 flex flex-wrap items-center justify-between gap-3">
                        <Link
                            href={report.backHref}
                            className="inline-flex items-center rounded-xl border border-[#d8cec4] px-4 py-2 text-sm font-semibold text-[#4a332a] transition hover:bg-[#f4f1ed]"
                        >
                            Volver
                        </Link>
                        <button
                            type="button"
                            onClick={printPage}
                            className="inline-flex items-center rounded-xl bg-[#4a332a] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#2f211c]"
                        >
                            Imprimir
                        </button>
                    </div>

                    <header className="border-b border-[#d8cec4] pb-6">
                        <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8b6f5e]">
                            {report.storeName}
                        </p>
                        <div className="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h1 className="text-3xl font-semibold text-[#1f1a17]">{report.title}</h1>
                                <p className="mt-2 text-sm text-[#6f625a]">{report.dateRangeLabel}</p>
                            </div>
                            <div className="text-sm text-[#4f4741] lg:text-right">
                                <p>Generado: {report.generatedAt}</p>
                                <p>Usuario: {report.generatedBy}</p>
                            </div>
                        </div>
                    </header>

                    <section className="mt-6">
                        <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-[#8b6f5e]">
                            Filtros aplicados
                        </h2>
                        {report.appliedFilters.length > 0 ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {report.appliedFilters.map((filter) => (
                                    <span
                                        key={`${filter.label}-${filter.value}`}
                                        className="rounded-full border border-[#d8cec4] px-3 py-1 text-xs font-medium text-[#4f4741]"
                                    >
                                        {filter.label}: {filter.value}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-[#6f625a]">Sin filtros adicionales.</p>
                        )}
                    </section>

                    <section className="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        {report.summary.map((item) => (
                            <div key={`${item.label}-${item.value}`} className="rounded-xl border border-[#d8cec4] p-4">
                                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#8b6f5e]">
                                    {item.label}
                                </p>
                                <p className="mt-2 text-xl font-semibold text-[#1f1a17]">{item.value}</p>
                            </div>
                        ))}
                    </section>

                    <section className="mt-8">
                        <div className="overflow-x-auto">
                            <table className="print-table min-w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr>
                                        {report.columns.map((column) => (
                                            <th
                                                key={column.key}
                                                className="border border-[#d8cec4] bg-[#f4f1ed] px-3 py-2 font-semibold text-[#3a2a24]"
                                            >
                                                {column.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.rows.length > 0 ? (
                                        report.rows.map((row, index) => (
                                            <tr key={index}>
                                                {report.columns.map((column) => (
                                                    <td
                                                        key={`${index}-${column.key}`}
                                                        className="border border-[#e7ddd4] px-3 py-2 align-top text-[#3f3732]"
                                                    >
                                                        {row[column.key] ?? ''}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={report.columns.length}
                                                className="border border-[#e7ddd4] px-3 py-8 text-center text-[#6f625a]"
                                            >
                                                Sin datos para los filtros aplicados.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <footer className="mt-8 border-t border-[#d8cec4] pt-4 text-center text-xs text-[#6f625a]">
                        {report.footer}
                    </footer>
                </div>
            </main>
        </>
    );
}
