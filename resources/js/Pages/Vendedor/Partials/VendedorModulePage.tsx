import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3 } from 'lucide-react';

type VendedorModulePageProps = {
    title: string;
    section: string;
    description: string;
};

export default function VendedorModulePage({
    title,
    section,
    description,
}: VendedorModulePageProps) {
    return (
        <VendedorLayout title={`Mati Boutique | ${title}`}>
            <div className="space-y-6">
                <header className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2 text-sm text-[color:var(--admin-muted)]">
                        <Link href="/vendedor" className="inline-flex items-center gap-2 hover:text-[color:var(--admin-text)]">
                            <ArrowLeft size={15} />
                            Panel vendedor
                        </Link>
                        <span>/</span>
                        <span>{section}</span>
                    </div>
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold text-[color:var(--admin-accent)]">
                            {section}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                            {title}
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {description}
                        </p>
                    </div>
                </header>

                <section className="sa-panel rounded-2xl p-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="flex gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[color:var(--admin-accent-soft)] text-[color:var(--admin-accent)]">
                                <Clock3 size={20} />
                            </span>
                            <div className="max-w-3xl">
                                <h2 className="text-lg font-semibold text-[color:var(--admin-text)]">
                                    Este modulo sera implementado en la siguiente fase.
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                                    La ruta, el acceso y la estructura visual ya estan disponibles. No se ejecutan ventas, reservas, pagos, confirmaciones ni movimientos de stock desde esta pantalla.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    {[
                        'Ruta protegida por rol vendedor.',
                        'Sidebar separado del superadmin.',
                        'Sin cambios de negocio en esta fase.',
                    ].map((item) => (
                        <div key={item} className="sa-dashboard-card flex items-start gap-3 rounded-2xl">
                            <CheckCircle2 className="mt-0.5 shrink-0 text-[color:var(--admin-success)]" size={18} />
                            <p className="text-sm leading-6 text-[color:var(--admin-muted)]">{item}</p>
                        </div>
                    ))}
                </section>
            </div>
        </VendedorLayout>
    );
}
