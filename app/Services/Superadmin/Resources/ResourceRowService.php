<?php

namespace App\Services\Superadmin\Resources;

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
            'alerts' => $this->alertRows($definition, $this->queries->indexRecords($definition, $request)),
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
            $available = $this->formatting->available($record);

            return [
                'id' => $record->id_inventario,
                'primary' => $record->variante?->prenda?->nom_pre ?? 'Inventario #' . $record->id_inventario,
                'secondary' => trim(implode(' · ', array_filter([$record->variante?->color?->nom_col, $record->variante?->talla?->nom_tal]))),
                'status' => $available <= 0
                    ? ['label' => 'Sin existencias', 'tone' => 'danger']
                    : ($available <= 2 ? ['label' => 'Corto', 'tone' => 'warning'] : ['label' => 'Estable', 'tone' => 'success']),
                'cells' => [
                    'sku' => $record->variante?->sku_prv ?? 'Sin código interno',
                    'available' => (string) $available,
                    'reserved' => (string) ($record->cre_inv ?? 0),
                    'location' => $record->ubi_inv ?: 'Sin ubicación',
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

    private function alertRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $available = $this->formatting->available($record);

            return [
                'id' => $record->id_inventario,
                'primary' => $record->variante?->prenda?->nom_pre ?? 'Alerta #' . $record->id_inventario,
                'secondary' => trim(implode(' · ', array_filter([$record->variante?->color?->nom_col, $record->variante?->talla?->nom_tal]))),
                'status' => $available <= 0
                    ? ['label' => 'Urgente', 'tone' => 'danger']
                    : ['label' => 'Seguimiento', 'tone' => 'warning'],
                'cells' => [
                    'available' => (string) $available,
                    'reserved' => (string) ($record->cre_inv ?? 0),
                    'location' => $record->ubi_inv ?: 'Sin ubicación',
                    'follow' => $available <= 0 ? 'Reposición' : 'Vigilar',
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_inventario),
                'editHref' => null,
            ];
        })->all();
    }

    private function saleRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_venta,
            'primary' => $record->nve_ven ?: 'Venta #' . $record->id_venta,
            'secondary' => $record->mpa_ven ?: 'Método sin definir',
            'status' => $this->formatting->textStatus($record->est_ven, 'Activa'),
            'cells' => [
                'client' => $this->formatting->userName($record->cliente) ?: 'Sin cliente',
                'type' => $record->tiv_ven ?: 'Sin tipo',
                'total' => $this->formatting->money((float) $record->tot_ven),
                'date' => $this->formatting->formatDate($record->fec_ven),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_venta),
            'editHref' => null,
        ])->all();
    }

    private function reservationRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_reserva,
            'primary' => $record->nre_res ?: 'Reserva #' . $record->id_reserva,
            'secondary' => $this->formatting->userName($record->responsable) ?: 'Sin responsable',
            'status' => $this->formatting->textStatus($record->est_res, 'Seguimiento'),
            'cells' => [
                'client' => $this->formatting->userName($record->cliente) ?: 'Sin cliente',
                'due' => $this->formatting->formatDate($record->ven_res),
                'items' => (string) ($record->detalles_count ?? $record->detalles?->count() ?? 0),
                'date' => $this->formatting->formatDate($record->fec_res),
            ],
            'showHref' => $this->definitions->showHref($definition, $record->id_reserva),
            'editHref' => null,
        ])->all();
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
