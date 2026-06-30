<?php

namespace App\Services\Vendedor;

use App\Models\DetalleVenta;
use App\Models\InventarioMovimiento;
use App\Models\PrendaVariante;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Services\Superadmin\PickupLogisticsService;
use App\Services\Superadmin\SaleInventoryService;
use App\Services\Superadmin\SaleNumberService;
use App\Services\SystemLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendedorSalesService
{
    private const PAYMENT_METHODS = ['efectivo', 'qr', 'transferencia_bancaria'];

    public function __construct(
        private readonly SaleNumberService $numbers,
        private readonly SaleInventoryService $inventory,
        private readonly PickupLogisticsService $pickup,
        private readonly SystemLogService $logs,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $sales = $this->visibleSalesQuery($request)
            ->orderByDesc('fec_ven')
            ->orderByDesc('id_venta')
            ->get()
            ->filter(fn (Venta $sale) => $this->saleMatchesFilters($sale, $request))
            ->values();

        return [
            'sales' => $sales->map(fn (Venta $sale) => $this->mapSaleRow($sale))->all(),
            'summary' => [
                ['label' => 'Ventas', 'value' => $this->number($sales->count()), 'tone' => 'accent'],
                ['label' => 'Total vendido', 'value' => $this->money((float) $sales->sum('tot_ven')), 'tone' => 'success'],
                ['label' => 'Pagos confirmados', 'value' => $this->number($sales->filter(fn (Venta $sale) => $this->latestPayment($sale)?->est_vpg === 'confirmado')->count()), 'tone' => 'success'],
                ['label' => 'Alcance', 'value' => $this->isSupervising($request) ? 'Global' : 'Mis ventas', 'tone' => 'info'],
            ],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'payment_method' => $request->string('payment_method')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'options' => [
                'paymentMethods' => $this->paymentMethodOptions(),
                'statuses' => [
                    ['value' => 'registrada', 'label' => 'Registrada'],
                    ['value' => 'en_revision', 'label' => 'En revision'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ],
            ],
            'scopeLabel' => $this->isSupervising($request)
                ? 'Supervision: todas las ventas directas visibles.'
                : 'Mostrando ventas registradas por tu usuario.',
        ];
    }

    public function createPayload(Request $request): array
    {
        return [
            'options' => [
                'seller' => [
                    'id' => (string) $request->user()->id,
                    'name' => $this->userName($request->user()),
                ],
                'customers' => $this->customerOptions(),
                'paymentMethods' => $this->paymentMethodOptions(),
                'variants' => $this->variantOptions(),
            ],
            'submitAction' => '/vendedor/ventas',
        ];
    }

    public function store(Request $request): Venta
    {
        $validated = $this->validateSale($request);
        $method = $this->normalizePaymentMethod($validated['payment_method']);
        $voucherPath = null;
        $voucherUrl = null;

        if ($request->hasFile('voucher')) {
            $voucherPath = $request->file('voucher')->store('ventas/comprobantes', 'public');
            $voucherUrl = Storage::disk('public')->url($voucherPath);
        }

        try {
            return DB::transaction(function () use ($request, $validated, $method, $voucherPath, $voucherUrl) {
                $items = collect($validated['sale_items'])
                    ->groupBy('variant_id')
                    ->map(fn ($rows, $variantId) => [
                        'variant_id' => (int) $variantId,
                        'quantity' => $rows->sum(fn ($row) => (int) ($row['quantity'] ?? 0)),
                    ])
                    ->values();

                $variants = PrendaVariante::query()
                    ->with(['inventario', 'prenda.imagenes', 'color', 'talla'])
                    ->whereIn('id_prenda_variante', $items->pluck('variant_id'))
                    ->get()
                    ->keyBy('id_prenda_variante');

                $this->ensureStockInsideTransaction($items, $variants);

                $subtotal = $items->sum(function (array $item) use ($variants) {
                    $variant = $variants->get($item['variant_id']);

                    return ((float) ($variant?->pve_prv ?? 0)) * $item['quantity'];
                });
                $total = round((float) $subtotal, 2);
                $paid = round((float) $validated['amount_paid'], 2);

                if (! $this->amountsMatch($paid, $total)) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'El monto pagado debe coincidir con el total de la venta.',
                    ]);
                }

                $logistics = $this->pickup->salePayload($variants->pluck('inventario')->filter());

                $sale = Venta::query()->create([
                    'nve_ven' => $this->numbers->next(),
                    'cod_usuario_cliente_ven' => filled($validated['customer_id'] ?? null) ? (int) $validated['customer_id'] : null,
                    'cod_usuario_vendedor_ven' => (int) $request->user()->id,
                    'tiv_ven' => 'fisica',
                    'sub_ven' => $total,
                    'des_ven' => 0,
                    'tot_ven' => $total,
                    'mpa_ven' => $method,
                    'est_ven' => 'registrada',
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
                    $price = (float) ($variant?->pve_prv ?? 0);

                    $inventory = $this->inventory->reserveForSale(
                        $variant,
                        $item['quantity'],
                        'venta',
                        (int) $sale->id_venta,
                        'Venta directa registrada por vendedor.',
                        [
                            'venta_id' => $sale->id_venta,
                            'numero_venta' => $sale->nve_ven,
                            'origen' => 'panel_vendedor',
                        ],
                    );

                    DetalleVenta::query()->create([
                        'cod_ventas_dve' => $sale->id_venta,
                        'cod_prenda_variantes_dve' => $variant->id_prenda_variante,
                        'can_dve' => $item['quantity'],
                        'pun_dve' => $price,
                        'sub_dve' => $price * $item['quantity'],
                        'fec_dve' => Carbon::now(),
                    ]);

                    $this->logs->info(
                        'inventario',
                        'stock_descontado_por_venta',
                        'Stock descontado por venta directa del vendedor: ' . $sale->nve_ven . '.',
                        $inventory,
                        [
                            'venta_id' => $sale->id_venta,
                            'numero_venta' => $sale->nve_ven,
                            'variante_id' => $variant->id_prenda_variante,
                            'cantidad' => $item['quantity'],
                            'cdi_inv' => (int) $inventory->cdi_inv,
                            'cre_inv' => (int) $inventory->cre_inv,
                        ],
                    );
                }

                $payment = VentaPago::query()->create([
                    'cod_ventas_vpg' => $sale->id_venta,
                    'monto_declarado_vpg' => $paid,
                    'monto_verificado_vpg' => $paid,
                    'metodo_pago_vpg' => $method,
                    'comprobante_ruta_vpg' => $voucherPath,
                    'comprobante_url_vpg' => $voucherUrl,
                    'est_vpg' => 'confirmado',
                    'cod_usuario_verifica_vpg' => (int) $request->user()->id,
                    'fec_verificacion_vpg' => Carbon::now(),
                    'obs_vpg' => 'Pago registrado por vendedor y confirmado automaticamente.',
                    'fec_vpg' => Carbon::now(),
                ]);

                $this->logs->info(
                    'ventas',
                    'venta_vendedor_creada',
                    'Venta directa registrada por vendedor: ' . $sale->nve_ven . '.',
                    $sale,
                    $this->saleMetadata($sale) + [
                        'items' => $items->count(),
                    ],
                );

                $this->logs->info(
                    'pagos',
                    'pago_vendedor_confirmado',
                    'Pago registrado por vendedor y confirmado: ' . $sale->nve_ven . '.',
                    $payment,
                    [
                        'venta_id' => $sale->id_venta,
                        'numero_venta' => $sale->nve_ven,
                        'metodo_pago' => $method,
                        'monto' => $paid,
                        'estado' => 'confirmado',
                    ],
                );

                return $sale->load([
                    'cliente',
                    'vendedor',
                    'pagos.usuarioVerifica',
                    'detalles.variante.prenda.imagenes',
                    'detalles.variante.color',
                    'detalles.variante.talla',
                ]);
            });
        } catch (\Throwable $exception) {
            if ($voucherPath) {
                Storage::disk('public')->delete($voucherPath);
            }

            throw $exception;
        }
    }

    public function showPayload(Request $request, Venta $sale): array
    {
        $this->authorizeSaleVisibility($request, $sale);

        $sale->loadMissing([
            'cliente',
            'vendedor',
            'pagos.usuarioVerifica',
            'detalles.variante.prenda.imagenes',
            'detalles.variante.color',
            'detalles.variante.talla',
        ]);

        $payment = $this->latestPayment($sale);
        $movements = InventarioMovimiento::query()
            ->with(['variante.prenda'])
            ->where('referencia_tipo_inm', 'venta')
            ->where('referencia_id_inm', $sale->id_venta)
            ->orderBy('id')
            ->get();

        return [
            'sale' => [
                'id' => (int) $sale->id_venta,
                'number' => $sale->nve_ven,
                'date' => $this->dateTime($sale->fec_ven),
                'seller' => $this->userName($sale->vendedor),
                'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente',
                'subtotal' => $this->money((float) $sale->sub_ven),
                'discount' => $this->money((float) $sale->des_ven),
                'total' => $this->money((float) $sale->tot_ven),
                'paymentMethod' => $this->paymentLabel($sale->mpa_ven),
                'paymentStatus' => $this->paymentStatusBadge($payment?->est_vpg),
                'saleStatus' => $this->saleStatusBadge($sale->est_ven),
                'logisticsStatus' => $this->display($sale->est_logistico_ven, 'Sin estado'),
                'voucherUrl' => $payment?->comprobante_url_vpg,
                'paidAmount' => $this->money((float) ($payment?->monto_verificado_vpg ?? 0)),
                'paymentVerifiedBy' => $payment?->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            ],
            'items' => $sale->detalles->map(fn (DetalleVenta $detail) => $this->mapDetailRow($detail))->values()->all(),
            'movements' => $movements->map(fn (InventarioMovimiento $movement) => [
                'id' => (int) $movement->id,
                'product' => $movement->variante?->prenda?->nom_pre ?? 'Prenda sin nombre',
                'sku' => $movement->variante?->sku_prv ?? 'Sin SKU',
                'quantity' => $this->number((int) $movement->cantidad_inm),
                'availableBefore' => $this->number((int) $movement->cdi_antes_inm),
                'availableAfter' => $this->number((int) $movement->cdi_despues_inm),
                'committedBefore' => $this->number((int) $movement->cre_antes_inm),
                'committedAfter' => $this->number((int) $movement->cre_despues_inm),
                'description' => $movement->descripcion_inm,
            ])->all(),
        ];
    }

    public function dashboardMetrics(Request $request): array
    {
        $query = $this->visibleSalesQuery($request)
            ->whereDate('fec_ven', Carbon::today());

        $salesToday = (int) (clone $query)->count();
        $soldToday = (float) (clone $query)->sum('tot_ven');

        return [
            'sales_today' => [
                'value' => $this->number($salesToday),
                'note' => $this->isSupervising($request)
                    ? 'Ventas directas registradas hoy en supervision global.'
                    : 'Ventas directas registradas hoy por tu usuario.',
            ],
            'sold_today' => [
                'value' => $this->money($soldToday),
                'note' => 'Total confirmado automaticamente por ventas directas de hoy.',
            ],
        ];
    }

    private function validateSale(Request $request): array
    {
        $method = $this->normalizePaymentMethod((string) $request->input('payment_method'));
        $request->merge(['payment_method' => $method]);

        return Validator::make($request->all(), [
            'customer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'voucher' => [Rule::requiredIf(in_array($method, ['qr', 'transferencia_bancaria'], true)), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'sale_items' => ['required', 'array', 'min:1'],
            'sale_items.*.variant_id' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
            'sale_items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'sale_items.required' => 'Agrega al menos un producto a la venta.',
            'sale_items.min' => 'Agrega al menos un producto a la venta.',
            'sale_items.*.quantity.min' => 'La cantidad debe ser mayor a cero.',
            'voucher.required' => 'Sube el comprobante para pagos QR o transferencia.',
        ])->after(function ($validator) use ($request) {
            $items = collect($request->input('sale_items', []))
                ->groupBy('variant_id')
                ->map(fn ($rows) => $rows->sum(fn ($row) => (int) ($row['quantity'] ?? 0)));

            $variants = PrendaVariante::query()
                ->with(['inventario', 'prenda'])
                ->whereIn('id_prenda_variante', $items->keys())
                ->get()
                ->keyBy('id_prenda_variante');

            $total = 0.0;

            foreach ($items as $variantId => $quantity) {
                $variant = $variants->get((int) $variantId);

                if (! $variant) {
                    $validator->errors()->add('sale_items', 'Una variante seleccionada no existe.');
                    continue;
                }

                if (! $variant->est_prv || ! $variant->prenda?->est_pre) {
                    $validator->errors()->add('sale_items', 'La variante seleccionada no esta activa para venta.');
                    continue;
                }

                if (! $variant->inventario) {
                    $validator->errors()->add('sale_items', 'La variante seleccionada no tiene inventario registrado.');
                    continue;
                }

                if ((int) $variant->inventario->cdi_inv < (int) $quantity) {
                    $validator->errors()->add('sale_items', 'No hay stock suficiente para ' . ($variant->sku_prv ?: $variant->prenda?->nom_pre ?: 'una variante') . '.');
                    continue;
                }

                $total += ((float) $variant->pve_prv) * (int) $quantity;
            }

            $paid = round((float) $request->input('amount_paid', 0), 2);
            $total = round($total, 2);

            if ($total > 0 && ! $this->amountsMatch($paid, $total)) {
                $validator->errors()->add('amount_paid', 'El monto pagado debe coincidir con el total de la venta.');
            }
        })->validate();
    }

    private function ensureStockInsideTransaction($items, $variants): void
    {
        foreach ($items as $item) {
            $variant = $variants->get($item['variant_id']);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'sale_items' => 'La variante seleccionada no existe.',
                ]);
            }

            if (! $variant->inventario || (int) $variant->inventario->cdi_inv < (int) $item['quantity']) {
                throw ValidationException::withMessages([
                    'sale_items' => 'No hay stock suficiente para ' . ($variant->sku_prv ?: $variant->prenda?->nom_pre ?: 'una variante') . '.',
                ]);
            }
        }
    }

    private function visibleSalesQuery(Request $request): Builder
    {
        $query = Venta::query()
            ->with(['cliente', 'vendedor', 'pagos.usuarioVerifica'])
            ->where('tiv_ven', 'fisica');

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_vendedor_ven', (int) $request->user()->id);
        }

        return $query;
    }

    private function isSupervising(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isSuperadmin') && $user->isSuperadmin();
    }

    private function authorizeSaleVisibility(Request $request, Venta $sale): void
    {
        abort_unless(
            $this->isSupervising($request) || (int) $sale->cod_usuario_vendedor_ven === (int) $request->user()->id,
            403,
        );
    }

    private function saleMatchesFilters(Venta $sale, Request $request): bool
    {
        if (filled($request->input('payment_method')) && $this->normalizePaymentMethod((string) $request->input('payment_method')) !== (string) $sale->mpa_ven) {
            return false;
        }

        if (filled($request->input('status')) && (string) $request->input('status') !== (string) $sale->est_ven) {
            return false;
        }

        if (filled($request->input('date_from')) && $sale->fec_ven?->lt(Carbon::parse($request->input('date_from'))->startOfDay())) {
            return false;
        }

        if (filled($request->input('date_to')) && $sale->fec_ven?->gt(Carbon::parse($request->input('date_to'))->endOfDay())) {
            return false;
        }

        $search = mb_strtolower(trim($request->string('search')->toString()), 'UTF-8');

        if ($search === '') {
            return true;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $sale->nve_ven,
            $this->userName($sale->cliente),
            $this->userName($sale->vendedor),
        ]))), 'UTF-8');

        return str_contains($haystack, $search);
    }

    private function mapSaleRow(Venta $sale): array
    {
        $payment = $this->latestPayment($sale);

        return [
            'id' => (int) $sale->id_venta,
            'primary' => $sale->nve_ven,
            'secondary' => $this->dateTime($sale->fec_ven),
            'number' => $sale->nve_ven,
            'date' => $this->dateTime($sale->fec_ven),
            'customer' => $sale->cliente ? $this->userName($sale->cliente) : 'Venta sin cliente',
            'seller' => $this->userName($sale->vendedor),
            'total' => $this->money((float) $sale->tot_ven),
            'paymentMethod' => $this->paymentLabel($sale->mpa_ven),
            'paymentStatus' => $this->paymentStatusBadge($payment?->est_vpg),
            'saleStatus' => $this->saleStatusBadge($sale->est_ven),
            'showHref' => '/vendedor/ventas/' . $sale->id_venta,
        ];
    }

    private function mapDetailRow(DetalleVenta $detail): array
    {
        $variant = $detail->variante;
        $product = $variant?->prenda;

        return [
            'id' => (int) $detail->id_detalle_venta,
            'primary' => $product?->nom_pre ?? 'Prenda sin nombre',
            'secondary' => $variant?->sku_prv ?: 'Sin SKU',
            'image' => $product ? $this->primaryImage($product) : null,
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?? 'Sin color',
            'size' => $variant?->talla?->nom_tal ?? 'Sin talla',
            'quantity' => $this->number((int) $detail->can_dve),
            'unitPrice' => $this->money((float) $detail->pun_dve),
            'subtotal' => $this->money((float) $detail->sub_dve),
        ];
    }

    private function variantOptions(): array
    {
        return PrendaVariante::query()
            ->with(['prenda.imagenes', 'color', 'talla', 'inventario'])
            ->where('est_prv', true)
            ->whereHas('prenda', fn (Builder $query) => $query->where('est_pre', true))
            ->orderBy('sku_prv')
            ->get()
            ->map(fn (PrendaVariante $variant) => [
                'id' => (string) $variant->id_prenda_variante,
                'label' => trim(implode(' - ', array_filter([
                    $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                    $variant->prenda?->nom_pre,
                    $variant->color?->nom_col,
                    $variant->talla?->nom_tal,
                ]))),
                'productName' => $variant->prenda?->nom_pre ?? 'Prenda sin nombre',
                'sku' => $variant->sku_prv ?: 'Sin SKU',
                'color' => $variant->color?->nom_col ?? 'Sin color',
                'size' => $variant->talla?->nom_tal ?? 'Sin talla',
                'price' => (float) $variant->pve_prv,
                'stock' => (int) ($variant->inventario?->cdi_inv ?? 0),
                'committed' => (int) ($variant->inventario?->cre_inv ?? 0),
                'location' => $variant->inventario?->ubi_inv ?: 'Sin ubicacion',
                'image' => $variant->prenda ? $this->primaryImage($variant->prenda) : null,
                'hasInventory' => $variant->inventario !== null,
            ])
            ->values()
            ->all();
    }

    private function customerOptions(): array
    {
        return User::query()
            ->with('rol')
            ->where('est_usu', true)
            ->whereHas('rol', fn (Builder $query) => $query->whereRaw('LOWER(nom_rol) = ?', ['cliente']))
            ->orderBy('nom_usu')
            ->get()
            ->map(fn (User $user) => [
                'label' => $this->userName($user) . ' - ' . $user->email,
                'value' => (string) $user->id,
            ])
            ->values()
            ->all();
    }

    private function latestPayment(Venta $sale): ?VentaPago
    {
        return $sale->pagos
            ? $sale->pagos->sortByDesc('id_venta_pago')->first()
            : $sale->pagos()->orderByDesc('id_venta_pago')->first();
    }

    private function saleMetadata(Venta $sale): array
    {
        return [
            'venta_id' => $sale->id_venta,
            'numero_venta' => $sale->nve_ven,
            'vendedor_id' => $sale->cod_usuario_vendedor_ven,
            'cliente_id' => $sale->cod_usuario_cliente_ven,
            'total' => (float) $sale->tot_ven,
            'metodo_pago' => $sale->mpa_ven,
            'estado' => $sale->est_ven,
            'origen' => 'panel_vendedor',
        ];
    }

    private function paymentMethodOptions(): array
    {
        return [
            ['value' => 'efectivo', 'label' => 'Efectivo'],
            ['value' => 'qr', 'label' => 'QR'],
            ['value' => 'transferencia_bancaria', 'label' => 'Transferencia bancaria'],
        ];
    }

    private function normalizePaymentMethod(string $method): string
    {
        return $method === 'transferencia' ? 'transferencia_bancaria' : $method;
    }

    private function amountsMatch(float $left, float $right): bool
    {
        return abs(round($left, 2) - round($right, 2)) < 0.01;
    }

    private function paymentLabel(?string $method): string
    {
        return match ($this->normalizePaymentMethod((string) $method)) {
            'efectivo' => 'Efectivo',
            'qr' => 'QR',
            'transferencia_bancaria' => 'Transferencia bancaria',
            default => $this->display($method, 'Sin metodo'),
        };
    }

    private function paymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Confirmado', 'tone' => 'success'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            'pendiente_revision' => ['label' => 'Pendiente revision', 'tone' => 'warning'],
            default => ['label' => 'Sin pago', 'tone' => 'neutral'],
        };
    }

    private function saleStatusBadge(?string $status): array
    {
        return match ($status) {
            'registrada' => ['label' => 'Registrada', 'tone' => 'success'],
            'en_revision' => ['label' => 'En revision', 'tone' => 'warning'],
            'cancelada', 'incompleta' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function primaryImage($product): ?string
    {
        $image = collect($product->imagenes ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
            ])
            ->first();

        return $this->imageUrl($image?->url_igp, $image?->rut_igp);
    }

    private function imageUrl(?string $url, ?string $path): ?string
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return $url;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function userName(mixed $user): string
    {
        if (! $user) {
            return 'Sin usuario';
        }

        if (method_exists($user, 'nombreCompleto')) {
            return $user->nombreCompleto();
        }

        return trim(implode(' ', array_filter([
            $user->nom_usu ?? null,
            $user->app_usu ?? null,
            $user->apm_usu ?? null,
        ]))) ?: ($user->email ?? 'Sin usuario');
    }

    private function display(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text === '' ? $fallback : $text;
    }

    private function dateTime($value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
