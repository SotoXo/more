import { exportableReportSlugs, type OperationalReport } from './types';
import { Download, Printer } from 'lucide-react';

function appendQuery(path: string, queryString: string) {
    return queryString ? `${path}?${queryString}` : path;
}

export default function ReportExportActions({
    report,
    queryString,
}: {
    report: OperationalReport;
    queryString: string;
}) {
    const slug = exportableReportSlugs[report.key];

    if (!slug) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            <a
                href={appendQuery(`/superadmin/reportes/exportar/${slug}.csv`, queryString)}
                className="sa-button-secondary min-h-10 px-4 py-2 text-center text-[0.72rem] uppercase tracking-[0.18em]"
            >
                <Download size={15} />
                Exportar CSV
            </a>
            <a
                href={appendQuery(`/superadmin/reportes/imprimir/${slug}`, queryString)}
                className="sa-button-ghost min-h-10 px-4 py-2 text-center text-[0.72rem] uppercase tracking-[0.18em]"
            >
                <Printer size={15} />
                Vista imprimible
            </a>
        </div>
    );
}
