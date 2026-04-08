import { shopRoutes } from '@/data/shop';
import type { LandingUser } from '@/types/landing';
import { Link } from '@inertiajs/react';
import { FormEvent } from 'react';

const shopLinks = [
    { label: 'Mujer', href: shopRoutes.women },
    { label: 'Hombre', href: shopRoutes.men },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Colecciones', href: shopRoutes.collections },
];

const editorialLinks = [
    { label: 'Editorial', href: shopRoutes.editorial, route: true },
    { label: 'Seleccion Mati Boutique', href: shopRoutes.collections, route: true },
    { label: 'Now Trending', href: shopRoutes.newIn, route: true },
    { label: 'Ayuda', href: '/login', route: true },
];

export default function LandingFooter({ user }: { user: LandingUser }) {
    const accessHref = user ? '/dashboard' : '/login';
    const accountHref = user ? '/profile' : '/register';
    const onSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
    };

    return (
        <footer className="border-t border-white/8 bg-[#05070b]">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid gap-10 border-b border-white/8 pb-12 lg:grid-cols-[1.15fr_0.7fr_0.7fr_0.95fr]">
                    <div className="max-w-md">
                        <p className="text-sm font-semibold uppercase tracking-[0.42em] text-white">
                            MORE
                        </p>
                        <p className="mt-3 text-[0.72rem] uppercase tracking-[0.24em] text-stone-400">
                            Mati Boutique curates the wardrobe
                        </p>
                        <p className="mt-5 max-w-sm text-sm leading-7 text-stone-300">
                            A digital fashion house shaped by refined edits,
                            quiet tailoring and boutique instinct.
                        </p>
                        <div className="mt-6 flex flex-wrap items-center gap-3 text-[0.72rem] uppercase tracking-[0.22em] text-stone-400">
                            <span>Instagram</span>
                            <span>TikTok</span>
                            <span>Pinterest</span>
                        </div>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Shop
                        </p>
                        <div className="mt-4 space-y-3">
                            {shopLinks.map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="block text-sm text-stone-400 transition hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Cuenta
                        </p>
                        <div className="mt-4 space-y-3">
                            <Link
                                href={accessHref}
                                className="block text-sm text-stone-400 transition hover:text-white"
                            >
                                {user ? 'Dashboard' : 'Iniciar sesion'}
                            </Link>
                            <Link
                                href={accountHref}
                                className="block text-sm text-stone-400 transition hover:text-white"
                            >
                                {user ? 'Mi cuenta' : 'Registrate'}
                            </Link>
                            <Link
                                href="/login"
                                className="block text-sm text-stone-400 transition hover:text-white"
                            >
                                Ayuda
                            </Link>
                            {user && (
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="block text-left text-sm text-stone-400 transition hover:text-white"
                                >
                                    Cerrar sesion
                                </Link>
                            )}
                        </div>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Newsletter
                        </p>
                        <p className="mt-4 max-w-xs text-sm leading-7 text-stone-300">
                            New drops, curated edits and quiet arrivals.
                        </p>
                        <form onSubmit={onSubmit} className="mt-5 space-y-3">
                            <input
                                type="email"
                                placeholder="Email"
                                className="w-full rounded-full border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-white placeholder:text-stone-500 focus:border-white/20 focus:outline-none focus:ring-0"
                            />
                            <button
                                type="submit"
                                className="w-full rounded-full border border-white/12 px-4 py-3 text-[0.72rem] uppercase tracking-[0.24em] text-white transition hover:border-white/20 hover:bg-white/[0.06]"
                            >
                                Suscribirme
                            </button>
                        </form>
                    </div>
                </div>

                <div className="grid gap-8 border-b border-white/8 py-8 lg:grid-cols-[0.75fr_1.25fr]">
                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-stone-400">
                            More journal
                        </p>
                        <p className="mt-3 text-sm leading-7 text-stone-300">
                            Editorial, now trending and Mati Boutique selections.
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {editorialLinks.map((link) =>
                            link.route ? (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="rounded-full border border-white/10 px-4 py-3 text-center text-[0.72rem] uppercase tracking-[0.22em] text-stone-300 transition hover:border-white/20 hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ) : (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="rounded-full border border-white/10 px-4 py-3 text-center text-[0.72rem] uppercase tracking-[0.22em] text-stone-300 transition hover:border-white/20 hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ),
                        )}
                    </div>
                </div>

                <div className="flex flex-col gap-4 pt-6 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-4">
                        <span>Atencion boutique</span>
                        <span>WhatsApp</span>
                        <span>La Paz</span>
                    </div>
                    <p>MORE x Mati Boutique</p>
                </div>
            </div>
        </footer>
    );
}
