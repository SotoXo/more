import { shopNavigationItems, shopRoutes } from '@/data/shop';
import type { LandingUser } from '@/types/landing';
import { Link } from '@inertiajs/react';
import {
    Heart,
    Menu,
    Search,
    ShoppingBag,
    User2,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';

const iconButtonClass =
    'inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-stone-100 transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.08]';

export default function LandingHeader({ user }: { user: LandingUser }) {
    const [isOpen, setIsOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

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

    const accessHref = user ? '/dashboard' : '/login';
    const accountHref = user ? '/profile' : '/login';
    const accessLabel = user ? 'Dashboard' : 'Acceder';

    return (
        <>
            <header className="fixed inset-x-0 top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
                <div
                    className={[
                        'mx-auto flex max-w-7xl items-center justify-between rounded-full border px-4 py-3 transition-all duration-500 sm:px-6',
                        isScrolled || isOpen
                            ? 'border-white/12 bg-[#070b11]/88 shadow-[0_20px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl'
                            : 'border-white/8 bg-[#070b11]/56 backdrop-blur-xl',
                    ].join(' ')}
                >
                    <Link href="/" className="flex items-center gap-4">
                        <div className="leading-none">
                            <span className="block text-sm font-semibold uppercase tracking-[0.42em] text-white">
                                MORE
                            </span>
                            <span className="mt-1 hidden text-[0.6rem] uppercase tracking-[0.28em] text-stone-400 sm:block">
                                Mati Boutique edit
                            </span>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-7 lg:flex">
                        {shopNavigationItems.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="relative text-[0.72rem] uppercase tracking-[0.24em] text-stone-300 transition hover:text-white after:absolute after:left-0 after:top-full after:mt-1 after:h-px after:w-0 after:bg-white/70 after:transition-all hover:after:w-full"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-2 lg:flex">
                        <Link
                            href={shopRoutes.newIn}
                            aria-label="Buscar"
                            className={iconButtonClass}
                        >
                            <Search size={16} />
                        </Link>
                        <Link
                            href={shopRoutes.collections}
                            aria-label="Favoritos"
                            className={iconButtonClass}
                        >
                            <Heart size={16} />
                        </Link>
                        <Link href={accountHref} aria-label="Cuenta" className={iconButtonClass}>
                            <User2 size={16} />
                        </Link>
                        <Link href={accessHref} aria-label="Bolsa" className={iconButtonClass}>
                            <ShoppingBag size={16} />
                        </Link>

                        {user ? (
                            <>
                                <span className="ml-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-[0.7rem] uppercase tracking-[0.22em] text-stone-200">
                                    {user.nombre}
                                </span>
                                <Link
                                    href={accessHref}
                                    className="rounded-full border border-white/10 px-4 py-2 text-[0.72rem] uppercase tracking-[0.24em] text-white transition duration-300 hover:-translate-y-px hover:border-white/20 hover:bg-white/[0.06]"
                                >
                                    {accessLabel}
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="ml-2 text-[0.72rem] uppercase tracking-[0.24em] text-stone-300 transition hover:text-white"
                                >
                                    Iniciar sesion
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full border border-white/14 px-4 py-2 text-[0.72rem] uppercase tracking-[0.24em] text-white transition duration-300 hover:-translate-y-px hover:border-white/24 hover:bg-white/[0.06]"
                                >
                                    Registrate
                                </Link>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        aria-label="Abrir menu"
                        aria-expanded={isOpen}
                        onClick={() => setIsOpen((value) => !value)}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-white lg:hidden"
                    >
                        {isOpen ? <X size={18} /> : <Menu size={18} />}
                    </button>
                </div>
            </header>

            <div
                className={[
                    'fixed inset-0 z-40 px-4 pt-24 transition duration-300 lg:hidden',
                    isOpen
                        ? 'pointer-events-auto opacity-100'
                        : 'pointer-events-none opacity-0',
                ].join(' ')}
            >
                <div className="absolute inset-0 bg-[#05070b]/94 backdrop-blur-2xl" />

                <div className="relative mx-auto max-w-xl rounded-[2rem] border border-white/10 bg-[#0b1016]/90 p-6 shadow-[0_30px_90px_rgba(0,0,0,0.45)]">
                    <div className="border-b border-white/10 pb-5">
                        <p className="text-sm font-semibold uppercase tracking-[0.38em] text-white">
                            MORE
                        </p>
                        <p className="mt-2 text-[0.68rem] uppercase tracking-[0.24em] text-stone-400">
                            Curated by Mati Boutique
                        </p>
                    </div>

                    <div className="mt-6 space-y-3">
                        {shopNavigationItems.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                onClick={() => setIsOpen(false)}
                                className="flex items-center justify-between rounded-[1.5rem] border border-white/8 bg-white/[0.03] px-4 py-4 text-sm uppercase tracking-[0.2em] text-white"
                            >
                                <span>{item.label}</span>
                                <span className="text-stone-500">/</span>
                            </Link>
                        ))}
                    </div>

                    <div className="mt-6 flex items-center gap-2">
                        <Link
                            href={shopRoutes.newIn}
                            className={iconButtonClass}
                            aria-label="Buscar"
                        >
                            <Search size={16} />
                        </Link>
                        <Link
                            href={shopRoutes.collections}
                            className={iconButtonClass}
                            aria-label="Favoritos"
                        >
                            <Heart size={16} />
                        </Link>
                        <Link href={accountHref} className={iconButtonClass} aria-label="Cuenta">
                            <User2 size={16} />
                        </Link>
                        <Link href={accessHref} className={iconButtonClass} aria-label="Bolsa">
                            <ShoppingBag size={16} />
                        </Link>
                    </div>

                    <div className="mt-6 border-t border-white/10 pt-6">
                        {user ? (
                            <div className="space-y-3">
                                <Link
                                    href={accessHref}
                                    onClick={() => setIsOpen(false)}
                                    className="block rounded-full bg-white px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em] text-slate-950"
                                >
                                    {accessLabel}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    onClick={() => setIsOpen(false)}
                                    className="block w-full rounded-full border border-white/12 px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em] text-white"
                                >
                                    Cerrar sesion
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                <Link
                                    href="/login"
                                    onClick={() => setIsOpen(false)}
                                    className="block rounded-full border border-white/12 px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em] text-white"
                                >
                                    Iniciar sesion
                                </Link>
                                <Link
                                    href="/register"
                                    onClick={() => setIsOpen(false)}
                                    className="block rounded-full bg-white px-5 py-3 text-center text-[0.74rem] uppercase tracking-[0.22em] text-slate-950"
                                >
                                    Registrate
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
