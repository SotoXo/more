import { superadminRoutes } from '@/lib/superadmin';
import type { LandingPageProps } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import { LogOut, Menu } from 'lucide-react';
import ThemeToggle, { type AdminTheme } from './ThemeToggle';

export default function SuperadminTopbar({
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

    return (
        <div className="sa-topbar sticky top-0 z-30">
            <div className="flex items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    onClick={onOpenSidebar}
                    className="sa-button-ghost h-11 w-11 p-0 xl:hidden"
                >
                    <Menu size={17} />
                </button>

                <div className="sa-topbar-card min-w-0 flex-1 px-4 py-3">
                    <p className="text-[0.68rem] uppercase tracking-[0.2em] text-[color:var(--admin-muted)]">
                        Acceso
                    </p>
                    <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                        Panel administrativo
                    </p>
                </div>

                <div className="hidden items-center gap-2 lg:flex">
                    <ThemeToggle theme={theme} onToggle={onToggleTheme} />
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="sa-button-ghost h-11 px-4 text-[0.72rem] uppercase tracking-[0.22em]"
                    >
                        <LogOut size={15} />
                        Cerrar sesión
                    </Link>
                </div>

                <Link
                    href={superadminRoutes.profile}
                    className="sa-user-card hidden min-w-[210px] items-center gap-3 px-4 py-2.5 sm:flex"
                >
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-[color:var(--admin-accent-soft)] bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                        {user?.nom_usu?.slice(0, 1)?.toUpperCase() ?? 'S'}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-[color:var(--admin-text)]">
                            {user?.nombre ?? user?.nom_usu ?? 'Administrador'}
                        </p>
                        <p className="truncate text-[0.68rem] uppercase tracking-[0.18em] text-[color:var(--admin-muted)]">
                            {role?.nombre ?? 'Sin rol'}
                        </p>
                    </div>
                </Link>

                <div className="sm:hidden">
                    <ThemeToggle theme={theme} onToggle={onToggleTheme} />
                </div>
            </div>
        </div>
    );
}
