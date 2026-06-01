<?php

namespace App\Services\Superadmin;

use App\Models\DetalleReserva;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\User;
use App\Services\CommercialNotificationService;
use App\Services\Superadmin\Resources\AdminFormattingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        private readonly ReservationNumberService $numbers,
        private readonly ReservationInventoryService $inventory,
        private readonly ReservationPaymentService $payments,
        private readonly PickupLogisticsService $pickup,
        private readonly ReservationToSaleService $toSale,
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
                'description' => 'Registra una reserva con comprobante, anticipo mínimo y stock separado.',
                'indexHref' => '/superadmin/comercial/reservas',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => $definition['sectionLabel']],
                ['label' => $definition['title'], 'href' => '/superadmin/comercial/reservas'],
                ['label' => 'Nueva reserva'],
            ],
            'options' => [
                'customers' => $this->customerOptions(),
                'responsible' => [
                    'id' => (string) $request->user()->id,
                    'name' => $this->formatting->userName($request->user()),
                ],
                'paymentMethods' => $this->paymentMethodOptions(),
                'variants' => $this->variantOptions(),
            ],
            'submitAction' => '/superadmin/comercial/reservas',
        ];
    }

    public function validateCreate(Request $request): array
    {
        return Validator::make($request->all(), [
            'cod_usuario_cliente_res' => ['required', 'integer', Rule::exists('users', 'id')],
            'des_res' => ['nullable', 'numeric', 'min:0'],
            'obs_res' => ['nullable', 'string', 'max:1000'],
            'monto_declarado_rpg' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago_rpg' => ['required', 'string', 'max:30', Rule::in(['qr', 'transferencia_bancaria'])],
            'comprobante_rpg' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'reservation_items' => ['required', 'array', 'min:1'],
            'reservation_items.*.cod_prenda_variantes_dre' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
            'reservation_items.*.can_dre' => ['required', 'integer', 'min:1'],
        ], [
            'cod_usuario_cliente_res.required' => 'Selecciona un cliente para la reserva.',
            'monto_declarado_rpg.required' => 'Ingresa el monto depositado.',
            'metodo_pago_rpg.in' => 'Selecciona un mÃ©todo de pago vÃ¡lido.',
            'comprobante_rpg.required' => 'Sube el comprobante de pago.',
            'comprobante_rpg.mimes' => 'El comprobante debe ser una imagen JPG, PNG o WEBP.',
            'reservation_items.required' => 'Selecciona al menos una prenda.',
            'reservation_items.min' => 'Selecciona al menos una prenda.',
            'reservation_items.*.cod_prenda_variantes_dre.required' => 'Selecciona una variante.',
            'reservation_items.*.can_dre.min' => 'La cantidad debe ser mayor a cero.',
        ])->after(function ($validator) use ($request) {
            if (!$request->user()) {
                $validator->errors()->add('auth', 'Debes iniciar sesión para finalizar la reserva.');
            }

            $clientId = $request->input('cod_usuario_cliente_res');
            if (filled($clientId) && !$this->isValidCustomer((int) $clientId)) {
                $validator->errors()->add('cod_usuario_cliente_res', 'Selecciona un cliente válido para la reserva.');
            }

            $items = collect($request->input('reservation_items', []));
            $subtotal = 0;

            foreach ($items->groupBy('cod_prenda_variantes_dre') as $variantId => $rows) {
                if ($variantId === '' || $variantId === null) {
                    continue;
                }

                $variant = PrendaVariante::query()->with(['inventario', 'prenda'])->find((int) $variantId);
                $quantity = $rows->sum(fn ($row) => (int) ($row['can_dre'] ?? 0));

                if (!$variant?->inventario) {
                    $validator->errors()->add('reservation_items', 'La variante seleccionada no tiene inventario registrado.');
                    continue;
                }

                if ((int) $variant->inventario->cdi_inv < $quantity) {
                    $validator->errors()->add('reservation_items', 'Ya no hay stock disponible para ' . ($variant->prenda?->nom_pre ?? 'esta prenda') . '.');
                }

                $subtotal += ((float) ($variant?->pve_prv ?? 0)) * $quantity;
            }

            $discount = min((float) $request->input('des_res', 0), $subtotal);
            $total = max(0, $subtotal - $discount);
            $minimum = round($total * 0.5, 2);

            $declaredAmount = (float) $request->input('monto_declarado_rpg', 0);

            if ($total > 0 && $declaredAmount < $minimum) {
                $validator->errors()->add('monto_declarado_rpg', 'El anticipo mínimo para reservar es del 50% del total.');
            }

            if ($total > 0 && $declaredAmount > round($total, 2)) {
                $validator->errors()->add('monto_declarado_rpg', 'El monto no puede ser mayor al total de la reserva.');
            }
        })->validate();
    }

    public function createReservation(Request $request, array $validated): Reserva
    {
        if (!$request->user()) {
            throw ValidationException::withMessages([
                'auth' => 'Debes iniciar sesión para finalizar la reserva.',
            ]);
        }

        if (!$this->isValidCustomer((int) $validated['cod_usuario_cliente_res'])) {
            throw ValidationException::withMessages([
                'cod_usuario_cliente_res' => 'Selecciona un cliente válido para la reserva.',
            ]);
        }

        return DB::transaction(function () use ($request, $validated) {
            $items = collect($validated['reservation_items'])
                ->groupBy('cod_prenda_variantes_dre')
                ->map(fn ($rows, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => $rows->sum(fn ($row) => (int) $row['can_dre']),
                ])
                ->values();

            $variants = PrendaVariante::query()
                ->with(['inventario', 'prenda'])
                ->whereIn('id_prenda_variante', $items->pluck('variant_id'))
                ->get()
                ->keyBy('id_prenda_variante');

            $subtotal = $items->sum(function (array $item) use ($variants) {
                $variant = $variants->get($item['variant_id']);

                return ((float) ($variant?->pve_prv ?? 0)) * $item['quantity'];
            });
            $discount = min((float) ($validated['des_res'] ?? 0), $subtotal);
            $total = max(0, $subtotal - $discount);
            $pickup = $this->pickup->reservationPayload($variants->pluck('inventario')->filter());

            $reservation = Reserva::query()->create([
                'nre_res' => $this->numbers->next(),
                'cod_usuario_cliente_res' => (int) $validated['cod_usuario_cliente_res'],
                'cod_usuario_responsable_res' => (int) $request->user()->id,
                'est_res' => 'por_procesar',
                'sub_res' => $subtotal,
                'des_res' => $discount,
                'tot_res' => $total,
                'monto_pagado_res' => 0,
                'saldo_res' => $total,
                'est_pago_res' => 'pendiente_revision',
                'est_logistico_res' => 'pendiente_revision',
                'ven_res' => $pickup['ven_res'],
                'fec_disponible_recojo_res' => $pickup['fec_disponible_recojo_res'],
                'fec_limite_recojo_res' => $pickup['fec_limite_recojo_res'],
                'punto_recojo_res' => $pickup['punto_recojo_res'],
                'origen_stock_res' => $pickup['origen_stock_res'],
                'obs_res' => filled($validated['obs_res'] ?? null) ? trim((string) $validated['obs_res']) : null,
                'fec_res' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'reservation_items' => 'La variante seleccionada no existe.',
                    ]);
                }

                $this->inventory->reserve($variant, $item['quantity']);
                $price = (float) ($variant->pve_prv ?? 0);

                DetalleReserva::query()->create([
                    'cod_reservas_dre' => $reservation->id_reserva,
                    'cod_prenda_variantes_dre' => $variant->id_prenda_variante,
                    'can_dre' => $item['quantity'],
                    'pun_dre' => $price,
                    'sub_dre' => $price * $item['quantity'],
                    'fec_dre' => Carbon::now(),
                ]);
            }

            $this->payments->registerPayment(
                $reservation,
                $request->file('comprobante_rpg'),
                (float) $validated['monto_declarado_rpg'],
                $validated['metodo_pago_rpg'] ?? null,
            );

            DB::afterCommit(function () use ($reservation) {
                $reservation->loadMissing('cliente');
                $this->notifications->notifyCustomerReservationCreated($reservation);
                $this->notifications->notifyAdminsReservationPending($reservation);
            });

            return $reservation->load(['cliente', 'responsable', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla', 'pagos']);
        });
    }

    public function addPayment(Request $request, Reserva $reservation): void
    {
        $validated = Validator::make($request->all(), [
            'monto_declarado_rpg' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago_rpg' => ['required', 'string', 'max:30', Rule::in(['qr', 'transferencia_bancaria'])],
            'comprobante_rpg' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ])->validate();

        if (!in_array($reservation->est_res, ['confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'Solo se pueden agregar pagos a reservas confirmadas o listas para recojo.',
            ]);
        }

        if ((float) $reservation->saldo_res <= 0) {
            throw ValidationException::withMessages([
                'reservation_action' => 'La reserva ya está pagada completamente.',
            ]);
        }

        DB::transaction(function () use ($request, $reservation, $validated) {
            $this->payments->registerPayment(
                $reservation,
                $request->file('comprobante_rpg'),
                (float) $validated['monto_declarado_rpg'],
                $validated['metodo_pago_rpg'] ?? null,
            );
        });
    }

    public function verifyLatestPendingPayment(Reserva $reservation, float $confirmedAmount, ?string $observation, int $userId): array
    {
        return DB::transaction(function () use ($reservation, $confirmedAmount, $observation, $userId) {
            $reservation = Reserva::query()->lockForUpdate()->findOrFail($reservation->id_reserva);
            $result = $this->payments->verifyPayment($reservation, $this->pendingPayment($reservation), $confirmedAmount, $observation, $userId);

            if (!$result['accepted']) {
                $this->releaseStock($reservation);
            } else {
                $reservation->refresh();
                $reservation->update($this->pickup->reservationModelPayload($reservation));
                DB::afterCommit(function () use ($reservation) {
                    $reservation->refresh();
                    $reservation->loadMissing('cliente');
                    $this->notifications->notifyCustomerReservationConfirmed($reservation);
                });
            }

            return $result;
        });
    }

    public function rejectLatestPendingPayment(Reserva $reservation, ?string $observation, int $userId): void
    {
        DB::transaction(function () use ($reservation, $observation, $userId) {
            $reservation = Reserva::query()->lockForUpdate()->findOrFail($reservation->id_reserva);
            $this->payments->rejectPayment($reservation, $this->pendingPayment($reservation), $observation, $userId);
            $this->releaseStock($reservation);
            $reservation->update(['est_logistico_res' => 'cancelada']);

            DB::afterCommit(function () use ($reservation) {
                $reservation->refresh();
                $reservation->loadMissing('cliente');
                $this->notifications->notifyCustomerReservationRejected($reservation);
            });
        });
    }

    public function markReady(Reserva $reservation): void
    {
        if ($reservation->est_res !== 'confirmada') {
            throw ValidationException::withMessages([
                'reservation_action' => 'Solo se puede marcar como lista para recojo una reserva confirmada.',
            ]);
        }

        $wasReadyForPickup = $reservation->est_logistico_res === 'lista_recojo';

        $reservation->update([
            'est_res' => 'lista_recojo',
            'est_logistico_res' => 'lista_recojo',
        ]);

        if (!$wasReadyForPickup) {
            $reservation->refresh();
            $reservation->loadMissing('cliente');
            $this->notifications->notifyCustomerReservationReadyForPickup($reservation);
        }
    }

    public function cancel(Reserva $reservation, ?string $observation = null): void
    {
        if ($reservation->est_res === 'completada') {
            throw ValidationException::withMessages([
                'reservation_action' => 'No se puede cancelar una reserva completada.',
            ]);
        }

        if ($reservation->est_res === 'cancelada') {
            throw ValidationException::withMessages([
                'reservation_action' => 'La reserva ya fue cancelada.',
            ]);
        }

        if (!in_array($reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'Esta reserva ya no tiene stock reservado para liberar.',
            ]);
        }

        DB::transaction(function () use ($reservation, $observation) {
            $this->releaseStock($reservation);

            $reservation->update([
                'est_res' => 'cancelada',
                'est_logistico_res' => 'cancelada',
                'obs_res' => filled($observation) ? $observation : $reservation->obs_res,
            ]);

            DB::afterCommit(function () use ($reservation) {
                $reservation->refresh();
                $reservation->loadMissing('cliente');
                $this->notifications->notifyCustomerReservationCancelled($reservation);
            });
        });
    }

    public function complete(Reserva $reservation, int $userId, ?float $finalPaymentAmount = null, ?string $finalPaymentMethod = null, ?string $observation = null, ?UploadedFile $finalPaymentVoucher = null): void
    {
        if (!in_array($reservation->est_res, ['confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'Solo se puede marcar como recogida una reserva confirmada o lista para recojo.',
            ]);
        }

        if ($reservation->est_pago_res === 'rechazado') {
            throw ValidationException::withMessages([
                'reservation_action' => 'No se puede marcar como recogida una reserva con pago rechazado.',
            ]);
        }

        DB::transaction(function () use ($reservation, $userId, $finalPaymentAmount, $finalPaymentMethod, $observation, $finalPaymentVoucher) {
            $reservation = Reserva::query()->lockForUpdate()->findOrFail($reservation->id_reserva);

            if ($reservation->est_res === 'completada') {
                throw ValidationException::withMessages([
                    'reservation_action' => 'La reserva ya fue recogida.',
                ]);
            }

            if (!in_array($reservation->est_res, ['confirmada', 'lista_recojo'], true)) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'Solo se puede marcar como recogida una reserva confirmada o lista para recojo.',
                ]);
            }

            $balance = $this->realBalance($reservation);

            if ($balance > 0) {
                $amount = round((float) ($finalPaymentAmount ?? 0), 2);

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'monto_pago_final' => 'Registra el pago restante antes de marcar la reserva como recogida.',
                    ]);
                }

                if ($amount > $balance) {
                    throw ValidationException::withMessages([
                        'monto_pago_final' => 'El monto recibido no puede ser mayor al saldo pendiente.',
                    ]);
                }

                if ($amount < $balance) {
                    throw ValidationException::withMessages([
                        'monto_pago_final' => 'El monto recibido debe completar el saldo pendiente.',
                    ]);
                }

                $this->validateFinalPickupPayment((string) $finalPaymentMethod, $finalPaymentVoucher);

                $this->payments->registerConfirmedPickupPayment(
                    $reservation,
                    $amount,
                    (string) $finalPaymentMethod,
                    $observation,
                    $userId,
                    $finalPaymentVoucher,
                );

                $reservation->refresh();
            }

            if ($this->realBalance($reservation) > 0) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'No se puede marcar como recogida si aun existe saldo pendiente.',
                ]);
            }

            $reservation->loadMissing('detalles.variante');

            foreach ($reservation->detalles as $detail) {
                if (!$detail->variante) {
                    throw ValidationException::withMessages([
                        'reservation_action' => 'Una prenda reservada no tiene variante asociada.',
                    ]);
                }

                $this->inventory->complete($detail->variante, (int) $detail->can_dre);
            }

            $this->toSale->convert($reservation, $userId);

            $reservation->update([
                'est_res' => 'completada',
                'est_pago_res' => 'pagado_total',
                'est_logistico_res' => 'recogida',
                'fec_recogida_res' => Carbon::now(),
                'cod_usuario_entrega_res' => $userId,
                'saldo_res' => 0,
                'obs_res' => filled($observation) ? $observation : $reservation->obs_res,
            ]);

            DB::afterCommit(function () use ($reservation) {
                $reservation->refresh();
                $reservation->loadMissing('cliente');
                $this->notifications->notifyCustomerReservationPickedUp($reservation);
            });
        });
    }

    public function settleCompletedReservation(Reserva $reservation, int $userId, ?float $finalPaymentAmount = null, ?string $finalPaymentMethod = null, ?string $observation = null, ?UploadedFile $finalPaymentVoucher = null): void
    {
        DB::transaction(function () use ($reservation, $userId, $finalPaymentAmount, $finalPaymentMethod, $observation, $finalPaymentVoucher) {
            $reservation = Reserva::query()->lockForUpdate()->findOrFail($reservation->id_reserva);

            if (!in_array($reservation->est_res, ['completada', 'recogida'], true)) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'Esta accion correctiva solo aplica a reservas que figuran como recogidas.',
                ]);
            }

            $balance = $this->realBalance($reservation);

            if ($balance > 0) {
                $amount = round((float) ($finalPaymentAmount ?? 0), 2);

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'monto_pago_final' => 'La reserva aun tiene saldo pendiente. Registra el pago restante antes de marcarla como recogida.',
                    ]);
                }

                if ($amount < $balance || $amount > $balance) {
                    throw ValidationException::withMessages([
                        'monto_pago_final' => $amount > $balance
                            ? 'El monto no puede ser mayor al saldo pendiente.'
                            : 'El monto recibido debe completar el saldo pendiente.',
                    ]);
                }

                $this->validateFinalPickupPayment((string) $finalPaymentMethod, $finalPaymentVoucher);

                $this->payments->registerConfirmedPickupPayment(
                    $reservation,
                    $amount,
                    (string) $finalPaymentMethod,
                    $observation,
                    $userId,
                    $finalPaymentVoucher,
                );

                $reservation->refresh();
            }

            if ($this->realBalance($reservation) > 0) {
                throw ValidationException::withMessages([
                    'reservation_action' => 'La reserva aun tiene saldo pendiente. Registra el pago restante antes de marcarla como recogida.',
                ]);
            }

            if (!$this->toSale->saleForReservation($reservation)) {
                $this->toSale->convert($reservation, $userId, true);
            }

            $reservation->update([
                'est_res' => 'completada',
                'est_pago_res' => 'pagado_total',
                'est_logistico_res' => 'recogida',
                'fec_recogida_res' => Carbon::now(),
                'cod_usuario_entrega_res' => $userId,
                'monto_pagado_res' => (float) $reservation->tot_res,
                'saldo_res' => 0,
                'obs_res' => filled($observation) ? $observation : $reservation->obs_res,
            ]);

        });
    }

    public function saleForReservation(Reserva $reservation): ?\App\Models\Venta
    {
        return $this->toSale->saleForReservation($reservation);
    }

    private function realBalance(Reserva $reservation): float
    {
        return max(0, round((float) $reservation->tot_res - (float) $reservation->monto_pagado_res, 2));
    }

    private function validateFinalPickupPayment(string $method, ?UploadedFile $voucher): void
    {
        if (blank($method)) {
            throw ValidationException::withMessages([
                'metodo_pago_final' => 'Selecciona el metodo de pago final.',
            ]);
        }

        if (!in_array($method, ['efectivo', 'qr', 'transferencia_bancaria'], true)) {
            throw ValidationException::withMessages([
                'metodo_pago_final' => 'Selecciona un metodo de pago final valido.',
            ]);
        }

        if ($method === 'efectivo') {
            return;
        }

        if (!$voucher) {
            throw ValidationException::withMessages([
                'comprobante_pago_final' => 'Sube el comprobante de pago.',
            ]);
        }

        Validator::make(
            ['comprobante_pago_final' => $voucher],
            ['comprobante_pago_final' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:4096']],
            [
                'comprobante_pago_final.mimes' => 'El comprobante debe ser una imagen JPG, PNG o WEBP.',
                'comprobante_pago_final.max' => 'El comprobante no debe superar 4MB.',
            ],
        )->validate();
    }

    public function markExpired(Reserva $reservation, ?string $observation = null): void
    {
        if (!in_array($reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            throw ValidationException::withMessages([
                'reservation_action' => 'Solo se puede vencer una reserva por procesar, confirmada o lista para recojo.',
            ]);
        }

        DB::transaction(function () use ($reservation, $observation) {
            $this->releaseStock($reservation);

            $reservation->update([
                'est_res' => 'vencida',
                'est_logistico_res' => 'vencida',
                'obs_res' => filled($observation) ? $observation : $reservation->obs_res,
            ]);
        });
    }

    public function isVisuallyExpired(?Reserva $reservation): bool
    {
        return $reservation
            && in_array($reservation->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)
            && $reservation->fec_limite_recojo_res
            && $reservation->fec_limite_recojo_res->lt(Carbon::now());
    }

    private function pendingPayment(Reserva $reservation): ReservaPago
    {
        $payment = $reservation->pagos()
            ->where('est_rpg', 'pendiente_revision')
            ->orderByDesc('fec_rpg')
            ->orderByDesc('id_reserva_pago')
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            throw ValidationException::withMessages([
                'reservation_action' => 'No hay comprobantes pendientes de revisión.',
            ]);
        }

        return $payment;
    }

    private function releaseStock(Reserva $reservation): void
    {
        $reservation->loadMissing('detalles.variante');

        foreach ($reservation->detalles as $detail) {
            $this->inventory->release($detail->variante, (int) $detail->can_dre);
        }
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
                'stock' => (int) ($variant->inventario?->cdi_inv ?? 0),
                'reserved' => (int) ($variant->inventario?->cre_inv ?? 0),
                'price' => (float) $variant->pve_prv,
                'location' => $variant->inventario?->ubi_inv ?: 'TIENDA',
                'hasInventory' => $variant->inventario !== null,
            ])
            ->values()
            ->all();
    }

    private function paymentMethodOptions(): array
    {
        return [
            ['label' => 'QR', 'value' => 'qr'],
            ['label' => 'Transferencia bancaria', 'value' => 'transferencia_bancaria'],
        ];
    }
}
