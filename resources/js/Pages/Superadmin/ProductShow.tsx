import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminMetricCard from '@/Components/Superadmin/SuperadminMetricCard';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminAction, SuperadminBreadcrumb, SuperadminMetric, SuperadminResourceMeta } from '@/types/superadmin';
import { Link, usePage } from '@inertiajs/react';

type ProductShowProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    recordLabel: string;
    product: {
        name: string;
        description: string;
        category: string;
        brand: string;
        season: string;
        gender: string;
        status: string;
        price: string;
        code: string;
        registeredBy: string;
        registeredAt: string;
    };
    metrics: SuperadminMetric[];
    gallery: Array<{
        id: number;
        url?: string | null;
        label: string;
        source: string;
        order: number;
        primary: boolean;
        variantLabel?: string | null;
        editHref: string;
    }>;
    variants: Array<{
        id: number;
        label: string;
        color: string;
        colorSwatch?: string | null;
        size: string;
        price: string;
        cost?: string | null;
        stock: number;
        reserved: number;
        location: string;
        active: boolean;
        editHref: string;
    }>;
    actions: SuperadminAction[];
};

export default function ProductShow({
    meta,
    breadcrumbs,
    recordLabel,
    product,
    metrics,
    gallery,
    variants,
    actions,
}: ProductShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;

    return (
        <SuperadminLayout title={`Mati Boutique | ${recordLabel}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={recordLabel}
                description="Detalle integral de la prenda, su galería, variantes e inventario."
                    breadcrumbs={breadcrumbs}
                    actions={actions}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric, index) => (
                        <SuperadminMetricCard key={metric.label} metric={metric} index={index} />
                    ))}
                </section>

                <section className="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div className="space-y-5">
                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Ficha base</p>
                            <div className="mt-5 grid gap-4 md:grid-cols-2">
                                <DetailCard label="Código interno" value={product.code} tone="accent" />
                                <DetailCard label="Precio base" value={product.price} />
                                <DetailCard label="Categoría" value={product.category} />
                                <DetailCard label="Marca" value={product.brand} />
                                <DetailCard label="Temporada" value={product.season} />
                                <DetailCard label="Género" value={product.gender} />
                                <DetailCard label="Estado" value={product.status} />
                                <DetailCard label="Alta" value={product.registeredAt} />
                                <DetailCard label="Registrado por" value={product.registeredBy} />
                            </div>
                            <div className="mt-4 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descripción</p>
                                <p className="mt-3 text-sm leading-7 text-stone-200">{product.description}</p>
                            </div>
                        </div>

                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Variantes e inventario</p>
                                <SuperadminBadge tone="accent">{variants.length} registros</SuperadminBadge>
                            </div>
                            <div className="mt-5 space-y-3">
                                {variants.map((variant) => (
                                    <div key={variant.id} className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
                                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm text-white">{variant.label}</p>
                                                    <SuperadminBadge tone={variant.active ? 'success' : 'neutral'}>
                                                        {variant.active ? 'Activa' : 'Inactiva'}
                                                    </SuperadminBadge>
                                                </div>
                                                <div className="mt-3 flex flex-wrap gap-3 text-sm text-stone-300">
                                                    <span className="inline-flex items-center gap-2">
                                                        {variant.colorSwatch && (
                                                            <span className="inline-flex h-3.5 w-3.5 rounded-full border border-white/15" style={{ backgroundColor: variant.colorSwatch }} />
                                                        )}
                                                        {variant.color}
                                                    </span>
                                                    <span>{variant.size}</span>
                                                    <span>{variant.location}</span>
                                                </div>
                                            </div>
                                            <Link href={variant.editHref} className="sa-button-ghost px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                                Editar variante
                                            </Link>
                                        </div>

                                        <div className="mt-4 grid gap-3 md:grid-cols-4">
                                            <DetailCard label="Precio" value={variant.price} compact />
                                            <DetailCard label="Costo" value={variant.cost ?? 'Sin costo'} compact />
                                            <DetailCard label="Disponible" value={String(variant.stock)} compact />
                                            <DetailCard label="Reservado" value={String(variant.reserved)} compact />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="space-y-5">
                        <div className="sa-panel rounded-[1.6rem] p-6">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">Galería</p>
                    <SuperadminBadge tone="neutral">{gallery.length} imágenes</SuperadminBadge>
                            </div>
                            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                {gallery.map((image) => (
                                    <div key={image.id} className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-3">
                                        <div className="overflow-hidden rounded-[1rem] border border-white/8 bg-[#090d13]">
                                            {image.url ? (
                                                <img src={image.url} alt={image.label} className="h-56 w-full object-cover" />
                                            ) : (
                                                <div className="flex h-56 items-center justify-center text-stone-500">Sin vista previa</div>
                                            )}
                                        </div>
                                        <div className="mt-3 flex flex-wrap items-center gap-2">
                                            <p className="text-sm text-white">{image.label}</p>
                                            {image.primary && <SuperadminBadge tone="accent">Principal</SuperadminBadge>}
                                            {image.variantLabel && <SuperadminBadge tone="neutral">{image.variantLabel}</SuperadminBadge>}
                                        </div>
                                        <div className="mt-3 flex items-center justify-between gap-3 text-sm text-stone-400">
                                            <span>Orden {image.order}</span>
                                            <span>{image.source}</span>
                                        </div>
                                        <Link href={image.editHref} className="sa-button-ghost mt-4 px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                            Editar imagen
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </SuperadminLayout>
    );
}

function DetailCard({
    label,
    value,
    compact = false,
    tone = 'default',
}: {
    label: string;
    value: string;
    compact?: boolean;
    tone?: 'default' | 'accent';
}) {
    return (
        <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
            <p className="text-[0.68rem] uppercase tracking-[0.16em] text-stone-400">{label}</p>
            <p className={`mt-3 ${compact ? 'text-sm' : 'text-base'} leading-7 ${tone === 'accent' ? 'text-[rgb(210,227,239)]' : 'text-white'}`}>
                {value}
            </p>
        </div>
    );
}
