import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import VendedorLayout from '@/Layouts/VendedorLayout';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import VendedorReadOnlyTable, { type VendedorBadge, type VendedorReadOnlyColumn, type VendedorReadOnlyRecord } from '../Partials/VendedorReadOnlyTable';

type SaleDetail = {
    id: number;
    number: string;
    date: string;
    seller: string;
    customer: string;
    subtotal: string;
    discount: string;
    total: string;
    paymentMethod: string;
    paymentStatus: VendedorBadge;
    saleStatus: VendedorBadge;
    logisticsStatus: string;
    voucherUrl: string | null;
    paidAmount: string;
    paymentVerifiedBy: string;
};

type SaleItem = VendedorReadOnlyRecord & {
    sku: string;
    color: string;
    size: string;
    quantity: string;
    unitPrice: string;
    subtotal: string;
};

type Movement = {
    id: number;
    product: string;
    sku: string;
    quantity: string;
    availableBefore: string;
    availableAfter: string;
    committedBefore: string;
    committedAfter: string;
    description: string | null;
};

type SaleShowProps = {
    sale: SaleDetail;
    items: SaleItem[];
    movements: Movement[];
};

const itemColumns: VendedorReadOnlyColumn<SaleItem>[] = [
    { key: 'sku', label: 'SKU', render: (row) => row.sku },
    { key: 'color', label: 'Color', render: (row) => row.color },
    { key: 'size', label: 'Talla', render: (row) => row.size },
    { key: 'quantity', label: 'Cantidad', align: 'right', render: (row) => row.quantity },
    { key: 'unit', label: 'Unitario', align: 'right', render: (row) => row.unitPrice },
    { key: 'subtotal', label: 'Subtotal', align: 'right', render: (row) => row.subtotal },
];

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="sa-panel-soft rounded-2xl p-4">
            <p className="text-xs font-semibold text-[color:var(--admin-muted)]">{label}</p>
            <p className="mt-2 text-sm font-medium text-[color:var(--admin-text)]">{value}</p>
        </div>
    );
}

export default function Show({ sale, items, movements }: SaleShowProps) {
    return (
        <VendedorLayout title={`Mati Boutique | ${sale.number}`}>
            <div className="space-y-6">
                <header className="max-w-4xl">
                    <Link href="/vendedor/ventas" className="inline-flex items-center gap-2 text-sm text-[color:var(--admin-muted)] hover:text-[color:var(--admin-text)]">
                        <ArrowLeft size={15} />
                        Ventas
                    </Link>
                    <p className="mt-4 text-sm font-semibold text-[color:var(--admin-accent)]">
                        Detalle de venta
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-[color:var(--admin-text)] sm:text-3xl">
                        {sale.number}
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-[color:var(--admin-muted)]">
                        Venta directa registrada desde el panel vendedor.
                    </p>
                </header>

                <section className="sa-panel rounded-2xl p-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <SuperadminBadge tone={sale.saleStatus.tone}>{sale.saleStatus.label}</SuperadminBadge>
                        <SuperadminBadge tone={sale.paymentStatus.tone}>{sale.paymentStatus.label}</SuperadminBadge>
                    </div>
                    <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Fecha" value={sale.date} />
                        <Detail label="Vendedor" value={sale.seller} />
                        <Detail label="Cliente" value={sale.customer} />
                        <Detail label="Metodo de pago" value={sale.paymentMethod} />
                        <Detail label="Monto pagado" value={sale.paidAmount} />
                        <Detail label="Verificado por" value={sale.paymentVerifiedBy} />
                        <Detail label="Estado logistico" value={sale.logisticsStatus} />
                        <Detail label="Total" value={sale.total} />
                    </div>
                </section>

                {sale.voucherUrl && (
                    <section className="sa-panel rounded-2xl p-5">
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">Comprobante</p>
                        <div className="mt-4">
                            {sale.voucherUrl.toLowerCase().endsWith('.pdf') ? (
                                <a href={sale.voucherUrl} target="_blank" rel="noreferrer" className="text-sm text-[color:var(--admin-accent)] underline-offset-4 hover:underline">
                                    Abrir comprobante PDF
                                </a>
                            ) : (
                                <img src={sale.voucherUrl} alt="Comprobante de pago" className="max-h-96 rounded-2xl border border-[color:var(--admin-border)] object-contain" />
                            )}
                        </div>
                    </section>
                )}

                <section className="space-y-3">
                    <div>
                        <p className="text-sm font-semibold text-[color:var(--admin-text)]">Productos vendidos</p>
                        <p className="mt-1 text-sm text-[color:var(--admin-muted)]">Detalle en modo lectura. No hay edicion ni anulacion en esta fase.</p>
                    </div>
                    <VendedorReadOnlyTable
                        rows={items}
                        columns={itemColumns}
                        emptyTitle="Sin productos"
                        emptyDescription="La venta no tiene productos asociados."
                    />
                </section>

                <section className="sa-panel rounded-2xl p-5">
                    <p className="text-sm font-semibold text-[color:var(--admin-text)]">Resumen de Kardex</p>
                    <p className="mt-1 text-sm text-[color:var(--admin-muted)]">
                        Disponible corresponde a cdi_inv y comprometido a cre_inv.
                    </p>
                    <div className="mt-4 grid gap-3">
                        {movements.length === 0 && (
                            <div className="sa-panel-muted rounded-2xl p-4 text-sm text-[color:var(--admin-muted)]">
                                Sin movimientos asociados.
                            </div>
                        )}
                        {movements.map((movement) => (
                            <div key={movement.id} className="grid gap-3 rounded-2xl border border-[color:var(--admin-border)] bg-[color:var(--admin-surface-soft)] p-4 md:grid-cols-5">
                                <Detail label="Producto" value={movement.product} />
                                <Detail label="SKU" value={movement.sku} />
                                <Detail label="Cantidad" value={movement.quantity} />
                                <Detail label="cdi_inv" value={`${movement.availableBefore} -> ${movement.availableAfter}`} />
                                <Detail label="cre_inv" value={`${movement.committedBefore} -> ${movement.committedAfter}`} />
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </VendedorLayout>
    );
}
