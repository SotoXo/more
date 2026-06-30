import NotificationBell from '@/Components/Notifications/NotificationBell';
import ThemeToggle, { type AdminTheme } from '@/Components/Superadmin/ThemeToggle';
import { vendedorNavGroups } from '@/lib/vendedor';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminNavItem } from '@/types/superadmin';
import { Head, Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { ChevronDown, ChevronLeft, ChevronRight, LogOut, Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';

const themeStorageKey = 'mati-vendedor-theme';

function splitUrl(value: string) {
    const [path, query = ''] = value.split('?');

    return {
        path,
        params: new URLSearchParams(query.split('#')[0]),
    };
}

function hrefQueryMatches(currentUrl: string, href: string) {
    const current = splitUrl(currentUrl);
    const target = splitUrl(href);

    if (!href.includes('?')) {
        return true;
    }

    return Array.from(target.params.entries()).every(
        ([key, value]) => current.params.get(key) === value,
    );
}

function isActive(currentUrl: string, href: string, exact?: boolean) {
    const current = splitUrl(currentUrl);
    const target = splitUrl(href);

    if (exact) {
        return current.path === target.path && hrefQueryMatches(currentUrl, href);
    }

    return (
        (current.path === target.path || current.path.startsWith(`${target.path}/`)) &&
        hrefQueryMatches(currentUrl, href)
    );
}

function hasActiveChild(currentUrl: string, item: SuperadminNavItem) {
    return item.children?.some((child) => isActive(currentUrl, child.href, child.exact)) ?? false;
}

function isItemActive(currentUrl: string, item: SuperadminNavItem) {
    return isActive(currentUrl, item.href, item.exact) || hasActiveChild(currentUrl, item);
}

function VendedorSidebar({
    currentPath,
    collapsed,
    onToggleCollapse,
    onNavigate,
}: {
    currentPath: string;
    collapsed: boolean;
    onToggleCollapse?: () => void;
    onNavigate?: () => void;
}) {
    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});

    useEffect(() => {
        setExpandedItems((current) => {
            const next = { ...current };

            vendedorNavGroups.forEach((group) => {
                group.items.forEach((item) => {
                    if (item.children && isItemActive(currentPath, item)) {
                        next[item.href] = true;
                    }
                });
            });

            return next;
        });
    }, [currentPath]);

    return (
        <div className="flex h-full flex-col">
            <div className="flex items-center justify-between border-b border-white/8 px-4 py-5">
                <div className="min-w-0">
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">
                        Mati Boutique
                    </p>
                    {!collapsed && (
                        <p className="mt-2 text-xs text-[color:var(--admin-muted)]">
                            Panel vendedor
                        </p>
                    )}
                </div>
                {onToggleCollapse && (
                    <button
                        type="button"
                        onClick={onToggleCollapse}
                        title={collapsed ? 'Expandir menu' : 'Contraer menu'}
                        aria-label={collapsed ? 'Expandir menu' : 'Contraer menu'}
                        className="sa-button-ghost hidden h-10 w-10 p-0 xl:inline-flex"
                    >
                        {collapsed ? <ChevronRight size={15} /> : <ChevronLeft size={15} />}
                    </button>
                )}
            </div>

            <div className="flex-1 space-y-7 overflow-y-auto px-3 py-4">
                {vendedorNavGroups.map((group) => (
                    <div key={group.label}>
                        {!collapsed && (
                            <p className="px-3 text-xs font-semibold uppercase text-[color:var(--admin-muted)]">
                                {group.label}
                            </p>
                        )}
                        <div className="mt-3 space-y-1">
                            {group.items.map((item) => {
                                const active = isItemActive(currentPath, item);
                                const Icon = item.icon;
                                const hasChildren = (item.children?.length ?? 0) > 0;
                                const expanded = expandedItems[item.href] ?? active;

                                if (hasChildren && !collapsed) {
                                    return (
                                        <div key={item.href} className="space-y-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setExpandedItems((current) => ({
                                                        ...current,
                                                        [item.href]: !(current[item.href] ?? active),
                                                    }))
                                                }
                                                className={[
                                                    'group flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition duration-200',
                                                    active
                                                        ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-[color:var(--admin-text)]'
                                                        : 'border-transparent text-[color:var(--admin-muted)] hover:border-white/8 hover:bg-[#121c27] hover:text-[color:var(--admin-text)]',
                                                ].join(' ')}
                                                aria-expanded={expanded}
                                            >
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#111922]">
                                                    <Icon size={17} />
                                                </span>
                                                <span className="min-w-0 flex-1 text-sm font-medium">
                                                    {item.label}
                                                </span>
                                                <ChevronDown
                                                    size={15}
                                                    className={`shrink-0 transition duration-200 ${expanded ? 'rotate-180' : ''}`}
                                                />
                                            </button>

                                            {expanded && (
                                                <div className="ml-7 space-y-1 border-l border-white/8 pl-4">
                                                    {item.children?.map((child) => {
                                                        const childActive = isActive(
                                                            currentPath,
                                                            child.href,
                                                            child.exact,
                                                        );

                                                        return (
                                                            <Link
                                                                key={child.href}
                                                                href={child.href}
                                                                onClick={onNavigate}
                                                                className={[
                                                                    'block rounded-xl border px-3 py-2 text-sm transition duration-200',
                                                                    childActive
                                                                        ? 'border-[rgba(216,189,143,0.34)] bg-[rgba(216,189,143,0.12)] text-[color:var(--admin-text)]'
                                                                        : 'border-transparent text-[color:var(--admin-muted)] hover:border-white/8 hover:bg-[#121c27] hover:text-[color:var(--admin-text)]',
                                                                ].join(' ')}
                                                            >
                                                                {child.label}
                                                            </Link>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    );
                                }

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={onNavigate}
                                        title={collapsed ? item.label : undefined}
                                        className={[
                                            'group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition duration-200',
                                            active
                                                ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-[color:var(--admin-text)]'
                                                : 'border-transparent text-[color:var(--admin-muted)] hover:border-white/8 hover:bg-[#121c27] hover:text-[color:var(--admin-text)]',
                                            collapsed ? 'justify-center' : '',
                                        ].join(' ')}
                                    >
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#111922]">
                                            <Icon size={17} />
                                        </span>
                                        {!collapsed && (
                                            <span className="min-w-0 flex-1 text-sm font-medium">
                                                {item.label}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function VendedorTopbar({
    theme,
    onToggleTheme,
    onOpenSidebar,
}: {
    theme: AdminTheme;
    onToggleTheme: () => void;
    onOpenSidebar: () => void;
}) {
    const page = usePage<LandingPageProps>();
    const auth = page.props.auth;
    const user = auth?.user ?? null;
    const role = auth?.rol ?? null;
    const initial = user?.nom_usu?.slice(0, 1)?.toUpperCase() ?? 'V';

    return (
        <div className="sa-topbar sticky top-0 z-30">
            <div className="flex items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    onClick={onOpenSidebar}
                    title="Abrir menu"
                    aria-label="Abrir menu"
                    className="sa-button-ghost h-11 w-11 p-0 xl:hidden"
                >
                    <Menu size={17} />
                </button>

                <div className="sa-topbar-card min-w-0 flex-1 px-4 py-3">
                    <p className="text-xs text-[color:var(--admin-muted)]">
                        Acceso
                    </p>
                    <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                        Panel vendedor
                    </p>
                </div>

                <div className="hidden items-center gap-2 sm:flex">
                    {user && <NotificationBell variant="admin" />}
                    <ThemeToggle theme={theme} onToggle={onToggleTheme} />
                </div>

                <Link
                    href="/profile"
                    className="sa-user-card hidden min-w-[210px] items-center gap-3 px-4 py-2.5 lg:flex"
                >
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-[color:var(--admin-accent-soft)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                        {initial}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                            {user?.nombre ?? user?.nom_usu ?? 'Vendedor'}
                        </p>
                        <p className="truncate text-xs text-[color:var(--admin-muted)]">
                            {role?.nombre ?? 'Sin rol'}
                        </p>
                    </div>
                </Link>

                <div className="flex items-center gap-2 sm:hidden">
                    {user && <NotificationBell variant="admin" />}
                    <ThemeToggle theme={theme} onToggle={onToggleTheme} />
                </div>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    title="Cerrar sesion"
                    aria-label="Cerrar sesion"
                    className="sa-button-ghost h-11 px-3 text-xs font-semibold lg:px-4"
                >
                    <LogOut size={15} />
                    <span className="hidden lg:inline">Cerrar sesion</span>
                </Link>
            </div>
        </div>
    );
}

export default function VendedorLayout({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const page = usePage();
    const currentUrl = page.url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const [theme, setTheme] = useState<AdminTheme>('dark');

    useEffect(() => {
        const storedTheme = window.localStorage.getItem(themeStorageKey);

        if (storedTheme === 'light' || storedTheme === 'dark') {
            setTheme(storedTheme);
        }
    }, []);

    const toggleTheme = () => {
        setTheme((current) => {
            const next = current === 'dark' ? 'light' : 'dark';
            window.localStorage.setItem(themeStorageKey, next);

            return next;
        });
    };

    return (
        <>
            <Head title={title} />
            <div className="sa-shell sa-grid-pattern relative overflow-hidden" data-admin-theme={theme}>
                <div
                    className={
                        collapsed
                            ? 'grid min-h-screen xl:grid-cols-[92px_minmax(0,1fr)]'
                            : 'grid min-h-screen xl:grid-cols-[280px_minmax(0,1fr)]'
                    }
                >
                    <aside className="sa-sidebar-shell relative z-40 hidden xl:block">
                        <VendedorSidebar
                            currentPath={currentUrl}
                            collapsed={collapsed}
                            onToggleCollapse={() => setCollapsed((value) => !value)}
                        />
                    </aside>

                    <AnimatePresence>
                        {mobileOpen && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                className="fixed inset-0 z-50 xl:hidden"
                            >
                                <div className="sa-mobile-overlay absolute inset-0" onClick={() => setMobileOpen(false)} />
                                <motion.aside
                                    initial={{ x: '-100%' }}
                                    animate={{ x: 0 }}
                                    exit={{ x: '-100%' }}
                                    transition={{ duration: 0.24, ease: [0.22, 1, 0.36, 1] }}
                                    className="sa-sidebar-shell absolute inset-y-0 left-0 w-full max-w-[280px]"
                                >
                                    <VendedorSidebar
                                        currentPath={currentUrl}
                                        collapsed={false}
                                        onNavigate={() => setMobileOpen(false)}
                                    />
                                </motion.aside>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    <div className="relative z-10 min-w-0">
                        <VendedorTopbar
                            theme={theme}
                            onToggleTheme={toggleTheme}
                            onOpenSidebar={() => setMobileOpen(true)}
                        />
                        <main className="px-4 py-5 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-[1500px]">{children}</div>
                        </main>
                    </div>
                </div>
            </div>
        </>
    );
}
