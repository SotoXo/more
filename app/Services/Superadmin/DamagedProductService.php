<?php

namespace App\Services\Superadmin;

use App\Models\Inventario;
use App\Models\PrendaDanioImagen;
use App\Models\PrendaDanioReporte;
use App\Models\PrendaVariante;
use App\Models\Prenda;
use App\Models\User;
use App\Services\InventoryMovementService;
use App\Services\SystemLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DamagedProductService
{
    public const ESTADOS = [
        'reportado' => 'Reportado',
        'en_revision' => 'En revisión',
        'confirmado' => 'Confirmado',
        'descartado' => 'Descartado',
        'reparado' => 'Reparado',
        'baja_definitiva' => 'Baja definitiva',
        'resuelto' => 'Resuelto',
    ];

    public const CAUSAS = [
        'dano_en_tienda' => 'Daño en tienda',
        'dano_en_transporte' => 'Daño en transporte',
        'dano_por_manipulacion' => 'Daño por manipulación',
        'defecto_de_fabrica' => 'Defecto de fábrica',
        'devolucion_danada' => 'Devolución dañada',
        'desgaste' => 'Desgaste',
        'mala_conservacion' => 'Mala conservación',
        'otro' => 'Otro',
    ];

    public const TIPOS_DANIO = [
        'mancha' => 'Mancha',
        'roto' => 'Roto',
        'cierre_danado' => 'Cierre dañado',
        'boton_faltante' => 'Botón faltante',
        'costura_abierta' => 'Costura abierta',
        'decoloracion' => 'Decoloración',
        'talla_mal_etiquetada' => 'Talla mal etiquetada',
        'defecto_fabrica' => 'Defecto de fábrica',
        'otro' => 'Otro',
    ];

    public const SEVERIDADES = [
        'leve' => 'Leve',
        'media' => 'Media',
        'grave' => 'Grave',
    ];

    public const DECISIONES = [
        'reparar' => 'Reparar',
        'vender_con_descuento' => 'Vender con descuento',
        'retirar_de_venta' => 'Retirar de venta',
        'baja_definitiva' => 'Baja definitiva',
        'descartar_reporte' => 'Descartar reporte',
        'mantener_en_observacion' => 'Mantener en observación',
    ];

    public function __construct(
        private readonly InventoryMovementService $movements,
        private readonly SystemLogService $logs,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $query = $this->filteredQuery($request)
            ->with([
                'usuarioReporta.rol',
                'usuarioRevisa.rol',
                'inventario',
                'prenda.imagenes',
                'variante.imagenes',
                'variante.prenda.imagenes',
                'variante.color',
                'variante.talla',
            ]);

        $this->applySorting($query, $request);

        $reports = $query
            ->paginate(15)
            ->withQueryString();

        return [
            'meta' => [
                'sectionLabel' => 'Inventario',
                'title' => 'Prendas dañadas',
                'description' => 'Control interno de calidad y pérdidas por prendas dañadas, independiente del registro de prendas.',
                'indexHref' => '/superadmin/prendas-daniadas',
                'createHref' => '/superadmin/prendas-daniadas/crear',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => 'Inventario'],
                ['label' => 'Prendas dañadas'],
            ],
            'metrics' => $this->metrics($request),
            'columns' => $this->columns(),
            'rows' => $reports->getCollection()
                ->map(fn (PrendaDanioReporte $report) => $this->serializeRow($report))
                ->values()
                ->all(),
            'pagination' => [
                'links' => $reports->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $reports->currentPage(),
                    'from' => $reports->firstItem(),
                    'last_page' => $reports->lastPage(),
                    'path' => $reports->path(),
                    'per_page' => $reports->perPage(),
                    'to' => $reports->lastItem(),
                    'total' => $reports->total(),
                ],
            ],
            'filterAction' => '/superadmin/prendas-daniadas',
            'filterControls' => $this->filterControls($request),
            'sortOptions' => $this->sortOptions(),
            'sortBy' => $request->string('sort_by')->toString(),
            'sortDirection' => $request->input('sort_dir', 'desc'),
            'appliedFilters' => $this->appliedFilters($request),
        ];
    }

    public function createPayload(): array
    {
        return [
            'meta' => [
                'sectionLabel' => 'Inventario',
                'title' => 'Registrar prenda dañada',
                'description' => 'Crea un reporte independiente. La creación no mueve stock ni registra Kardex.',
                'indexHref' => '/superadmin/prendas-daniadas',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => 'Prendas dañadas', 'href' => '/superadmin/prendas-daniadas'],
                ['label' => 'Registrar'],
            ],
            'options' => [
                'causes' => $this->optionsFrom(self::CAUSAS),
                'damageTypes' => $this->optionsFrom(self::TIPOS_DANIO),
                'severities' => $this->optionsFrom(self::SEVERIDADES),
                'inventories' => $this->inventoryOptions(),
            ],
            'submitAction' => '/superadmin/prendas-daniadas',
        ];
    }

    public function showPayload(PrendaDanioReporte $report): array
    {
        $report->loadMissing([
            'prenda.imagenes',
            'variante.imagenes',
            'variante.prenda.imagenes',
            'variante.color',
            'variante.talla',
            'inventario.variante.prenda',
            'inventario.variante.imagenes',
            'inventario.variante.prenda.imagenes',
            'inventario.variante.color',
            'inventario.variante.talla',
            'usuarioReporta.rol',
            'usuarioRevisa.rol',
            'imagenes',
        ]);

        return [
            'meta' => [
                'sectionLabel' => 'Inventario',
                'title' => $report->codigo_pdr,
                'description' => 'Detalle del reporte de daño, evidencia, vínculo opcional con inventario y acciones de revisión.',
                'indexHref' => '/superadmin/prendas-daniadas',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => 'Prendas dañadas', 'href' => '/superadmin/prendas-daniadas'],
                ['label' => $report->codigo_pdr],
            ],
            'report' => $this->serializeDetail($report),
            'decisionOptions' => $this->optionsFrom(self::DECISIONES),
            'actions' => $this->availableActions($report),
        ];
    }

    public function createReport(Request $request): PrendaDanioReporte
    {
        $validated = $this->validateCreation($request);
        $inventory = $this->inventoryFromPayload($validated);
        $variant = $this->variantFromPayload($validated, $inventory);
        $createdPaths = [];

        try {
            return DB::transaction(function () use ($request, $validated, $inventory, $variant, &$createdPaths) {
                $report = PrendaDanioReporte::query()->create([
                    'codigo_pdr' => $this->nextCode(),
                    'nombre_prenda_pdr' => trim((string) $validated['nombre_prenda_pdr']),
                    'descripcion_prenda_pdr' => $this->nullableText($validated['descripcion_prenda_pdr'] ?? null),
                    'cod_prenda_pdr' => $inventory?->variante?->prenda?->id_prenda
                        ?? $variant?->prenda?->id_prenda
                        ?? $this->nullableInt($validated['cod_prenda_pdr'] ?? null),
                    'cod_prenda_variante_pdr' => $inventory?->cod_prenda_variantes_inv
                        ?? $variant?->id_prenda_variante
                        ?? null,
                    'cod_inventario_pdr' => $inventory?->id_inventario,
                    'cod_usuario_reporta_pdr' => Auth::id(),
                    'causa_pdr' => (string) $validated['causa_pdr'],
                    'tipo_danio_pdr' => (string) $validated['tipo_danio_pdr'],
                    'severidad_pdr' => (string) $validated['severidad_pdr'],
                    'cantidad_pdr' => (int) $validated['cantidad_pdr'],
                    'estado_pdr' => 'reportado',
                    'observacion_pdr' => $this->nullableText($validated['observacion_pdr'] ?? null),
                    'fecha_reporte_pdr' => Carbon::now(config('app.timezone')),
                    'afecta_inventario_pdr' => $request->boolean('afecta_inventario_pdr') && $inventory !== null,
                    'metadata_pdr' => [
                        'origen' => 'modulo_independiente',
                        'creado_desde' => 'superadmin_prendas_daniadas',
                    ],
                ]);

                foreach ($request->file('imagenes', []) as $index => $file) {
                    $path = $this->storeEvidence($file, $report, $index + 1);
                    $createdPaths[] = $path;

                    PrendaDanioImagen::query()->create([
                        'cod_prenda_danio_reporte_pdi' => $report->id,
                        'ruta_pdi' => $path,
                        'url_pdi' => Storage::url($path),
                        'descripcion_pdi' => 'Evidencia ' . ($index + 1),
                        'orden_pdi' => $index + 1,
                    ]);
                }

                $report->loadMissing(['inventario', 'variante.prenda']);

                $this->logs->info(
                    'prendas_daniadas',
                    'reporte_danio_creado',
                    'Reporte de prenda dañada creado: ' . $report->codigo_pdr . '.',
                    $report,
                    $this->reportMetadata($report),
                );

                return $report;
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    public function createVendorReport(Request $request, User $reporter): PrendaDanioReporte
    {
        $request->merge(['afecta_inventario_pdr' => '0']);

        $validated = $this->validateCreation($request);
        $inventory = $this->inventoryFromPayload($validated);
        $variant = $this->variantFromPayload($validated, $inventory);
        $createdPaths = [];

        try {
            return DB::transaction(function () use ($request, $validated, $inventory, $variant, $reporter, &$createdPaths) {
                $report = PrendaDanioReporte::query()->create([
                    'codigo_pdr' => $this->nextCode(),
                    'nombre_prenda_pdr' => trim((string) $validated['nombre_prenda_pdr']),
                    'descripcion_prenda_pdr' => $this->nullableText($validated['descripcion_prenda_pdr'] ?? null),
                    'cod_prenda_pdr' => $inventory?->variante?->prenda?->id_prenda
                        ?? $variant?->prenda?->id_prenda
                        ?? $this->nullableInt($validated['cod_prenda_pdr'] ?? null),
                    'cod_prenda_variante_pdr' => $inventory?->cod_prenda_variantes_inv
                        ?? $variant?->id_prenda_variante
                        ?? null,
                    'cod_inventario_pdr' => $inventory?->id_inventario,
                    'cod_usuario_reporta_pdr' => $reporter->getKey(),
                    'causa_pdr' => (string) $validated['causa_pdr'],
                    'tipo_danio_pdr' => (string) $validated['tipo_danio_pdr'],
                    'severidad_pdr' => (string) $validated['severidad_pdr'],
                    'cantidad_pdr' => (int) $validated['cantidad_pdr'],
                    'estado_pdr' => 'reportado',
                    'observacion_pdr' => $this->nullableText($validated['observacion_pdr'] ?? null),
                    'fecha_reporte_pdr' => Carbon::now(config('app.timezone')),
                    'afecta_inventario_pdr' => false,
                    'metadata_pdr' => [
                        'origen' => 'panel_vendedor',
                        'creado_desde' => 'vendedor_prendas_daniadas',
                    ],
                ]);

                foreach ($request->file('imagenes', []) as $index => $file) {
                    $path = $this->storeEvidence($file, $report, $index + 1);
                    $createdPaths[] = $path;

                    PrendaDanioImagen::query()->create([
                        'cod_prenda_danio_reporte_pdi' => $report->id,
                        'ruta_pdi' => $path,
                        'url_pdi' => Storage::url($path),
                        'descripcion_pdi' => 'Evidencia ' . ($index + 1),
                        'orden_pdi' => $index + 1,
                    ]);
                }

                $report->loadMissing(['inventario', 'variante.prenda']);

                $this->logs->info(
                    'prendas_daniadas',
                    'reporte_danio_creado_por_vendedor',
                    'Reporte de prenda daniada creado por vendedor: ' . $report->codigo_pdr . '.',
                    $report,
                    $this->reportMetadata($report) + [
                        'vendedor_id' => $reporter->getKey(),
                        'vendedor' => $reporter->nombreCompleto(),
                        'tiene_prenda_vinculada' => (bool) $report->cod_inventario_pdr,
                    ],
                );

                return $report;
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    public function markInReview(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['reportado'], 'Solo los reportes en estado reportado pueden pasar a revisión.');

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'en_revision',
            ]));

            $this->logs->info(
                'prendas_daniadas',
                'reporte_danio_en_revision',
                'Reporte de prenda dañada marcado en revisión: ' . $locked->codigo_pdr . '.',
                $locked,
                $this->reportMetadata($locked->fresh()),
            );

            return $locked->fresh();
        });
    }

    public function confirmDamage(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['reportado', 'en_revision'], 'Solo se puede confirmar un reporte pendiente o en revisión.');

            $stockChanged = false;

            if ($locked->afecta_inventario_pdr && $locked->cod_inventario_pdr) {
                $inventory = Inventario::query()
                    ->whereKey($locked->cod_inventario_pdr)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw ValidationException::withMessages([
                        'reporte' => 'El inventario vinculado ya no existe.',
                    ]);
                }

                $quantity = (int) $locked->cantidad_pdr;
                $cdiAntes = (int) $inventory->cdi_inv;
                $creAntes = (int) $inventory->cre_inv;
                $ubicacionAntes = $inventory->ubi_inv;

                if ($quantity > $cdiAntes) {
                    throw ValidationException::withMessages([
                        'reporte' => 'La cantidad dañada supera el stock disponible real.',
                    ]);
                }

                $inventory->update([
                    'cdi_inv' => $cdiAntes - $quantity,
                ]);

                $this->movements->danoConfirmado(
                    $inventory,
                    -$quantity,
                    $cdiAntes,
                    (int) $inventory->cdi_inv,
                    $creAntes,
                    (int) $inventory->cre_inv,
                    'prenda_danio',
                    (int) $locked->id,
                    'Daño confirmado: stock disponible descontado.',
                    [
                        'reporte_codigo' => $locked->codigo_pdr,
                        'ubicacion_antes' => $ubicacionAntes,
                        'ubicacion_despues' => $inventory->ubi_inv,
                    ],
                );

                $stockChanged = true;
            }

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'confirmado',
                'decision_pdr' => $payload['decision_pdr'] ?? 'mantener_en_observacion',
            ]));

            $fresh = $locked->fresh();

            $this->logs->warning(
                'prendas_daniadas',
                'reporte_danio_confirmado',
                'Reporte de prenda dañada confirmado: ' . $fresh->codigo_pdr . '.',
                $fresh,
                $this->reportMetadata($fresh) + ['stock_modificado' => $stockChanged],
            );

            return $fresh;
        });
    }

    public function discardReport(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $request->validate([
            'observacion_revision_pdr' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'observacion_revision_pdr.required' => 'Ingresa el motivo para descartar el reporte.',
            'observacion_revision_pdr.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['reportado', 'en_revision'], 'Solo se puede descartar un reporte pendiente o en revisión.');

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'descartado',
                'decision_pdr' => 'descartar_reporte',
            ]));

            $fresh = $locked->fresh();

            $this->logs->info(
                'prendas_daniadas',
                'reporte_danio_descartado',
                'Reporte de prenda dañada descartado: ' . $fresh->codigo_pdr . '.',
                $fresh,
                $this->reportMetadata($fresh),
            );

            return $fresh;
        });
    }

    public function repairReport(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['confirmado'], 'Solo se puede reparar un reporte confirmado.');

            $stockReturned = false;

            if ($locked->afecta_inventario_pdr && $locked->cod_inventario_pdr) {
                $inventory = Inventario::query()
                    ->whereKey($locked->cod_inventario_pdr)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw ValidationException::withMessages([
                        'reporte' => 'El inventario vinculado ya no existe.',
                    ]);
                }

                $quantity = (int) $locked->cantidad_pdr;
                $cdiAntes = (int) $inventory->cdi_inv;
                $creAntes = (int) $inventory->cre_inv;
                $ubicacionAntes = $inventory->ubi_inv;

                $inventory->update([
                    'cdi_inv' => $cdiAntes + $quantity,
                ]);

                $this->movements->reparacionDanio(
                    $inventory,
                    $quantity,
                    $cdiAntes,
                    (int) $inventory->cdi_inv,
                    $creAntes,
                    (int) $inventory->cre_inv,
                    'prenda_danio',
                    (int) $locked->id,
                    'Reparación de daño: stock disponible devuelto.',
                    [
                        'reporte_codigo' => $locked->codigo_pdr,
                        'ubicacion_antes' => $ubicacionAntes,
                        'ubicacion_despues' => $inventory->ubi_inv,
                    ],
                );

                $stockReturned = true;
            }

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'reparado',
                'decision_pdr' => 'reparar',
            ]));

            $fresh = $locked->fresh();

            $this->logs->info(
                'prendas_daniadas',
                'reporte_danio_reparado',
                'Reporte de prenda dañada reparado: ' . $fresh->codigo_pdr . '.',
                $fresh,
                $this->reportMetadata($fresh) + ['stock_devuelto' => $stockReturned],
            );

            return $fresh;
        });
    }

    public function markDefinitiveLoss(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $request->validate([
            'observacion_revision_pdr' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'observacion_revision_pdr.required' => 'Ingresa el motivo de la baja definitiva.',
            'observacion_revision_pdr.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['confirmado'], 'Solo se puede dar baja definitiva a un reporte confirmado.');

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'baja_definitiva',
                'decision_pdr' => 'baja_definitiva',
            ]));

            $fresh = $locked->fresh();

            $this->logs->warning(
                'prendas_daniadas',
                'reporte_danio_baja_definitiva',
                'Reporte de prenda dañada marcado como baja definitiva: ' . $fresh->codigo_pdr . '.',
                $fresh,
                $this->reportMetadata($fresh) + ['stock_modificado' => false],
            );

            return $fresh;
        });
    }

    public function resolveReport(Request $request, PrendaDanioReporte $report): PrendaDanioReporte
    {
        $payload = $this->validateReviewPayload($request);

        return DB::transaction(function () use ($report, $payload) {
            $locked = $this->lockedReport($report);
            $this->ensureState($locked, ['confirmado', 'reparado', 'baja_definitiva', 'descartado'], 'El reporte debe estar revisado antes de resolverse.');

            $locked->update($this->reviewUpdate($locked, $payload, [
                'estado_pdr' => 'resuelto',
                'decision_pdr' => $payload['decision_pdr'] ?? $locked->decision_pdr ?? 'mantener_en_observacion',
            ]));

            $fresh = $locked->fresh();

            $this->logs->info(
                'prendas_daniadas',
                'reporte_danio_resuelto',
                'Reporte de prenda dañada resuelto: ' . $fresh->codigo_pdr . '.',
                $fresh,
                $this->reportMetadata($fresh),
            );

            return $fresh;
        });
    }

    private function validateCreation(Request $request): array
    {
        $request->merge(['nombre_prenda_pdr' => app(\App\Services\Superadmin\Resources\AdminFormattingService::class)->normalizeNullableText($request->input('nombre_prenda_pdr'))]);

        $validated = $request->validate([
            'nombre_prenda_pdr' => ['required', 'string', 'max:180', \App\Support\DamagedProductName::RULE],
            'descripcion_prenda_pdr' => ['nullable', 'string', 'max:5000'],
            'cod_prenda_pdr' => ['nullable', 'integer', 'exists:prendas,id_prenda'],
            'cod_prenda_variante_pdr' => ['nullable', 'integer', 'exists:prenda_variantes,id_prenda_variante'],
            'cod_inventario_pdr' => ['nullable', 'integer', 'exists:inventarios,id_inventario'],
            'causa_pdr' => ['required', 'string', Rule::in(array_keys(self::CAUSAS))],
            'tipo_danio_pdr' => ['required', 'string', Rule::in(array_keys(self::TIPOS_DANIO))],
            'severidad_pdr' => ['required', 'string', Rule::in(array_keys(self::SEVERIDADES))],
            'cantidad_pdr' => ['required', 'integer', 'min:1'],
            'observacion_pdr' => ['nullable', 'string', 'max:5000'],
            'afecta_inventario_pdr' => ['nullable', 'boolean'],
            'imagenes' => ['nullable', 'array'],
            'imagenes.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->boolean('afecta_inventario_pdr') && empty($validated['cod_inventario_pdr'])) {
            throw ValidationException::withMessages([
                'afecta_inventario_pdr' => 'Para afectar inventario al confirmar debes seleccionar una variante con inventario.',
            ]);
        }

        return $validated;
    }

    private function validateReviewPayload(Request $request): array
    {
        return $request->validate([
            'observacion_revision_pdr' => ['nullable', 'string', 'max:5000'],
            'decision_pdr' => ['nullable', 'string', Rule::in(array_keys(self::DECISIONES))],
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = PrendaDanioReporte::query();

        if ($request->filled('fecha_desde')) {
            $date = $this->dateBoundary((string) $request->input('fecha_desde'), 'start');

            if ($date) {
                $query->where('fecha_reporte_pdr', '>=', $date);
            }
        }

        if ($request->filled('fecha_hasta')) {
            $date = $this->dateBoundary((string) $request->input('fecha_hasta'), 'end');

            if ($date) {
                $query->where('fecha_reporte_pdr', '<=', $date);
            }
        }

        foreach (['estado' => 'estado_pdr', 'causa' => 'causa_pdr', 'tipo_danio' => 'tipo_danio_pdr', 'severidad' => 'severidad_pdr'] as $input => $column) {
            if ($request->filled($input)) {
                $query->where($column, $request->input($input));
            }
        }

        if ($request->filled('buscar')) {
            $term = mb_strtolower(trim((string) $request->input('buscar')), 'UTF-8');
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            $query->where(function (Builder $searchQuery) use ($like) {
                $searchQuery
                    ->whereRaw('LOWER(codigo_pdr) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(nombre_prenda_pdr) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(descripcion_prenda_pdr, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(observacion_pdr, \'\')) LIKE ?', [$like]);
            });
        }

        return $query;
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->string('sort_by')->toString();
        $direction = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $columns = [
            'fecha' => 'fecha_reporte_pdr',
            'nombre' => 'nombre_prenda_pdr',
            'cantidad' => 'cantidad_pdr',
            'estado' => 'estado_pdr',
            'severidad' => 'severidad_pdr',
        ];

        if (array_key_exists($sortBy, $columns)) {
            $query->orderBy($columns[$sortBy], $direction)->orderByDesc('id');
            return;
        }

        $query->orderByDesc('fecha_reporte_pdr')->orderByDesc('id');
    }

    private function metrics(Request $request): array
    {
        $base = $this->filteredQuery($request);

        return [
            [
                'label' => 'Pendientes',
                'value' => (string) (clone $base)->whereIn('estado_pdr', ['reportado', 'en_revision'])->count(),
                'note' => 'Reportes esperando revisión o confirmación.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Confirmados',
                'value' => (string) (clone $base)->where('estado_pdr', 'confirmado')->count(),
                'note' => 'Daños validados por superadmin.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Reparadas',
                'value' => (string) (clone $base)->where('estado_pdr', 'reparado')->count(),
                'note' => 'Casos reparados con devolución de stock cuando aplicó.',
                'tone' => 'success',
            ],
            [
                'label' => 'Bajas definitivas',
                'value' => (string) (clone $base)->where('estado_pdr', 'baja_definitiva')->count(),
                'note' => 'Prendas retiradas definitivamente del control operativo.',
                'tone' => 'danger',
            ],
            [
                'label' => 'Unidades afectadas',
                'value' => (string) (int) (clone $base)->sum('cantidad_pdr'),
                'note' => 'Suma de cantidades reportadas para los filtros actuales.',
                'tone' => 'info',
            ],
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'fecha', 'label' => 'Fecha'],
            ['key' => 'nombre', 'label' => 'Prenda'],
            ['key' => 'causa', 'label' => 'Causa'],
            ['key' => 'tipo', 'label' => 'Tipo'],
            ['key' => 'severidad', 'label' => 'Gravedad'],
            ['key' => 'cantidad', 'label' => 'Cantidad', 'align' => 'right'],
            ['key' => 'afecta_stock', 'label' => 'Afecta stock'],
            ['key' => 'vinculada', 'label' => 'Inventario'],
            ['key' => 'estado', 'label' => 'Estado'],
            ['key' => 'reportado_por', 'label' => 'Reportado por'],
        ];
    }

    private function serializeRow(PrendaDanioReporte $report): array
    {
        return [
            'id' => $report->id,
            'primary' => $report->codigo_pdr,
            'secondary' => Str::limit($report->nombre_prenda_pdr, 80, ''),
            'image' => $this->linkedImageUrl($report->variante, $report->variante?->prenda ?? $report->prenda),
            'status' => [
                'label' => $this->stateLabel($report->estado_pdr),
                'tone' => $this->stateTone($report->estado_pdr),
            ],
            'cells' => [
                'fecha' => $this->formatDate($report->fecha_reporte_pdr, 'd/m/Y H:i'),
                'nombre' => Str::limit($report->nombre_prenda_pdr, 70, ''),
                'causa' => $this->causeLabel($report->causa_pdr),
                'tipo' => $this->damageTypeLabel($report->tipo_danio_pdr),
                'severidad' => ['label' => $this->severityLabel($report->severidad_pdr), 'tone' => $this->severityTone($report->severidad_pdr)],
                'cantidad' => (string) $report->cantidad_pdr,
                'afecta_stock' => ['label' => $report->afecta_inventario_pdr ? 'Sí' : 'No', 'tone' => $report->afecta_inventario_pdr ? 'warning' : 'neutral'],
                'vinculada' => ['label' => $report->cod_inventario_pdr ? 'Sí' : 'No', 'tone' => $report->cod_inventario_pdr ? 'accent' : 'neutral'],
                'estado' => ['label' => $this->stateLabel($report->estado_pdr), 'tone' => $this->stateTone($report->estado_pdr)],
                'reportado_por' => $this->userName($report->usuarioReporta),
            ],
            'showHref' => '/superadmin/prendas-daniadas/' . $report->id,
        ];
    }

    private function serializeDetail(PrendaDanioReporte $report): array
    {
        return [
            'id' => $report->id,
            'code' => $report->codigo_pdr,
            'name' => $report->nombre_prenda_pdr,
            'description' => $report->descripcion_prenda_pdr ?: 'Sin descripción registrada.',
            'cause' => $report->causa_pdr,
            'causeLabel' => $this->causeLabel($report->causa_pdr),
            'damageType' => $report->tipo_danio_pdr,
            'damageTypeLabel' => $this->damageTypeLabel($report->tipo_danio_pdr),
            'severity' => $report->severidad_pdr,
            'severityLabel' => $this->severityLabel($report->severidad_pdr),
            'severityTone' => $this->severityTone($report->severidad_pdr),
            'quantity' => (int) $report->cantidad_pdr,
            'status' => $report->estado_pdr,
            'statusLabel' => $this->stateLabel($report->estado_pdr),
            'statusTone' => $this->stateTone($report->estado_pdr),
            'decision' => $report->decision_pdr,
            'decisionLabel' => $report->decision_pdr ? $this->decisionLabel($report->decision_pdr) : 'Sin decisión registrada',
            'observation' => $report->observacion_pdr ?: 'Sin observaciones.',
            'reviewObservation' => $report->observacion_revision_pdr ?: 'Sin observación de revisión.',
            'reportedAt' => $this->formatDate($report->fecha_reporte_pdr, 'd/m/Y H:i:s'),
            'reviewedAt' => $this->formatDate($report->fecha_revision_pdr, 'd/m/Y H:i:s'),
            'affectsInventory' => (bool) $report->afecta_inventario_pdr,
            'inventoryImpactMessage' => $this->inventoryImpactMessage($report),
            'stockActionStatus' => $this->stockActionStatus($report),
            'reportedBy' => $this->userName($report->usuarioReporta),
            'reviewedBy' => $this->userName($report->usuarioRevisa),
            'inventory' => $this->inventorySnapshot($report),
            'images' => $report->imagenes->map(fn (PrendaDanioImagen $image) => [
                'id' => $image->id,
                'url' => $image->url_pdi,
                'path' => $image->ruta_pdi,
                'description' => $image->descripcion_pdi ?: 'Evidencia',
                'order' => (int) $image->orden_pdi,
            ])->values()->all(),
        ];
    }

    private function filterControls(Request $request): array
    {
        return [
            ['key' => 'fecha_desde', 'label' => 'Fecha desde', 'type' => 'date', 'value' => $request->string('fecha_desde')->toString()],
            ['key' => 'fecha_hasta', 'label' => 'Fecha hasta', 'type' => 'date', 'value' => $request->string('fecha_hasta')->toString()],
            ['key' => 'estado', 'label' => 'Estado', 'type' => 'select', 'value' => $request->string('estado')->toString(), 'options' => $this->optionsFrom(self::ESTADOS)],
            ['key' => 'causa', 'label' => 'Causa', 'type' => 'select', 'value' => $request->string('causa')->toString(), 'options' => $this->optionsFrom(self::CAUSAS)],
            ['key' => 'tipo_danio', 'label' => 'Tipo de daño', 'type' => 'select', 'value' => $request->string('tipo_danio')->toString(), 'options' => $this->optionsFrom(self::TIPOS_DANIO)],
            ['key' => 'severidad', 'label' => 'Gravedad', 'type' => 'select', 'value' => $request->string('severidad')->toString(), 'options' => $this->optionsFrom(self::SEVERIDADES)],
            ['key' => 'buscar', 'label' => 'Buscar', 'type' => 'text', 'value' => $request->string('buscar')->toString(), 'placeholder' => 'Código, nombre o descripción'],
        ];
    }

    private function sortOptions(): array
    {
        return [
            ['label' => 'Fecha', 'value' => 'fecha'],
            ['label' => 'Nombre', 'value' => 'nombre'],
            ['label' => 'Cantidad', 'value' => 'cantidad'],
            ['label' => 'Estado', 'value' => 'estado'],
            ['label' => 'Gravedad', 'value' => 'severidad'],
        ];
    }

    private function appliedFilters(Request $request): array
    {
        $labels = [
            'fecha_desde' => 'Fecha desde',
            'fecha_hasta' => 'Fecha hasta',
            'estado' => 'Estado',
            'causa' => 'Causa',
            'tipo_danio' => 'Tipo',
            'severidad' => 'Gravedad',
            'buscar' => 'Buscar',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $key) => $request->filled($key))
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => match ($key) {
                    'estado' => $this->stateLabel((string) $request->input($key)),
                    'causa' => $this->causeLabel((string) $request->input($key)),
                    'tipo_danio' => $this->damageTypeLabel((string) $request->input($key)),
                    'severidad' => $this->severityLabel((string) $request->input($key)),
                    default => (string) $request->input($key),
                },
            ])
            ->values()
            ->all();
    }

    private function availableActions(PrendaDanioReporte $report): array
    {
        $actions = [
            'reportado' => [
                ['key' => 'en_revision', 'label' => 'Marcar en revisión', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/en-revision', 'tone' => 'info', 'confirm' => '¿Marcar este reporte en revisión?'],
                ['key' => 'confirmar', 'label' => 'Confirmar daño', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/confirmar', 'tone' => 'warning', 'confirm' => '¿Confirmar el daño? Si afecta inventario, se descontará cdi_inv.'],
                ['key' => 'descartar', 'label' => 'Descartar', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/descartar', 'tone' => 'neutral', 'confirm' => '¿Descartar este reporte?'],
            ],
            'en_revision' => [
                ['key' => 'confirmar', 'label' => 'Confirmar daño', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/confirmar', 'tone' => 'warning', 'confirm' => '¿Confirmar el daño? Si afecta inventario, se descontará cdi_inv.'],
                ['key' => 'descartar', 'label' => 'Descartar', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/descartar', 'tone' => 'neutral', 'confirm' => '¿Descartar este reporte?'],
            ],
            'confirmado' => [
                ['key' => 'reparar', 'label' => 'Reparar', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/reparar', 'tone' => 'success', 'confirm' => '¿Marcar como reparado? Si afectó inventario, se devolverá cdi_inv.'],
                ['key' => 'baja_definitiva', 'label' => 'Baja definitiva', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/baja-definitiva', 'tone' => 'danger', 'confirm' => '¿Marcar como baja definitiva? No se descontará stock nuevamente.'],
                ['key' => 'resolver', 'label' => 'Resolver', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/resolver', 'tone' => 'accent', 'confirm' => '¿Resolver este reporte?'],
            ],
            'reparado' => [
                ['key' => 'resolver', 'label' => 'Resolver', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/resolver', 'tone' => 'accent', 'confirm' => '¿Resolver este reporte reparado?'],
            ],
            'baja_definitiva' => [
                ['key' => 'resolver', 'label' => 'Resolver', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/resolver', 'tone' => 'accent', 'confirm' => '¿Resolver este reporte con baja definitiva?'],
            ],
            'descartado' => [
                ['key' => 'resolver', 'label' => 'Resolver', 'url' => '/superadmin/prendas-daniadas/' . $report->id . '/resolver', 'tone' => 'accent', 'confirm' => '¿Resolver este reporte descartado?'],
            ],
        ];

        return $actions[$report->estado_pdr] ?? [];
    }

    private function inventoryOptions(): array
    {
        return Inventario::query()
            ->with(['variante.imagenes', 'variante.prenda.imagenes', 'variante.color', 'variante.talla'])
            ->orderBy('id_inventario')
            ->get()
            ->map(function (Inventario $inventory) {
                $variant = $inventory->variante;
                $product = $variant?->prenda;
                $label = trim(implode(' - ', array_filter([
                    $product?->nom_pre,
                    $variant?->sku_prv,
                    $variant?->color?->nom_col,
                    $variant?->talla?->nom_tal,
                ]))) ?: 'Inventario #' . $inventory->id_inventario;

                return [
                    'label' => $label . ' - Stock ' . (int) $inventory->cdi_inv,
                    'value' => (string) $inventory->id_inventario,
                    'inventoryId' => (int) $inventory->id_inventario,
                    'variantId' => $variant?->id_prenda_variante,
                    'productId' => $product?->id_prenda,
                    'productName' => $product?->nom_pre ?: 'Sin prenda',
                    'product' => $product?->nom_pre ?: 'Sin prenda',
                    'sku' => $variant?->sku_prv ?: 'Sin SKU',
                    'color' => $variant?->color?->nom_col ?: 'Sin color',
                    'size' => $variant?->talla?->nom_tal ?: 'Sin talla',
                    'imageUrl' => $this->linkedImageUrl($variant, $product),
                    'availableStock' => (int) $inventory->cdi_inv,
                    'committedStock' => (int) $inventory->cre_inv,
                    'stock' => (int) $inventory->cdi_inv,
                    'reserved' => (int) $inventory->cre_inv,
                    'location' => $inventory->ubi_inv ?: 'Sin ubicación',
                    'active' => (bool) ($variant?->est_prv ?? false),
                    'statusLabel' => (bool) ($variant?->est_prv ?? false) ? 'Activa' : 'Inactiva',
                ];
            })
            ->values()
            ->all();
    }

    private function inventorySnapshot(PrendaDanioReporte $report): array
    {
        $inventory = $report->inventario;
        $variant = $inventory?->variante ?? $report->variante;
        $product = $variant?->prenda ?? $report->prenda;

        return [
            'linked' => $inventory !== null || $variant !== null || $product !== null,
            'affectsInventory' => (bool) $report->afecta_inventario_pdr,
            'inventoryId' => $inventory?->id_inventario,
            'variantId' => $variant?->id_prenda_variante,
            'productId' => $product?->id_prenda,
            'product' => $product?->nom_pre ?: 'Sin prenda vinculada',
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?: 'Sin color',
            'size' => $variant?->talla?->nom_tal ?: 'Sin talla',
            'imageUrl' => $this->linkedImageUrl($variant, $product),
            'stock' => $inventory ? (int) $inventory->cdi_inv : null,
            'reserved' => $inventory ? (int) $inventory->cre_inv : null,
            'availableStock' => $inventory ? (int) $inventory->cdi_inv : null,
            'committedStock' => $inventory ? (int) $inventory->cre_inv : null,
            'location' => $inventory?->ubi_inv ?: 'Sin ubicación',
            'variantStatusLabel' => (bool) ($variant?->est_prv ?? false) ? 'Activa' : ($variant ? 'Inactiva' : 'Sin variante'),
        ];
    }

    private function inventoryFromPayload(array $payload): ?Inventario
    {
        if (empty($payload['cod_inventario_pdr'])) {
            return null;
        }

        return Inventario::query()
            ->with(['variante.prenda', 'variante.color', 'variante.talla'])
            ->whereKey((int) $payload['cod_inventario_pdr'])
            ->first();
    }

    private function variantFromPayload(array $payload, ?Inventario $inventory): ?PrendaVariante
    {
        if ($inventory?->variante) {
            return $inventory->variante;
        }

        if (empty($payload['cod_prenda_variante_pdr'])) {
            return null;
        }

        return PrendaVariante::query()
            ->with(['prenda', 'color', 'talla', 'inventario'])
            ->whereKey((int) $payload['cod_prenda_variante_pdr'])
            ->first();
    }

    private function lockedReport(PrendaDanioReporte $report): PrendaDanioReporte
    {
        return PrendaDanioReporte::query()
            ->whereKey($report->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureState(PrendaDanioReporte $report, array $allowed, string $message): void
    {
        if (!in_array($report->estado_pdr, $allowed, true)) {
            throw ValidationException::withMessages([
                'reporte' => $message,
            ]);
        }
    }

    private function reviewUpdate(PrendaDanioReporte $report, array $payload, array $values): array
    {
        $nextObservation = $this->nullableText($payload['observacion_revision_pdr'] ?? null);

        return $values + [
            'cod_usuario_revisa_pdr' => Auth::id(),
            'fecha_revision_pdr' => Carbon::now(config('app.timezone')),
            'observacion_revision_pdr' => $nextObservation ?? $report->observacion_revision_pdr,
        ];
    }

    private function nextCode(): string
    {
        $prefix = 'PDR-' . Carbon::now(config('app.timezone'))->format('Ymd') . '-';
        $next = PrendaDanioReporte::query()
            ->where('codigo_pdr', 'like', $prefix . '%')
            ->count() + 1;

        do {
            $code = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (PrendaDanioReporte::query()->where('codigo_pdr', $code)->exists());

        return $code;
    }

    private function storeEvidence($file, PrendaDanioReporte $report, int $order): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : Str::slug($report->codigo_pdr);
        $fileName = $report->codigo_pdr . '-' . $order . '-' . Str::lower(Str::random(8)) . '.' . Str::lower($extension);

        return $file->storeAs('prendas/danios', $fileName, 'public');
    }

    private function linkedImageUrl(?PrendaVariante $variant, ?Prenda $product): ?string
    {
        $variantImage = $this->firstImageUrl($variant?->imagenes);

        if ($variantImage) {
            return $variantImage;
        }

        return $this->firstImageUrl($product?->imagenes);
    }

    private function firstImageUrl(mixed $images): ?string
    {
        $image = collect($images ?? [])
            ->sortBy([
                ['pri_igp', 'desc'],
                ['ord_igp', 'asc'],
                ['id_imagen_prenda', 'asc'],
            ])
            ->first();

        if (!$image) {
            return null;
        }

        return $image->url_igp ?: ($image->rut_igp ? Storage::url($image->rut_igp) : null);
    }

    private function inventoryImpactMessage(PrendaDanioReporte $report): string
    {
        if (!$report->cod_inventario_pdr || !$report->afecta_inventario_pdr) {
            return 'Este reporte solo es administrativo.';
        }

        return match ($report->estado_pdr) {
            'reportado', 'en_revision' => 'Este reporte descontará stock únicamente si se confirma el daño.',
            'confirmado', 'baja_definitiva' => 'Este reporte descontó stock al confirmarse.',
            'reparado' => 'Este reporte descontó stock al confirmarse y luego lo devolvió por reparación.',
            default => 'Este reporte tuvo gestión de inventario según su decisión final.',
        };
    }

    private function stockActionStatus(PrendaDanioReporte $report): string
    {
        if (!$report->cod_inventario_pdr || !$report->afecta_inventario_pdr) {
            return 'No aplica descuento de stock.';
        }

        return match ($report->estado_pdr) {
            'reportado', 'en_revision' => 'Pendiente: no se descontó stock.',
            'confirmado', 'baja_definitiva' => 'Descontado al confirmar.',
            'reparado' => 'Descontado y devuelto por reparación.',
            default => 'Revisado según decisión final.',
        };
    }

    private function reportMetadata(PrendaDanioReporte $report): array
    {
        return [
            'reporte_id' => $report->id,
            'codigo' => $report->codigo_pdr,
            'estado' => $report->estado_pdr,
            'cantidad' => (int) $report->cantidad_pdr,
            'inventario_id' => $report->cod_inventario_pdr,
            'variante_id' => $report->cod_prenda_variante_pdr,
            'prenda_id' => $report->cod_prenda_pdr,
            'afecta_inventario' => (bool) $report->afecta_inventario_pdr,
        ];
    }

    private function optionsFrom(array $values): array
    {
        return collect($values)
            ->map(fn (string $label, string $value) => ['label' => $label, 'value' => $value])
            ->values()
            ->all();
    }

    private function dateBoundary(string $value, string $boundary): ?Carbon
    {
        try {
            $date = Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $boundary === 'end' ? $date->endOfDay() : $date->startOfDay();
    }

    private function formatDate(mixed $date, string $format): string
    {
        if (!$date instanceof Carbon) {
            return 'Sin fecha';
        }

        return $date->timezone(config('app.timezone'))->format($format);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function userName(?User $user): string
    {
        return $user?->nombreCompleto()
            ?: ($user?->email ?? 'Sin usuario');
    }

    private function stateLabel(string $value): string
    {
        return self::ESTADOS[$value] ?? $this->humanize($value);
    }

    private function causeLabel(string $value): string
    {
        return self::CAUSAS[$value] ?? $this->humanize($value);
    }

    private function damageTypeLabel(string $value): string
    {
        return self::TIPOS_DANIO[$value] ?? $this->humanize($value);
    }

    private function severityLabel(string $value): string
    {
        return self::SEVERIDADES[$value] ?? $this->humanize($value);
    }

    private function decisionLabel(string $value): string
    {
        return self::DECISIONES[$value] ?? $this->humanize($value);
    }

    private function stateTone(string $value): string
    {
        return match ($value) {
            'reportado' => 'accent',
            'en_revision' => 'info',
            'confirmado' => 'warning',
            'reparado', 'resuelto' => 'success',
            'baja_definitiva' => 'danger',
            default => 'neutral',
        };
    }

    private function severityTone(string $value): string
    {
        return match ($value) {
            'grave' => 'danger',
            'media' => 'warning',
            'leve' => 'success',
            default => 'neutral',
        };
    }

    private function humanize(string $value): string
    {
        return Str::ucfirst(str_replace('_', ' ', trim($value)));
    }
}
