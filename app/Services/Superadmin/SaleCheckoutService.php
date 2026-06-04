<?php

namespace App\Services\Superadmin;

use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\PrendaVariante;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\CommercialNotificationService;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleCheckoutService
{
    public function __construct(
        private readonly SaleNumberService $numbers,
        private readonly CommercialNotificationService $notifications,
        private readonly SystemLogService $logs,
    )
    {
    }

    public function validateOnlineCheckout(Request $request): array
    {
        return Validator::make($request->all(), [
            'sale_items' => ['required', 'array', 'min:1'],
            'sale_items.*.variante_id' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
            'sale_items.*.cantidad' => ['required', 'integer', 'min:1'],
            'metodo_pago' => ['required', 'string', Rule::in(['qr', 'transferencia_bancaria'])],
            'monto_declarado' => ['required', 'numeric', 'min:0.01'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'sale_items.required' => 'Agrega al menos una prenda al carrito.',
            'sale_items.*.variante_id.required' => 'Selecciona una variante para comprar.',
            'sale_items.*.cantidad.required' => 'Ingresa la cantidad.',
            'sale_items.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'metodo_pago.required' => 'Selecciona el metodo de pago.',
            'metodo_pago.in' => 'La compra online solo permite QR o transferencia bancaria.',
            'monto_declarado.required' => 'Ingresa el monto pagado.',
            'monto_declarado.min' => 'Para completar la compra debes pagar el 100% del total.',
            'comprobante.required' => 'Sube el comprobante de pago.',
            'comprobante.mimes' => 'El comprobante debe ser una imagen JPG, PNG o WEBP.',
            'comprobante.max' => 'El comprobante no debe superar 4MB.',
        ])->validate();
    }

    public function createOnlineCheckout(Request $request, User $customer, array $validated): Venta
    {
        return DB::transaction(function () use ($request, $customer, $validated) {
            $items = collect($validated['sale_items'])
                ->groupBy('variante_id')
                ->map(fn ($rows, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => $rows->sum(fn ($row) => (int) ($row['cantidad'] ?? 0)),
                ])
                ->values();

            $variants = PrendaVariante::query()
                ->with('prenda')
                ->where('est_prv', true)
                ->whereHas('prenda', fn ($query) => $query->where('est_pre', true))
                ->whereIn('id_prenda_variante', $items->pluck('variant_id'))
                ->get()
                ->keyBy('id_prenda_variante');

            if ($variants->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'sale_items' => 'Una prenda del carrito ya no esta disponible.',
                ]);
            }

            $inventoryRows = Inventario::query()
                ->whereIn('cod_prenda_variantes_inv', $items->pluck('variant_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('cod_prenda_variantes_inv');

            $subtotal = 0;

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $inventory = $inventoryRows->get($item['variant_id']);

                if (!$inventory) {
                    throw ValidationException::withMessages([
                        'sale_items' => 'Una prenda del carrito no tiene inventario registrado.',
                    ]);
                }

                if ((int) $inventory->cdi_inv < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'sale_items' => 'No hay stock suficiente para una prenda del carrito.',
                    ]);
                }

                $subtotal += round((float) $variant->pve_prv, 2) * $item['quantity'];
            }

            $subtotal = round($subtotal, 2);
            $total = $subtotal;
            $declared = round((float) $validated['monto_declarado'], 2);

            if ($declared < $total) {
                throw ValidationException::withMessages([
                    'monto_declarado' => 'El monto pagado no puede ser menor al total de la compra.',
                ]);
            }

            if ($declared > $total) {
                throw ValidationException::withMessages([
                    'monto_declarado' => 'El monto pagado no puede ser mayor al total de la compra.',
                ]);
            }

            try {
                $path = $request->file('comprobante')->store('ventas/comprobantes', 'public');
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'comprobante' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
                ]);
            }

            if (!$path) {
                throw ValidationException::withMessages([
                    'comprobante' => 'No se pudo guardar el comprobante de pago. Intenta subir la imagen nuevamente.',
                ]);
            }

            $sale = Venta::query()->create([
                'nve_ven' => $this->numbers->next(),
                'cod_usuario_cliente_ven' => $customer->id,
                'cod_usuario_vendedor_ven' => null,
                'tiv_ven' => 'online',
                'sub_ven' => $subtotal,
                'des_ven' => 0,
                'tot_ven' => $total,
                'mpa_ven' => $validated['metodo_pago'],
                'est_ven' => 'en_revision',
                'est_logistico_ven' => 'pendiente_revision',
                'fec_ven' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $unitPrice = round((float) $variant->pve_prv, 2);
                $inventory = $inventoryRows->get($item['variant_id']);

                if ((int) $inventory->cdi_inv < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'sale_items' => 'No hay stock disponible suficiente.',
                    ]);
                }

                $inventory->update([
                    'cdi_inv' => ((int) $inventory->cdi_inv) - $item['quantity'],
                    'cre_inv' => ((int) $inventory->cre_inv) + $item['quantity'],
                ]);

                DetalleVenta::query()->create([
                    'cod_ventas_dve' => $sale->id_venta,
                    'cod_prenda_variantes_dve' => $variant->id_prenda_variante,
                    'can_dve' => $item['quantity'],
                    'pun_dve' => $unitPrice,
                    'sub_dve' => round($unitPrice * $item['quantity'], 2),
                    'fec_dve' => Carbon::now(),
                ]);
            }

            $payment = VentaPago::query()->create([
                'cod_ventas_vpg' => $sale->id_venta,
                'monto_declarado_vpg' => $declared,
                'monto_verificado_vpg' => 0,
                'metodo_pago_vpg' => $validated['metodo_pago'],
                'comprobante_ruta_vpg' => $path,
                'comprobante_url_vpg' => Storage::disk('public')->url($path),
                'est_vpg' => 'pendiente_revision',
                'fec_vpg' => Carbon::now(),
            ]);

            $this->logs->info(
                'ventas',
                'compra_online_enviada_revision',
                'Compra online enviada a revision: ' . $sale->nve_ven . '.',
                $sale,
                [
                    'venta_id' => $sale->id_venta,
                    'numero_venta' => $sale->nve_ven,
                    'cliente_id' => $sale->cod_usuario_cliente_ven,
                    'total' => (float) $sale->tot_ven,
                    'estado' => $sale->est_ven,
                    'items' => $items->count(),
                ],
            );

            $this->logs->info(
                'pagos',
                'comprobante_subido',
                'Comprobante subido para compra online: ' . $sale->nve_ven . '.',
                $payment,
                [
                    'venta_id' => $sale->id_venta,
                    'numero_venta' => $sale->nve_ven,
                    'monto_declarado' => $declared,
                    'metodo_pago' => $validated['metodo_pago'],
                    'estado' => $payment->est_vpg,
                ],
            );

            DB::afterCommit(function () use ($sale) {
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleCreated($sale);
                $this->notifications->notifyAdminsSalePending($sale);
            });

            return $sale->load(['cliente', 'detalles.variante.prenda', 'pagos']);
        });
    }
}
