<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\InventarioMovimiento;
use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementController extends Controller
{
    private const TYPES = [
        'entrada',
        'reserva',
        'liberacion_reserva',
        'venta_en_revision',
        'venta',
        'cancelacion_venta',
        'ajuste_manual',
        'cambio_ubicacion',
        'correccion',
        'conversion_reserva_venta',
        'dano_confirmado',
        'reparacion_danio',
    ];

    public function __construct(
        private readonly SystemLogService $logs,
    ) {
    }

    public function index(Request $request)
    {
        $this->logConsultation($request);

        return $this->renderMovements($request);
    }

    public function show(Request $request, InventarioMovimiento $movement)
    {
        $movement->loadMissing(['inventario', 'variante.prenda', 'variante.color', 'variante.talla', 'usuario.rol']);

        return $this->renderMovements($request, $movement);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = $this->filteredQuery($request)
            ->with(['variante.prenda', 'usuario'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $filename = 'mati-boutique-kardex-' . Carbon::now(config('app.timezone'))->format('Ymd-His') . '.csv';

        $this->logs->info(
            'inventario',
            'exportacion_kardex',
            'CSV de Kardex exportado.',
            null,
            [
                'filename' => $filename,
                'filas' => $rows->count(),
                'filtros' => $this->filtersMetadata($request),
            ],
        );

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Fecha',
                'Prenda',
                'Variante',
                'SKU',
                'Tipo',
                'Cantidad',
                'Disponible antes',
                'Disponible después',
                'Comprometido antes',
                'Comprometido después',
                'Referencia',
                'Usuario',
                'Descripcion',
            ], ';');

            foreach ($rows as $movement) {
                fputcsv($output, [
                    $movement->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '',
                    $this->sanitizeCell($movement->variante?->prenda?->nom_pre ?? ''),
                    $this->sanitizeCell($this->variantLabel($movement)),
                    $this->sanitizeCell($movement->variante?->sku_prv ?? ''),
                    $this->typeLabel($movement->tipo_movimiento_inm),
                    (string) $movement->cantidad_inm,
                    (string) $movement->cdi_antes_inm,
                    (string) $movement->cdi_despues_inm,
                    (string) $movement->cre_antes_inm,
                    (string) $movement->cre_despues_inm,
                    $this->referenceLabel($movement),
                    $this->sanitizeCell($this->userName($movement)),
                    $this->sanitizeCell($movement->descripcion_inm ?? ''),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function renderMovements(Request $request, ?InventarioMovimiento $selectedMovement = null)
    {
        $query = $this->filteredQuery($request)
            ->with(['inventario', 'variante.prenda', 'variante.color', 'variante.talla', 'usuario.rol']);

        $this->applySorting($query, $request);

        $movements = $query
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Superadmin/InventoryMovements', [
            'meta' => [
                'sectionLabel' => 'Inventario',
                'title' => 'Kardex de inventario',
                'description' => 'Historial automatico de entradas, reservas, ventas, liberaciones, ajustes y cambios de ubicacion.',
                'indexHref' => '/superadmin/inventario/movimientos',
            ],
            'breadcrumbs' => [
                ['label' => 'Panel administrativo', 'href' => '/superadmin'],
                ['label' => 'Inventario'],
                ['label' => 'Kardex de inventario'],
            ],
            'metrics' => $this->metrics($request),
            'columns' => $this->columns(),
            'rows' => $movements->getCollection()
                ->map(fn (InventarioMovimiento $movement) => $this->serializeRow($movement, $request))
                ->values()
                ->all(),
            'pagination' => [
                'links' => $movements->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $movements->currentPage(),
                    'from' => $movements->firstItem(),
                    'last_page' => $movements->lastPage(),
                    'path' => $movements->path(),
                    'per_page' => $movements->perPage(),
                    'to' => $movements->lastItem(),
                    'total' => $movements->total(),
                ],
            ],
            'filterAction' => '/superadmin/inventario/movimientos',
            'filterControls' => $this->filterControls($request),
            'sortOptions' => $this->sortOptions(),
            'sortBy' => $request->string('sort_by')->toString(),
            'sortDirection' => $request->input('sort_dir', 'desc'),
            'appliedFilters' => $this->appliedFilters($request),
            'selectedMovement' => $selectedMovement ? $this->serializeDetail($selectedMovement) : null,
            'closeDetailHref' => $this->indexHrefWithQuery($request),
            'exportHref' => $this->exportHref($request),
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = InventarioMovimiento::query();

        if ($request->filled('fecha_desde')) {
            $date = $this->dateBoundary((string) $request->input('fecha_desde'), 'start');

            if ($date) {
                $query->where('created_at', '>=', $date);
            }
        }

        if ($request->filled('fecha_hasta')) {
            $date = $this->dateBoundary((string) $request->input('fecha_hasta'), 'end');

            if ($date) {
                $query->where('created_at', '<=', $date);
            }
        }

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento_inm', $request->input('tipo_movimiento'));
        }

        if ($request->filled('variante')) {
            $term = mb_strtolower(trim((string) $request->input('variante')), 'UTF-8');
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            $query->whereHas('variante', function (Builder $variantQuery) use ($term, $like) {
                $variantQuery->whereRaw('LOWER(sku_prv) LIKE ?', [$like]);

                if (ctype_digit($term)) {
                    $variantQuery->orWhere('id_prenda_variante', (int) $term);
                }
            });
        }

        if ($request->filled('prenda')) {
            $term = mb_strtolower(trim((string) $request->input('prenda')), 'UTF-8');
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            $query->whereHas('variante.prenda', fn (Builder $productQuery) => $productQuery->whereRaw('LOWER(nom_pre) LIKE ?', [$like]));
        }

        if ($request->filled('usuario')) {
            $query->where('cod_usuario_inm', (int) $request->input('usuario'));
        }

        if ($request->filled('referencia_tipo')) {
            $query->where('referencia_tipo_inm', $request->input('referencia_tipo'));
        }

        if ($request->filled('buscar')) {
            $term = mb_strtolower(trim((string) $request->input('buscar')), 'UTF-8');
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $query->whereRaw('LOWER(COALESCE(descripcion_inm, \'\')) LIKE ?', [$like]);
        }

        return $query;
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->string('sort_by')->toString();
        $direction = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $columns = [
            'fecha' => 'created_at',
            'tipo' => 'tipo_movimiento_inm',
            'cantidad' => 'cantidad_inm',
            'disponible' => 'cdi_despues_inm',
            'comprometido' => 'cre_despues_inm',
        ];

        if (array_key_exists($sortBy, $columns)) {
            $query->orderBy($columns[$sortBy], $direction)->orderByDesc('id');
            return;
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function metrics(Request $request): array
    {
        $base = $this->filteredQuery($request);
        $today = Carbon::today(config('app.timezone'))->toDateString();
        $salesTypes = ['venta_en_revision', 'venta', 'conversion_reserva_venta'];

        return [
            [
                'label' => 'Movimientos de hoy',
                'value' => (string) (clone $base)->whereDate('created_at', $today)->count(),
                'note' => 'Registros operativos creados hoy.',
                'tone' => 'accent',
            ],
            [
                'label' => 'Entradas',
                'value' => (string) abs((int) (clone $base)->where('tipo_movimiento_inm', 'entrada')->sum('cantidad_inm')),
                'note' => 'Unidades registradas como ingreso.',
                'tone' => 'success',
            ],
            [
                'label' => 'Salidas / ventas',
                'value' => (string) abs((int) (clone $base)->whereIn('tipo_movimiento_inm', $salesTypes)->sum('cantidad_inm')),
                'note' => 'Unidades asociadas a venta o conversion.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Comprometido movido',
                'value' => (string) $this->sumAbsoluteExpression((clone $base), 'cre_despues_inm - cre_antes_inm'),
                'note' => 'Variacion absoluta de stock comprometido.',
                'tone' => 'info',
            ],
            [
                'label' => 'Ajustes manuales',
                'value' => (string) (clone $base)->where('tipo_movimiento_inm', 'ajuste_manual')->count(),
                'note' => 'Correcciones hechas desde inventario.',
                'tone' => 'danger',
            ],
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'fecha', 'label' => 'Fecha'],
            ['key' => 'tipo', 'label' => 'Tipo'],
            ['key' => 'cantidad', 'label' => 'Cantidad', 'align' => 'right'],
            ['key' => 'cdi_antes', 'label' => 'Disponible antes', 'align' => 'right'],
            ['key' => 'cdi_despues', 'label' => 'Disponible después', 'align' => 'right'],
            ['key' => 'cre_antes', 'label' => 'Comprometido antes', 'align' => 'right'],
            ['key' => 'cre_despues', 'label' => 'Comprometido después', 'align' => 'right'],
            ['key' => 'referencia', 'label' => 'Referencia'],
            ['key' => 'usuario', 'label' => 'Usuario'],
            ['key' => 'descripcion', 'label' => 'Descripcion'],
        ];
    }

    private function serializeRow(InventarioMovimiento $movement, Request $request): array
    {
        return [
            'id' => $movement->id,
            'primary' => $this->variantLabel($movement),
            'secondary' => $this->variantDescription($movement),
            'status' => [
                'label' => $this->typeLabel($movement->tipo_movimiento_inm),
                'tone' => $this->typeTone($movement->tipo_movimiento_inm),
            ],
            'cells' => [
                'fecha' => $movement->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin fecha',
                'tipo' => ['label' => $this->typeLabel($movement->tipo_movimiento_inm), 'tone' => $this->typeTone($movement->tipo_movimiento_inm)],
                'cantidad' => $this->signedQuantity((int) $movement->cantidad_inm),
                'cdi_antes' => (string) $movement->cdi_antes_inm,
                'cdi_despues' => (string) $movement->cdi_despues_inm,
                'cre_antes' => (string) $movement->cre_antes_inm,
                'cre_despues' => (string) $movement->cre_despues_inm,
                'referencia' => $this->referenceLabel($movement),
                'usuario' => $this->userName($movement),
                'descripcion' => Str::limit($movement->descripcion_inm ?: 'Sin descripcion', 80, ''),
            ],
            'showHref' => $this->detailHref($movement, $request),
        ];
    }

    private function serializeDetail(InventarioMovimiento $movement): array
    {
        return [
            'id' => $movement->id,
            'code' => 'KDX-' . str_pad((string) $movement->id, 5, '0', STR_PAD_LEFT),
            'date' => $movement->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? 'Sin fecha',
            'type' => $this->typeLabel($movement->tipo_movimiento_inm),
            'typeTone' => $this->typeTone($movement->tipo_movimiento_inm),
            'quantity' => $this->signedQuantity((int) $movement->cantidad_inm),
            'description' => $movement->descripcion_inm ?: 'Sin descripcion',
            'reference' => $this->referenceLabel($movement),
            'user' => $this->userName($movement),
            'product' => $movement->variante?->prenda?->nom_pre ?: 'Sin prenda',
            'variant' => $this->variantLabel($movement),
            'sku' => $movement->variante?->sku_prv ?: 'Sin SKU',
            'inventory' => $movement->cod_inventario_inm ? ('Inventario #' . $movement->cod_inventario_inm) : 'Sin inventario asociado',
            'stock' => [
                ['label' => 'Disponible antes', 'value' => (string) $movement->cdi_antes_inm],
                ['label' => 'Disponible después', 'value' => (string) $movement->cdi_despues_inm],
                ['label' => 'Comprometido antes', 'value' => (string) $movement->cre_antes_inm],
                ['label' => 'Comprometido después', 'value' => (string) $movement->cre_despues_inm],
            ],
            'locations' => [
                ['label' => 'Ubicacion antes', 'value' => $movement->ubicacion_antes_inm ?: 'Sin ubicacion'],
                ['label' => 'Ubicacion después', 'value' => $movement->ubicacion_despues_inm ?: 'Sin ubicacion'],
            ],
            'metadata' => $movement->metadata_inm ?: [],
        ];
    }

    private function filterControls(Request $request): array
    {
        return [
            ['key' => 'fecha_desde', 'label' => 'Fecha desde', 'type' => 'date', 'value' => $request->string('fecha_desde')->toString()],
            ['key' => 'fecha_hasta', 'label' => 'Fecha hasta', 'type' => 'date', 'value' => $request->string('fecha_hasta')->toString()],
            ['key' => 'tipo_movimiento', 'label' => 'Tipo de movimiento', 'type' => 'select', 'value' => $request->string('tipo_movimiento')->toString(), 'options' => $this->typeOptions()],
            ['key' => 'variante', 'label' => 'Variante / SKU', 'type' => 'text', 'value' => $request->string('variante')->toString(), 'placeholder' => 'SKU o ID'],
            ['key' => 'prenda', 'label' => 'Prenda', 'type' => 'text', 'value' => $request->string('prenda')->toString(), 'placeholder' => 'Nombre de prenda'],
            ['key' => 'usuario', 'label' => 'Usuario', 'type' => 'select', 'value' => $request->string('usuario')->toString(), 'options' => $this->userOptions()],
            ['key' => 'referencia_tipo', 'label' => 'Referencia tipo', 'type' => 'select', 'value' => $request->string('referencia_tipo')->toString(), 'options' => $this->referenceOptions()],
            ['key' => 'buscar', 'label' => 'Descripcion', 'type' => 'text', 'value' => $request->string('buscar')->toString(), 'placeholder' => 'Buscar descripcion'],
        ];
    }

    private function sortOptions(): array
    {
        return [
            ['label' => 'Fecha', 'value' => 'fecha'],
            ['label' => 'Tipo', 'value' => 'tipo'],
            ['label' => 'Cantidad', 'value' => 'cantidad'],
            ['label' => 'Disponible final', 'value' => 'disponible'],
            ['label' => 'Comprometido final', 'value' => 'comprometido'],
        ];
    }

    private function appliedFilters(Request $request): array
    {
        $labels = [
            'fecha_desde' => 'Fecha desde',
            'fecha_hasta' => 'Fecha hasta',
            'tipo_movimiento' => 'Tipo',
            'variante' => 'Variante',
            'prenda' => 'Prenda',
            'usuario' => 'Usuario',
            'referencia_tipo' => 'Referencia',
            'buscar' => 'Descripcion',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $key) => $request->filled($key))
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => $key === 'tipo_movimiento'
                    ? $this->typeLabel((string) $request->input($key))
                    : (string) $request->input($key),
            ])
            ->values()
            ->all();
    }

    private function filtersMetadata(Request $request): array
    {
        return collect(['fecha_desde', 'fecha_hasta', 'tipo_movimiento', 'variante', 'prenda', 'usuario', 'referencia_tipo', 'buscar'])
            ->filter(fn (string $key) => $request->filled($key))
            ->mapWithKeys(fn (string $key) => [$key => (string) $request->input($key)])
            ->all();
    }

    private function typeOptions(): array
    {
        $existing = InventarioMovimiento::query()
            ->select('tipo_movimiento_inm')
            ->distinct()
            ->whereNotNull('tipo_movimiento_inm')
            ->pluck('tipo_movimiento_inm')
            ->all();

        return collect(array_unique([...self::TYPES, ...$existing]))
            ->filter()
            ->sort()
            ->map(fn (string $value) => ['label' => $this->typeLabel($value), 'value' => $value])
            ->values()
            ->all();
    }

    private function userOptions(): array
    {
        $userIds = InventarioMovimiento::query()
            ->whereNotNull('cod_usuario_inm')
            ->distinct()
            ->pluck('cod_usuario_inm');

        return User::query()
            ->with('rol')
            ->whereIn('id', $userIds)
            ->orderBy('nom_usu')
            ->get()
            ->map(fn (User $user) => [
                'label' => $user->nombreCompleto() . ' - ' . ($user->rol?->nom_rol ?? 'Sin rol'),
                'value' => (string) $user->id,
            ])
            ->values()
            ->all();
    }

    private function referenceOptions(): array
    {
        return InventarioMovimiento::query()
            ->select('referencia_tipo_inm')
            ->distinct()
            ->whereNotNull('referencia_tipo_inm')
            ->orderBy('referencia_tipo_inm')
            ->pluck('referencia_tipo_inm')
            ->map(fn (string $value) => ['label' => $this->humanize($value), 'value' => $value])
            ->values()
            ->all();
    }

    private function sumAbsoluteExpression(Builder $query, string $expression): int
    {
        $value = $query
            ->selectRaw('COALESCE(SUM(ABS(' . $expression . ')), 0) as total')
            ->value('total');

        return (int) $value;
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

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'liberacion_reserva' => 'Liberacion de reserva',
            'venta_en_revision' => 'Venta en revision',
            'cancelacion_venta' => 'Cancelacion de venta',
            'ajuste_manual' => 'Ajuste manual',
            'cambio_ubicacion' => 'Cambio de ubicacion',
            'conversion_reserva_venta' => 'Conversion reserva/venta',
            'dano_confirmado' => 'Daño confirmado',
            'reparacion_danio' => 'Reparacion de daño',
            default => $this->humanize($type),
        };
    }

    private function typeTone(string $type): string
    {
        return match ($type) {
            'entrada' => 'success',
            'reserva', 'venta_en_revision' => 'accent',
            'liberacion_reserva', 'cancelacion_venta' => 'info',
            'venta', 'conversion_reserva_venta' => 'warning',
            'dano_confirmado' => 'danger',
            'reparacion_danio' => 'success',
            'ajuste_manual', 'correccion' => 'danger',
            default => 'neutral',
        };
    }

    private function variantLabel(InventarioMovimiento $movement): string
    {
        return $movement->variante?->sku_prv ?: 'Variante #' . $movement->cod_prenda_variante_inm;
    }

    private function variantDescription(InventarioMovimiento $movement): string
    {
        return trim(implode(' - ', array_filter([
            $movement->variante?->prenda?->nom_pre,
            $movement->variante?->color?->nom_col,
            $movement->variante?->talla?->nom_tal,
        ]))) ?: 'Sin detalle de prenda';
    }

    private function referenceLabel(InventarioMovimiento $movement): string
    {
        if (!$movement->referencia_tipo_inm || !$movement->referencia_id_inm) {
            return 'Sin referencia';
        }

        return $this->humanize($movement->referencia_tipo_inm) . ' #' . $movement->referencia_id_inm;
    }

    private function userName(InventarioMovimiento $movement): string
    {
        return $movement->usuario?->nombreCompleto()
            ?: ($movement->usuario?->email ?? ($movement->cod_usuario_inm ? 'Usuario #' . $movement->cod_usuario_inm : 'Sistema'));
    }

    private function signedQuantity(int $quantity): string
    {
        if ($quantity > 0) {
            return '+' . $quantity;
        }

        return (string) $quantity;
    }

    private function humanize(string $value): string
    {
        return Str::ucfirst(str_replace('_', ' ', trim($value)));
    }

    private function sanitizeCell(mixed $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', (string) $value);

        if (preg_match('/^[=+\-@\t]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    private function detailHref(InventarioMovimiento $movement, Request $request): string
    {
        $query = $request->query();
        unset($query['page']);

        return '/superadmin/inventario/movimientos/' . $movement->id . ($query ? '?' . http_build_query($query) : '');
    }

    private function indexHrefWithQuery(Request $request): string
    {
        $query = $request->query();

        return '/superadmin/inventario/movimientos' . ($query ? '?' . http_build_query($query) : '');
    }

    private function exportHref(Request $request): string
    {
        $query = $request->query();
        unset($query['page']);

        return '/superadmin/inventario/movimientos/exportar.csv' . ($query ? '?' . http_build_query($query) : '');
    }

    private function logConsultation(Request $request): void
    {
        $this->logs->info(
            'inventario',
            'consulta_kardex',
            'Consulta de Kardex de inventario.',
            null,
            ['filtros' => $this->filtersMetadata($request)],
        );
    }
}
