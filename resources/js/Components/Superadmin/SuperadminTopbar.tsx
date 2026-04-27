import { superadminRoutes } from '@/lib/superadmin';
import type { LandingPageProps } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import { LogOut, Menu, UserRound } from 'lucide-react';

export default function SuperadminTopbar({
    onOpenSidebar,
}: {
    onOpenSidebar: () => void;
}) {
    const page = usePage<LandingPageProps>();
    const auth = page.props.auth;
    const user = auth?.user ?? null;
    const role = auth?.rol ?? null;

    return (
        <div className="sticky top-0 z-30 border-b border-white/8 bg-[#0d141d]">
            <div className="flex items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    onClick={onOpenSidebar}
                    className="sa-button-ghost h-11 w-11 p-0 xl:hidden"
                >
                    <Menu size={17} />
                </button>

                <div className="min-w-0 flex-1 rounded-2xl border border-white/8 bg-[#101822] px-4 py-3">
                    <p className="text-[0.68rem] uppercase tracking-[0.2em] text-stone-400">
                        Acceso
                    </p>
                    <p className="truncate text-sm font-medium text-white">
                        Panel superadmin
                    </p>
                </div>

                <div className="hidden items-center gap-2 lg:flex">
                    <Link
                        href={superadminRoutes.profile}
                        className="sa-button-secondary h-11 px-4 text-[0.72rem] uppercase tracking-[0.22em]"
                    >
                        <UserRound size={15} />
                        Mi perfil
                    </Link>
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
                    className="hidden min-w-[210px] items-center gap-3 rounded-2xl border border-white/8 bg-[#101822] px-4 py-2.5 transition hover:border-[rgba(126,154,178,0.24)] hover:bg-[#121c27] sm:flex"
                >
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-[rgba(126,154,178,0.24)] bg-[rgba(126,154,178,0.12)] text-[rgb(194,214,232)]">
                        {user?.nom_usu?.slice(0, 1)?.toUpperCase() ?? 'S'}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-white">
                            {user?.nombre ?? user?.nom_usu ?? 'Superadmin'}
                        </p>
                        <p className="truncate text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                            {role?.nombre ?? 'Sin rol'}
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    );
}
