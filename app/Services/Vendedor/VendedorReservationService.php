<?php

namespace App\Services\Vendedor;

use App\Models\DetalleReserva;
use App\Models\InventarioMovimiento;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Services\Superadmin\PickupLogisticsService;
use App\Services\Superadmin\ReservationInventoryService;
use App\Services\Superadmin\ReservationNumberService;
use App\Services\Superadmin\ReservationPaymentService;
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

class VendedorReservationService
{
    private const PAYMENT_METHODS = ['efectivo', 'qr', 'transferencia_bancaria'];
    private const ACTIVE_STATES = ['por_procesar', 'confirmada', 'lista_recojo'];
    private const MINIMUM_PAYMENT_RATE = 0.5;

    public function __construct(
        private readonly ReservationNumberService $numbers,
        private readonly ReservationInventoryService $inventory,
        private readonly ReservationPaymentService $payments,
        private readonly PickupLogisticsService $pickup,
        private readonly SystemLogService $logs,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $reservations = $this->visibleReservationsQuery($request)
            ->orderByDesc('fec_res')
            ->orderByDesc('id_reserva')
            ->get()
            ->filter(fn (Reserva $reservation) => $this->reservationMatchesFilters($reservation, $request))
            ->values();

        $activeCount = $reservations->filter(fn (Reserva $reservation) => in_array((string) $reservation->est_res, self::ACTIVE_STATES, true))->count();

        return [
            'reservations' => $reservations->map(fn (Reserva $reservation) => $this->mapReservationRow($reservation))->all(),
            'summary' => [
                ['label' => 'Reservas', 'value' => $this->number($reservations->count()), 'tone' => 'accent'],
                ['label' => 'Activas', 'value' => $this->number($activeCount), 'tone' => 'success'],
                ['label' => 'Pagado', 'value' => $this->money((float) $reservations->sum('monto_pagado_res')), 'tone' => 'info'],
                ['label' => 'Saldo pendiente', 'value' => $this->money((float) $reservations->sum('saldo_res')), 'tone' => 'warning'],
            ],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'status' => $request->string('status')->toString(),
                'customer' => $request->string('customer')->toString(),
            ],
            'options' => [
                'statuses' => $this->reservationStatusOptions(),
                'customers' => $this->customerOptions(),
            ],
            'scopeLabel' => $this->isSupervising($request)
                ? 'Supervision: todas las reservas visibles del panel vendedor.'
                : 'Mostrando reservas registradas por tu usuario.',
        ];
    }

    public function createPayload(Request $request): array
    {
        return [
            'options' => [
                'responsible' => [
                    'id' => (string) $request->user()->id,
                    'name' => $this->userName($request->user()),
                ],
                'customers' => $this->customerOptions(),
                'paymentMethods' => $this->paymentMethodOptions(),
                'variants' => $this->variantOptions(),
            ],
            'rules' => [
                'minimumPaymentRate' => self::MINIMUM_PAYMENT_RATE,
                'minimumPaymentLabel' => '50%',
            ],
            'submitAction' => '/vendedor/reservas',
        ];
    }

    public function store(Request $request): Reserva
    {
        $validated = $this->validateCreate($request);
        $method = $this->normalizePaymentMethod($validated['payment_method']);

        return DB::transaction(function () use ($request, $validated, $method) {
            $items = collect($validated['reservation_items'])
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

            $subtotal = round((float) $items->sum(function (array $item) use ($variants) {
                $variant = $variants->get($item['variant_id']);

                return ((float) ($variant?->pve_prv ?? 0)) * $item['quantity'];
            }), 2);
            $total = $subtotal;
            $paid = round((float) $validated['amount_paid'], 2);
            $logistics = $this->pickup->reservationPayload($variants->pluck('inventario')->filter());

            $reservation = Reserva::query()->create([
                'nre_res' => $this->numbers->next(),
                'cod_usuario_cliente_res' => (int) $validated['customer_id'],
                'cod_usuario_responsable_res' => (int) $request->user()->id,
                'est_res' => 'confirmada',
                'sub_res' => $subtotal,
                'des_res' => 0,
                'tot_res' => $total,
                'monto_pagado_res' => 0,
                'saldo_res' => $total,
                'est_pago_res' => 'pendiente_revision',
                'est_logistico_res' => $logistics['est_logistico_res'],
                'ven_res' => $logistics['ven_res'],
                'fec_disponible_recojo_res' => $logistics['fec_disponible_recojo_res'],
                'fec_limite_recojo_res' => $logistics['fec_limite_recojo_res'],
                'punto_recojo_res' => $logistics['punto_recojo_res'],
                'origen_stock_res' => $logistics['origen_stock_res'],
                'obs_res' => filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null,
                'fec_res' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $price = (float) ($variant?->pve_prv ?? 0);

                $inventory = $this->inventory->reserve(
                    $variant,
                    $item['quantity'],
                    'reserva',
                    (int) $reservation->id_reserva,
                    'Reserva registrada por vendedor.',
                    [
                        'reserva_id' => $reservation->id_reserva,
                        'numero_reserva' => $reservation->nre_res,
                        'estado_reserva' => $reservation->est_res,
                        'origen' => 'panel_vendedor',
                    ],
                );

                DetalleReserva::query()->create([
                    'cod_reservas_dre' => $reservation->id_reserva,
                    'cod_prenda_variantes_dre' => $variant->id_prenda_variante,
                    'can_dre' => $item['quantity'],
                    'pun_dre' => $price,
                    'sub_dre' => round($price * $item['quantity'], 2),
                    'fec_dre' => Carbon::now(),
                ]);

                $this->logs->info(
                    'inventario',
                    'stock_separado_por_reserva',
                    'Stock separado por reserva del vendedor: ' . $reservation->nre_res . '.',
                    $inventory,
                    [
                        'reserva_id' => $reservation->id_reserva,
                        'numero_reserva' => $reservation->nre_res,
                        'variante_id' => $variant->id_prenda_variante,
                        'cantidad' => $item['quantity'],
                        'cdi_inv' => (int) $inventory->cdi_inv,
                        'cre_inv' => (int) $inventory->cre_inv,
                    ],
                );
            }

            $payment = $this->payments->registerConfirmedPickupPayment(
                $reservation,
                $paid,
                $method,
                'Pago registrado por vendedor y confirmado automaticamente.',
                (int) $request->user()->id,
                $request->file('voucher'),
            );

            $reservation->refresh();

            $this->logs->info(
                'reservas',
                'reserva_vendedor_creada',
                'Reserva registrada por vendedor: ' . $reservation->nre_res . '.',
                $reservation,
                $this->reservationMetadata($reservation) + [
                    'items' => $items->count(),
                    'monto_pagado_inicial' => $paid,
                    'metodo_pago' => $method,
                ],
            );

            $this->logVendorPayment($reservation, $payment, $method, $paid);

            return $reservation->load([
                'cliente',
                'responsable',
                'pagos.usuarioVerifica',
                'detalles.variante.prenda.imagenes',
                'detalles.variante.color',
                'detalles.variante.talla',
            ]);
        });
    }

    public function showPayload(Request $request, Reserva $reservation): array
    {
        $this->authorizeReservationVisibility($request, $reservation);

        $reservation->loadMissing([
            'cliente',
            'responsable',
            'pagos.usuarioVerifica',
            'detalles.variante.prenda.imagenes',
            'detalles.variante.color',
            'detalles.variante.talla',
        ]);

        $movements = InventarioMovimiento::query()
            ->with(['variante.prenda'])
            ->where('referencia_tipo_inm', 'reserva')
            ->where('referencia_id_inm', $reservation->id_reserva)
            ->orderBy('id')
            ->get();

        $balance = $this->balance($reservation);
        $canCancel = $this->canCancel($reservation);
        $hasConfirmedPayments = $reservation->pagos->contains(fn (ReservaPago $payment) => $payment->est_rpg === 'confirmado');

        return [
            'reservation' => [
                'id' => (int) $reservation->id_reserva,
                'number' => $reservation->nre_res,
                'date' => $this->dateTime($reservation->fec_res),
                'responsible' => $this->userName($reservation->responsable),
                'customer' => $this->userName($reservation->cliente),
                'status' => $this->reservationStatusBadge($reservation->est_res),
                'paymentStatus' => $this->paymentStatusBadge($reservation->est_pago_res),
                'logisticsStatus' => $this->reservationLogisticsLabel($reservation->est_logistico_res),
                'expiresAt' => $this->date($reservation->ven_res),
                'availableAt' => $this->date($reservation->fec_disponible_recojo_res),
                'limitAt' => $this->date($reservation->fec_limite_recojo_res),
                'pickupPoint' => $this->display($reservation->punto_recojo_res, 'Recojo en tienda Mati Boutique'),
                'stockOrigin' => $this->display($reservation->origen_stock_res, 'TIENDA'),
                'note' => $this->display($reservation->obs_res, 'Sin observación'),
                'subtotal' => $this->money((float) $reservation->sub_res),
                'total' => $this->money((float) $reservation->tot_res),
                'paid' => $this->money((float) $reservation->monto_pagado_res),
                'balance' => $this->money($balance),
                'balanceAmount' => $balance,
                'hasConfirmedPayments' => $hasConfirmedPayments,
                'canAddPayment' => in_array((string) $reservation->est_res, ['confirmada', 'lista_recojo'], true) && $balance > 0,
                'canCancel' => $canCancel,
                'paymentAction' => '/vendedor/reservas/' . $reservation->id_reserva . '/pagos',
                'cancelAction' => '/vendedor/reservas/' . $reservation->id_reserva . '/cancelar',
            ],
            'items' => $reservation->detalles->map(fn (DetalleReserva $detail) => $this->mapDetailRow($detail))->values()->all(),
            'payments' => $reservation->pagos
                ->sortByDesc('id_reserva_pago')
                ->map(fn (ReservaPago $payment) => $this->mapPaymentRow($payment))
                ->values()
                ->all(),
            'movements' => $movements->map(fn (InventarioMovimiento $movement) => [
                'id' => (int) $movement->id,
                'product' => $movement->variante?->prenda?->nom_pre ?? 'Prenda sin nombre',
                'sku' => $movement->variante?->sku_prv ?? 'Sin SKU',
                'type' => $this->movementLabel($movement->tipo_movimiento_inm),
                'quantity' => $this->number((int) $movement->cantidad_inm),
                'availableBefore' => $this->number((int) $movement->cdi_antes_inm),
                'availableAfter' => $this->number((int) $movement->cdi_despues_inm),
                'committedBefore' => $this->number((int) $movement->cre_antes_inm),
                'committedAfter' => $this->number((int) $movement->cre_despues_inm),
                'description' => $movement->descripcion_inm,
            ])->all(),
            'options' => [
                'paymentMethods' => $this->paymentMethodOptions(),
            ],
        ];
    }

    public function storePayment(Request $request, Reserva $reservation): void
    {
        $this->authorizeReservationVisibility($request, $reservation);
        $validated = $this->validatePayment($request, $reservation);
        $method = $this->normalizePaymentMethod($validated['payment_method']);

        DB::transaction(function () use ($request, $reservation, $validated, $method) {
            $reservation = Reserva::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id_reserva);

            $this->authorizeReservationVisibility($request, $reservation);

            if (! in_array((string) $reservation->est_res, ['confirmada', 'lista_recojo'], true)) {
                throw ValidationException::withMessages([
                    'payment_action' => 'Solo se pueden agregar pagos a reservas confirmadas o listas para recojo.',
                ]);
            }

            $balance = $this->balance($reservation);
            $amount = round((float) $validated['amount_paid'], 2);

            if ($balance <= 0) {
                throw ValidationException::withMessages([
                    'payment_action' => 'La reserva ya está pagada completamente.',
                ]);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'El pago no puede superar el saldo pendiente.',
                ]);
            }

            $payment = $this->payments->registerConfirmedPickupPayment(
                $reservation,
                $amount,
                $method,
                filled($validated['note'] ?? null)
                    ? trim((string) $validated['note'])
                    : 'Pago adicional registrado por vendedor y confirmado automaticamente.',
                (int) $request->user()->id,
                $request->file('voucher'),
            );

            $reservation->refresh();
            $this->logVendorPayment($reservation, $payment, $method, $amount);
        });
    }

    public function cancel(Request $request, Reserva $reservation): void
    {
        $this->authorizeReservationVisibility($request, $reservation);
        $validated = Validator::make($request->all(), [
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'cancellation_reason.required' => 'Ingresa el motivo de cancelación.',
            'cancellation_reason.min' => 'El motivo de cancelación debe tener al menos 5 caracteres.',
        ])->validate();

        DB::transaction(function () use ($request, $reservation, $validated) {
            $reservation = Reserva::query()
                ->with(['detalles.variante'])
                ->lockForUpdate()
                ->findOrFail($reservation->id_reserva);

            $this->authorizeReservationVisibility($request, $reservation);

            if (! $this->canCancel($reservation)) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'Esta reserva ya no puede cancelarse desde el panel vendedor.',
                ]);
            }

            $this->payments->ensureCancellationAllowed($reservation);

            if ($reservation->detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'La reserva no tiene prendas para liberar.',
                ]);
            }

            foreach ($reservation->detalles as $detail) {
                if (! $detail->variante) {
                    throw ValidationException::withMessages([
                        'reservation_action' => 'Una prenda reservada no tiene variante asociada.',
                    ]);
                }

                $inventory = $this->inventory->release(
                    $detail->variante,
                    (int) $detail->can_dre,
                    'reserva',
                    (int) $reservation->id_reserva,
                    'Reserva cancelada por vendedor. Stock liberado.',
                    [
                        'reserva_id' => $reservation->id_reserva,
                        'numero_reserva' => $reservation->nre_res,
                        'estado_reserva' => $reservation->est_res,
                        'origen' => 'panel_vendedor',
                    ],
                );

                $this->logs->warning(
                    'inventario',
                    'stock_liberado_por_reserva_cancelada',
                    'Stock liberado por cancelacion de reserva del vendedor: ' . $reservation->nre_res . '.',
                    $inventory,
                    [
                        'reserva_id' => $reservation->id_reserva,
                        'numero_reserva' => $reservation->nre_res,
                        'variante_id' => $detail->variante->id_prenda_variante,
                        'cantidad' => (int) $detail->can_dre,
                        'cdi_inv' => (int) $inventory->cdi_inv,
                        'cre_inv' => (int) $inventory->cre_inv,
                    ],
                );
            }

            $reason = trim((string) $validated['cancellation_reason']);

            $reservation->update([
                'est_res' => 'cancelada',
                'est_logistico_res' => 'cancelada',
                'obs_res' => trim(implode("\n", array_filter([
                    $reservation->obs_res,
                    'Cancelada por vendedor: ' . $reason,
                ]))),
            ]);

            $this->logs->warning(
                'reservas',
                'reserva_vendedor_cancelada',
                'Reserva cancelada por vendedor: ' . $reservation->nre_res . '.',
                $reservation,
                $this->reservationMetadata($reservation) + [
                    'motivo' => $reason,
                    'tiene_pagos_confirmados' => $reservation->pagos()->where('est_rpg', 'confirmado')->exists(),
                ],
            );
        });
    }

    public function dashboardMetrics(Request $request): array
    {
        $activeQuery = $this->visibleReservationsQuery($request)
            ->whereIn('est_res', self::ACTIVE_STATES);

        $active = (int) (clone $activeQuery)->count();
        $expiring = (int) (clone $activeQuery)
            ->whereNotNull('fec_limite_recojo_res')
            ->where('fec_limite_recojo_res', '<=', Carbon::now()->addDays(3))
            ->count();

        return [
            'active_reservations' => [
                'value' => $this->number($active),
                'note' => $expiring > 0
                    ? $this->number($expiring) . ' vencidas o proximas a vencer.'
                    : ($this->isSupervising($request) ? 'Reservas activas en supervision global.' : 'Reservas activas registradas por tu usuario.'),
                'tone' => $expiring > 0 ? 'warning' : 'info',
            ],
        ];
    }

    private function validateCreate(Request $request): array
    {
        $method = $this->normalizePaymentMethod((string) $request->input('payment_method'));
        $request->merge(['payment_method' => $method]);

        return Validator::make($request->all(), [
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'voucher' => [Rule::requiredIf(in_array($method, ['qr', 'transferencia_bancaria'], true)), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
            'reservation_items' => ['required', 'array', 'min:1'],
            'reservation_items.*.variant_id' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
            'reservation_items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'customer_id.required' => 'Selecciona un cliente para la reserva.',
            'voucher.required' => 'Sube el comprobante para pagos QR o transferencia.',
            'reservation_items.required' => 'Agrega al menos un producto a la reserva.',
            'reservation_items.min' => 'Agrega al menos un producto a la reserva.',
            'reservation_items.*.quantity.min' => 'La cantidad debe ser mayor a cero.',
        ])->after(function ($validator) use ($request) {
            if (filled($request->input('customer_id')) && ! $this->isValidCustomer((int) $request->input('customer_id'))) {
                $validator->errors()->add('customer_id', 'Selecciona un cliente valido para la reserva.');
            }

            $items = collect($request->input('reservation_items', []))
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
                    $validator->errors()->add('reservation_items', 'Una variante seleccionada no existe.');
                    continue;
                }

                if (! $variant->est_prv || ! $variant->prenda?->est_pre) {
                    $validator->errors()->add('reservation_items', 'La variante seleccionada no está activa para reserva.');
                    continue;
                }

                if (! $variant->inventario) {
                    $validator->errors()->add('reservation_items', 'La variante seleccionada no tiene inventario registrado.');
                    continue;
                }

                if ((int) $variant->inventario->cdi_inv < (int) $quantity) {
                    $validator->errors()->add('reservation_items', 'No hay stock suficiente para ' . ($variant->sku_prv ?: $variant->prenda?->nom_pre ?: 'una variante') . '.');
                    continue;
                }

                $total += ((float) $variant->pve_prv) * (int) $quantity;
            }

            $total = round($total, 2);
            $paid = round((float) $request->input('amount_paid', 0), 2);
            $minimum = round($total * self::MINIMUM_PAYMENT_RATE, 2);

            if ($total <= 0) {
                $validator->errors()->add('reservation_items', 'La reserva debe tener un total mayor a cero.');
                return;
            }

            if ($paid < $minimum) {
                $validator->errors()->add('amount_paid', 'El anticipo minimo para reservar es del 50% del total.');
            }

            if ($paid > $total) {
                $validator->errors()->add('amount_paid', 'El monto no puede ser mayor al total de la reserva.');
            }
        })->validate();
    }

    private function validatePayment(Request $request, Reserva $reservation): array
    {
        $method = $this->normalizePaymentMethod((string) $request->input('payment_method'));
        $request->merge(['payment_method' => $method]);

        return Validator::make($request->all(), [
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'voucher' => [Rule::requiredIf(in_array($method, ['qr', 'transferencia_bancaria'], true)), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'voucher.required' => 'Sube el comprobante para pagos QR o transferencia.',
        ])->after(function ($validator) use ($request, $reservation) {
            $amount = round((float) $request->input('amount_paid', 0), 2);
            $balance = $this->balance($reservation);

            if ($amount > $balance) {
                $validator->errors()->add('amount_paid', 'El pago no puede superar el saldo pendiente.');
            }
        })->validate();
    }

    private function ensureStockInsideTransaction($items, $variants): void
    {
        foreach ($items as $item) {
            $variant = $variants->get($item['variant_id']);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'reservation_items' => 'La variante seleccionada no existe.',
                ]);
            }

            if (! $variant->inventario || (int) $variant->inventario->cdi_inv < (int) $item['quantity']) {
                throw ValidationException::withMessages([
                    'reservation_items' => 'No hay stock suficiente para ' . ($variant->sku_prv ?: $variant->prenda?->nom_pre ?: 'una variante') . '.',
                ]);
            }
        }
    }

    private function visibleReservationsQuery(Request $request): Builder
    {
        $query = Reserva::query()->with(['cliente', 'responsable', 'pagos.usuarioVerifica']);

        if (! $this->isSupervising($request)) {
            $query->where('cod_usuario_responsable_res', (int) $request->user()->id);
        }

        return $query;
    }

    private function isSupervising(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isSuperadmin') && $user->isSuperadmin();
    }

    private function authorizeReservationVisibility(Request $request, Reserva $reservation): void
    {
        abort_unless(
            $this->isSupervising($request) || (int) $reservation->cod_usuario_responsable_res === (int) $request->user()->id,
            403,
        );
    }

    private function reservationMatchesFilters(Reserva $reservation, Request $request): bool
    {
        if (filled($request->input('status')) && (string) $request->input('status') !== (string) $reservation->est_res) {
            return false;
        }

        if (filled($request->input('customer')) && (string) $request->input('customer') !== (string) $reservation->cod_usuario_cliente_res) {
            return false;
        }

        if (filled($request->input('date_from')) && $reservation->fec_res?->lt(Carbon::parse($request->input('date_from'))->startOfDay())) {
            return false;
        }

        if (filled($request->input('date_to')) && $reservation->fec_res?->gt(Carbon::parse($request->input('date_to'))->endOfDay())) {
            return false;
        }

        $search = mb_strtolower(trim($request->string('search')->toString()), 'UTF-8');

        if ($search === '') {
            return true;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $reservation->nre_res,
            $this->userName($reservation->cliente),
            $this->userName($reservation->responsable),
        ]))), 'UTF-8');

        return str_contains($haystack, $search);
    }

    private function mapReservationRow(Reserva $reservation): array
    {
        return [
            'id' => (int) $reservation->id_reserva,
            'primary' => $reservation->nre_res,
            'secondary' => $this->dateTime($reservation->fec_res),
            'number' => $reservation->nre_res,
            'date' => $this->dateTime($reservation->fec_res),
            'customer' => $this->userName($reservation->cliente),
            'responsible' => $this->userName($reservation->responsable),
            'total' => $this->money((float) $reservation->tot_res),
            'paid' => $this->money((float) $reservation->monto_pagado_res),
            'balance' => $this->money($this->balance($reservation)),
            'expiresAt' => $this->date($reservation->ven_res),
            'status' => $this->reservationStatusBadge($reservation->est_res),
            'paymentStatus' => $this->paymentStatusBadge($reservation->est_pago_res),
            'showHref' => '/vendedor/reservas/' . $reservation->id_reserva,
        ];
    }

    private function mapDetailRow(DetalleReserva $detail): array
    {
        $variant = $detail->variante;
        $product = $variant?->prenda;

        return [
            'id' => (int) $detail->id_detalle_reserva,
            'primary' => $product?->nom_pre ?? 'Prenda sin nombre',
            'secondary' => $variant?->sku_prv ?: 'Sin SKU',
            'image' => $product ? $this->primaryImage($product) : null,
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?? 'Sin color',
            'size' => $variant?->talla?->nom_tal ?? 'Sin talla',
            'quantity' => $this->number((int) $detail->can_dre),
            'unitPrice' => $this->money((float) $detail->pun_dre),
            'subtotal' => $this->money((float) $detail->sub_dre),
        ];
    }

    private function mapPaymentRow(ReservaPago $payment): array
    {
        return [
            'id' => (int) $payment->id_reserva_pago,
            'date' => $this->dateTime($payment->fec_rpg),
            'method' => $this->paymentLabel($payment->metodo_pago_rpg),
            'declared' => $this->money((float) $payment->monto_declarado_rpg),
            'confirmed' => $this->money((float) $payment->monto_confirmado_rpg),
            'status' => $this->reservationPaymentRowStatusBadge($payment->est_rpg),
            'verifiedBy' => $payment->usuarioVerifica ? $this->userName($payment->usuarioVerifica) : 'Sin verificador',
            'verifiedAt' => $this->dateTime($payment->fec_verificacion_rpg),
            'voucherUrl' => $payment->comprobante_url_rpg,
            'note' => $this->display($payment->obs_rpg, 'Sin observación'),
        ];
    }

    private function logVendorPayment(Reserva $reservation, ReservaPago $payment, string $method, float $amount): void
    {
        $this->logs->info(
            'pagos',
            'pago_reserva_vendedor_confirmado',
            'Pago de reserva registrado por vendedor y confirmado: ' . $reservation->nre_res . '.',
            $payment,
            [
                'reserva_id' => $reservation->id_reserva,
                'numero_reserva' => $reservation->nre_res,
                'metodo_pago' => $method,
                'monto' => $amount,
                'monto_pagado' => (float) $reservation->monto_pagado_res,
                'saldo' => (float) $reservation->saldo_res,
                'estado' => 'confirmado',
            ],
        );
    }

    private function reservationMetadata(Reserva $reservation): array
    {
        return [
            'reserva_id' => $reservation->id_reserva,
            'numero_reserva' => $reservation->nre_res,
            'cliente_id' => $reservation->cod_usuario_cliente_res,
            'responsable_id' => $reservation->cod_usuario_responsable_res,
            'estado' => $reservation->est_res,
            'estado_pago' => $reservation->est_pago_res,
            'estado_logistico' => $reservation->est_logistico_res,
            'total' => (float) $reservation->tot_res,
            'monto_pagado' => (float) $reservation->monto_pagado_res,
            'saldo' => (float) $reservation->saldo_res,
            'origen' => 'panel_vendedor',
        ];
    }

    private function canCancel(Reserva $reservation): bool
    {
        return in_array((string) $reservation->est_res, self::ACTIVE_STATES, true);
    }

    private function balance(Reserva $reservation): float
    {
        return max(0, round((float) $reservation->tot_res - (float) $reservation->monto_pagado_res, 2));
    }

    private function isValidCustomer(int $userId): bool
    {
        $user = User::query()->with('rol')->find($userId);

        return $user !== null
            && $user->isActive()
            && $user->rol !== null
            && $user->rol->isActive()
            && mb_strtolower((string) $user->rol->nom_rol, 'UTF-8') === 'cliente';
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
                'location' => $variant->inventario?->ubi_inv ?: 'Sin ubicación',
                'image' => $variant->prenda ? $this->primaryImage($variant->prenda) : null,
                'hasInventory' => $variant->inventario !== null,
            ])
            ->values()
            ->all();
    }

    private function paymentMethodOptions(): array
    {
        return [
            ['value' => 'efectivo', 'label' => 'Efectivo'],
            ['value' => 'qr', 'label' => 'QR'],
            ['value' => 'transferencia_bancaria', 'label' => 'Transferencia bancaria'],
        ];
    }

    private function reservationStatusOptions(): array
    {
        return [
            ['value' => 'por_procesar', 'label' => 'En revisión'],
            ['value' => 'confirmada', 'label' => 'Confirmada'],
            ['value' => 'lista_recojo', 'label' => 'Lista para recojo'],
            ['value' => 'completada', 'label' => 'Completada'],
            ['value' => 'cancelada', 'label' => 'Cancelada'],
            ['value' => 'vencida', 'label' => 'Vencida'],
            ['value' => 'rechazada', 'label' => 'Rechazada'],
        ];
    }

    private function normalizePaymentMethod(string $method): string
    {
        return $method === 'transferencia' ? 'transferencia_bancaria' : $method;
    }

    private function paymentLabel(?string $method): string
    {
        return match ($this->normalizePaymentMethod((string) $method)) {
            'efectivo' => 'Efectivo',
            'qr' => 'QR',
            'transferencia_bancaria' => 'Transferencia bancaria',
            default => $this->display($method, 'Sin método'),
        };
    }

    private function reservationStatusBadge(?string $status): array
    {
        return match ($status) {
            'por_procesar' => ['label' => 'En revisión', 'tone' => 'warning'],
            'confirmada' => ['label' => 'Confirmada', 'tone' => 'success'],
            'lista_recojo' => ['label' => 'Lista para recojo', 'tone' => 'info'],
            'completada', 'recogida' => ['label' => 'Completada', 'tone' => 'success'],
            'cancelada', 'rechazada', 'vencida' => ['label' => ucfirst((string) $status), 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function paymentStatusBadge(?string $status): array
    {
        return match ($status) {
            'anticipo_confirmado' => ['label' => 'Adelanto confirmado', 'tone' => 'success'],
            'pagado_total' => ['label' => 'Pagado completamente', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revisión', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin pago'), 'tone' => 'neutral'],
        };
    }

    private function reservationPaymentRowStatusBadge(?string $status): array
    {
        return match ($status) {
            'confirmado' => ['label' => 'Confirmado', 'tone' => 'success'],
            'pendiente_revision' => ['label' => 'Pendiente revisión', 'tone' => 'warning'],
            'rechazado' => ['label' => 'Rechazado', 'tone' => 'danger'],
            default => ['label' => $this->display($status, 'Sin estado'), 'tone' => 'neutral'],
        };
    }

    private function reservationLogisticsLabel(?string $status): string
    {
        return match ($status) {
            'lista_recojo' => 'Lista para recojo',
            'pendiente_traslado' => 'Pendiente de traslado',
            'pendiente_preparacion' => 'Pendiente de preparación',
            'recogida' => 'Recogida',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
            default => $this->display($status, 'Sin estado logistico'),
        };
    }

    private function movementLabel(?string $type): string
    {
        return match ($type) {
            'reserva' => 'Reserva',
            'liberacion_reserva' => 'Liberacion reserva',
            default => $this->display($type, 'Movimiento'),
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

    private function date($value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y');
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
