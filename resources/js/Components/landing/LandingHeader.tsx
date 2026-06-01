import { shopNavigationItems, shopRoutes } from '@/lib/shop';
import {
    PURCHASE_CART_EVENT,
    purchaseCartCount,
    readPurchaseCart,
} from '@/lib/purchaseCart';
import NotificationBell from '@/Components/Notifications/NotificationBell';
import type { LandingPageProps, LandingUser } from '@/types/landing';
import { Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    ChevronDown,
    Heart,
    LogOut,
    Menu,
    Package,
    Search,
    ShoppingBag,
    User2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const iconButtonClass =
    'more-icon-button inline-flex h-9 w-9 items-center justify-center text-stone-100';

const dropdownLinkClass =
    'flex items-center gap-3 rounded-[1rem] px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-200 transition hover:bg-[rgba(181,122,109,0.12)] hover:text-white';

function AccountDropdown({
    user,
    onClose,
}: {
    user: LandingUser;
    onClose: () => void;
}) {
    if (!user) return null;

    return (
        <motion.div
            initial={{ opacity: 0, y: 10, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 8, scale: 0.98 }}
            transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
            className="more-panel absolute right-0 top-full mt-3 w-64 rounded-[1.35rem] p-2"
        >
            <div className="border-b border-white/8 px-4 py-3">
                <p className="text-[0.68rem] uppercase tracking-[0.24em] text-stone-300">
                    Cuenta
                </p>
                <p className="mt-2 text-sm text-white">{user.nom_usu}</p>
            </div>

            <div className="mt-1 space-y-0.5">
                <Link href="/cuenta" onClick={onClose} className={dropdownLinkClass}>
                    <User2 size={14} />
                    Mi cuenta
                </Link>
                <Link
                    href="/cuenta/favoritos"
                    onClick={onClose}
                    className={dropdownLinkClass}
                >
                    <Heart size={14} />
                    Favoritos
                </Link>
                <Link
                    href="/cuenta/reservas"
                    onClick={onClose}
                    className={dropdownLinkClass}
                >
                    <Package size={14} />
                    Reservas
                </Link>
                <Link
                    href="/cuenta/pedidos"
                    onClick={onClose}
                    className={dropdownLinkClass}
                >
                    <ShoppingBag size={14} />
                    Compras
                </Link>
            </div>

            <div className="mt-1 border-t border-white/8 pt-1">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    onClick={onClose}
                    className="flex w-full items-center gap-3 rounded-[1rem] px-4 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-stone-300 transition hover:bg-[rgba(181,122,109,0.12)] hover:text-white"
                >
                    <LogOut size={14} />
                    Cerrar sesión
                </Link>
            </div>
        </motion.div>
    );
}

export default function LandingHeader({ user }: { user: LandingUser }) {
    const page = usePage<LandingPageProps>();
    const currentPath = page.url.split('?')[0];
    const [isOpen, setIsOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);
    const [reservationBagCount, setReservationBagCount] = useState(
        Number(page.props.reservation_bag_count ?? 0),
    );
    const accountRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const onScroll = () => setIsScrolled(window.scrollY > 14);
        onScroll();
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    useEffect(() => {
        const handler = (event: MouseEvent) => {
            if (accountRef.current && !accountRef.current.contains(event.target as Node)) {
                setAccountOpen(false);
            }
        };

        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    useEffect(() => {
        const syncCart = () => setCartCount(purchaseCartCount(readPurchaseCart()));

        syncCart();
        window.addEventListener(PURCHASE_CART_EVENT, syncCart);
        window.addEventListener('storage', syncCart);

        return () => {
            window.removeEventListener(PURCHASE_CART_EVENT, syncCart);
            window.removeEventListener('storage', syncCart);
        };
    }, []);

    useEffect(() => {
        setReservationBagCount(Number(page.props.reservation_bag_count ?? 0));
    }, [page.props.reservation_bag_count]);

    useEffect(() => {
        const syncReservationBag = (event: Event) => {
            const detail = (event as CustomEvent<{ count?: number }>).detail;

            if (typeof detail?.count === 'number') {
                setReservationBagCount(detail.count);
            }
        };

        window.addEventListener('mati-boutique:reservation-bag-updated', syncReservationBag);

        return () => {
            window.removeEventListener('mati-boutique:reservation-bag-updated', syncReservationBag);
        };
    }, []);

    function CartIconLink({
        mobile = false,
        onClick,
    }: {
        mobile?: boolean;
        onClick?: () => void;
    }) {
        return (
            <Link
                href={shopRoutes.cart}
                aria-label="Carrito de compras"
                className={`${iconButtonClass} relative`}
                onClick={onClick}
            >
                <ShoppingBag size={16} />
                {cartCount > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[0.58rem] font-semibold text-slate-950">
                        {cartCount > 99 ? '99+' : cartCount}
                    </span>
                )}
                {mobile && <span className="sr-only">Carrito</span>}
            </Link>
        );
    }

    function ReservationBagIconLink({
        mobile = false,
        onClick,
    }: {
        mobile?: boolean;
        onClick?: () => void;
    }) {
        return (
            <Link
                href={shopRoutes.bag}
                aria-label="Bolsa de reservas"
                className={`${iconButtonClass} relative`}
                onClick={onClick}
            >
                <Package size={16} />
                {reservationBagCount > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[rgb(223,184,166)] px-1 text-[0.58rem] font-semibold text-slate-950">
                        {reservationBagCount > 99 ? '99+' : reservationBagCount}
                    </span>
                )}
                {mobile && <span className="sr-only">Bolsa</span>}
            </Link>
        );
    }

    return (
        <>
            <header className="fixed inset-x-0 top-0 z-50 px-4 pt-3 sm:px-6 lg:px-8">
                <motion.div
                    initial={{ opacity: 0, y: -12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }}
                    className={[
                        'mx-auto flex w-full max-w-7xl items-center justify-between rounded-full border px-3 py-2 transition-all duration-500 sm:px-5',
                        isScrolled || isOpen
                            ? 'border-[rgba(181,122,109,0.28)] bg-[#05070a]/92 shadow-[0_24px_80px_rgba(0,0,0,0.55)] backdrop-blur-2xl'
                            : 'border-white/10 bg-[#05070a]/68 backdrop-blur-xl',
                    ].join(' ')}
                >
                    <Link href="/" className="flex items-center gap-4">
                        <div className="leading-none">
                            <span className="block max-w-[11rem] truncate text-[0.76rem] font-semibold uppercase tracking-[0.22em] text-white sm:max-w-none sm:tracking-[0.3em]">
                                Mati Boutique
                            </span>
                            <div className="mt-0.5 hidden items-center gap-2 md:flex">
                                <span className="h-px w-6 bg-[linear-gradient(90deg,rgba(223,184,166,0.9),transparent)]" />
                                <span className="text-[0.6rem] uppercase tracking-[0.28em] text-stone-400">
                                    Catalogo, compras y reservas
                                </span>
                            </div>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-5 lg:flex">
                        {shopNavigationItems.map((item) => {
                            const active = currentPath === item.href;

                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={[
                                        'relative text-[0.68rem] uppercase tracking-[0.22em] transition duration-300 after:absolute after:left-0 after:top-full after:mt-1 after:h-px after:w-full after:origin-left after:transition-all',
                                        active
                                            ? 'text-white after:scale-x-100 after:bg-[linear-gradient(90deg,rgba(223,184,166,0.96),rgba(181,122,109,0.28))]'
                                            : 'text-stone-200 after:scale-x-[0.25] after:bg-[linear-gradient(90deg,rgba(223,184,166,0.82),transparent)] hover:text-white hover:after:scale-x-100',
                                    ].join(' ')}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="hidden items-center gap-2 lg:flex">
                        <Link
                            href={shopRoutes.catalog}
                            aria-label="Ver catálogo"
                            className={iconButtonClass}
                        >
                            <Search size={16} />
                        </Link>

                        <Link
                            href={user ? '/cuenta/favoritos' : '/login'}
                            aria-label="Favoritos"
                            className={iconButtonClass}
                        >
                            <Heart size={16} />
                        </Link>

                        <CartIconLink />
                        <ReservationBagIconLink />
                        {user && <NotificationBell variant="storefront" />}

                        {user ? (
                            <div ref={accountRef} className="relative ml-1">
                                <button
                                    type="button"
                                    onClick={() => setAccountOpen((value) => !value)}
                                    className="inline-flex items-center gap-2 rounded-full border border-[rgba(181,122,109,0.22)] bg-[rgba(181,122,109,0.1)] px-3.5 py-1.5 text-[0.68rem] uppercase tracking-[0.2em] text-stone-100"
                                >
                                    {user.nom_usu}
                                    <ChevronDown
                                        size={12}
                                        className={`transition-transform duration-200 ${accountOpen ? 'rotate-180' : ''}`}
                                    />
                                </button>
                                <AnimatePresence>
                                    {accountOpen && (
                                        <AccountDropdown
                                            user={user}
                                            onClose={() => setAccountOpen(false)}
                                        />
                                    )}
                                </AnimatePresence>
                            </div>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="ml-1 text-[0.68rem] uppercase tracking-[0.22em] text-stone-200 transition hover:text-white"
                                >
                                    Iniciar sesión
                                </Link>
                                <Link
                                    href="/register"
                                    className="more-cta-secondary px-3.5 py-1.5 text-[0.68rem] uppercase tracking-[0.22em]"
                                >
                                    Crear cuenta
                                </Link>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        aria-label="Abrir menú"
                        aria-expanded={isOpen}
                        onClick={() => setIsOpen((value) => !value)}
                        className="more-icon-button inline-flex h-9 w-9 items-center justify-center text-white lg:hidden"
                    >
                        {isOpen ? <X size={18} /> : <Menu size={18} />}
                    </button>
                </motion.div>
            </header>

            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ duration: 0.2 }}
                        className="fixed inset-0 z-40 px-4 pt-24 lg:hidden"
                    >
                        <div className="absolute inset-0 bg-[#05070b]/94 backdrop-blur-2xl" />

                        <motion.div
                            initial={{ opacity: 0, y: 16, scale: 0.98 }}
                            animate={{ opacity: 1, y: 0, scale: 1 }}
                            exit={{ opacity: 0, y: 10, scale: 0.98 }}
                            transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
                            className="more-panel relative mx-auto max-w-xl rounded-[1.6rem] p-6"
                        >
                            <div className="border-b border-white/10 pb-5">
                                <p className="text-sm font-semibold uppercase tracking-[0.22em] text-white">
                                    Mati Boutique
                                </p>
                                <p className="mt-2 text-[0.68rem] uppercase tracking-[0.2em] text-stone-400">
                                    Catalogo, compras y reservas
                                </p>
                            </div>

                            <div className="mt-6 space-y-3">
                                {shopNavigationItems.map((item) => (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        onClick={() => setIsOpen(false)}
                                        className={[
                                            'flex items-center justify-between rounded-[1.2rem] border px-4 py-4 text-sm uppercase tracking-[0.2em] transition duration-300',
                                            currentPath === item.href
                                                ? 'border-[rgba(181,122,109,0.28)] bg-[rgba(181,122,109,0.12)] text-white'
                                                : 'border-white/10 bg-black/20 text-white hover:border-[rgba(181,122,109,0.24)] hover:bg-[rgba(181,122,109,0.1)]',
                                        ].join(' ')}
                                    >
                                        <span>{item.label}</span>
                                        <span className="text-stone-300">/</span>
                                    </Link>
                                ))}
                            </div>

                            <div className="mt-6 flex items-center gap-2">
                                <Link
                                    href={shopRoutes.catalog}
                                    className={iconButtonClass}
                                    aria-label="Ver catálogo"
                                >
                                    <Search size={16} />
                                </Link>
                                <Link
                                    href={user ? '/cuenta/favoritos' : '/login'}
                                    className={iconButtonClass}
                                    aria-label="Favoritos"
                                    onClick={() => setIsOpen(false)}
                                >
                                    <Heart size={16} />
                                </Link>
                                <CartIconLink mobile onClick={() => setIsOpen(false)} />
                                <ReservationBagIconLink mobile onClick={() => setIsOpen(false)} />
                                {user && <NotificationBell variant="storefront" />}
                            </div>

                            <div className="mt-6 border-t border-white/10 pt-6">
                                {user ? (
                                    <div className="space-y-2">
                                        <p className="mb-3 text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                                            Hola, {user.nom_usu}
                                        </p>
                                        {[
                                            { href: '/cuenta', label: 'Mi cuenta' },
                                            { href: '/cuenta/favoritos', label: 'Favoritos' },
                                            { href: '/cuenta/reservas', label: 'Reservas' },
                                            { href: '/cuenta/pedidos', label: 'Compras' },
                                        ].map((link) => (
                                            <Link
                                                key={link.href}
                                                href={link.href}
                                                onClick={() => setIsOpen(false)}
                                                className="block rounded-[1.1rem] border border-white/10 bg-black/20 px-4 py-3 text-[0.74rem] uppercase tracking-[0.2em] text-white transition hover:border-[rgba(181,122,109,0.22)] hover:bg-[rgba(181,122,109,0.1)]"
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            onClick={() => setIsOpen(false)}
                                            className="more-cta-secondary mt-2 block w-full px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em]"
                                        >
                                            Cerrar sesión
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        <Link
                                            href="/login"
                                            onClick={() => setIsOpen(false)}
                                            className="more-cta-secondary inline-flex w-full justify-center px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em]"
                                        >
                                            Iniciar sesión
                                        </Link>
                                        <Link
                                            href="/register"
                                            onClick={() => setIsOpen(false)}
                                            className="more-cta-primary inline-flex w-full justify-center px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em]"
                                        >
                                            Crear cuenta
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
