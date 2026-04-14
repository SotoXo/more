import type { LucideIcon } from 'lucide-react';

export type SuperadminTone =
    | 'neutral'
    | 'accent'
    | 'success'
    | 'warning'
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

export type SuperadminTableRow = {
    id: string | number;
    primary: string;
    secondary?: string;
    status?: SuperadminRowStatus;
    cells: Record<string, SuperadminTableCell>;
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
};

export type SuperadminFormField = {
    name: string;
    label: string;
    value: string;
    type?: 'text' | 'textarea' | 'select' | 'number' | 'date' | 'toggle' | 'color' | 'email' | 'password' | 'tel' | 'file';
    placeholder?: string;
    hint?: string;
    required?: boolean;
    disabled?: boolean;
    accept?: string;
    transform?: 'uppercase' | 'lowercase' | 'digits';
    maxLength?: number;
    options?: SuperadminFormFieldOption[];
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
