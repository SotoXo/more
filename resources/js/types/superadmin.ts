import type { LucideIcon } from 'lucide-react';

export type SuperadminTone =
    | 'neutral'
    | 'accent'
    | 'success'
    | 'warning'
    | 'info'
    | 'danger';

export type SuperadminBreadcrumb = {
    label: string;
    href?: string;
};

export type SuperadminNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    badge?: string;
    badgeTone?: SuperadminTone;
    exact?: boolean;
};

export type SuperadminNavGroup = {
    label: string;
    items: SuperadminNavItem[];
};

export type SuperadminAction = {
    label: string;
    href?: string;
    kind?: 'primary' | 'secondary' | 'ghost';
};

export type SuperadminSegmentedFilter = {
    label: string;
    href: string;
    active?: boolean;
    count?: string;
};

export type SuperadminMetric = {
    label: string;
    value: string;
    note: string;
    tone?: SuperadminTone;
    href?: string;
};

export type SuperadminInsightCard = {
    eyebrow: string;
    title: string;
    description: string;
    tone?: SuperadminTone;
    href?: string;
};

export type SuperadminActivityItem = {
    title: string;
    description: string;
    date: string;
    tone?: SuperadminTone;
    href?: string;
};

export type SuperadminPermissionNotice = {
    title: string;
    description: string;
    items: string[];
};

export type SuperadminResourceMeta = {
    sectionKey: string;
    sectionLabel: string;
    resourceKey: string;
    title: string;
    singular: string;
    description: string;
    indexHref: string;
    createHref?: string | null;
    moduleStatus?: 'crud' | 'read_only' | 'not_ready';
    moduleStatusLabel?: string;
    moduleStatusDescription?: string;
    moduleIsSecondary?: boolean;
};

export type SuperadminTableColumn = {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
};

export type SuperadminRowStatus = {
    label: string;
    tone?: SuperadminTone;
};

export type SuperadminTableCell = string | { label: string; tone?: SuperadminTone };
export type SuperadminTableSwatchCell = { label: string; swatch: string; value?: string };

export type SuperadminTableRow = {
    id: string | number;
    primary: string;
    secondary?: string;
    status?: SuperadminRowStatus;
    cells: Record<string, SuperadminTableCell | SuperadminTableSwatchCell>;
    showHref?: string;
    editHref?: string;
};

export type SuperadminFilterControl = {
    key: string;
    label: string;
    type?: 'text' | 'select' | 'number' | 'date';
    value?: string;
    placeholder?: string;
    options?: SuperadminFormFieldOption[];
};

export type SuperadminSortOption = {
    label: string;
    value: string;
};

export type SuperadminAppliedFilter = {
    label: string;
    value: string;
};

export type SuperadminEmptyState = {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
};

export type SuperadminErrorState = {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
};

export type SuperadminFormFieldOption = {
    label: string;
    value: string;
    swatch?: string;
    segment?: string | null;
    system?: string | null;
    group?: string | null;
    active?: boolean;
    recommended?: boolean;
};

export type SuperadminFormFieldSuggestionGroup = {
    segment?: string;
    group?: string;
    system?: string;
    values: string[];
};

export type SuperadminFormField = {
    name: string;
    label: string;
    value: string;
    type?: 'text' | 'textarea' | 'select' | 'number' | 'date' | 'toggle' | 'color' | 'color-family' | 'hidden' | 'email' | 'password' | 'tel' | 'file';
    placeholder?: string;
    hint?: string;
    required?: boolean;
    disabled?: boolean;
    accept?: string;
    transform?: 'uppercase' | 'lowercase' | 'digits';
    maxLength?: number;
    min?: number;
    max?: number;
    pattern?: string;
    invalidMessage?: string;
    disallowNumbers?: boolean;
    options?: SuperadminFormFieldOption[];
    suggestions?: SuperadminFormFieldSuggestionGroup[];
};

export type SuperadminFormSection = {
    title: string;
    description: string;
    fields: SuperadminFormField[];
};

export type SuperadminFormSubmit = {
    action: string;
    method: 'post' | 'put';
    deleteAction?: string | null;
};

export type SuperadminFormStateAction = {
    label: string;
    value: string;
    confirm?: string;
};

export type SuperadminDetailField = {
    label: string;
    value: string;
    tone?: SuperadminTone;
};

export type SuperadminDetailSection = {
    title: string;
    description?: string;
    fields: SuperadminDetailField[];
};

export type SuperadminTimelineItem = {
    title: string;
    note: string;
    date: string;
    tone?: SuperadminTone;
};

export type SuperadminReportCard = {
    title: string;
    description: string;
    status: string;
    tone?: SuperadminTone;
};

export type SuperadminLogEntry = {
    id: string;
    title: string;
    description: string;
    date: string;
    tone?: SuperadminTone;
};

export type SuperadminDashboardMetric = {
    key: string;
    label: string;
    value: string;
    note: string;
    tone?: SuperadminTone;
    priority?: 'high' | 'normal';
    href?: string | null;
    icon: string;
};

export type SuperadminChartPoint = {
    label: string;
    value: number;
    amount?: number;
    tone?: SuperadminTone;
    href?: string | null;
};

export type SuperadminDashboardListItem = {
    title: string;
    meta?: string | null;
    value: string;
    label?: string | null;
    tone?: SuperadminTone;
    href?: string | null;
};

export type SuperadminDashboardAlert = {
    title: string;
    value: string;
    description: string;
    tone?: SuperadminTone;
    href?: string | null;
};

export type SuperadminDashboardPayload = {
    metrics: SuperadminDashboardMetric[];
    charts: {
        stockByCategory: SuperadminChartPoint[];
        productsByCategory: SuperadminChartPoint[];
        stockByState: SuperadminChartPoint[];
        salesByPeriod: SuperadminChartPoint[];
        reservationsByState: SuperadminChartPoint[];
        topCategories: SuperadminChartPoint[];
        topBrands: SuperadminChartPoint[];
        topFavorites: SuperadminChartPoint[];
    };
    lists: {
        lowStock: SuperadminDashboardListItem[];
        recentProducts: SuperadminDashboardListItem[];
        recentSales: SuperadminDashboardListItem[];
        recentReservations: SuperadminDashboardListItem[];
        alerts: SuperadminDashboardAlert[];
    };
};
