import { shopRoutes } from '@/lib/shop';
import type { LandingUser } from '@/types/landing';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { FormEvent } from 'react';

const shopLinks = [
    { label: 'Mujer', href: shopRoutes.women },
    { label: 'Hombre', href: shopRoutes.men },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Colecciones', href: shopRoutes.collections },
];

const editorialLinks = [
    { label: 'Editorial', href: shopRoutes.editorial },
    { label: 'Selección curada', href: shopRoutes.collections },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Guía de compra', href: shopRoutes.catalog },
];

export default function LandingFooter({ user }: { user: LandingUser }) {
    const accessHref = user ? '/cuenta' : '/login';
    const accountHref = user ? '/profile' : '/register';
    const supportHref = user ? '/cuenta/favoritos' : shopRoutes.collections;
    const supportLabel = user ? 'Favoritos' : 'Guía de compra';

    const onSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
    };

    return (
        <footer className="mt-24 border-t border-white/10 bg-[#030407]">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid gap-10 border-b border-white/8 pb-12 lg:grid-cols-[1.1fr_0.72fr_0.72fr_0.96fr]">
                    <div className="max-w-md">
                        <div className="flex items-center gap-3">
                            <p className="text-sm font-semibold uppercase tracking-[0.42em] text-white">
                                MORE
                            </p>
                            <span className="h-px w-10 bg-[linear-gradient(90deg,rgba(223,184,166,0.92),transparent)]" />
                        </div>
                        <p className="mt-3 text-[0.72rem] uppercase tracking-[0.24em] text-stone-300">
                            Firma Mati Boutique
                        </p>
                        <p className="mt-5 max-w-sm text-sm leading-7 text-stone-200/90">
                            Un guardarropa pensado con calma: sastrería suave, contraste
                            preciso y piezas que se sienten elegantes sin esfuerzo.
                        </p>
                        <div className="mt-6 flex flex-wrap items-center gap-3 text-[0.72rem] uppercase tracking-[0.22em] text-stone-300">
                            <span className="more-chip px-3 py-1.5">Instagram</span>
                            <span className="more-chip px-3 py-1.5">TikTok</span>
                            <span className="more-chip px-3 py-1.5">Pinterest</span>
                        </div>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Tienda
                        </p>
                        <div className="mt-5 space-y-3">
                            {shopLinks.map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="block text-sm text-stone-300 transition hover:text-white"
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
                        <div className="mt-5 space-y-3">
                            <Link
                                href={accessHref}
                                className="block text-sm text-stone-300 transition hover:text-white"
                            >
                                {user ? 'Mi cuenta' : 'Iniciar sesión'}
                            </Link>
                            <Link
                                href={accountHref}
                                className="block text-sm text-stone-300 transition hover:text-white"
                            >
                                {user ? 'Editar perfil' : 'Crear cuenta'}
                            </Link>
                            <Link
                                href={supportHref}
                                className="block text-sm text-stone-300 transition hover:text-white"
                            >
                                {supportLabel}
                            </Link>
                            {user && (
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="block text-left text-sm text-stone-300 transition hover:text-white"
                                >
                                    Cerrar sesión
                                </Link>
                            )}
                        </div>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Newsletter
                        </p>
                        <p className="mt-4 max-w-xs text-sm leading-7 text-stone-200/90">
                            Novedades, ediciones curadas y piezas para mirar con tiempo.
                        </p>
                        <form onSubmit={onSubmit} className="mt-5 space-y-3">
                            <input
                                type="email"
                                placeholder="Tu email"
                                className="w-full rounded-full border border-white/12 bg-black/30 px-4 py-3 text-sm text-white placeholder:text-stone-400 focus:border-[rgba(181,122,109,0.36)] focus:outline-none focus:ring-0"
                            />
                            <button
                                type="submit"
                                className="more-cta-secondary w-full px-4 py-3 text-[0.72rem] uppercase tracking-[0.24em]"
                            >
                                Suscribirme
                            </button>
                        </form>
                    </div>
                </div>

                <div className="grid gap-8 border-b border-white/8 py-8 lg:grid-cols-[0.8fr_1.2fr]">
                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-stone-300">
                            Cuaderno MORE
                        </p>
                        <p className="mt-3 text-sm leading-7 text-stone-200/90">
                            Inspiración visual, rutas de compra y una mirada más narrativa
                            sobre la boutique.
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {editorialLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="group rounded-full border border-white/12 bg-black/20 px-4 py-3 text-center text-[0.72rem] uppercase tracking-[0.22em] text-stone-200 transition hover:border-[rgba(181,122,109,0.28)] hover:bg-[rgba(181,122,109,0.1)] hover:text-white"
                            >
                                <span className="inline-flex items-center gap-2">
                                    {link.label}
                                    <ArrowRight
                                        size={13}
                                        className="opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100"
                                    />
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>

                <div className="flex flex-col gap-4 pt-6 text-sm text-stone-400 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-4">
                        <span>Atención boutique</span>
                        <span>WhatsApp</span>
                        <span>La Paz</span>
                    </div>
                    <p>MORE por Mati Boutique</p>
                </div>
            </div>
        </footer>
    );
}
