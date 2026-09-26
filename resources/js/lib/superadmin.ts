import type {
    SuperadminNavGroup,
    SuperadminTone,
} from '@/types/superadmin';
import {
    BadgeCheck,
    BarChart3,
    ClipboardList,
    DatabaseBackup,
    FolderKanban,
    LayoutDashboard,
    PackageSearch,
    Palette,
    Receipt,
    Ruler,
    ShieldCheck,
    Shirt,
    ShieldAlert,
    Sparkles,
    Users2,
    Warehouse,
    ScrollText,
    SwatchBook,
    History,
} from 'lucide-react';

export const superadminRoutes = {
    dashboard: '/superadmin',
    profile: '/superadmin/perfil',
    reports: '/superadmin/reportes',
    reportSection: (section: string) =>
        `/superadmin/reportes?section=${section}`,
    logs: '/superadmin/logs',
    audit: '/superadmin/auditoria',
    backups: '/superadmin/backups',
    inventoryMovements: '/superadmin/inventario/movimientos',
    damagedProducts: '/superadmin/prendas-daniadas',
    damagedProductsCreate: '/superadmin/prendas-daniadas/crear',
    damagedProductShow: (record: string | number) =>
        `/superadmin/prendas-daniadas/${record}`,
    resource: (section: string, resource: string) =>
        `/superadmin/${section}/${resource}`,
    create: (section: string, resource: string) =>
        `/superadmin/${section}/${resource}/nuevo`,
    show: (section: string, resource: string, record: string | number) =>
        `/superadmin/${section}/${resource}/${record}`,
    edit: (section: string, resource: string, record: string | number) =>
        `/superadmin/${section}/${resource}/${record}/editar`,
} as const;

export const superadminNavGroups: SuperadminNavGroup[] = [
    {
        label: 'Panel',
        items: [
            {
                label: 'Resumen',
                href: superadminRoutes.dashboard,
                icon: LayoutDashboard,
                exact: true,
            },
        ],
    },
    {
        label: 'Seguridad',
        items: [
            {
                label: 'Roles',
                href: superadminRoutes.resource('seguridad', 'roles'),
                icon: ShieldCheck,
            },
            {
                label: 'Usuarios',
                href: superadminRoutes.resource('seguridad', 'usuarios'),
                icon: Users2,
            },
        ],
    },
    {
        label: 'Catálogo',
        items: [
            {
                label: 'Prendas',
                href: superadminRoutes.resource('catalogo', 'prendas'),
                icon: Shirt,
            },
            {
                label: 'Categorías',
                href: superadminRoutes.resource('catalogo', 'categorias'),
                icon: FolderKanban,
            },
            {
                label: 'Marcas',
                href: superadminRoutes.resource('catalogo', 'marcas'),
                icon: BadgeCheck,
            },
            {
                label: 'Colores',
                href: superadminRoutes.resource('catalogo', 'colores'),
                icon: Palette,
            },
            { label: 'Patrones', href: superadminRoutes.resource('catalogo', 'patrones'), icon: SwatchBook },
            { label: 'Texturas', href: superadminRoutes.resource('catalogo', 'texturas'), icon: SwatchBook },
            { label: 'Materiales', href: superadminRoutes.resource('catalogo', 'materiales'), icon: SwatchBook },
            {
                label: 'Tallas',
                href: superadminRoutes.resource('catalogo', 'tallas'),
                icon: Ruler,
            },
            {
                label: 'Temporadas',
                href: superadminRoutes.resource('catalogo', 'temporadas'),
                icon: SwatchBook,
            },
        ],
    },
    {
        label: 'Inventario',
        items: [
            {
                label: 'Existencias',
                href: superadminRoutes.resource('inventario', 'stock'),
                icon: Warehouse,
                badge: 'Lectura',
                badgeTone: 'accent',
            },
            {
                label: 'Alertas de stock',
                href: superadminRoutes.resource('inventario', 'alertas'),
                icon: PackageSearch,
                badge: 'Lectura',
                badgeTone: 'accent',
            },
            {
                label: 'Kardex / Movimientos',
                href: superadminRoutes.inventoryMovements,
                icon: History,
                badge: 'Lectura',
                badgeTone: 'accent',
            },
            {
                label: 'Prendas dañadas',
                href: superadminRoutes.damagedProducts,
                icon: ShieldAlert,
            },
        ],
    },
    {
        label: 'Comercial',
        items: [
            {
                label: 'Ventas',
                href: superadminRoutes.resource('comercial', 'ventas'),
                icon: Receipt,
            },
            {
                label: 'Reservas',
                href: superadminRoutes.resource('comercial', 'reservas'),
                icon: ClipboardList,
            },
            {
                label: 'Favoritos',
                href: superadminRoutes.resource('comercial', 'favoritos'),
                icon: Sparkles,
            },
        ],
    },
    {
        label: 'Análisis',
        items: [
            {
                label: 'Reportes operativos',
                href: superadminRoutes.reports,
                icon: BarChart3,
                exact: false,
                children: [
                    { label: 'Resumen', href: superadminRoutes.reportSection('resumen') },
                    { label: 'Ventas', href: superadminRoutes.reportSection('ventas') },
                    { label: 'Reservas', href: superadminRoutes.reportSection('reservas') },
                    { label: 'Pagos', href: superadminRoutes.reportSection('pagos') },
                    { label: 'Inventario', href: superadminRoutes.reportSection('inventario') },
                    { label: 'Clientes', href: superadminRoutes.reportSection('clientes') },
                ],
            },
            {
                label: 'Registro de actividad',
                href: superadminRoutes.logs,
                icon: ScrollText,
            },
            {
                label: 'Auditoría crítica',
                href: superadminRoutes.audit,
                icon: ShieldAlert,
            },
            {
                label: 'Copias de seguridad',
                href: superadminRoutes.backups,
                icon: DatabaseBackup,
            },
        ],
    },
];

export function formatAdminDate(value?: string | null) {
    if (!value) {
        return 'Sin fecha';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('es-BO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatCompactNumber(value: number) {
    return new Intl.NumberFormat('es-BO', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
}

export function toneClasses(tone: SuperadminTone = 'neutral') {
    return {
        neutral: 'sa-badge text-stone-200',
        accent: 'sa-badge sa-badge-accent',
        success: 'sa-badge sa-badge-success',
        warning: 'sa-badge sa-badge-warning',
        info: 'sa-badge sa-badge-info',
        danger: 'sa-badge sa-badge-danger',
    }[tone];
}

export function isSuperadminPath(pathname: string) {
    return pathname === superadminRoutes.dashboard || pathname.startsWith('/superadmin/');
}
