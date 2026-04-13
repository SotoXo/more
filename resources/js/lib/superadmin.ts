import type {
    SuperadminNavGroup,
    SuperadminTone,
} from '@/types/superadmin';
import {
    BadgeCheck,
    Boxes,
    ClipboardList,
    FolderKanban,
    LayoutDashboard,
    PackageSearch,
    Palette,
    Receipt,
    Ruler,
    ShieldCheck,
    Shirt,
    Sparkles,
    Users2,
    Warehouse,
    Waypoints,
    BookOpenText,
    ScrollText,
    Images,
    SwatchBook,
} from 'lucide-react';

export const superadminRoutes = {
    dashboard: '/superadmin',
    profile: '/superadmin/perfil',
    reports: '/superadmin/reportes',
    logs: '/superadmin/logs',
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
                label: 'Dashboard',
                href: superadminRoutes.dashboard,
                icon: LayoutDashboard,
                exact: true,
            },
            {
                label: 'Mi perfil',
                href: superadminRoutes.profile,
                icon: Users2,
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
            {
                label: 'Prendas',
                href: superadminRoutes.resource('catalogo', 'prendas'),
                icon: Shirt,
            },
            {
                label: 'Variantes',
                href: superadminRoutes.resource('catalogo', 'variantes'),
                icon: Waypoints,
            },
            {
                label: 'Imágenes',
                href: superadminRoutes.resource('catalogo', 'imagenes'),
                icon: Images,
            },
        ],
    },
    {
        label: 'Inventario',
        items: [
            {
                label: 'Control de stock',
                href: superadminRoutes.resource('inventario', 'stock'),
                icon: Warehouse,
            },
            {
                label: 'Ajustes',
                href: superadminRoutes.resource('inventario', 'ajustes'),
                icon: Boxes,
            },
            {
                label: 'Alertas',
                href: superadminRoutes.resource('inventario', 'alertas'),
                icon: PackageSearch,
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
                label: 'Reportes',
                href: superadminRoutes.reports,
                icon: BookOpenText,
            },
            {
                label: 'Logs',
                href: superadminRoutes.logs,
                icon: ScrollText,
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
        danger: 'sa-badge sa-badge-danger',
    }[tone];
}

export function isSuperadminPath(pathname: string) {
    return pathname === superadminRoutes.dashboard || pathname.startsWith('/superadmin/');
}
