import { Head, Link } from '@inertiajs/react';

export default function NotFound() {
    return <main className="flex min-h-screen items-center justify-center bg-[#080b10] px-6 py-16 text-white">
        <Head title="Página no encontrada | Mati Boutique" />
        <section className="w-full max-w-xl text-center">
            <p className="text-xs uppercase tracking-[0.3em] text-stone-400">Mati Boutique</p>
            <p className="mt-10 font-serif text-8xl text-[#dfb8a6]">404</p>
            <h1 className="mt-6 font-serif text-4xl">Página no encontrada</h1>
            <p className="mt-5 leading-7 text-stone-300">La página que buscas no está disponible. Puedes volver al inicio o descubrir nuestro catálogo.</p>
            <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link href="/" className="rounded-full border border-white/30 px-6 py-3 focus-visible:outline">Volver al inicio</Link>
                <Link href="/catalogo" className="rounded-full bg-[#dfb8a6] px-6 py-3 text-slate-950 focus-visible:outline">Ver catálogo</Link>
            </div>
        </section>
    </main>;
}
