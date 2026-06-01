import axios from 'axios';
import { AnimatePresence, motion } from 'framer-motion';
import {
    Bell,
    CheckCheck,
    ExternalLink,
    Loader2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export type NotificationItem = {
    id: string;
    title: string;
    message: string;
    type?: string | null;
    url?: string | null;
    severity?: string | null;
    icon?: string | null;
    read_at?: string | null;
    created_at?: string | null;
    entity_type?: string | null;
    entity_id?: number | null;
};

type NotificationSummary = {
    unread_count: number;
    latest_notifications: NotificationItem[];
};

type NotificationBellProps = {
    variant?: 'storefront' | 'admin';
    className?: string;
};

const endpointSummary = '/notificaciones/resumen';
const endpointReadAll = '/notificaciones/leer-todas';

function formatDate(value?: string | null) {
    if (!value) return '';

    try {
        return new Intl.DateTimeFormat('es-BO', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    } catch {
        return '';
    }
}

function severityDot(severity?: string | null) {
    if (severity === 'success') return 'bg-emerald-400';
    if (severity === 'warning') return 'bg-amber-400';
    if (severity === 'error') return 'bg-rose-400';

    return 'bg-sky-400';
}

export default function NotificationBell({
    variant = 'storefront',
    className = '',
}: NotificationBellProps) {
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [error, setError] = useState<string | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const isAdmin = variant === 'admin';

    const buttonClass = isAdmin
        ? 'sa-button-ghost relative h-11 w-11 p-0'
        : 'more-icon-button relative inline-flex h-9 w-9 items-center justify-center text-stone-100';

    const dropdownClass = isAdmin
        ? 'absolute right-0 top-full mt-3 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-[1.4rem] border border-[color:var(--admin-border)] bg-[color:var(--admin-panel)] shadow-2xl'
        : 'more-panel absolute right-0 top-full mt-3 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-[1.4rem]';

    const itemClass = isAdmin
        ? 'border-b border-[color:var(--admin-border)] px-4 py-3 text-left transition hover:bg-[color:var(--admin-accent-soft)]'
        : 'border-b border-white/8 px-4 py-3 text-left transition hover:bg-[rgba(181,122,109,0.12)]';

    async function loadSummary() {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.get<NotificationSummary>(endpointSummary, {
                headers: { Accept: 'application/json' },
            });

            setUnreadCount(Number(response.data.unread_count ?? 0));
            setNotifications(response.data.latest_notifications ?? []);
        } catch (requestError) {
            if (axios.isAxiosError(requestError) && requestError.response?.status === 401) {
                setUnreadCount(0);
                setNotifications([]);
                setOpen(false);
                return;
            }

            setError('No se pudieron cargar las notificaciones.');
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void loadSummary();
    }, []);

    useEffect(() => {
        if (!open) return;

        void loadSummary();
    }, [open]);

    useEffect(() => {
        const handler = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    async function markAsRead(notification: NotificationItem) {
        try {
            const response = await axios.post<{
                unread_count: number;
                notification?: NotificationItem;
            }>(
                `/notificaciones/${notification.id}/leer`,
                {},
                { headers: { Accept: 'application/json' } },
            );

            setUnreadCount(Number(response.data.unread_count ?? 0));
            setNotifications((current) =>
                current.map((item) =>
                    item.id === notification.id
                        ? { ...item, read_at: response.data.notification?.read_at ?? new Date().toISOString() }
                        : item,
                ),
            );

            if (notification.url) {
                window.location.assign(notification.url);
            }
        } catch {
            setError('No se pudo marcar la notificación como leída.');
        }
    }

    async function markAllAsRead() {
        try {
            await axios.post(endpointReadAll, {}, { headers: { Accept: 'application/json' } });
            setUnreadCount(0);
            setNotifications((current) =>
                current.map((notification) => ({
                    ...notification,
                    read_at: notification.read_at ?? new Date().toISOString(),
                })),
            );
        } catch {
            setError('No se pudieron marcar todas como leídas.');
        }
    }

    return (
        <div ref={containerRef} className={`relative ${className}`}>
            <button
                type="button"
                aria-label="Abrir notificaciones"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className={buttonClass}
            >
                <Bell size={isAdmin ? 17 : 16} />
                {unreadCount > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[rgb(223,184,166)] px-1 text-[0.56rem] font-bold text-slate-950 shadow-lg">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            <AnimatePresence>
                {open && (
                    <motion.div
                        initial={{ opacity: 0, y: 10, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 8, scale: 0.98 }}
                        transition={{ duration: 0.2, ease: [0.22, 1, 0.36, 1] }}
                        className={dropdownClass}
                    >
                        <div className={isAdmin ? 'border-b border-[color:var(--admin-border)] px-4 py-4' : 'border-b border-white/10 px-4 py-4'}>
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className={isAdmin ? 'text-sm font-semibold text-[color:var(--admin-text)]' : 'text-sm font-semibold text-white'}>
                                        Notificaciones
                                    </p>
                                    <p className={isAdmin ? 'mt-1 text-xs text-[color:var(--admin-muted)]' : 'mt-1 text-xs text-stone-400'}>
                                        {unreadCount > 0
                                            ? `${unreadCount} sin leer`
                                            : 'Todo al día'}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={markAllAsRead}
                                    disabled={unreadCount === 0}
                                    className={isAdmin
                                        ? 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[0.66rem] uppercase tracking-[0.18em] text-[color:var(--admin-muted)] transition hover:bg-[color:var(--admin-accent-soft)] hover:text-[color:var(--admin-text)] disabled:opacity-40'
                                        : 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[0.66rem] uppercase tracking-[0.18em] text-stone-300 transition hover:bg-white/8 hover:text-white disabled:opacity-40'}
                                >
                                    <CheckCheck size={13} />
                                    Leer todas
                                </button>
                            </div>
                        </div>

                        <div className="max-h-[24rem] overflow-y-auto">
                            {loading && notifications.length === 0 ? (
                                <div className={isAdmin ? 'flex items-center gap-2 px-4 py-6 text-sm text-[color:var(--admin-muted)]' : 'flex items-center gap-2 px-4 py-6 text-sm text-stone-300'}>
                                    <Loader2 size={15} className="animate-spin" />
                                    Cargando notificaciones...
                                </div>
                            ) : notifications.length === 0 ? (
                                <div className={isAdmin ? 'px-4 py-7 text-sm text-[color:var(--admin-muted)]' : 'px-4 py-7 text-sm text-stone-300'}>
                                    No tienes notificaciones.
                                </div>
                            ) : (
                                notifications.map((notification) => {
                                    const unread = !notification.read_at;

                                    return (
                                        <button
                                            type="button"
                                            key={notification.id}
                                            onClick={() => void markAsRead(notification)}
                                            className={`${itemClass} block w-full`}
                                        >
                                            <div className="flex gap-3">
                                                <span
                                                    className={`mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${
                                                        unread ? severityDot(notification.severity) : 'bg-transparent ring-1 ring-current opacity-30'
                                                    }`}
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className={isAdmin ? 'block text-sm font-semibold text-[color:var(--admin-text)]' : 'block text-sm font-semibold text-white'}>
                                                        {notification.title || 'Notificación'}
                                                    </span>
                                                    <span className={isAdmin ? 'mt-1 block line-clamp-2 text-xs leading-5 text-[color:var(--admin-muted)]' : 'mt-1 block line-clamp-2 text-xs leading-5 text-stone-300'}>
                                                        {notification.message}
                                                    </span>
                                                    <span className={isAdmin ? 'mt-2 flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.16em] text-[color:var(--admin-muted)]' : 'mt-2 flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.16em] text-stone-400'}>
                                                        {formatDate(notification.created_at)}
                                                        {notification.url && (
                                                            <>
                                                                <span>/</span>
                                                                <span className="inline-flex items-center gap-1">
                                                                    Ver <ExternalLink size={11} />
                                                                </span>
                                                            </>
                                                        )}
                                                    </span>
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })
                            )}
                        </div>

                        {error && (
                            <div className={isAdmin ? 'border-t border-[color:var(--admin-border)] px-4 py-3 text-xs text-rose-400' : 'border-t border-white/10 px-4 py-3 text-xs text-rose-200'}>
                                {error}
                            </div>
                        )}
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
