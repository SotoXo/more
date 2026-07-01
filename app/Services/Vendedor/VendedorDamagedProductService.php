<?php

namespace App\Services\Vendedor;

use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaDanioImagen;
use App\Models\PrendaDanioReporte;
use App\Models\PrendaVariante;
use App\Models\User;
use App\Services\Superadmin\DamagedProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendedorDamagedProductService
{
    private const ESTADOS = [
        'reportado' => 'Reportado',
        'en_revision' => 'En revisión',
        'confirmado' => 'Confirmado',
        'descartado' => 'Descartado',
        'reparado' => 'Reparado',
        'baja_definitiva' => 'Baja definitiva',
        'resuelto' => 'Resuelto',
    ];

    private const CAUSAS = [
        'dano_en_tienda' => 'Daño en tienda',
        'dano_en_transporte' => 'Daño en transporte',
        'dano_por_manipulacion' => 'Daño por manipulación',
        'defecto_de_fabrica' => 'Defecto de fábrica',
        'devolucion_danada' => 'Devolución dañada',
        'desgaste' => 'Desgaste',
        'mala_conservacion' => 'Mala conservación',
        'otro' => 'Otro',
    ];

    private const TIPOS_DANIO = [
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

    private const SEVERIDADES = [
        'leve' => 'Leve',
        'media' => 'Media',
        'grave' => 'Grave',
    ];

    public function __construct(
        private readonly DamagedProductService $damagedProducts,
    ) {
    }

    public function indexPayload(Request $request): array
    {
        $query = $this->filteredQuery($request)
            ->with([
                'usuarioReporta.rol',
                'prenda.imagenes',
                'variante.imagenes',
                'variante.prenda.imagenes',
                'variante.color',
                'variante.talla',
                'inventario.variante.prenda.imagenes',
                'inventario.variante.imagenes',
                'inventario.variante.color',
                'inventario.variante.talla',
            ])
            ->orderByDesc('fecha_reporte_pdr')
            ->orderByDesc('id');

        /** @var LengthAwarePaginator $reports */
        $reports = $query->paginate(15)->withQueryString();

        return [
            'reports' => $reports->getCollection()
                ->map(fn (PrendaDanioReporte $report) => $this->serializeRow($report))
                ->values()
                ->all(),
            'summary' => $this->summary($request),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'estado' => $request->string('estado')->toString(),
                'causa' => $request->string('causa')->toString(),
                'tipo_danio' => $request->string('tipo_danio')->toString(),
                'severidad' => $request->string('severidad')->toString(),
                'fecha_desde' => $request->string('fecha_desde')->toString(),
                'fecha_hasta' => $request->string('fecha_hasta')->toString(),
            ],
            'options' => [
                'states' => $this->optionsFrom(self::ESTADOS),
                'causes' => $this->optionsFrom(self::CAUSAS),
                'damageTypes' => $this->optionsFrom(self::TIPOS_DANIO),
                'severities' => $this->optionsFrom(self::SEVERIDADES),
            ],
            'pagination' => [
                'links' => $reports->linkCollection()->toArray(),
                'meta' => [
                    'from' => $reports->firstItem(),
                    'to' => $reports->lastItem(),
                    'total' => $reports->total(),
                ],
            ],
            'createHref' => '/vendedor/prendas-daniadas/crear',
            'scopeLabel' => $this->isSupervisor($request)
                ? 'Supervisión de reportes creados desde el panel vendedor. Sin acciones administrativas en esta vista.'
                : 'Tus reportes de prendas dañadas. Crear un reporte no confirma daño, no mueve stock y no registra Kardex.',
        ];
    }

    public function createPayload(): array
    {
        return [
            'options' => [
                'causes' => $this->optionsFrom(self::CAUSAS),
                'damageTypes' => $this->optionsFrom(self::TIPOS_DANIO),
                'severities' => $this->optionsFrom(self::SEVERIDADES),
                'inventories' => $this->inventoryOptions(),
            ],
            'submitAction' => '/vendedor/prendas-daniadas',
            'indexHref' => '/vendedor/prendas-daniadas',
        ];
    }

    public function store(Request $request): PrendaDanioReporte
    {
        $this->validateVendorCreation($request);

        $request->merge(['afecta_inventario_pdr' => '0']);

        /** @var User $user */
        $user = $request->user();

        return $this->damagedProducts->createVendorReport($request, $user);
    }

    public function showPayload(Request $request, PrendaDanioReporte $report): array
    {
        $this->ensureCanView($request, $report);

        $report->loadMissing([
            'usuarioReporta.rol',
            'usuarioRevisa.rol',
            'prenda.imagenes',
            'variante.imagenes',
            'variante.prenda.imagenes',
            'variante.color',
            'variante.talla',
            'inventario.variante.prenda.imagenes',
            'inventario.variante.imagenes',
            'inventario.variante.color',
            'inventario.variante.talla',
            'imagenes',
        ]);

        return [
            'report' => $this->serializeDetail($report),
            'indexHref' => '/vendedor/prendas-daniadas',
            'scopeLabel' => 'Este reporte está pendiente de revisión administrativa. El panel vendedor no confirma daños ni modifica inventario.',
        ];
    }

    public function dashboardMetrics(Request $request): array
    {
        $pending = $this->baseQuery($request)
            ->whereIn('estado_pdr', ['reportado', 'en_revision'])
            ->count();

        return [
            'damaged_reports_pending' => [
                'value' => $this->number($pending),
                'note' => $pending > 0
                    ? 'Reportes de daño pendientes de revisión administrativa.'
                    : 'Sin reportes de daño pendientes.',
                'tone' => $pending > 0 ? 'warning' : 'success',
            ],
        ];
    }

    private function validateVendorCreation(Request $request): void
    {
        $request->validate([
            'nombre_prenda_pdr' => ['required', 'string', 'max:180'],
            'descripcion_prenda_pdr' => ['nullable', 'string', 'max:5000'],
            'cod_prenda_pdr' => ['nullable', 'integer', 'exists:prendas,id_prenda'],
            'cod_prenda_variante_pdr' => ['nullable', 'integer', 'exists:prenda_variantes,id_prenda_variante'],
            'cod_inventario_pdr' => ['nullable', 'integer', 'exists:inventarios,id_inventario'],
            'causa_pdr' => ['required', 'string', Rule::in(array_keys(DamagedProductService::CAUSAS))],
            'tipo_danio_pdr' => ['required', 'string', Rule::in(array_keys(DamagedProductService::TIPOS_DANIO))],
            'severidad_pdr' => ['required', 'string', Rule::in(array_keys(DamagedProductService::SEVERIDADES))],
            'cantidad_pdr' => ['required', 'integer', 'min:1'],
            'observacion_pdr' => ['required', 'string', 'max:5000'],
            'imagenes' => ['nullable', 'array'],
            'imagenes.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = $this->baseQuery($request);

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

        $search = mb_strtolower(trim($request->string('search')->toString()), 'UTF-8');

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

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

    private function baseQuery(Request $request): Builder
    {
        $query = PrendaDanioReporte::query();

        if (! $this->isSupervisor($request)) {
            $query->where('cod_usuario_reporta_pdr', $request->user()?->getAuthIdentifier());
        }

        return $query;
    }

    private function ensureCanView(Request $request, PrendaDanioReporte $report): void
    {
        if ($this->isSupervisor($request)) {
            return;
        }

        abort_unless((int) $report->cod_usuario_reporta_pdr === (int) $request->user()?->getAuthIdentifier(), 404);
    }

    private function isSupervisor(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'isSuperadmin') && $user->isSuperadmin();
    }

    private function summary(Request $request): array
    {
        $base = $this->filteredQuery($request);
        $pending = (clone $base)->whereIn('estado_pdr', ['reportado', 'en_revision'])->count();

        return [
            ['label' => 'Reportes', 'value' => $this->number((clone $base)->count()), 'tone' => 'accent'],
            ['label' => 'Pendientes', 'value' => $this->number($pending), 'tone' => $pending > 0 ? 'warning' : 'success'],
            ['label' => 'Vinculados', 'value' => $this->number((clone $base)->whereNotNull('cod_inventario_pdr')->count()), 'tone' => 'info'],
            ['label' => 'Cantidad afectada', 'value' => $this->number((int) (clone $base)->sum('cantidad_pdr')), 'tone' => 'neutral'],
        ];
    }

    private function serializeRow(PrendaDanioReporte $report): array
    {
        return [
            'id' => $report->id,
            'primary' => $report->codigo_pdr,
            'secondary' => Str::limit((string) $report->nombre_prenda_pdr, 80, ''),
            'image' => $this->linkedImageUrl($report),
            'code' => $report->codigo_pdr,
            'date' => $this->dateTime($report->fecha_reporte_pdr),
            'name' => Str::limit((string) $report->nombre_prenda_pdr, 70, ''),
            'cause' => $this->causeLabel((string) $report->causa_pdr),
            'damageType' => $this->damageTypeLabel((string) $report->tipo_danio_pdr),
            'severity' => ['label' => $this->severityLabel((string) $report->severidad_pdr), 'tone' => $this->severityTone((string) $report->severidad_pdr)],
            'quantity' => (string) $report->cantidad_pdr,
            'linked' => ['label' => $report->cod_inventario_pdr ? 'Si' : 'No', 'tone' => $report->cod_inventario_pdr ? 'accent' : 'neutral'],
            'stockImpact' => ['label' => 'No mueve stock', 'tone' => 'success'],
            'status' => ['label' => $this->stateLabel((string) $report->estado_pdr), 'tone' => $this->stateTone((string) $report->estado_pdr)],
            'reportedBy' => $this->userName($report->usuarioReporta),
            'showHref' => '/vendedor/prendas-daniadas/' . $report->id,
        ];
    }

    private function serializeDetail(PrendaDanioReporte $report): array
    {
        return [
            'id' => $report->id,
            'code' => (string) $report->codigo_pdr,
            'name' => (string) $report->nombre_prenda_pdr,
            'description' => $this->display($report->descripcion_prenda_pdr, 'Sin descripción registrada.'),
            'causeLabel' => $this->causeLabel((string) $report->causa_pdr),
            'damageTypeLabel' => $this->damageTypeLabel((string) $report->tipo_danio_pdr),
            'severityLabel' => $this->severityLabel((string) $report->severidad_pdr),
            'severityTone' => $this->severityTone((string) $report->severidad_pdr),
            'quantity' => (int) $report->cantidad_pdr,
            'statusLabel' => $this->stateLabel((string) $report->estado_pdr),
            'statusTone' => $this->stateTone((string) $report->estado_pdr),
            'observation' => $this->display($report->observacion_pdr, 'Sin observación.'),
            'reviewObservation' => $this->display($report->observacion_revision_pdr, 'Sin observación administrativa.'),
            'reportedAt' => $this->dateTime($report->fecha_reporte_pdr),
            'reviewedAt' => $this->dateTime($report->fecha_revision_pdr),
            'reportedBy' => $this->userName($report->usuarioReporta),
            'reviewedBy' => $this->userName($report->usuarioRevisa),
            'affectsInventory' => (bool) $report->afecta_inventario_pdr,
            'inventory' => $this->inventorySnapshot($report),
            'images' => $report->imagenes->map(fn (PrendaDanioImagen $image) => [
                'id' => $image->id,
                'url' => $image->url_pdi,
                'description' => $image->descripcion_pdi ?: 'Evidencia',
                'order' => (int) $image->orden_pdi,
            ])->values()->all(),
        ];
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
                    'label' => $label . ' - stock disponible ' . (int) $inventory->cdi_inv,
                    'value' => (string) $inventory->id_inventario,
                    'inventoryId' => (int) $inventory->id_inventario,
                    'variantId' => $variant?->id_prenda_variante,
                    'productId' => $product?->id_prenda,
                    'productName' => $product?->nom_pre ?: 'Sin prenda',
                    'sku' => $variant?->sku_prv ?: 'Sin SKU',
                    'color' => $variant?->color?->nom_col ?: 'Sin color',
                    'size' => $variant?->talla?->nom_tal ?: 'Sin talla',
                    'imageUrl' => $this->inventoryImageUrl($variant, $product),
                    'availableStock' => (int) $inventory->cdi_inv,
                    'committedStock' => (int) $inventory->cre_inv,
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
            'inventoryId' => $inventory?->id_inventario,
            'variantId' => $variant?->id_prenda_variante,
            'productId' => $product?->id_prenda,
            'product' => $product?->nom_pre ?: 'Sin prenda vinculada',
            'sku' => $variant?->sku_prv ?: 'Sin SKU',
            'color' => $variant?->color?->nom_col ?: 'Sin color',
            'size' => $variant?->talla?->nom_tal ?: 'Sin talla',
            'imageUrl' => $this->inventoryImageUrl($variant, $product),
            'availableStock' => $inventory ? (int) $inventory->cdi_inv : null,
            'committedStock' => $inventory ? (int) $inventory->cre_inv : null,
            'location' => $inventory?->ubi_inv ?: 'Sin ubicación',
            'variantStatusLabel' => (bool) ($variant?->est_prv ?? false) ? 'Activa' : ($variant ? 'Inactiva' : 'Sin variante'),
        ];
    }

    private function linkedImageUrl(PrendaDanioReporte $report): ?string
    {
        $inventory = $report->inventario;
        $variant = $inventory?->variante ?? $report->variante;
        $product = $variant?->prenda ?? $report->prenda;

        return $this->inventoryImageUrl($variant, $product);
    }

    private function inventoryImageUrl(?PrendaVariante $variant, ?Prenda $product): ?string
    {
        return $this->firstImageUrl($variant?->imagenes)
            ?? $this->firstImageUrl($product?->imagenes);
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

        if (! $image) {
            return null;
        }

        return $this->imageUrl($image->url_igp ?? null, $image->rut_igp ?? null);
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

        return Storage::url($path);
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

    private function dateTime(mixed $value): string
    {
        if (! $value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
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

    private function stateTone(string $value): string
    {
        return match ($value) {
            'reportado' => 'accent',
            'en_revision' => 'info',
            'confirmado' => 'warning',
            'reparado', 'resuelto' => 'success',
            'descartado', 'baja_definitiva' => 'danger',
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

    private function display(mixed $value, string $fallback): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : $fallback;
    }

    private function humanize(string $value): string
    {
        return Str::ucfirst(str_replace('_', ' ', trim($value)));
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
