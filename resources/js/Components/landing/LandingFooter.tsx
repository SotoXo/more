import { shopRoutes } from '@/lib/shop';
import type { LandingUser } from '@/types/landing';
import { Link } from '@inertiajs/react';

const shopLinks = [
    { label: 'Catálogo', href: shopRoutes.catalog },
    { label: 'Novedades', href: shopRoutes.newIn },
    { label: 'Mujer', href: shopRoutes.women },
    { label: 'Hombre', href: shopRoutes.men },
    { label: 'Accesorios', href: shopRoutes.accessories },
];

const brandLinks = [
    { label: 'Colecciones por categoría', href: shopRoutes.collections },
    { label: 'Editorial', href: shopRoutes.editorial },
    { label: 'Disponibilidad', href: shopRoutes.catalog },
];

export default function LandingFooter({ user }: { user: LandingUser }) {
    const accountLinks = user
        ? [
            { label: 'Mi cuenta', href: '/cuenta' },
            { label: 'Favoritos', href: '/cuenta/favoritos' },
            { label: 'Reservas', href: '/cuenta/reservas' },
        ]
        : [
            { label: 'Iniciar sesión', href: '/login' },
            { label: 'Crear cuenta', href: '/register' },
        ];

    return (
        <footer className="border-t border-white/10 bg-[#030407]">
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 lg:grid-cols-[1.12fr_0.62fr_0.62fr_0.84fr]">
                    <div className="max-w-md">
                        <Link href="/" className="inline-flex items-end gap-3">
                            <span className="text-base font-semibold uppercase tracking-[0.24em] text-white">
                                Mati Boutique
                            </span>
                            <span className="mb-1 h-px w-12 bg-[linear-gradient(90deg,rgba(223,184,166,0.92),transparent)]" />
                        </Link>
                        <p className="mt-3 text-[0.7rem] uppercase tracking-[0.26em] text-stone-400">
                            Catálogo y reservas
                        </p>
                        <p className="mt-6 max-w-sm text-sm leading-7 text-stone-300">
                            Moda y accesorios seleccionados para revisar disponibilidad,
                            elegir talla y reservar con atención personalizada.
                        </p>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Tienda
                        </p>
                        <nav className="mt-5 space-y-3">
                            {shopLinks.map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="block text-sm text-stone-300 transition hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Marca
                        </p>
                        <nav className="mt-5 space-y-3">
                            {brandLinks.map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="block text-sm text-stone-300 transition hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    <div>
                        <p className="text-[0.72rem] uppercase tracking-[0.26em] text-white">
                            Cuenta
                        </p>
                        <nav className="mt-5 space-y-3">
                            {accountLinks.map((link) => (
                                <Link
                                    key={link.label}
                                    href={link.href}
                                    className="block text-sm text-stone-300 transition hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ))}
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
                        </nav>

                        <p className="mt-8 text-xs uppercase tracking-[0.18em] text-stone-400">Boletín · Próximamente</p>
                    </div>
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>Mati Boutique</p>
                    <div className="flex flex-wrap items-center gap-4">
                        <span>Atención boutique</span>
                        <span>La Paz</span>
                    </div>
                </div>
            </div>
        </footer>
    );
}
