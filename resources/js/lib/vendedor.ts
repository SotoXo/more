import type { SuperadminNavGroup } from '@/types/superadmin';
import {
    AlertTriangle,
    BarChart3,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    PackageSearch,
    Receipt,
    ShieldAlert,
    Shirt,
    Truck,
    Warehouse,
} from 'lucide-react';

export const vendedorRoutes = {
    dashboard: '/vendedor',
    prendas: '/vendedor/prendas',
    existencias: '/vendedor/existencias',
    alertasStock: '/vendedor/alertas-stock',
    ventas: '/vendedor/ventas',
    reservas: '/vendedor/reservas',
    pagos: '/vendedor/pagos',
    recojos: '/vendedor/recojos',
    prendasDaniadas: '/vendedor/prendas-daniadas',
    reportes: '/vendedor/reportes',
} as const;

export const vendedorNavGroups: SuperadminNavGroup[] = [
    {
        label: 'Panel',
        items: [
            {
                label: 'Resumen',
                href: vendedorRoutes.dashboard,
                icon: LayoutDashboard,
                exact: true,
            },
        ],
    },
    {
        label: 'Catalogo',
        items: [
            {
                label: 'Consultar prendas',
                href: vendedorRoutes.prendas,
                icon: Shirt,
            },
        ],
    },
    {
        label: 'Inventario',
        items: [
            {
                label: 'Existencias',
                href: vendedorRoutes.existencias,
                icon: Warehouse,
            },
            {
                label: 'Alertas de stock',
                href: vendedorRoutes.alertasStock,
                icon: PackageSearch,
            },
            {
                label: 'Reportar prenda danada',
                href: vendedorRoutes.prendasDaniadas,
                icon: ShieldAlert,
            },
        ],
    },
    {
        label: 'Comercial',
        items: [
            {
                label: 'Ventas',
                href: vendedorRoutes.ventas,
                icon: Receipt,
            },
            {
                label: 'Reservas',
                href: vendedorRoutes.reservas,
                icon: ClipboardList,
            },
            {
                label: 'Pagos',
                href: vendedorRoutes.pagos,
                icon: CreditCard,
            },
            {
                label: 'Recojos / entregas',
                href: vendedorRoutes.recojos,
                icon: Truck,
            },
        ],
    },
    {
        label: 'Analisis',
        items: [
            {
                label: 'Mis reportes',
                href: vendedorRoutes.reportes,
                icon: BarChart3,
            },
        ],
    },
];

export function isVendedorPath(pathname: string) {
    return pathname === vendedorRoutes.dashboard || pathname.startsWith('/vendedor/');
}
