import { superadminNavGroups } from '@/lib/superadmin';
import type { SuperadminNavItem } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import SuperadminBadge from './SuperadminBadge';

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
    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});

    useEffect(() => {
        setExpandedItems((current) => {
            const next = { ...current };

            superadminNavGroups.forEach((group) => {
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
                    <p className="text-sm font-semibold tracking-[0.08em] text-white">
                        Mati Boutique
                    </p>
                    {!collapsed && (
                        <p className="mt-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-400">
                            Panel administrativo
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
                                                        ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-white'
                                                        : 'border-transparent text-stone-300 hover:border-white/8 hover:bg-[#121c27] hover:text-white',
                                                ].join(' ')}
                                                aria-expanded={expanded}
                                            >
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#111922]">
                                                    <Icon size={17} />
                                                </span>
                                                <span className="min-w-0 flex-1 text-sm font-medium">
                                                    {item.label}
                                                </span>
                                                {item.badge && (
                                                    <SuperadminBadge tone={item.badgeTone ?? 'accent'}>
                                                        {item.badge}
                                                    </SuperadminBadge>
                                                )}
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
                                                                        ? 'border-[rgba(216,189,143,0.34)] bg-[rgba(216,189,143,0.12)] text-white'
                                                                        : 'border-transparent text-stone-400 hover:border-white/8 hover:bg-[#121c27] hover:text-white',
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
                                        className={[
                                            'group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition duration-200',
                                            active
                                                ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-white'
                                                : 'border-transparent text-stone-300 hover:border-white/8 hover:bg-[#121c27] hover:text-white',
                                            collapsed ? 'justify-center' : '',
                                        ].join(' ')}
                                    >
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/8 bg-[#111922]">
                                            <Icon size={17} />
                                        </span>
                                        {!collapsed && (
                                            <>
                                                <span className="min-w-0 flex-1 text-sm font-medium">{item.label}</span>
                                                {item.badge && (
                                                    <SuperadminBadge tone={item.badgeTone ?? 'accent'}>
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
