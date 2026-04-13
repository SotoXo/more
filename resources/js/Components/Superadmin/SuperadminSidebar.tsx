import { superadminNavGroups } from '@/lib/superadmin';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import SuperadminBadge from './SuperadminBadge';

function isActive(pathname: string, href: string, exact?: boolean) {
    if (exact) {
        return pathname === href;
    }

    return pathname === href || pathname.startsWith(`${href}/`);
}

export default function SuperadminSidebar({
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
    return (
        <div className="flex h-full flex-col">
            <div className="flex items-center justify-between border-b border-white/8 px-4 py-5">
                <div className="min-w-0">
                    <p className="text-sm font-semibold uppercase tracking-[0.32em] text-white">
                        MORE
                    </p>
                    {!collapsed && (
                        <p className="mt-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-400">
                            Superadmin
                        </p>
                    )}
                </div>
                {onToggleCollapse && (
                    <button
                        type="button"
                        onClick={onToggleCollapse}
                        className="sa-button-ghost hidden h-10 w-10 p-0 xl:inline-flex"
                    >
                        {collapsed ? <ChevronRight size={15} /> : <ChevronLeft size={15} />}
                    </button>
                )}
            </div>

            <div className="flex-1 space-y-7 overflow-y-auto px-3 py-4">
                {superadminNavGroups.map((group) => (
                    <div key={group.label}>
                        {!collapsed && (
                            <p className="px-3 text-[0.68rem] uppercase tracking-[0.2em] text-stone-400">
                                {group.label}
                            </p>
                        )}
                        <div className="mt-3 space-y-1">
                            {group.items.map((item) => {
                                const active = isActive(currentPath, item.href, item.exact);
                                const Icon = item.icon;

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={onNavigate}
                                        className={[
                                            'group flex items-center gap-3 rounded-2xl border px-3 py-3 transition duration-200',
                                            active
                                                ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-white'
                                                : 'border-transparent text-stone-300 hover:border-white/8 hover:bg-[#121c27] hover:text-white',
                                            collapsed ? 'justify-center' : '',
                                        ].join(' ')}
                                    >
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/8 bg-[#111922]">
                                            <Icon size={17} />
                                        </span>
                                        {!collapsed && (
                                            <>
                                                <span className="min-w-0 flex-1 text-sm font-medium">{item.label}</span>
                                                {item.badge && (
                                                    <SuperadminBadge tone="accent">
                                                        {item.badge}
                                                    </SuperadminBadge>
                                                )}
                                            </>
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
