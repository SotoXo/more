<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Venta;
use App\Services\Superadmin\InventoryStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class ResourceRowService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceQueryService $queries,
        private readonly ResourceOptionsService $options,
        private readonly InventoryStatusService $inventoryStatus,
    ) {
    }

    public function indexRows(array $definition, Request $request): array
    {
        return match ($definition['kind']) {
            'taxonomy' => $this->taxonomyRows($definition, $this->queries->indexRecords($definition, $request)),
            'users' => $this->userRows($definition, $this->queries->indexRecords($definition, $request), $request),
            'products' => $this->productRows($definition, $this->queries->indexRecords($definition, $request)),
            'variants' => $this->variantRows($definition, $this->queries->indexRecords($definition, $request)),
            'images' => $this->imageRows($definition, $this->queries->indexRecords($definition, $request)),
            'inventory' => $this->inventoryRows($definition, $this->queries->indexRecords($definition, $request)),
            'adjustments' => $this->adjustmentRows($definition, $this->queries->indexRecords($definition, $request)),
            'alerts' => $this->alertRows($definition, $this->queries->indexRecords($definition, $request), $request),
            'sales' => $this->saleRows($definition, $this->queries->indexRecords($definition, $request)),
            'reservations' => $this->reservationRows($definition, $this->queries->indexRecords($definition, $request)),
            'favorites' => $this->favoriteRows($definition, $this->queries->indexRecords($definition, $request)),
            default => [],
        };
    }

    private function taxonomyRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $key = $this->definitions->definitionKey($definition);
            $keyName = $record->getKeyName();
            $relationCount = !empty($definition['countRelation']) ? ($record->{$definition['countRelation'] . '_count'} ?? 0) : 0;
            $summary = $record->{$definition['descriptionField']} ?: 'Sin detalle adicional';

            if ($key === 'catalogo.tallas') {
                $segment = $record->seg_tal ?: 'ADULTO';
                $system = $record->sis_tal ?: 'Sin sistema';
                $group = $record->gru_tal ?: 'Sin grupo';

                return [
                    'id' => $record->{$keyName},
                    'primary' => $record->{$definition['nameField']},
                    'secondary' => trim($segment . ' / ' . $group . ' / ' . $system),
                    'status' => $this->formatting->booleanStatus($record->{$definition['statusField']}),
                    'cells' => [
                        'segment' => $segment,
                        'group' => $group,
                        'system' => $system,
                        'order' => 'Orden ' . (string) ($record->ord_tal ?? 0),
                        'linked' => $relationCount . ' ' . ($definition['countLabel'] ?? 'variantes'),
                    ],
                    'showHref' => $this->definitions->showHref($definition, $record->{$keyName}),
                    'editHref' => $this->definitions->rowEditHref($definition, $record->{$keyName}),
                ];
            }

            if ($key === 'catalogo.colores') {
                $exact = $record->hex_col ?: '#000000';
                $dominant = $record->hex_dom_col ?: $exact;
                $family = $record->fam_col ?: 'Sin familia';

                return [
                    'id' => $record->{$keyName},
                    'primary' => $record->{$definition['nameField']},
                    'secondary' => 'Familia: ' . $family . ' · ' . $exact,
                    'status' => $this->formatting->booleanStatus($record->{$definition['statusField']}),
                    'cells' => [
                        'exact' => ['label' => $record->{$definition['nameField']}, 'swatch' => $exact],
                        'family' => $family,
                        'dominant' => ['label' => $family, 'swatch' => $dominant, 'value' => $dominant],
                        'hex' => $exact,
                        'order' => 'Orden ' . (string) ($record->ord_col ?? 0),
                        'linked' => $relationCount . ' ' . ($definition['countLabel'] ?? 'variantes'),
                    ],
                    'showHref' => $this->definitions->showHref($definition, $record->{$keyName}),
                    'editHref' => $this->definitions->rowEditHref($definition, $record->{$keyName}),
                ];
            }

            if ($key === 'catalogo.temporadas') {
                $period = $this->seasonPeriodLabel($record);
                $periodStatus = $this->seasonPeriodStatus($record);

                return [
                    'id' => $record->{$keyName},
                    'primary' => $record->{$definition['nameField']},
                    'secondary' => trim(implode(' · ', array_filter([$record->cod_tem, $period !== 'Sin periodo definido' ? $period : null]))) ?: 'Sin periodo definido',
                    'status' => $this->formatting->booleanStatus($record->{$definition['statusField']}),
                    'cells' => [
                        'code' => $record->cod_tem ?: 'Sin código',
                        'period' => $period,
                        'period_status' => $periodStatus,
                        'state' => $this->formatting->booleanStatus($record->{$definition['statusField']}),
                        'order' => 'Orden ' . (string) ($record->ord_tem ?? 0),
                        'linked' => $relationCount . ' ' . ($definition['countLabel'] ?? 'prendas'),
                    ],
                    'showHref' => $this->definitions->showHref($definition, $record->{$keyName}),
                    'editHref' => $this->definitions->rowEditHref($definition, $record->{$keyName}),
                ];
            }

            return [
                'id' => $record->{$keyName},
                'primary' => $record->{$definition['nameField']},
                'secondary' => is_string($summary) ? $summary : 'Registro interno',
                'status' => $this->formatting->booleanStatus($record->{$definition['statusField']}),
                'cells' => [
                    'summary' => is_scalar($summary) ? (string) $summary : 'Registro interno',
                    'linked' => $relationCount . ' ' . ($definition['countLabel'] ?? 'cruces'),
                    'date' => $this->formatting->formatDate($record->{$definition['dateField']}),
                ],
                'showHref' => $this->definitions->showHref($definition, $record->{$keyName}),
                'editHref' => $this->definitions->rowEditHref($definition, $record->{$keyName}),
            ];
        })->all();
    }

    private function seasonPeriodLabel($record): string
    {
        if (!$record?->fec_ini_tem || !$record?->fec_fin_tem) {
            return 'Sin periodo definido';
        }

        return Carbon::parse($record->fec_ini_tem)->format('d/m/Y')
            . ' - '
            . Carbon::parse($record->fec_fin_tem)->format('d/m/Y');
    }

    private function seasonPeriodStatus($record): array
    {
        if (!$record?->fec_ini_tem || !$record?->fec_fin_tem) {
            return ['label' => 'Sin periodo definido', 'tone' => 'neutral'];
        }

        $today = Carbon::today();
        $start = Carbon::parse($record->fec_ini_tem)->startOfDay();
        $end = Carbon::parse($record->fec_fin_tem)->endOfDay();

        if ($today->lt($start)) {
            return ['label' => 'Próxima', 'tone' => 'warning'];
        }

        if ($today->gt($end)) {
            return ['label' => 'Finalizada', 'tone' => 'neutral'];
        }

        return ['label' => 'Temporada vigente', 'tone' => 'success'];
    }

    private function userRows(array $definition, Collection $records, ?Request $request = null): array
    {
        $slug = $request ? $request->string('role_slug')->toString() : '';

        return $records->map(function ($record) use ($definition, $slug) {
            $rolLabel = $record->rol?->nom_rol ? $this->options->roleLabel((string) $record->rol->nom_rol) : 'Sin rol';
            $rolSlug = $record->rol ? mb_strtolower((string) $record->rol->nom_rol, 'UTF-8') : '';

            $rolTone = match ($rolSlug) {
                'superadmin' => 'danger',
                'vendedor' => 'warning',
                'cliente' => 'success',
                default => 'neutral',
            };

            $cells = [
                'role' => ['label' => $rolLabel, 'tone' => $rolTone],
                'email' => $record->email ?? 'Sin correo',
                'phone' => $record->tel_usu ?: '—',
                'status_label' => $record->est_usu
                    ? ['label' => 'Activo', 'tone' => 'success']
                    : ['label' => 'Inactivo', 'tone' => 'neutral'],
                'date' => $this->formatting->formatDate($record->fec_usu),
            ];

            return [
                'id' => $record->id,
                'primary' => $this->formatting->userName($record),
                'secondary' => $slug === 'superadmin' ? $record->email : ($record->rol?->nom_rol ? $this->options->roleLabel((string) $record->rol->nom_rol) : 'Sin rol'),
                'status' => $this->formatting->booleanStatus($record->est_usu),
                'cells' => $cells,
                'showHref' => $this->definitions->showHref($definition, $record->id),
                'editHref' => $this->definitions->rowEditHref($definition, $record->id),
            ];
        })->all();
    }

    private function productRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_prenda,
            'primary' => $record->nom_pre,
            'secondary' => trim(implode(' · ', array_filter([
                $record->temporada?->nom_tem,
                $record->gen_pre ? mb_strtoupper((string) $record->gen_pre, 'UTF-8') : null,
            ]))) ?: 'Sin clasificación',
            'status' => $this->formatting->booleanStatus($record->est_pre, 'Visible', 'Oculta'),
            'cells' => [
                'brand' => $record->marca?->nom_mar ?? 'Sin marca',
                'category' => $record->categoria?->nom_cat ?? 'Sin categoría',
                'variants' => (string) ($record->variantes_count ?? $record->variantes?->count() ?? 0),
                'stock' => (string) $this->queries->productAvailable($record),
                'price' => $this->formatting->money((float) $record->prb_pre),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_prenda),
            'editHref' => $this->definitions->rowEditHref($definition, $record->id_prenda),
        ])->all();
    }

    private function variantRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_prenda_variante,
            'primary' => $record->sku_prv ?: 'Variante #' . $record->id_prenda_variante,
            'secondary' => $record->prenda?->nom_pre ?? 'Sin prenda',
            'status' => $this->formatting->booleanStatus($record->est_prv, 'Activa', 'Pausada'),
            'cells' => [
                'product' => $record->prenda?->nom_pre ?? 'Sin prenda',
                'color' => $record->color?->nom_col ?? 'Sin color',
                'size' => $record->talla?->nom_tal ?? 'Sin talla',
                'stock' => (string) $this->formatting->available($record->inventario),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_prenda_variante),
            'editHref' => $this->definitions->rowEditHref($definition, $record->id_prenda_variante),
        ])->all();
    }

    private function imageRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_imagen_prenda,
            'primary' => $record->rut_igp ?: ($record->url_igp ?: 'Imagen #' . $record->id_imagen_prenda),
            'secondary' => $record->prenda?->nom_pre ?? 'Sin prenda',
            'status' => $record->pri_igp
                ? ['label' => 'Principal', 'tone' => 'accent']
                : ['label' => 'Secundaria', 'tone' => 'neutral'],
            'cells' => [
                'product' => $record->prenda?->nom_pre ?? 'Sin prenda',
                'variant' => $record->variante?->sku_prv ?? 'Sin variante',
                'priority' => $record->pri_igp ? 'Alta' : 'Media',
                'source' => $record->fue_igp ?: 'Biblioteca',
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_imagen_prenda),
            'editHref' => $this->definitions->rowEditHref($definition, $record->id_imagen_prenda),
        ])->all();
    }

    private function inventoryRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $available = $this->inventoryStatus->available($record);
            $reserved = $this->inventoryStatus->reserved($record);

            return [
                'id' => $record->id_inventario,
                'primary' => $record->variante?->prenda?->nom_pre ?? 'Inventario #' . $record->id_inventario,
                'secondary' => trim(implode(' - ', array_filter([$record->variante?->color?->nom_col, $record->variante?->talla?->nom_tal]))),
                'status' => $this->inventoryStatus->status($record),
                'cells' => [
                    'product' => $record->variante?->prenda?->nom_pre ?? 'Sin prenda',
                    'variant' => trim(implode(' / ', array_filter([$record->variante?->color?->nom_col, $record->variante?->talla?->nom_tal]))) ?: 'Sin variante',
                    'color' => $record->variante?->color?->nom_col ?? 'Sin color',
                    'size' => $record->variante?->talla?->nom_tal ?? 'Sin talla',
                    'sku' => $record->variante?->sku_prv ?? 'Sin codigo interno',
                    'available' => (string) $available,
                    'reserved' => (string) $reserved,
                    'total' => (string) $this->inventoryStatus->total($record),
                    'location' => $record->ubi_inv ?: 'Sin ubicacion',
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_inventario),
                'editHref' => $this->definitions->rowEditHref($definition, $record->id_inventario),
            ];
        })->all();
    }
    private function adjustmentRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record['id'],
            'primary' => $record['primary'],
            'secondary' => $record['secondary'],
            'status' => $record['status'],
            'cells' => $record['cells'],
            'showHref' => $this->definitions->showHref($definition, $record['id']),
            'editHref' => null,
        ])->all();
    }

    private function alertRows(array $definition, Collection $records, ?Request $request = null): array
    {
        $inventoryRows = $records->map(function ($record) use ($definition) {
            $available = $this->inventoryStatus->available($record);
            $reserved = $this->inventoryStatus->reserved($record);

            return [
                'id' => $record->id_inventario,
                'primary' => $record->variante?->prenda?->nom_pre ?? 'Alerta #' . $record->id_inventario,
                'secondary' => trim(implode(' - ', array_filter([$record->variante?->color?->nom_col, $record->variante?->talla?->nom_tal]))),
                'status' => $this->inventoryStatus->status($record),
                'cells' => [
                    'type' => $available === 0 ? 'Sin stock' : 'Bajo stock',
                    'sku' => $record->variante?->sku_prv ?? 'Sin codigo interno',
                    'available' => (string) $available,
                    'reserved' => (string) $reserved,
                    'location' => $record->ubi_inv ?: 'Sin ubicacion',
                    'follow' => $available === 0 ? 'Reposicion' : 'Vigilar',
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_inventario),
                'editHref' => null,
            ];
        });

        $variantsWithoutInventory = PrendaVariante::query()
            ->with(['prenda', 'color', 'talla'])
            ->doesntHave('inventario')
            ->get()
            ->filter(fn ($variant) => $this->variantMatchesAlertFilters($variant, $request))
            ->map(fn ($variant) => [
                'id' => 'variant-' . $variant->id_prenda_variante,
                'primary' => $variant->prenda?->nom_pre ?? 'Variante sin inventario',
                'secondary' => trim(implode(' - ', array_filter([$variant->color?->nom_col, $variant->talla?->nom_tal]))),
                'status' => ['label' => 'Sin inventario', 'tone' => 'accent'],
                'cells' => [
                    'type' => 'Variante sin inventario',
                    'sku' => $variant->sku_prv ?: 'Sin codigo interno',
                    'available' => '0',
                    'reserved' => '0',
                    'location' => 'Sin ubicacion',
                    'follow' => 'Crear linea',
                ],
                'showHref' => null,
                'editHref' => null,
            ]);

        $productsWithoutImages = Prenda::query()
            ->with(['marca', 'categoria'])
            ->doesntHave('imagenes')
            ->get()
            ->filter(fn ($product) => $this->productMatchesAlertFilters($product, $request))
            ->map(fn ($product) => [
                'id' => 'product-image-' . $product->id_prenda,
                'primary' => $product->nom_pre ?? 'Prenda sin imagen',
                'secondary' => trim(implode(' - ', array_filter([$product->marca?->nom_mar, $product->categoria?->nom_cat]))),
                'status' => ['label' => 'Sin imagen', 'tone' => 'neutral'],
                'cells' => [
                    'type' => 'Prenda sin imagen',
                    'sku' => 'No aplica',
                    'available' => '-',
                    'reserved' => '-',
                    'location' => 'No aplica',
                    'follow' => 'Completar ficha',
                ],
                'showHref' => null,
                'editHref' => null,
            ]);

        return $inventoryRows
            ->merge($variantsWithoutInventory)
            ->merge($productsWithoutImages)
            ->values()
            ->all();
    }

    private function variantMatchesAlertFilters(PrendaVariante $variant, ?Request $request): bool
    {
        if (!$request) {
            return true;
        }

        return (blank($request->input('category')) || (string) $variant->prenda?->cod_categorias_pre === (string) $request->input('category'))
            && (blank($request->input('brand')) || (string) $variant->prenda?->cod_marcas_pre === (string) $request->input('brand'))
            && (blank($request->input('season')) || (string) $variant->prenda?->cod_temporadas_pre === (string) $request->input('season'))
            && (blank($request->input('gender')) || (string) $variant->prenda?->gen_pre === (string) $request->input('gender'))
            && (blank($request->input('color')) || (string) $variant->cod_colores_prv === (string) $request->input('color'))
            && (blank($request->input('size')) || (string) $variant->cod_tallas_prv === (string) $request->input('size'))
            && blank($request->input('location'))
            && (blank($request->input('stock')) || $request->input('stock') === 'agotado');
    }

    private function productMatchesAlertFilters(Prenda $product, ?Request $request): bool
    {
        if (!$request) {
            return true;
        }

        return (blank($request->input('category')) || (string) $product->cod_categorias_pre === (string) $request->input('category'))
            && (blank($request->input('brand')) || (string) $product->cod_marcas_pre === (string) $request->input('brand'))
            && (blank($request->input('season')) || (string) $product->cod_temporadas_pre === (string) $request->input('season'))
            && (blank($request->input('gender')) || (string) $product->gen_pre === (string) $request->input('gender'))
            && blank($request->input('color'))
            && blank($request->input('size'))
            && blank($request->input('location'))
            && blank($request->input('stock'));
    }
    private function saleRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_venta,
            'primary' => $record->nve_ven ?: 'Venta #' . $record->id_venta,
            'secondary' => collect($record->detalles ?? [])->map(fn ($detail) => $detail->variante?->prenda?->nom_pre)->filter()->unique()->take(2)->implode(', ') ?: 'Sin prendas',
            'status' => ['label' => $this->saleStatusLabel($record->est_ven), 'tone' => $this->saleStatusTone($record->est_ven)],
            'cells' => [
                'client' => $this->formatting->userName($record->cliente) ?: 'Sin cliente',
                'total' => $this->formatting->money((float) $record->tot_ven),
                'state' => ['label' => $this->saleStatusLabel($record->est_ven), 'tone' => $this->saleStatusTone($record->est_ven)],
                'pickup' => ['label' => $this->salePickupStatusLabel($record->est_logistico_ven), 'tone' => $this->salePickupStatusTone($record->est_logistico_ven)],
                'date' => $this->formatting->formatDate($record->fec_ven),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_venta),
            'editHref' => null,
            'extraActions' => $record->est_ven === 'registrada'
                ? [[
                    'label' => 'Recibo',
                    'href' => route('superadmin.receipts.sales.show', ['venta' => $record->id_venta]),
                ]]
                : [],
        ])->all();
    }

    private function saleStatusLabel(?string $status): string
    {
        return match ($status) {
            'en_revision' => 'En revisión',
            'registrada' => 'Venta confirmada',
            'cancelada' => 'Venta cancelada',
            'incompleta' => 'Venta incompleta',
            default => 'En seguimiento',
        };
    }

    private function saleStatusTone(?string $status): string
    {
        return match ($status) {
            'en_revision' => 'warning',
            'registrada' => 'success',
            'cancelada' => 'danger',
            'incompleta' => 'danger',
            default => 'neutral',
        };
    }

    private function salePickupStatusLabel(?string $status): string
    {
        return match ($status) {
            'pendiente_revision' => 'Pendiente de revisión',
            'pendiente_preparacion' => 'Pendiente de preparación',
            'pendiente_traslado' => 'Pendiente de traslado',
            'lista_recojo' => 'Lista para recojo',
            'entregada' => 'Entregada',
            'cancelada' => 'Cancelada',
            default => 'Sin seguimiento',
        };
    }

    private function salePickupStatusTone(?string $status): string
    {
        return match ($status) {
            'lista_recojo' => 'success',
            'entregada' => 'accent',
            'pendiente_traslado', 'pendiente_preparacion' => 'warning',
            'cancelada' => 'danger',
            'pendiente_revision' => 'info',
            default => 'neutral',
        };
    }

    private function reservationRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $isExpired = in_array($record->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)
                && $record->fec_limite_recojo_res
                && $record->fec_limite_recojo_res->lt(Carbon::now());
            $paymentTone = match ($record->est_pago_res) {
                'pagado_total' => 'success',
                'anticipo_confirmado' => 'accent',
                'rechazado' => 'danger',
                default => 'warning',
            };
            $generatedSale = $this->saleForReservation($record);
            $isCompletedLike = in_array($record->est_res, ['completada', 'recogida'], true);
            $hasCompletedInconsistency = $isCompletedLike
                && ((float) ($record->saldo_res ?? 0) > 0 || ($record->est_pago_res ?? null) !== 'pagado_total' || !$generatedSale);
            $statusLabel = $isExpired
                ? 'Vencida'
                : ($hasCompletedInconsistency ? 'Recogida con saldo pendiente' : $this->reservationStatusLabel($record->est_res));
            $paymentLabel = $this->reservationPaymentLabel($record->est_pago_res);
            $latestPayment = $record->pagos?->sortByDesc('id_reserva_pago')->first();

            return [
                'id' => $record->id_reserva,
                'primary' => $record->nre_res ?: 'Reserva #' . $record->id_reserva,
                'secondary' => $this->formatting->userName($record->cliente) ?: 'Sin cliente',
                'status' => $isExpired
                    ? ['label' => 'Vencida', 'tone' => 'danger']
                    : ['label' => $statusLabel, 'tone' => $hasCompletedInconsistency || $record->est_res === 'rechazada' || $record->est_res === 'cancelada' ? 'danger' : 'success'],
                'cells' => [
                    'client' => $this->formatting->userName($record->cliente) ?: 'Sin cliente',
                    'total' => $this->formatting->money((float) $record->tot_res),
                    'declared' => $latestPayment ? $this->formatting->money((float) $latestPayment->monto_declarado_rpg) : 'Bs 0.00',
                    'confirmed' => $this->formatting->money((float) $record->monto_pagado_res),
                    'balance' => $this->formatting->money((float) $record->saldo_res),
                    'payment' => ['label' => $paymentLabel, 'tone' => $paymentTone],
                    'reservation' => ['label' => $statusLabel, 'tone' => ($isExpired || $hasCompletedInconsistency) ? 'danger' : ($record->est_res === 'rechazada' || $record->est_res === 'cancelada' ? 'danger' : 'accent')],
                    'pickup' => $this->formatting->formatDate($record->fec_disponible_recojo_res),
                    'pickup_limit' => $this->formatting->formatDate($record->fec_limite_recojo_res),
                    'items' => (string) ($record->detalles_count ?? $record->detalles?->count() ?? 0),
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_reserva),
                'editHref' => null,
            ];
        })->all();
    }

    private function reservationStatusLabel(?string $status): string
    {
        return match ($status) {
            'por_procesar' => 'En revision',
            'confirmada' => 'Confirmada',
            'lista_recojo' => 'Lista para recojo',
            'completada', 'recogida' => 'Reserva y compra completada',
            'cancelada' => 'Cancelada',
            'rechazada' => 'Rechazada',
            'vencida' => 'Vencida',
            default => 'En seguimiento',
        };
    }

    private function saleForReservation($reservation): ?Venta
    {
        if (!$reservation?->nre_res) {
            return null;
        }

        $reservationNumber = preg_replace('/[^A-Za-z0-9-]/', '', (string) $reservation->nre_res);
        $saleNumber = $reservationNumber ? substr('VEN-RES-' . $reservationNumber, 0, 50) : null;

        return $saleNumber ? Venta::query()->where('nve_ven', $saleNumber)->first() : null;
    }

    private function reservationPaymentLabel(?string $status): string
    {
        return match ($status) {
            'pendiente_revision' => 'En revision',
            'anticipo_confirmado' => 'Adelanto confirmado',
            'pagado_total' => 'Pagado completamente',
            'rechazado' => 'Rechazado',
            default => 'En revision',
        };
    }

    private function favoriteRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_favorito,
            'primary' => $record->prenda?->nom_pre ?? 'Favorito #' . $record->id_favorito,
            'secondary' => $this->formatting->userName($record->usuario) ?: 'Sin usuario',
            'status' => ['label' => 'Interes', 'tone' => 'accent'],
            'cells' => [
                'client' => $this->formatting->userName($record->usuario) ?: 'Sin cliente',
                'brand' => $record->prenda?->marca?->nom_mar ?? 'Sin marca',
                'category' => $record->prenda?->categoria?->nom_cat ?? 'Sin categoría',
                'date' => $this->formatting->formatDate($record->fec_fav),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_favorito),
            'editHref' => null,
        ])->all();
    }


}
