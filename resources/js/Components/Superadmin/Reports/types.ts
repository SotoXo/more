import type {
    SuperadminAppliedFilter,
    SuperadminChartPoint,
    SuperadminEmptyState,
    SuperadminFilterControl,
    SuperadminMetric,
    SuperadminSortOption,
    SuperadminTableColumn,
    SuperadminTableRow,
    SuperadminTone,
} from '@/types/superadmin';

export type ReportRankingItem = {
    label: string;
    value: string;
    note?: string;
    tone?: SuperadminTone;
};

export type ReportRanking = {
    title: string;
    items: ReportRankingItem[];
};

export type ReportTable = {
    title: string;
    description?: string;
    columns: SuperadminTableColumn[];
    rows: SuperadminTableRow[];
};

export type ReportChart = {
    title: string;
    description?: string;
    type: 'donut' | 'bar' | 'area';
    data: SuperadminChartPoint[];
    emptyMessage?: string;
};

export type OperationalReport = {
    key: string;
    title: string;
    description: string;
    metrics: SuperadminMetric[];
    charts: ReportChart[];
    rankings: ReportRanking[];
    tables: ReportTable[];
};

export type ReportSectionKey =
    | 'summary'
    | 'sales'
    | 'reservations'
    | 'payments'
    | 'inventory'
    | 'customers';

export type ReportsProps = {
    metrics: SuperadminMetric[];
    reports: OperationalReport[];
    filterControls: SuperadminFilterControl[];
    sortOptions: SuperadminSortOption[];
    sortBy?: string;
    sortDirection?: string;
    appliedFilters: SuperadminAppliedFilter[];
    emptyState: SuperadminEmptyState;
};

export const exportableReportSlugs: Record<string, string> = {
    sales: 'ventas',
    reservations: 'reservas',
    payments: 'pagos',
    inventory: 'inventario',
    customers: 'clientes',
};

export const reportSections: Array<{
    key: ReportSectionKey;
    label: string;
    description: string;
    queryValue: string;
}> = [
    { key: 'summary', label: 'Resumen', description: 'Pulso general del negocio', queryValue: 'resumen' },
    { key: 'sales', label: 'Ventas', description: 'Estados, origenes y detalle comercial', queryValue: 'ventas' },
    { key: 'reservations', label: 'Reservas', description: 'Estados, recojo y prendas separadas', queryValue: 'reservas' },
    { key: 'payments', label: 'Pagos', description: 'Revision de comprobantes y metodos', queryValue: 'pagos' },
    { key: 'inventory', label: 'Inventario', description: 'Disponible, comprometido y alertas', queryValue: 'inventario' },
    { key: 'customers', label: 'Clientes', description: 'Frecuencia de compras y reservas', queryValue: 'clientes' },
];
