import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import VendedorLayout from '@/Layouts/VendedorLayout';
import type { LandingPageProps } from '@/types/landing';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';
import { type FormEvent } from 'react';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';

type SaleDetail = {
    id: number;
    number: string;
    date: string;
    seller: string;
    customer: string;
    type: string;
    total: string;
    paid: string;
    balance: string;
    paymentPercent: string;
    paymentStatus: VendedorBadge;
    saleStatus: VendedorBadge;
    logisticsStatus: string;
    pickupPoint: string;
    stockOrigin: string;
    availableAt: string;
    limitAt: string;
    deliveredAt: string;
    canDeliver: boolean;
    blockReason: string | null;
    deliverAction: string;
};

type SaleItem = VendedorReadOnlyRecord & {
    sku: string;
    color: string;
    size: string;
    quantity: string;
    location: string;
};

type Payment = {
    id: number;
    date: string;
    method: string;
    declared: string;
    confirmed: string;
    status: VendedorBadge;
    verifiedBy: string;
    voucherUrl: string | null;
};

type SaleShowProps = {
    sale: SaleDetail;
    items: SaleItem[];
    payments: Payment[];
    rules: {
        paymentBlockMessage: string;
        stockNotice: string;
    };
};

const itemColumns: VendedorReadOnlyColumn<SaleItem>[] = [
    { key: 'sku', label: 'SKU', render: (row) => row.sku },
    { key: 'color', label: 'Color', render: (row) => row.color },
    { key: 'size', label: 'Talla', render: (row) => row.size },
    { key: 'quantity', label: 'Cantidad', align: 'right', render: (row) => row.quantity },
    { key: 'location', label: 'Ubicacion', render: (row) => row.location },
];

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="sa-panel-soft rounded-2xl p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-2 break-words text-sm font-medium text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

function PaymentVoucher({ url }: { url: string | null }) {
    if (!url) {
        return <span className="text-sm text-[color:var(--admin-muted)]">Sin comprobante</span>;
    }

    if (url.toLowerCase().endsWith('.pdf')) {
        return (
            <a href={url} target="_blank" rel="noreferrer" className="text-sm text-[color:var(--admin-accent)] underline-offset-4 hover:underline">
                Abrir PDF
            </a>
        );
    }

    return <img src={url} alt="Comprobante de pago" className="h-24 max-w-40 rounded-xl border border-[color:var(--admin-border)] object-contain" />;
}

function FormErrors({ errors }: { errors: Partial<Record<string, string>> }) {
    const values = Object.entries(errors).filter((entry): entry is [string, string] => Boolean(entry[1]));

    if (values.length === 0) {
        return null;
    }

    return (
        <div className="rounded-2xl border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4 text-sm leading-6 text-[rgb(245,208,210)]">
            {values.map(([field, message]) => (
                <p key={field}>{message}</p>
            ))}
        </div>
    );
}

export default function SaleShow({ sale, items, payments, rules }: SaleShowProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const form = useForm({});

    const submitDelivery = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!sale.canDeliver) {
            return;
        }

        if (!window.confirm('¿Marcar esta venta como entregada?')) {
            return;
        }

        form.post(sale.deliverAction, {
            preserveScroll: true,
        });
    };

    return (
        <VendedorLayout title={`Mati Boutique | ${sale.number}`}>
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <Link href="/vendedor/recojos" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                        <ArrowLeft size={15} />
                        Recojos
                    </Link>
                    <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                        Entrega de venta
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        {sale.number}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        {rules.stockNotice}
                    </p>
                </header>

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <section className="sa-panel rounded-2xl p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <SuperadminBadge tone={sale.saleStatus.tone}>{sale.saleStatus.label}</SuperadminBadge>
                        <SuperadminBadge tone={sale.paymentStatus.tone}>{sale.paymentStatus.label}</SuperadminBadge>
                    </div>
                    <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Tipo" value={sale.type} />
                        <Detail label="Fecha" value={sale.date} />
                        <Detail label="Cliente" value={sale.customer} />
                        <Detail label="Vendedor" value={sale.seller} />
                        <Detail label="Total" value={sale.total} />
                        <Detail label="Pagado confirmado" value={sale.paid} />
                        <Detail label="Saldo" value={sale.balance} />
                        <Detail label="Porcentaje pagado" value={sale.paymentPercent} />
                        <Detail label="Estado logistico" value={sale.logisticsStatus} />
                        <Detail label="Punto de recojo" value={sale.pickupPoint} />
                        <Detail label="Origen stock" value={sale.stockOrigin} />
                        <Detail label="Entregada" value={sale.deliveredAt} />
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <section className="space-y-3">
                            <div>
                                <p className="text-sm font-semibold text-[color:var(--admin-text)]">Productos a entregar</p>
                                <p className="mt-1 text-sm text-[color:var(--admin-muted)]">Detalle de prendas. Esta pantalla no edita productos ni stock.</p>
                            </div>
                            <VendedorReadOnlyTable
                                rows={items}
                                columns={itemColumns}
                                emptyTitle="Sin productos"
                                emptyDescription="La venta no tiene productos asociados."
                            />
                        </section>

                        <section className="sa-panel rounded-2xl p-5">
                            <p className="text-sm font-semibold text-[color:var(--admin-text)]">Pagos</p>
                            <div className="mt-4 grid gap-3">
                                {payments.length === 0 && (
                                    <div className="sa-panel-muted rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                                        Sin pagos asociados.
                                    </div>
                                )}
                                {payments.map((payment) => (
                                    <div key={payment.id} className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 md:grid-cols-5">
                                        <Detail label="Fecha" value={payment.date} />
                                        <Detail label="Método" value={payment.method} />
                                        <Detail label="Confirmado" value={payment.confirmed} />
                                        <div className="sa-panel-soft rounded-2xl p-4">
                                            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Estado</p>
                                            <div className="mt-2">
                                                <SuperadminBadge tone={payment.status.tone}>{payment.status.label}</SuperadminBadge>
                                            </div>
                                        </div>
                                        <div className="sa-panel-soft rounded-2xl p-4">
                                            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">Comprobante</p>
                                            <div className="mt-2">
                                                <PaymentVoucher url={payment.voucherUrl} />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </div>

                    <form onSubmit={submitDelivery} className="sa-panel h-fit rounded-2xl p-5">
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">Confirmar entrega</p>
                        <p className="mt-2 text-sm leading-6 text-[color:var(--admin-muted)]">
                            {sale.canDeliver ? 'La venta está pagada al 100% y lista para entrega física.' : (sale.blockReason ?? rules.paymentBlockMessage)}
                        </p>
                        <FormErrors errors={form.errors} />
                        <button
                            type="submit"
                            disabled={!sale.canDeliver || form.processing}
                            className="sa-button-primary mt-4 h-11 w-full text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <CheckCircle2 size={16} />
                            {form.processing ? 'Marcando entrega' : 'Marcar como entregada'}
                        </button>
                    </form>
                </section>
            </div>
        </VendedorLayout>
    );
}
