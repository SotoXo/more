<?php

namespace App\Services\Superadmin;

use App\Models\DetalleVenta;
use App\Models\PrendaVariante;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\CommercialNotificationService;
use App\Services\Superadmin\Resources\AdminFormattingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly SaleNumberService $numbers,
        private readonly SaleInventoryService $inventory,
        private readonly PickupLogisticsService $pickup,
        private readonly AdminFormattingService $formatting,
        private readonly CommercialNotificationService $notifications,
    ) {
    }

    public function createPayload(array $definition, Request $request): array
    {
        return [
            'meta' => [
                'sectionKey' => $definition['sectionKey'],
                'sectionLabel' => $definition['sectionLabel'],
                'resourceKey' => $definition['resourceKey'],
                'title' => $definition['title'],
                'singular' => $definition['singular'],
                'description' => 'Registra una venta real y descuenta existencias del inventario.',
                'indexHref' => '/superadmin/comercial/ventas',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => $definition['sectionLabel']],
                ['label' => $definition['title'], 'href' => '/superadmin/comercial/ventas'],
                ['label' => 'Nueva venta'],
            ],
            'options' => [
                'customers' => $this->customerOptions(),
                'seller' => [
                    'id' => (string) $request->user()->id,
                    'name' => $this->formatting->userName($request->user()),
                ],
                'types' => [
                    ['label' => 'Fisica', 'value' => 'fisica'],
                    ['label' => 'Otro', 'value' => 'otro'],
                ],
                'paymentMethods' => [
                    ['label' => 'Efectivo', 'value' => 'efectivo'],
                    ['label' => 'QR', 'value' => 'qr'],
                    ['label' => 'Tarjeta', 'value' => 'tarjeta'],
                    ['label' => 'Transferencia', 'value' => 'transferencia'],
                    ['label' => 'Otro', 'value' => 'otro'],
                ],
                'variants' => $this->variantOptions(),
            ],
            'submitAction' => '/superadmin/comercial/ventas',
        ];
    }

    public function validate(Request $request): array
    {
        return Validator::make($request->all(), [
            'cod_usuario_cliente_ven' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'tiv_ven' => ['required', 'string', Rule::in(['fisica', 'online', 'otro'])],
            'mpa_ven' => ['required', 'string', 'max:30'],
            'des_ven' => ['nullable', 'numeric', 'min:0'],
            'sale_items' => ['required', 'array', 'min:1'],
            'sale_items.*.cod_prenda_variantes_dve' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
            'sale_items.*.can_dve' => ['required', 'integer', 'min:1'],
        ], [
            'sale_items.required' => 'Agrega al menos una variante a la venta.',
            'sale_items.*.cod_prenda_variantes_dve.required' => 'Selecciona una variante.',
            'sale_items.*.can_dve.min' => 'La cantidad debe ser mayor a cero.',
        ])->after(function ($validator) use ($request) {
            if ($request->input('tiv_ven') === 'online' && !in_array($request->input('mpa_ven'), ['qr', 'transferencia_bancaria'], true)) {
                $validator->errors()->add('mpa_ven', 'La venta online solo permite QR o transferencia bancaria.');
            }

            if ($request->input('tiv_ven') === 'online' && blank($request->input('cod_usuario_cliente_ven'))) {
                $validator->errors()->add('cod_usuario_cliente_ven', 'Selecciona un cliente para la venta online.');
            }

            if ($request->input('tiv_ven') === 'online') {
                $validator->errors()->add('tiv_ven', 'La venta online en revisión debe registrarse desde el checkout para guardar comprobante y pago.');
            }

            $items = collect($request->input('sale_items', []));
            $grouped = $items->groupBy('cod_prenda_variantes_dve');

            foreach ($grouped as $variantId => $rows) {
                if ($variantId === '' || $variantId === null) {
                    continue;
                }

                $variant = PrendaVariante::query()
                    ->with('inventario')
                    ->find((int) $variantId);
                $quantity = $rows->sum(fn ($row) => (int) ($row['can_dve'] ?? 0));

                if (!$variant?->inventario) {
                    $validator->errors()->add('sale_items', 'La variante seleccionada no tiene inventario registrado.');
                    continue;
                }

                if ((int) $variant->inventario->cdi_inv < $quantity) {
                    $validator->errors()->add('sale_items', 'No hay stock suficiente para esta variante.');
                }
            }
        })->validate();
    }

    public function createSale(Request $request, array $validated): Venta
    {
        return DB::transaction(function () use ($request, $validated) {
            $items = collect($validated['sale_items'])
                ->groupBy('cod_prenda_variantes_dve')
                ->map(fn ($rows, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => $rows->sum(fn ($row) => (int) $row['can_dve']),
                ])
                ->values();

            $variants = PrendaVariante::query()
                ->with('inventario')
                ->whereIn('id_prenda_variante', $items->pluck('variant_id'))
                ->get()
                ->keyBy('id_prenda_variante');

            $subtotal = $items->sum(function (array $item) use ($variants) {
                $variant = $variants->get($item['variant_id']);

                return ((float) ($variant?->pve_prv ?? 0)) * $item['quantity'];
            });
            $discount = min((float) ($validated['des_ven'] ?? 0), $subtotal);
            $isOnlineReview = ($validated['tiv_ven'] ?? null) === 'online';

            if ($isOnlineReview) {
                throw ValidationException::withMessages([
                    'tiv_ven' => 'La venta online en revisión debe registrarse desde el checkout para guardar comprobante y pago.',
                ]);
            }

            $logistics = $this->pickup->salePayload($variants->pluck('inventario')->filter());

            $sale = Venta::query()->create([
                'nve_ven' => $this->numbers->next(),
                'cod_usuario_cliente_ven' => filled($validated['cod_usuario_cliente_ven'] ?? null) ? (int) $validated['cod_usuario_cliente_ven'] : null,
                'cod_usuario_vendedor_ven' => (int) $request->user()->id,
                'tiv_ven' => $validated['tiv_ven'],
                'sub_ven' => $subtotal,
                'des_ven' => $discount,
                'tot_ven' => max(0, $subtotal - $discount),
                'mpa_ven' => $validated['mpa_ven'],
                'est_ven' => $isOnlineReview ? 'en_revision' : 'registrada',
                'est_logistico_ven' => 'entregada',
                'fec_disponible_recojo_ven' => $logistics['fec_disponible_recojo_ven'],
                'fec_limite_recojo_ven' => $logistics['fec_limite_recojo_ven'],
                'punto_recojo_ven' => $logistics['punto_recojo_ven'],
                'origen_stock_ven' => $logistics['origen_stock_ven'],
                'fec_entrega_ven' => Carbon::now(),
                'cod_usuario_entrega_ven' => (int) $request->user()->id,
                'fec_ven' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                if (!$variant) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'sale_items' => 'La variante seleccionada no existe.',
                    ]);
                }
                $price = (float) ($variant?->pve_prv ?? 0);

                $this->inventory->reserveForSale($variant, $item['quantity']);

                DetalleVenta::query()->create([
                    'cod_ventas_dve' => $sale->id_venta,
                    'cod_prenda_variantes_dve' => $variant->id_prenda_variante,
                    'can_dve' => $item['quantity'],
                    'pun_dve' => $price,
                    'sub_dve' => $price * $item['quantity'],
                    'fec_dve' => Carbon::now(),
                ]);
            }

            return $sale->load(['cliente', 'vendedor', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla']);
        });
    }

    public function confirmOnlineSale(Venta $sale, float $verifiedAmount, int $sellerId, ?string $observation = null): void
    {
        DB::transaction(function () use ($sale, $verifiedAmount, $sellerId, $observation) {
            $sale = Venta::query()
                ->with(['detalles.variante.inventario', 'pagos'])
                ->lockForUpdate()
                ->findOrFail($sale->id_venta);

            if ($sale->est_ven === 'registrada') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Esta venta ya fue confirmada.',
                ]);
            }

            if ($sale->est_ven !== 'en_revision') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Solo se pueden confirmar ventas en revisión.',
                ]);
            }

            $total = round((float) $sale->tot_ven, 2);
            $amount = round($verifiedAmount, 2);

            if ($amount < $total) {
                throw ValidationException::withMessages([
                    'monto_verificado_ven' => 'El monto verificado es menor al total. La venta no puede confirmarse.',
                ]);
            }

            if ($amount > $total) {
                throw ValidationException::withMessages([
                    'monto_verificado_ven' => 'El monto verificado no puede ser mayor al total.',
                ]);
            }

            $payment = $this->latestPaymentForSale($sale);

            if (($sale->tiv_ven ?? null) === 'online' && !$payment) {
                throw ValidationException::withMessages([
                    'sale_action' => 'La venta online no tiene pago ni comprobante asociado para revisar.',
                ]);
            }

            $this->inventory->confirmSeparatedSale($sale);
            $logistics = $this->pickup->saleModelPayload($sale);

            $sale->update([
                'cod_usuario_vendedor_ven' => $sellerId,
                'est_ven' => 'registrada',
                ...$logistics,
            ]);

            $payment?->update([
                'monto_verificado_vpg' => $amount,
                'est_vpg' => 'confirmado',
                'cod_usuario_verifica_vpg' => $sellerId,
                'fec_verificacion_vpg' => Carbon::now(),
                'obs_vpg' => $observation,
            ]);

            DB::afterCommit(function () use ($sale) {
                $sale->refresh();
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleConfirmed($sale);
            });
        });
    }

    public function markOnlineSaleIncomplete(Venta $sale, float $verifiedAmount, int $sellerId, ?string $observation = null): void
    {
        if (round($verifiedAmount, 2) >= round((float) $sale->tot_ven, 2)) {
            throw ValidationException::withMessages([
                'monto_verificado_ven' => 'Para marcar incompleta, el monto verificado debe ser menor al total.',
            ]);
        }

        if ($sale->est_ven !== 'en_revision') {
            throw ValidationException::withMessages([
                'sale_action' => 'Solo se pueden marcar incompletas ventas en revisión.',
            ]);
        }

        DB::transaction(function () use ($sale, $verifiedAmount, $sellerId, $observation) {
            $sale = Venta::query()->with('pagos')->lockForUpdate()->findOrFail($sale->id_venta);

            if ($sale->est_ven !== 'en_revision') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Solo se pueden marcar incompletas ventas en revision.',
                ]);
            }

            $this->inventory->releaseSeparatedSale($sale);

            $sale->update([
                'cod_usuario_vendedor_ven' => $sellerId,
                'est_ven' => 'incompleta',
                'est_logistico_ven' => 'cancelada',
            ]);

            $payment = $this->latestPaymentForSale($sale);

            if ($payment) {
                $payment->update([
                    'monto_verificado_vpg' => round($verifiedAmount, 2),
                    'est_vpg' => 'rechazado',
                    'cod_usuario_verifica_vpg' => $sellerId,
                    'fec_verificacion_vpg' => Carbon::now(),
                    'obs_vpg' => $observation ?: 'Monto verificado menor al total de la compra.',
                ]);
            }

            DB::afterCommit(function () use ($sale) {
                $sale->refresh();
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleCancelled($sale);
            });
        });
    }

    public function cancelOnlineSale(Venta $sale, int $sellerId, ?string $observation = null): void
    {
        if ($sale->est_ven === 'registrada') {
            throw ValidationException::withMessages([
                'sale_action' => 'No se puede cancelar una venta confirmada desde esta acción.',
            ]);
        }

        if ($sale->est_ven === 'cancelada') {
            throw ValidationException::withMessages([
                'sale_action' => 'La venta ya está cancelada.',
            ]);
        }

        DB::transaction(function () use ($sale, $sellerId, $observation) {
            $sale = Venta::query()->with('pagos')->lockForUpdate()->findOrFail($sale->id_venta);

            if ($sale->est_ven === 'registrada') {
                throw ValidationException::withMessages([
                    'sale_action' => 'No se puede cancelar una venta confirmada desde esta accion.',
                ]);
            }

            if ($sale->est_ven === 'cancelada') {
                throw ValidationException::withMessages([
                    'sale_action' => 'La venta ya esta cancelada.',
                ]);
            }

            if ($sale->est_ven !== 'en_revision') {
                throw ValidationException::withMessages([
                    'sale_action' => 'Solo se pueden cancelar ventas online en revision.',
                ]);
            }

            $this->inventory->releaseSeparatedSale($sale);

            $sale->update([
                'cod_usuario_vendedor_ven' => $sellerId,
                'est_ven' => 'cancelada',
                'est_logistico_ven' => 'cancelada',
            ]);

            $payment = $this->latestPaymentForSale($sale);

            if ($payment) {
                $payment->update([
                    'est_vpg' => 'rechazado',
                    'cod_usuario_verifica_vpg' => $sellerId,
                    'fec_verificacion_vpg' => Carbon::now(),
                    'obs_vpg' => $observation ?: 'Comprobante rechazado o venta cancelada.',
                ]);
            }

            DB::afterCommit(function () use ($sale) {
                $sale->refresh();
                $sale->loadMissing('cliente');
                $this->notifications->notifyCustomerSaleCancelled($sale);
            });
        });
    }

    private function latestPaymentForSale(Venta $sale): ?VentaPago
    {
        return $sale->pagos
            ? $sale->pagos->sortByDesc('id_venta_pago')->first()
            : $sale->pagos()->orderByDesc('id_venta_pago')->first();
    }

    private function customerOptions(): array
    {
        return User::query()
            ->with('rol')
            ->where(function ($query) {
                $query->whereDoesntHave('rol')
                    ->orWhereHas('rol', fn ($role) => $role->whereRaw('LOWER(nom_rol) = ?', ['cliente']));
            })
            ->orderBy('nom_usu')
            ->get()
            ->map(fn (User $user) => [
                'label' => $this->formatting->userName($user) . ' - ' . $user->email,
                'value' => (string) $user->id,
            ])
            ->values()
            ->all();
    }

    private function variantOptions(): array
    {
        return PrendaVariante::query()
            ->with(['prenda', 'color', 'talla', 'inventario'])
            ->orderBy('sku_prv')
            ->get()
            ->map(fn (PrendaVariante $variant) => [
                'label' => trim(implode(' - ', array_filter([
                    $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                    $variant->prenda?->nom_pre,
                    $variant->color?->nom_col,
                    $variant->talla?->nom_tal,
                ]))),
                'value' => (string) $variant->id_prenda_variante,
                'price' => (float) $variant->pve_prv,
                'stock' => (int) ($variant->inventario?->cdi_inv ?? 0),
                'hasInventory' => $variant->inventario !== null,
            ])
            ->values()
            ->all();
    }
}
