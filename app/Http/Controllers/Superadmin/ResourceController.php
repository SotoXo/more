<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Favorito;
use App\Models\ImagenPrenda;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\Talla;
use App\Models\Temporada;
use App\Models\User;
use App\Models\Venta;
use App\Support\UserValidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResourceController extends Controller
{
    private const CRUD_RESOURCES = [
        'seguridad.roles',
        'seguridad.usuarios',
        'catalogo.categorias',
        'catalogo.marcas',
        'catalogo.colores',
        'catalogo.tallas',
        'catalogo.temporadas',
        'catalogo.prendas',
    ];

    private const SECONDARY_CRUD_RESOURCES = [
        'catalogo.variantes',
        'catalogo.imagenes',
    ];

    private const NOT_READY_RESOURCES = [
        'inventario.ajustes',
    ];

    public function index(Request $request, string $section, string $resource)
    {
        $definition = $this->definition($section, $resource);
        $rows = $this->indexRows($definition, $request);
        $filterControls = $this->filterControls($definition, $request);
        $sortOptions = $this->sortOptions($definition);
        $headerActions = $this->headerActions($definition, $request);
        $segmentedFilters = $this->segmentedFilters($definition, $request);
        $emptyState = $this->emptyState($definition, $request);

        return Inertia::render('Superadmin/ResourceIndex', [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $this->breadcrumbs($definition),
            'metrics' => $this->metrics($definition),
            'columns' => $this->columns($definition, $request),
            'rows' => $rows,
            'headerActions' => $headerActions,
            'segmentedFilters' => $segmentedFilters,
            'filterAction' => $this->filterAction($definition, $request),
            'filterControls' => $filterControls,
            'sortOptions' => $sortOptions,
            'sortBy' => $request->string('sort_by')->toString(),
            'sortDirection' => $request->input('sort_dir', 'desc'),
            'appliedFilters' => $this->appliedFilters($filterControls, $sortOptions, $request),
            'emptyState' => $emptyState,
            'errorState' => [
                'title' => 'No se pudo cargar el módulo',
                'description' => 'Vuelve al panel e intenta otra vez.',
                'actionLabel' => 'Volver al panel',
                'actionHref' => '/superadmin',
            ],
        ]);
    }

    public function create(Request $request, string $section, string $resource)
    {
        $definition = $this->definition($section, $resource);
        abort_unless($this->supportsCrud($definition), 404);

        if ($this->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductCreate', $this->productEditorPayload(
                $definition,
                null,
                $request,
                'create',
            ));
        }

        $viewData = $this->viewData($definition, null, $request);

        return Inertia::render('Superadmin/ResourceForm', [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $this->breadcrumbs($definition, 'Nuevo ' . $definition['singular']),
            'mode' => 'create',
            'recordLabel' => null,
            'sections' => $viewData['formSections'],
            'title' => $viewData['formTitle'] ?? null,
            'description' => $viewData['formDescription'] ?? null,
            'submit' => $this->supportsCrud($definition)
                ? [
                    'action' => $this->indexHref($definition),
                    'method' => 'post',
                    'deleteAction' => null,
                ]
                : null,
        ]);
    }

    public function show(string $section, string $resource, string $record)
    {
        $definition = $this->definition($section, $resource);
        $resolvedRecord = $this->resolveRecord($definition, $record);

        if ($this->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductShow', $this->productShowPayload($definition, $resolvedRecord));
        }

        $viewData = $this->viewData($definition, $resolvedRecord);

        return Inertia::render('Superadmin/ResourceShow', [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $this->breadcrumbs($definition, $viewData['recordLabel']),
            'recordLabel' => $viewData['recordLabel'],
            'summaryMetrics' => $viewData['summaryMetrics'],
            'detailSections' => $viewData['detailSections'],
            'timeline' => $viewData['timeline'],
            'relatedColumns' => $viewData['relatedColumns'],
            'relatedRows' => $viewData['relatedRows'],
            'editHref' => $this->supportsCrud($definition) ? $this->editHref($definition, $this->recordKey($resolvedRecord)) : null,
        ]);
    }

    public function edit(Request $request, string $section, string $resource, string $record)
    {
        $definition = $this->definition($section, $resource);
        $resolvedRecord = $this->resolveRecord($definition, $record);

        if (!$this->supportsCrud($definition)) {
            return redirect($this->showHref($definition, $this->recordKey($resolvedRecord)));
        }

        if ($this->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductCreate', $this->productEditorPayload(
                $definition,
                $resolvedRecord,
                $request,
                'edit',
            ));
        }

        $viewData = $this->viewData($definition, $resolvedRecord, $request);

        return Inertia::render('Superadmin/ResourceForm', [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $this->breadcrumbs($definition, $viewData['recordLabel'], 'Editar'),
            'mode' => 'edit',
            'recordLabel' => $viewData['recordLabel'],
            'sections' => $viewData['formSections'],
            'title' => $viewData['formTitle'] ?? null,
            'description' => $viewData['formDescription'] ?? null,
            'submit' => $this->supportsCrud($definition)
                ? [
                    'action' => $this->showHref($definition, $this->recordKey($resolvedRecord)),
                    'method' => 'put',
                    'deleteAction' => $this->deleteActionFor($definition, $resolvedRecord)
                        ? $this->showHref($definition, $this->recordKey($resolvedRecord))
                        : null,
                ]
                : null,
            'stateAction' => $this->stateAction($definition, $resolvedRecord, $request->user()),
        ]);
    }

    public function store(Request $request, string $section, string $resource): RedirectResponse
    {
        $definition = $this->definition($section, $resource);
        abort_unless($this->supportsCrud($definition), 404);

        $validated = $this->validateWrite($request, $definition);
        $record = $this->createRecord($request, $definition, $validated);

        return redirect($this->editHref($definition, $this->recordKey($record)))
            ->with('success', ucfirst($definition['singular']) . ' creado correctamente.');
    }

    public function storeInline(Request $request, string $section, string $resource): JsonResponse
    {
        $definition = $this->definition($section, $resource);
        abort_unless($this->supportsInlineCreate($definition), 404);

        if ($this->definitionKey($definition) === 'catalogo.tallas' && !$request->filled('ord_tal')) {
            $request->merge([
                'ord_tal' => $this->suggestSizeOrder((string) $request->input('nom_tal')),
            ]);
        }

        $validated = $this->validateWrite($request, $definition);
        $record = $this->createRecord($request, $definition, $validated);

        return response()->json([
            'message' => ucfirst($definition['singular']) . ' creado correctamente.',
            'option' => $this->inlineOption($definition, $record),
        ], 201);
    }

    public function update(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        $definition = $this->definition($section, $resource);
        abort_unless($this->supportsCrud($definition), 404);

        $resolvedRecord = $this->resolveRecord($definition, $record);
        $validated = $this->validateWrite($request, $definition, $resolvedRecord);
        $this->updateRecord($request, $definition, $validated, $resolvedRecord);

        return redirect($this->editHref($definition, $this->recordKey($resolvedRecord)))
            ->with('success', ucfirst($definition['singular']) . ' actualizado correctamente.');
    }

    public function destroy(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        $definition = $this->definition($section, $resource);
        abort_unless($this->supportsCrud($definition), 404);

        $resolvedRecord = $this->resolveRecord($definition, $record);
        $deleteError = $this->deleteBlockedReason($definition, $resolvedRecord, $request->user());

        if ($deleteError) {
            return redirect($this->editHref($definition, $this->recordKey($resolvedRecord)))
                ->with('error', $deleteError);
        }

        try {
            $this->deleteRecord($definition, $resolvedRecord);
        } catch (\Throwable) {
            return redirect($this->editHref($definition, $this->recordKey($resolvedRecord)))
                ->with('error', 'No se pudo eliminar el registro porque tiene dependencias asociadas.');
        }

        return redirect($this->indexHref($definition))
            ->with('success', ucfirst($definition['singular']) . ' eliminado correctamente.');
    }

    private function definition(string $section, string $resource): array
    {
        $definitions = [
            'seguridad.roles' => [
                'sectionKey' => 'seguridad',
                'sectionLabel' => 'Seguridad',
                'resourceKey' => 'roles',
                'title' => 'Roles',
                'singular' => 'rol',
                'description' => 'Perfiles de acceso del sistema.',
                'kind' => 'taxonomy',
                'modelClass' => Role::class,
                'nameField' => 'nom_rol',
                'descriptionField' => 'des_rol',
                'statusField' => 'est_rol',
                'dateField' => 'fec_rol',
                'countRelation' => 'users',
                'countLabel' => 'usuarios',
                'filters' => ['Activos', 'Relacionados', 'Recientes'],
            ],
            'seguridad.usuarios' => [
                'sectionKey' => 'seguridad',
                'sectionLabel' => 'Seguridad',
                'resourceKey' => 'usuarios',
                'title' => 'Usuarios',
                'singular' => 'usuario',
                'description' => 'Cuentas registradas y rol asignado.',
                'kind' => 'users',
                'modelClass' => User::class,
                'statusField' => 'est_usu',
                'dateField' => 'fec_usu',
                'filters' => ['Activos', 'Con rol', 'Alta reciente'],
            ],
            'catalogo.categorias' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'categorias',
                'title' => 'Categorías',
                'singular' => 'categoría',
                'description' => 'Categorías del catálogo.',
                'kind' => 'taxonomy',
                'modelClass' => Categoria::class,
                'nameField' => 'nom_cat',
                'descriptionField' => 'des_cat',
                'statusField' => 'est_cat',
                'dateField' => 'fec_cat',
                'countRelation' => 'prendas',
                'countLabel' => 'prendas',
                'filters' => ['Activas', 'Con prendas', 'Maestras'],
            ],
            'catalogo.marcas' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'marcas',
                'title' => 'Marcas',
                'singular' => 'marca',
                'description' => 'Marcas registradas.',
                'kind' => 'taxonomy',
                'modelClass' => Marca::class,
                'nameField' => 'nom_mar',
                'descriptionField' => 'des_mar',
                'statusField' => 'est_mar',
                'dateField' => 'fec_mar',
                'countRelation' => 'prendas',
                'countLabel' => 'prendas',
                'filters' => ['Activas', 'Con prendas', 'Recientes'],
            ],
            'catalogo.colores' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'colores',
                'title' => 'Colores',
                'singular' => 'color',
                'description' => 'Colores disponibles para variantes.',
                'kind' => 'taxonomy',
                'modelClass' => Color::class,
                'nameField' => 'nom_col',
                'descriptionField' => 'hex_col',
                'statusField' => 'est_col',
                'dateField' => 'fec_col',
                'countRelation' => 'variantes',
                'countLabel' => 'variantes',
                'filters' => ['Activos', 'Con variantes', 'Paleta'],
            ],
            'catalogo.tallas' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'tallas',
                'title' => 'Tallas',
                'singular' => 'talla',
                'description' => 'Tallas disponibles.',
                'kind' => 'taxonomy',
                'modelClass' => Talla::class,
                'nameField' => 'nom_tal',
                'descriptionField' => 'ord_tal',
                'statusField' => 'est_tal',
                'dateField' => 'fec_tal',
                'countRelation' => 'variantes',
                'countLabel' => 'variantes',
                'filters' => ['Activas', 'Con variantes', 'Ordenadas'],
            ],
            'catalogo.temporadas' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'temporadas',
                'title' => 'Temporadas',
                'singular' => 'temporada',
                'description' => 'Temporadas registradas.',
                'kind' => 'taxonomy',
                'modelClass' => Temporada::class,
                'nameField' => 'nom_tem',
                'descriptionField' => 'des_tem',
                'statusField' => 'est_tem',
                'dateField' => 'fec_tem',
                'countRelation' => 'prendas',
                'countLabel' => 'prendas',
                'filters' => ['Activas', 'Con prendas', 'Edición'],
            ],
            'catalogo.prendas' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'prendas',
                'title' => 'Prendas',
                'singular' => 'prenda',
                'description' => 'Centro operativo del catálogo: ficha, galería, variantes y existencias.',
                'kind' => 'products',
                'modelClass' => Prenda::class,
                'statusField' => 'est_pre',
                'dateField' => 'fec_pre',
                'filters' => ['Activas', 'Con imagen', 'Temporada'],
            ],
            'catalogo.variantes' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'variantes',
                'title' => 'Variantes',
                'singular' => 'variante',
                'description' => 'Subregistro técnico para color, talla y precio por prenda.',
                'kind' => 'variants',
                'modelClass' => PrendaVariante::class,
                'statusField' => 'est_prv',
                'dateField' => 'fec_prv',
                'filters' => ['Activas', 'Con existencias', 'Con imagen'],
            ],
            'catalogo.imagenes' => [
                'sectionKey' => 'catalogo',
                'sectionLabel' => 'Catálogo',
                'resourceKey' => 'imagenes',
                'title' => 'Imágenes',
                'singular' => 'imagen',
                'description' => 'Subregistro técnico de galería y soporte visual de prendas.',
                'kind' => 'images',
                'modelClass' => ImagenPrenda::class,
                'dateField' => 'fec_igp',
                'filters' => ['Principales', 'Con variante', 'Orden visual'],
            ],
            'inventario.stock' => [
                'sectionKey' => 'inventario',
                'sectionLabel' => 'Inventario',
                'resourceKey' => 'stock',
                'title' => 'Consulta de existencias',
                'singular' => 'registro de inventario',
                'description' => 'Consulta de disponible, reservado y ubicación. Los movimientos de inventario no están habilitados en esta fase.',
                'kind' => 'inventory',
                'modelClass' => Inventario::class,
                'filters' => ['Crítico', 'Reservado', 'Disponible'],
            ],
            'inventario.ajustes' => [
                'sectionKey' => 'inventario',
                'sectionLabel' => 'Inventario',
                'resourceKey' => 'ajustes',
                'title' => 'Ajustes sugeridos',
                'singular' => 'ajuste',
                'description' => 'Vista secundaria de recomendaciones. No ejecuta cambios de inventario ni guarda ajustes.',
                'kind' => 'adjustments',
                'filters' => ['Prioritario', 'Inventario', 'Comercial'],
            ],
            'inventario.alertas' => [
                'sectionKey' => 'inventario',
                'sectionLabel' => 'Inventario',
                'resourceKey' => 'alertas',
                'title' => 'Alertas de inventario',
                'singular' => 'alerta',
                'description' => 'Consulta de alertas de inventario y existencias bajas.',
                'kind' => 'alerts',
                'modelClass' => Inventario::class,
                'filters' => ['Crítico', 'Seguimiento', 'Ubicación'],
            ],
            'comercial.ventas' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'ventas',
                'title' => 'Consulta de ventas',
                'singular' => 'venta',
                'description' => 'Consulta de ventas registradas.',
                'kind' => 'sales',
                'modelClass' => Venta::class,
                'dateField' => 'fec_ven',
                'filters' => ['Recientes', 'Pago', 'Cierre'],
            ],
            'comercial.reservas' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'reservas',
                'title' => 'Consulta de reservas',
                'singular' => 'reserva',
                'description' => 'Consulta de reservas activas y vencimientos.',
                'kind' => 'reservations',
                'modelClass' => Reserva::class,
                'dateField' => 'fec_res',
                'filters' => ['Vigentes', 'Por vencer', 'Seguimiento'],
            ],
            'comercial.favoritos' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'favoritos',
                'title' => 'Favoritos guardados',
                'singular' => 'favorito',
                'description' => 'Consulta de favoritos guardados por clientes.',
                'kind' => 'favorites',
                'modelClass' => Favorito::class,
                'dateField' => 'fec_fav',
                'filters' => ['Recientes', 'Por cliente', 'Por prenda'],
            ],
        ];

        $key = $section . '.' . $resource;
        abort_unless(isset($definitions[$key]), 404);

        return $definitions[$key];
    }

    private function meta(array $definition): array
    {
        $moduleStatus = $this->moduleStatus($definition);

        return [
            'sectionKey' => $definition['sectionKey'],
            'sectionLabel' => $definition['sectionLabel'],
            'resourceKey' => $definition['resourceKey'],
            'title' => $definition['title'],
            'singular' => $definition['singular'],
            'description' => $definition['description'],
            'indexHref' => $this->indexHref($definition),
            'createHref' => $this->supportsCrud($definition) ? $this->createHref($definition) : null,
            'moduleStatus' => $moduleStatus['status'],
            'moduleStatusLabel' => $moduleStatus['label'],
            'moduleStatusDescription' => $moduleStatus['description'],
            'moduleIsSecondary' => $moduleStatus['secondary'],
        ];
    }

    private function headerActions(array $definition, Request $request): array
    {
        if ($this->definitionKey($definition) === 'seguridad.usuarios') {
            $creatableSlugs = ['superadmin', 'vendedor'];

            return $this->managedUserRoles()
                ->filter(fn (array $role) => in_array($role['slug'], $creatableSlugs, true))
                ->map(fn (array $role) => [
                    'label' => 'Nuevo ' . ucfirst($role['singular']),
                    'href' => $this->createHref($definition) . '?role_slug=' . urlencode($role['slug']),
                    'kind' => $role['slug'] === $request->string('role_slug')->toString() ? 'primary' : 'secondary',
                ])
                ->values()
                ->all();
        }

        if (!$this->supportsCrud($definition)) {
            return [];
        }

        return [[
            'label' => 'Nuevo ' . $definition['singular'],
            'href' => $this->createHref($definition),
            'kind' => 'primary',
        ]];
    }

    private function segmentedFilters(array $definition, Request $request): array
    {
        if ($this->definitionKey($definition) !== 'seguridad.usuarios') {
            return [];
        }

        $activeSlug = $request->string('role_slug')->toString();
        $baseHref = $this->indexHref($definition);
        $query = collect($request->query())
            ->except(['role_slug'])
            ->all();

        $allHref = $query ? $baseHref . '?' . http_build_query($query) : $baseHref;

        $filters = [[
            'label' => 'Todos',
            'href' => $allHref,
            'active' => $activeSlug === '',
            'count' => (string) User::query()->count(),
        ]];

        return array_merge(
            $filters,
            $this->managedUserRoles()
                ->map(function (array $role) use ($activeSlug, $baseHref, $query) {
                    $href = $baseHref . '?' . http_build_query([
                        ...$query,
                        'role_slug' => $role['slug'],
                    ]);

                    return [
                        'label' => $role['label'],
                        'href' => $href,
                        'active' => $activeSlug === $role['slug'],
                        'count' => (string) $role['count'],
                    ];
                })
                ->values()
                ->all(),
        );
    }

    private function filterAction(array $definition, Request $request): string
    {
        if ($this->definitionKey($definition) !== 'seguridad.usuarios') {
            return $this->indexHref($definition);
        }

        $query = [];

        if ($request->string('role_slug')->toString() !== '') {
            $query['role_slug'] = $request->string('role_slug')->toString();
        }

        return $query === []
            ? $this->indexHref($definition)
            : $this->indexHref($definition) . '?' . http_build_query($query);
    }

    private function emptyState(array $definition, Request $request): array
    {
        if ($this->definitionKey($definition) === 'seguridad.usuarios') {
            $activeRole = $this->activeManagedUserRole($request);

            return [
                'title' => $activeRole
                    ? 'Sin ' . mb_strtolower($activeRole['label'], 'UTF-8')
                    : 'Sin usuarios',
                'description' => $activeRole
                    ? 'No hay cuentas registradas en ese grupo por ahora.'
                    : 'No hay usuarios registrados en este módulo.',
                'actionLabel' => $activeRole ? 'Nuevo ' . $activeRole['singular'] : 'Nuevo usuario',
                'actionHref' => $activeRole
                    ? $this->createHref($definition) . '?role_slug=' . urlencode($activeRole['slug'])
                    : $this->createHref($definition),
            ];
        }

        return [
            'title' => 'Sin registros',
            'description' => 'No hay registros disponibles en ' . mb_strtolower($definition['title']) . '.',
            'actionLabel' => $this->supportsCrud($definition) ? 'Nuevo ' . $definition['singular'] : null,
            'actionHref' => $this->supportsCrud($definition) ? $this->createHref($definition) : null,
        ];
    }

    private function stateAction(array $definition, mixed $record, $authUser = null): ?array
    {
        // Los usuarios no tienen accion separada de estado.
        // El estado se gestiona unicamente desde el toggle en el formulario de edicion.
        if ($this->definitionKey($definition) === 'seguridad.usuarios') {
            return null;
        }

        return null;
    }

    private function breadcrumbs(array $definition, ?string $last = null, ?string $mode = null): array
    {
        $crumbs = [
            ['label' => 'Superadmin', 'href' => '/superadmin'],
            ['label' => $definition['sectionLabel']],
            ['label' => $definition['title'], 'href' => $this->indexHref($definition)],
        ];

        if ($last) {
            $crumbs[] = ['label' => $mode ? $mode . ' · ' . $last : $last];
        }

        return $crumbs;
    }

    private function indexHref(array $definition): string
    {
        return '/superadmin/' . $definition['sectionKey'] . '/' . $definition['resourceKey'];
    }

    private function createHref(array $definition): string
    {
        return $this->indexHref($definition) . '/nuevo';
    }

    private function showHref(array $definition, string|int $record): string
    {
        return $this->indexHref($definition) . '/' . urlencode((string) $record);
    }

    private function editHref(array $definition, string|int $record): string
    {
        return $this->showHref($definition, $record) . '/editar';
    }

    private function rowEditHref(array $definition, string|int $record): ?string
    {
        return $this->supportsCrud($definition) ? $this->editHref($definition, $record) : null;
    }

    private function definitionKey(array $definition): string
    {
        return $definition['sectionKey'] . '.' . $definition['resourceKey'];
    }

    private function supportsCrud(array $definition): bool
    {
        $key = $this->definitionKey($definition);

        return in_array($key, self::CRUD_RESOURCES, true)
            || in_array($key, self::SECONDARY_CRUD_RESOURCES, true);
    }

    private function moduleStatus(array $definition): array
    {
        $key = $this->definitionKey($definition);

        if (in_array($key, self::CRUD_RESOURCES, true)) {
            return [
                'status' => 'crud',
                'label' => 'CRUD real',
                'description' => 'Este módulo permite crear, consultar, editar y guardar registros reales.',
                'secondary' => false,
            ];
        }

        if (in_array($key, self::SECONDARY_CRUD_RESOURCES, true)) {
            return [
                'status' => 'crud',
                'label' => 'CRUD real secundario',
                'description' => 'Este módulo guarda registros reales, pero depende de prendas y debe tratarse como gestión subordinada.',
                'secondary' => true,
            ];
        }

        if (in_array($key, self::NOT_READY_RESOURCES, true)) {
            return [
                'status' => 'not_ready',
                'label' => 'No listo',
                'description' => 'Esta vista es informativa y no ejecuta cambios operativos todavía.',
                'secondary' => true,
            ];
        }

        return [
            'status' => 'read_only',
            'label' => 'Solo lectura',
            'description' => 'Este módulo permite consultar e inspeccionar datos, sin crear ni editar registros desde esta pantalla.',
            'secondary' => true,
        ];
    }

    private function deleteActionFor(array $definition, mixed $record): bool
    {
        if ($this->definitionKey($definition) === 'seguridad.usuarios') {
            return false;
        }

        if ($this->definitionKey($definition) === 'seguridad.roles' && $record instanceof Role && $record->isSystemRole()) {
            return false;
        }

        return true;
    }

    private function supportsInlineCreate(array $definition): bool
    {
        return in_array($this->definitionKey($definition), [
            'catalogo.categorias',
            'catalogo.marcas',
            'catalogo.colores',
            'catalogo.tallas',
            'catalogo.temporadas',
        ], true);
    }

    private function recordKey($record): string|int
    {
        return method_exists($record, 'getKey') ? $record->getKey() : $record['id'];
    }

    private function baseQuery(array $definition): ?Builder
    {
        return match ($definition['kind']) {
            'taxonomy' => tap(($definition['modelClass'])::query(), function ($query) use ($definition) {
                if (!empty($definition['countRelation'])) {
                    $query->withCount($definition['countRelation']);
                }
            }),
            'users' => User::query()->with('rol'),
            'products' => Prenda::query()
                ->with(['marca', 'categoria', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes'])
                ->withCount(['variantes', 'imagenes', 'favoritos']),
            'variants' => PrendaVariante::query()
                ->with(['prenda', 'color', 'talla', 'inventario', 'imagenes'])
                ->withCount('imagenes'),
            'images' => ImagenPrenda::query()->with(['prenda', 'variante.prenda']),
            'inventory', 'alerts' => Inventario::query()->with(['variante.prenda', 'variante.color', 'variante.talla']),
            'sales' => Venta::query()
                ->with(['cliente', 'vendedor', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla'])
                ->withCount('detalles'),
            'reservations' => Reserva::query()
                ->with(['cliente', 'responsable', 'detalles.variante.prenda', 'detalles.variante.color', 'detalles.variante.talla'])
                ->withCount('detalles'),
            'favorites' => Favorito::query()->with(['usuario', 'prenda.marca', 'prenda.categoria', 'prenda.temporada']),
            default => null,
        };
    }

    private function indexRecords(array $definition, Request $request): Collection
    {
        if ($definition['kind'] === 'adjustments') {
            return $this->adjustmentTemplates();
        }

        $query = $this->baseQuery($definition);
        if (!$query) {
            return collect();
        }

        $records = match ($definition['kind']) {
            'taxonomy' => $query->orderBy($definition['nameField'])->get(),
            'users' => $query->orderByDesc('fec_usu')->orderByDesc('id')->get(),
            'products' => $query->orderByDesc('fec_pre')->orderByDesc('id_prenda')->get(),
            'variants' => $query->orderByDesc('fec_prv')->orderByDesc('id_prenda_variante')->get(),
            'images' => $query->orderBy('cod_prendas_igp')->orderBy('ord_igp')->orderByDesc('id_imagen_prenda')->get(),
            'inventory' => $query->orderByDesc('id_inventario')->get(),
            'alerts' => $query->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->get(),
            'sales' => $query->orderByDesc('fec_ven')->orderByDesc('id_venta')->get(),
            'reservations' => $query->orderByDesc('fec_res')->orderByDesc('id_reserva')->get(),
            'favorites' => $query->orderByDesc('fec_fav')->orderByDesc('id_favorito')->get(),
            default => collect(),
        };

        return $this->sortIndexRecords(
            $this->filterIndexRecords($records, $definition, $request),
            $definition,
            $request,
        )->values();
    }

    private function indexRows(array $definition, Request $request): array
    {
        return match ($definition['kind']) {
            'taxonomy' => $this->taxonomyRows($definition, $this->indexRecords($definition, $request)),
            'users' => $this->userRows($definition, $this->indexRecords($definition, $request), $request),
            'products' => $this->productRows($definition, $this->indexRecords($definition, $request)),
            'variants' => $this->variantRows($definition, $this->indexRecords($definition, $request)),
            'images' => $this->imageRows($definition, $this->indexRecords($definition, $request)),
            'inventory' => $this->inventoryRows($definition, $this->indexRecords($definition, $request)),
            'adjustments' => $this->adjustmentRows($definition, $this->indexRecords($definition, $request)),
            'alerts' => $this->alertRows($definition, $this->indexRecords($definition, $request)),
            'sales' => $this->saleRows($definition, $this->indexRecords($definition, $request)),
            'reservations' => $this->reservationRows($definition, $this->indexRecords($definition, $request)),
            'favorites' => $this->favoriteRows($definition, $this->indexRecords($definition, $request)),
            default => [],
        };
    }

    private function columns(array $definition, ?Request $request = null): array
    {
        return match ($definition['kind']) {
            'taxonomy' => [
                ['key' => 'summary', 'label' => 'Resumen'],
                ['key' => 'linked', 'label' => 'Cruces', 'align' => 'center'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'users' => $this->userColumns($request),
            'products' => [
                ['key' => 'brand', 'label' => 'Marca'],
                ['key' => 'category', 'label' => 'Categoría'],
                ['key' => 'variants', 'label' => 'Variantes', 'align' => 'center'],
                ['key' => 'stock', 'label' => 'Disponible', 'align' => 'right'],
                ['key' => 'price', 'label' => 'Precio base', 'align' => 'right'],
            ],
            'variants' => [
                ['key' => 'product', 'label' => 'Prenda'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'size', 'label' => 'Talla'],
                ['key' => 'stock', 'label' => 'Disponible', 'align' => 'right'],
            ],
            'images' => [
                ['key' => 'product', 'label' => 'Prenda'],
                ['key' => 'variant', 'label' => 'Variante'],
                ['key' => 'priority', 'label' => 'Prioridad', 'align' => 'center'],
                ['key' => 'source', 'label' => 'Fuente', 'align' => 'right'],
            ],
            'inventory' => [
                ['key' => 'sku', 'label' => 'Código interno'],
                ['key' => 'available', 'label' => 'Disponible', 'align' => 'right'],
                ['key' => 'reserved', 'label' => 'Reservado', 'align' => 'right'],
                ['key' => 'location', 'label' => 'Ubicación', 'align' => 'right'],
            ],
            'adjustments' => [
                ['key' => 'area', 'label' => 'Área'],
                ['key' => 'focus', 'label' => 'Enfoque'],
                ['key' => 'cut', 'label' => 'Corte'],
                ['key' => 'updated', 'label' => 'Actualizado', 'align' => 'right'],
            ],
            'alerts' => [
                ['key' => 'available', 'label' => 'Disponible', 'align' => 'right'],
                ['key' => 'reserved', 'label' => 'Reservado', 'align' => 'right'],
                ['key' => 'location', 'label' => 'Ubicación'],
                ['key' => 'follow', 'label' => 'Seguimiento', 'align' => 'right'],
            ],
            'sales' => [
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'type', 'label' => 'Tipo'],
                ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'reservations' => [
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'due', 'label' => 'Vence'],
                ['key' => 'items', 'label' => 'Artículos', 'align' => 'center'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'favorites' => [
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'brand', 'label' => 'Marca'],
                ['key' => 'category', 'label' => 'Categoría'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            default => [],
        };
    }

    private function metrics(array $definition): array
    {
        return match ($definition['kind']) {
            'taxonomy' => $this->taxonomyMetrics($definition),
            'users' => [
                ['label' => 'Usuarios', 'value' => (string) User::query()->count(), 'note' => 'Cuentas registradas.', 'tone' => 'accent'],
                ['label' => 'Superadmins', 'value' => (string) $this->countUsersByRoleSlug('superadmin'), 'note' => 'Acceso total al panel.', 'tone' => 'danger'],
                ['label' => 'Vendedores', 'value' => (string) $this->countUsersByRoleSlug('vendedor'), 'note' => 'Operación comercial.', 'tone' => 'warning'],
                ['label' => 'Clientes', 'value' => (string) $this->countUsersByRoleSlug('cliente'), 'note' => 'Cuentas del ecommerce.', 'tone' => 'success'],
            ],
            'products' => [
                ['label' => 'Prendas', 'value' => (string) Prenda::query()->count(), 'note' => 'Prendas registradas.', 'tone' => 'accent'],
                ['label' => 'Activas', 'value' => (string) Prenda::query()->where('est_pre', true)->count(), 'note' => 'Prendas visibles.', 'tone' => 'success'],
                ['label' => 'Variantes', 'value' => (string) PrendaVariante::query()->count(), 'note' => 'Cruces comerciales cargados.', 'tone' => 'neutral'],
                ['label' => 'Disponible', 'value' => (string) max(0, (int) Inventario::query()->sum('cdi_inv') - (int) Inventario::query()->sum('cre_inv')), 'note' => 'Existencias disponibles.', 'tone' => 'warning'],
            ],
            'variants' => [
                ['label' => 'Variantes', 'value' => (string) PrendaVariante::query()->count(), 'note' => 'Variantes registradas.', 'tone' => 'accent'],
                ['label' => 'Activas', 'value' => (string) PrendaVariante::query()->where('est_prv', true)->count(), 'note' => 'Variantes habilitadas.', 'tone' => 'success'],
                ['label' => 'Con existencias', 'value' => (string) PrendaVariante::query()->has('inventario')->count(), 'note' => 'Variantes con inventario.', 'tone' => 'neutral'],
                ['label' => 'Última alta', 'value' => $this->latestDate(PrendaVariante::query(), 'fec_prv'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'images' => [
                ['label' => 'Imágenes', 'value' => (string) ImagenPrenda::query()->count(), 'note' => 'Imágenes registradas.', 'tone' => 'accent'],
                ['label' => 'Principales', 'value' => (string) ImagenPrenda::query()->where('pri_igp', true)->count(), 'note' => 'Imágenes principales.', 'tone' => 'success'],
                ['label' => 'Por variante', 'value' => (string) ImagenPrenda::query()->whereNotNull('cod_prenda_variantes_igp')->count(), 'note' => 'Imágenes ligadas a variantes.', 'tone' => 'neutral'],
                ['label' => 'Última alta', 'value' => $this->latestDate(ImagenPrenda::query(), 'fec_igp'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'inventory' => [
                ['label' => 'Líneas', 'value' => (string) Inventario::query()->count(), 'note' => 'Registros de inventario.', 'tone' => 'accent'],
                ['label' => 'Disponible', 'value' => (string) (Inventario::query()->sum('cdi_inv') - Inventario::query()->sum('cre_inv')), 'note' => 'Existencias disponibles.', 'tone' => 'success'],
                ['label' => 'Reservado', 'value' => (string) Inventario::query()->sum('cre_inv'), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Existencias críticas', 'value' => (string) Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->count(), 'note' => 'Registros por revisar.', 'tone' => 'danger'],
            ],
            'adjustments' => [
                ['label' => 'Ajustes', 'value' => (string) $this->adjustmentTemplates()->count(), 'note' => 'Ajustes sugeridos.', 'tone' => 'accent'],
                ['label' => 'Existencias críticas', 'value' => (string) Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->count(), 'note' => 'Registros por revisar.', 'tone' => 'warning'],
                ['label' => 'Ventas', 'value' => (string) Venta::query()->count(), 'note' => 'Ventas registradas.', 'tone' => 'success'],
                ['label' => 'Reservas', 'value' => (string) Reserva::query()->count(), 'note' => 'Reservas activas.', 'tone' => 'neutral'],
            ],
            'alerts' => [
                ['label' => 'Alertas', 'value' => (string) Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->count(), 'note' => 'Registros con existencias bajas.', 'tone' => 'danger'],
                ['label' => 'Sin existencias', 'value' => (string) Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 0')->count(), 'note' => 'Registros sin disponible.', 'tone' => 'warning'],
                ['label' => 'Reservado', 'value' => (string) Inventario::query()->where('cre_inv', '>', 0)->count(), 'note' => 'Líneas con reserva activa.', 'tone' => 'accent'],
                ['label' => 'Ubicaciónes', 'value' => (string) Inventario::query()->whereNotNull('ubi_inv')->count(), 'note' => 'Ubicaciónes cargadas.', 'tone' => 'neutral'],
            ],
            'sales' => [
                ['label' => 'Ventas', 'value' => (string) Venta::query()->count(), 'note' => 'Operaciónes registradas.', 'tone' => 'accent'],
                ['label' => 'Facturación', 'value' => $this->money((float) Venta::query()->sum('tot_ven')), 'note' => 'Total acumulado.', 'tone' => 'success'],
                ['label' => 'Detalles', 'value' => (string) \App\Models\DetalleVenta::query()->count(), 'note' => 'Líneas de venta.', 'tone' => 'neutral'],
                ['label' => 'Última venta', 'value' => $this->latestDate(Venta::query(), 'fec_ven'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'reservations' => [
                ['label' => 'Reservas', 'value' => (string) Reserva::query()->count(), 'note' => 'Apartados registrados.', 'tone' => 'accent'],
                ['label' => 'Detalles', 'value' => (string) \App\Models\DetalleReserva::query()->count(), 'note' => 'Líneas de reserva.', 'tone' => 'neutral'],
                ['label' => 'Por vencer', 'value' => (string) Reserva::query()->whereNotNull('ven_res')->where('ven_res', '<=', now()->addDays(7))->count(), 'note' => 'Vencen en 7 días.', 'tone' => 'warning'],
                ['label' => 'Última reserva', 'value' => $this->latestDate(Reserva::query(), 'fec_res'), 'note' => 'Último registro.', 'tone' => 'success'],
            ],
            'favorites' => [
                ['label' => 'Favoritos', 'value' => (string) Favorito::query()->count(), 'note' => 'Favoritos registrados.', 'tone' => 'accent'],
                ['label' => 'Clientes', 'value' => (string) Favorito::query()->distinct('cod_usuarios_fav')->count('cod_usuarios_fav'), 'note' => 'Clientes con favoritos.', 'tone' => 'success'],
                ['label' => 'Prendas', 'value' => (string) Favorito::query()->distinct('cod_prendas_fav')->count('cod_prendas_fav'), 'note' => 'Prendas guardadas.', 'tone' => 'neutral'],
                ['label' => 'Último registro', 'value' => $this->latestDate(Favorito::query(), 'fec_fav'), 'note' => 'Último movimiento.', 'tone' => 'warning'],
            ],
            default => [],
        };
    }

    private function filterControls(array $definition, Request $request): array
    {
        return match ($definition['kind']) {
            'users' => [
                [
                    'key' => 'search',
                    'label' => 'Buscar',
                    'type' => 'text',
                    'value' => $request->string('search')->toString(),
                    'placeholder' => 'Nombre o correo',
                ],
                $this->selectFilter('role', 'Rol', $request, $this->managedUserRoleOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Activos', 'Inactivos')),
            ],
            'products' => [
                $this->selectFilter('category', 'Categoría', $request, $this->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->genderOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Visibles', 'Ocultas')),
                $this->selectFilter('stock', 'Disponibilidad', $request, $this->stockFilterOptions()),
                $this->numberFilter('price_min', 'Precio desde', $request),
                $this->numberFilter('price_max', 'Precio hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'variants' => [
                $this->selectFilter('category', 'Categoría', $request, $this->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->genderOptions()),
                $this->selectFilter('color', 'Color', $request, $this->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Activas', 'Pausadas')),
                $this->selectFilter('stock', 'Disponibilidad', $request, $this->stockFilterOptions()),
                $this->numberFilter('price_min', 'Precio desde', $request),
                $this->numberFilter('price_max', 'Precio hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'inventory', 'alerts' => [
                $this->selectFilter('category', 'Categoría', $request, $this->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->genderOptions()),
                $this->selectFilter('color', 'Color', $request, $this->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->sizeOptions()),
                $this->selectFilter('stock', 'Disponibilidad', $request, $this->stockFilterOptions()),
            ],
            'sales' => [
                $this->selectFilter('category', 'Categoría', $request, $this->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
                $this->selectFilter('color', 'Color', $request, $this->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->distinctTextOptions('ventas', 'est_ven')),
                $this->numberFilter('price_min', 'Total desde', $request),
                $this->numberFilter('price_max', 'Total hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'reservations' => [
                $this->selectFilter('category', 'Categoría', $request, $this->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->brandOptions()),
                $this->selectFilter('color', 'Color', $request, $this->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->distinctTextOptions('reservas', 'est_res')),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            default => [],
        };
    }

    private function sortOptions(array $definition): array
    {
        return match ($definition['kind']) {
            'users' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Rol', 'value' => 'role'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'products' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Precio', 'value' => 'price'],
                ['label' => 'Existencias', 'value' => 'stock'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'variants' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Precio', 'value' => 'price'],
                ['label' => 'Existencias', 'value' => 'stock'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'inventory', 'alerts' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Existencias', 'value' => 'stock'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'sales' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Precio', 'value' => 'price'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            'reservations' => [
                ['label' => 'Nombre', 'value' => 'name'],
                ['label' => 'Fecha', 'value' => 'date'],
                ['label' => 'Estado', 'value' => 'status'],
            ],
            default => [],
        };
    }

    private function appliedFilters(array $controls, array $sortOptions, Request $request): array
    {
        $applied = collect($controls)
            ->map(function ($control) {
                $value = trim((string) ($control['value'] ?? ''));

                if ($value === '') {
                    return null;
                }

                if (($control['type'] ?? 'text') === 'select') {
                    $label = collect($control['options'] ?? [])
                        ->firstWhere('value', $value)['label'] ?? $value;

                    return [
                        'label' => $control['label'],
                        'value' => $label,
                    ];
                }

                return [
                    'label' => $control['label'],
                    'value' => $value,
                ];
            })
            ->filter()
            ->values();

        $sortBy = $request->string('sort_by')->toString();

        if ($sortBy !== '') {
            $sortLabel = collect($sortOptions)->firstWhere('value', $sortBy)['label'] ?? $sortBy;
            $applied->push([
                'label' => 'Orden',
                'value' => $sortLabel . ' · ' . ($request->input('sort_dir', 'desc') === 'asc' ? 'Ascendente' : 'Descendente'),
            ]);
        }

        if ($request->string('role_slug')->toString() !== '') {
            $role = $this->managedUserRoles()
                ->firstWhere('slug', $request->string('role_slug')->toString());

            if ($role) {
                $applied->prepend([
                    'label' => 'Grupo',
                    'value' => $role['label'],
                ]);
            }
        }

        return $applied->all();
    }

    private function selectFilter(string $key, string $label, Request $request, array $options): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'select',
            'value' => $request->string($key)->toString(),
            'options' => $options,
        ];
    }

    private function numberFilter(string $key, string $label, Request $request): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'value' => $request->string($key)->toString(),
            'placeholder' => '0',
        ];
    }

    private function dateFilter(string $key, string $label, Request $request): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'date',
            'value' => $request->string($key)->toString(),
        ];
    }

    private function stockFilterOptions(): array
    {
        return [
            ['label' => 'Disponible', 'value' => 'disponible'],
            ['label' => 'Bajo', 'value' => 'bajo'],
            ['label' => 'Agotado', 'value' => 'agotado'],
        ];
    }

    private function booleanSelectOptions(string $trueLabel, string $falseLabel): array
    {
        return [
            ['label' => $trueLabel, 'value' => '1'],
            ['label' => $falseLabel, 'value' => '0'],
        ];
    }

    private function distinctTextOptions(string $table, string $column): array
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value) => [
                'label' => (string) $value,
                'value' => (string) $value,
            ])
            ->values()
            ->all();
    }

    private function filterIndexRecords(Collection $records, array $definition, Request $request): Collection
    {
        $key = $this->definitionKey($definition);

        return $records->filter(function ($record) use ($key, $request) {
            return match ($key) {
                'seguridad.usuarios' => $this->userMatchesFilters($record, $request),
                'catalogo.prendas' => $this->productMatchesFilters($record, $request),
                'catalogo.variantes' => $this->variantMatchesFilters($record, $request),
                'inventario.stock', 'inventario.alertas' => $this->inventoryMatchesFilters($record, $request),
                'comercial.ventas' => $this->saleMatchesFilters($record, $request),
                'comercial.reservas' => $this->reservationMatchesFilters($record, $request),
                default => true,
            };
        });
    }

    private function sortIndexRecords(Collection $records, array $definition, Request $request): Collection
    {
        $sortBy = $request->string('sort_by')->toString();
        $direction = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $key = $this->definitionKey($definition);

        if ($sortBy === '') {
            return $records;
        }

        $resolver = match ($key) {
            'seguridad.usuarios' => fn ($record) => match ($sortBy) {
                'name' => $this->userName($record),
                'date' => $record->fec_usu?->timestamp ?? 0,
                'role' => mb_strtolower((string) ($record->rol?->nom_rol ?? ''), 'UTF-8'),
                'status' => $record->est_usu ? 1 : 0,
                default => $this->userName($record),
            },
            'catalogo.prendas' => fn ($record) => match ($sortBy) {
                'name' => $record->nom_pre ?? '',
                'date' => $record->fec_pre?->timestamp ?? 0,
                'price' => (float) $record->prb_pre,
                'stock' => $this->productAvailable($record),
                'status' => $record->est_pre ? 1 : 0,
                default => $record->nom_pre ?? '',
            },
            'catalogo.variantes' => fn ($record) => match ($sortBy) {
                'name' => $record->sku_prv ?: ($record->prenda?->nom_pre ?? ''),
                'date' => $record->fec_prv?->timestamp ?? 0,
                'price' => (float) $record->pve_prv,
                'stock' => $this->available($record->inventario),
                'status' => $record->est_prv ? 1 : 0,
                default => $record->sku_prv ?: ($record->prenda?->nom_pre ?? ''),
            },
            'inventario.stock', 'inventario.alertas' => fn ($record) => match ($sortBy) {
                'name' => trim((string) ($record->variante?->prenda?->nom_pre . ' ' . ($record->variante?->sku_prv ?? ''))),
                'stock' => $this->available($record),
                'status' => $this->stockStateScore($this->available($record)),
                default => trim((string) ($record->variante?->prenda?->nom_pre . ' ' . ($record->variante?->sku_prv ?? ''))),
            },
            'comercial.ventas' => fn ($record) => match ($sortBy) {
                'name' => $record->nve_ven ?: $this->userName($record->cliente),
                'date' => $record->fec_ven?->timestamp ?? 0,
                'price' => (float) $record->tot_ven,
                'status' => (string) ($record->est_ven ?? ''),
                default => $record->nve_ven ?: $this->userName($record->cliente),
            },
            'comercial.reservas' => fn ($record) => match ($sortBy) {
                'name' => $record->nre_res ?: $this->userName($record->cliente),
                'date' => $record->fec_res?->timestamp ?? 0,
                'status' => (string) ($record->est_res ?? ''),
                default => $record->nre_res ?: $this->userName($record->cliente),
            },
            default => null,
        };

        if (!$resolver) {
            return $records;
        }

        return $records->sortBy($resolver, SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc');
    }

    private function userMatchesFilters($record, Request $request): bool
    {
        $activeSegment = $request->string('role_slug')->toString();
        if ($activeSegment !== '' && mb_strtolower((string) ($record->rol?->nom_rol ?? ''), 'UTF-8') !== $activeSegment) {
            return false;
        }

        if (!$this->matchesOption($request->input('role'), $record->cod_roles_usu)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_usu)) {
            return false;
        }

        $search = trim($request->string('search')->toString());
        if ($search === '') {
            return true;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $this->userName($record),
            $record->email,
            $record->rol?->nom_rol,
        ]))), 'UTF-8');

        return str_contains($haystack, mb_strtolower($search, 'UTF-8'));
    }

    private function productMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->gen_pre)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_pre)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->prb_pre)) {
            return false;
        }

        if (!$this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_pre)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->productAvailable($record));
    }

    private function variantMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->prenda?->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->prenda?->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->prenda?->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->prenda?->gen_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('color'), $record->cod_colores_prv)) {
            return false;
        }

        if (!$this->matchesOption($request->input('size'), $record->cod_tallas_prv)) {
            return false;
        }

        if (!$this->matchesBooleanOption($request->input('status'), $record->est_prv)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->pve_prv)) {
            return false;
        }

        if (!$this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_prv)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->available($record->inventario));
    }

    private function inventoryMatchesFilters($record, Request $request): bool
    {
        if (!$this->matchesOption($request->input('category'), $record->variante?->prenda?->cod_categorias_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('brand'), $record->variante?->prenda?->cod_marcas_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('season'), $record->variante?->prenda?->cod_temporadas_pre)) {
            return false;
        }

        if (!$this->matchesText($request->input('gender'), $record->variante?->prenda?->gen_pre)) {
            return false;
        }

        if (!$this->matchesOption($request->input('color'), $record->variante?->cod_colores_prv)) {
            return false;
        }

        if (!$this->matchesOption($request->input('size'), $record->variante?->cod_tallas_prv)) {
            return false;
        }

        return $this->matchesStockFilter($request->input('stock'), $this->available($record));
    }

    private function saleMatchesFilters($record, Request $request): bool
    {
        $details = collect($record->detalles ?? []);

        if (!$this->matchesRelatedOption($request->input('category'), $details->pluck('variante.prenda.cod_categorias_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('brand'), $details->pluck('variante.prenda.cod_marcas_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('color'), $details->pluck('variante.cod_colores_prv'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('size'), $details->pluck('variante.cod_tallas_prv'))) {
            return false;
        }

        if (!$this->matchesText($request->input('status'), $record->est_ven)) {
            return false;
        }

        if (!$this->matchesRange($request->input('price_min'), $request->input('price_max'), (float) $record->tot_ven)) {
            return false;
        }

        return $this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_ven);
    }

    private function reservationMatchesFilters($record, Request $request): bool
    {
        $details = collect($record->detalles ?? []);

        if (!$this->matchesRelatedOption($request->input('category'), $details->pluck('variante.prenda.cod_categorias_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('brand'), $details->pluck('variante.prenda.cod_marcas_pre'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('color'), $details->pluck('variante.cod_colores_prv'))) {
            return false;
        }

        if (!$this->matchesRelatedOption($request->input('size'), $details->pluck('variante.cod_tallas_prv'))) {
            return false;
        }

        if (!$this->matchesText($request->input('status'), $record->est_res)) {
            return false;
        }

        return $this->matchesDateRange($request->input('date_from'), $request->input('date_to'), $record->fec_res);
    }

    private function matchesOption($selected, $actual): bool
    {
        return blank($selected) || (string) $selected === (string) $actual;
    }

    private function matchesRelatedOption($selected, Collection $values): bool
    {
        return blank($selected) || $values->filter()->contains(fn ($value) => (string) $value === (string) $selected);
    }

    private function matchesText($selected, $actual): bool
    {
        return blank($selected) || mb_strtolower((string) $selected, 'UTF-8') === mb_strtolower((string) $actual, 'UTF-8');
    }

    private function matchesBooleanOption($selected, ?bool $actual): bool
    {
        return blank($selected) || ((string) $selected === '1' ? (bool) $actual : !$actual);
    }

    private function matchesRange($min, $max, float $value): bool
    {
        if (filled($min) && $value < (float) $min) {
            return false;
        }

        if (filled($max) && $value > (float) $max) {
            return false;
        }

        return true;
    }

    private function matchesDateRange($from, $to, $value): bool
    {
        if (!$value) {
            return blank($from) && blank($to);
        }

        $date = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

        if (filled($from) && $date->lt(Carbon::parse($from)->startOfDay())) {
            return false;
        }

        if (filled($to) && $date->gt(Carbon::parse($to)->endOfDay())) {
            return false;
        }

        return true;
    }

    private function matchesStockFilter($selected, int $available): bool
    {
        if (blank($selected)) {
            return true;
        }

        return $this->stockState($available) === $selected;
    }

    private function stockState(int $available): string
    {
        return $available <= 0 ? 'agotado' : ($available <= 2 ? 'bajo' : 'disponible');
    }

    private function stockStateScore(int $available): int
    {
        return match ($this->stockState($available)) {
            'agotado' => 0,
            'bajo' => 1,
            default => 2,
        };
    }

    private function productAvailable($product): int
    {
        return collect($product->variantes ?? [])
            ->sum(fn ($variant) => $this->available($variant->inventario));
    }

    private function taxonomyMetrics(array $definition): array
    {
        $modelClass = $definition['modelClass'];
        $query = $modelClass::query();
        $linkedCount = !empty($definition['countRelation'])
            ? $modelClass::has($definition['countRelation'])->count()
            : 0;

        return [
            ['label' => $definition['title'], 'value' => (string) $query->count(), 'note' => 'Registros cargados.', 'tone' => 'accent'],
            ['label' => 'Activos', 'value' => (string) $modelClass::where($definition['statusField'], true)->count(), 'note' => 'Registros habilitados.', 'tone' => 'success'],
            ['label' => 'Con cruces', 'value' => (string) $linkedCount, 'note' => 'Registros vinculados.', 'tone' => 'neutral'],
            ['label' => 'Última alta', 'value' => $this->latestDate($query, $definition['dateField']), 'note' => 'Último registro.', 'tone' => 'warning'],
        ];
    }

    private function validateWrite(Request $request, array $definition, $record = null): array
    {
        $key = $this->definitionKey($definition);
        $rules = match ($key) {
            'seguridad.roles' => [
                'nom_rol' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u', Rule::unique('roles', 'nom_rol')->ignore($record?->id_rol, 'id_rol')],
                'des_rol' => ['nullable', 'string', 'max:255'],
                'est_rol' => ['nullable', 'boolean'],
            ],
            'seguridad.usuarios' => UserValidation::adminRules($record instanceof User ? $record : null),
            'catalogo.categorias' => [
                'nom_cat' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s]+$/u', Rule::unique('categorias', 'nom_cat')->ignore($record?->id_categoria, 'id_categoria')],
                'des_cat' => ['nullable', 'string', 'max:255'],
                'cod_categoria_padre_cat' => ['nullable', 'integer', Rule::exists('categorias', 'id_categoria')],
                'tip_tal_cat' => ['nullable', 'string', Rule::in(['ALFA', 'NUMERICA', 'UNITALLA'])],
                'est_cat' => ['nullable', 'boolean'],
            ],
            'catalogo.marcas' => [
                'nom_mar' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s&.\-]+$/u', Rule::unique('marcas', 'nom_mar')->ignore($record?->id_marca, 'id_marca')],
                'des_mar' => ['nullable', 'string', 'max:255'],
                'est_mar' => ['nullable', 'boolean'],
            ],
            'catalogo.colores' => [
                'nom_col' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u', Rule::unique('colores', 'nom_col')->ignore($record?->id_color, 'id_color')],
                'fam_col' => ['nullable', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u'],
                'hex_col' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
                'hex_dom_col' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
                'ord_col' => ['nullable', 'integer', 'min:0'],
                'est_col' => ['nullable', 'boolean'],
            ],
            'catalogo.tallas' => [
                'nom_tal' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('tallas', 'nom_tal')->ignore($record?->id_talla, 'id_talla')],
                'ord_tal' => ['required', 'integer', 'min:0'],
                'sis_tal' => ['nullable', 'string', Rule::in(['UNICA', 'ALFA', 'NUMERICA'])],
                'gru_tal' => ['nullable', 'string', Rule::in(['GENERAL', 'SUPERIOR', 'INFERIOR'])],
                'est_tal' => ['nullable', 'boolean'],
            ],
            'catalogo.temporadas' => [
                'nom_tem' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u', Rule::unique('temporadas', 'nom_tem')->ignore($record?->id_temporada, 'id_temporada')],
                'cod_tem' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-]+$/'],
                'des_tem' => ['nullable', 'string', 'max:255'],
                'ord_tem' => ['nullable', 'integer', 'min:0'],
                'est_tem' => ['nullable', 'boolean'],
            ],
            'catalogo.prendas' => [
                'nom_pre' => ['required', 'string', 'max:150', 'regex:/^[\p{L}\p{N}\s\-_.]+$/u'],
                'des_pre' => ['nullable', 'string'],
                'cod_categorias_pre' => ['required', 'integer', Rule::exists('categorias', 'id_categoria')],
                'cod_marcas_pre' => ['required', 'integer', Rule::exists('marcas', 'id_marca')],
                'cod_temporadas_pre' => ['required', 'integer', Rule::exists('temporadas', 'id_temporada')],
                'gen_pre' => ['required', 'string', Rule::in(['mujer', 'hombre', 'unisex'])],
                'prb_pre' => ['required', 'numeric', 'min:0.01'],
                'est_pre' => ['nullable', 'boolean'],
                'usa_variantes' => ['nullable', 'boolean'],
                'imagen_principal' => [$record ? 'nullable' : 'required', 'file', 'image', 'max:6144'],
                'fue_igp' => ['nullable', 'string', 'max:50'],
                'existing_images' => ['nullable', 'array'],
                'existing_images.*.id' => ['required', 'integer', Rule::exists('imagenes_prenda', 'id_imagen_prenda')],
                'existing_images.*.ord_igp' => ['required', 'integer', 'min:0'],
                'existing_images.*.pri_igp' => ['nullable', 'boolean'],
                'existing_images.*.fue_igp' => ['nullable', 'string', 'max:50'],
                'existing_images.*.remove' => ['nullable', 'boolean'],
                'gallery_images' => ['nullable', 'array'],
                'gallery_images.*.archivo' => ['nullable', 'file', 'image', 'max:6144'],
                'gallery_images.*.ord_igp' => ['required_with:gallery_images.*.archivo', 'integer', 'min:0'],
                'gallery_images.*.pri_igp' => ['nullable', 'boolean'],
                'gallery_images.*.fue_igp' => ['nullable', 'string', 'max:50'],
                'variant_rows' => ['required', 'array', 'min:1'],
                'variant_rows.*.id' => ['nullable', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
                'variant_rows.*.cod_colores_prv' => ['required', 'integer', Rule::exists('colores', 'id_color')],
                'variant_rows.*.cod_tallas_prv' => ['required', 'integer', Rule::exists('tallas', 'id_talla')],
                'variant_rows.*.cob_prv' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-]+$/', 'distinct'],
                'variant_rows.*.pve_prv' => ['required', 'numeric', 'min:0.01'],
                'variant_rows.*.cos_prv' => ['nullable', 'numeric', 'min:0'],
                'variant_rows.*.stock_inicial' => ['required', 'integer', 'min:1'],
                'variant_rows.*.ubi_inv' => ['required', 'string', Rule::in(['DEPOSITO', 'TIENDA'])],
                'variant_rows.*.est_prv' => ['nullable', 'boolean'],
                'variant_rows.*.remove' => ['nullable', 'boolean'],
            ],
            'catalogo.variantes' => [
                'cod_prendas_prv' => ['required', 'integer', Rule::exists('prendas', 'id_prenda')],
                'cod_colores_prv' => ['required', 'integer', Rule::exists('colores', 'id_color')],
                'cod_tallas_prv' => ['required', 'integer', Rule::exists('tallas', 'id_talla')],
                'sku_prv' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('prenda_variantes', 'sku_prv')->ignore($record?->id_prenda_variante, 'id_prenda_variante')],
                'cob_prv' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('prenda_variantes', 'cob_prv')->ignore($record?->id_prenda_variante, 'id_prenda_variante')],
                'pve_prv' => ['required', 'numeric', 'min:0.01'],
                'cos_prv' => ['nullable', 'numeric', 'min:0'],
                'est_prv' => ['nullable', 'boolean'],
            ],
            'catalogo.imagenes' => [
                'cod_prendas_igp' => ['required', 'integer', Rule::exists('prendas', 'id_prenda')],
                'cod_prenda_variantes_igp' => ['nullable', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante')],
                'archivo_igp' => [$record ? 'nullable' : 'required', 'file', 'image', 'max:6144'],
                'pri_igp' => ['nullable', 'boolean'],
                'ord_igp' => ['required', 'integer', 'min:0'],
                'fue_igp' => ['nullable', 'string', 'max:50'],
            ],
            'inventario.stock' => [
                'cod_prenda_variantes_inv' => ['required', 'integer', Rule::exists('prenda_variantes', 'id_prenda_variante'), Rule::unique('inventarios', 'cod_prenda_variantes_inv')->ignore($record?->id_inventario, 'id_inventario')],
                'cdi_inv' => ['required', 'integer', 'min:0'],
                'cre_inv' => ['required', 'integer', 'min:0'],
                'ubi_inv' => ['required', 'string', Rule::in(['DEPOSITO', 'TIENDA'])],
            ],
            default => [],
        };

        $messages = [
            ...UserValidation::messages(),
            'regex' => 'Formato inválido.',
            'nom_rol.regex' => 'El rol solo puede contener letras y espacios; se guardará en MAYÚSCULAS.',
            'nom_cat.regex' => 'La categoría solo puede contener letras y espacios; se guardará en MAYÚSCULAS.',
            'nom_mar.regex' => 'La marca solo puede contener letras, números, espacios y signos comerciales simples.',
            'nom_col.regex' => 'El color solo puede contener letras y espacios; se guardará en MAYÚSCULAS.',
            'fam_col.regex' => 'La familia del color solo puede contener letras y espacios.',
            'nom_tal.regex' => 'La talla solo puede contener letras o números, sin espacios.',
            'nom_tem.regex' => 'La temporada solo puede contener letras y espacios; se guardará en MAYÚSCULAS.',
            'cod_tem.regex' => 'El código de temporada solo puede contener letras, números y guiones.',
            'nom_pre.regex' => 'El nombre de la prenda solo puede contener letras, números, espacios, guiones, puntos o guion bajo.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'tel_usu.regex' => 'El teléfono debe tener 8 dígitos y comenzar con 6 o 7.',
            'hex_col.regex' => 'El color debe usar formato HEX, por ejemplo #1A1A1A.',
            'hex_dom_col.regex' => 'La base dominante debe usar formato HEX, por ejemplo #1A1A1A.',
            'sku_prv.regex' => 'El código interno solo puede contener letras, números, guiones y guion bajo.',
            'cob_prv.regex' => 'El código adicional solo puede contener letras, números y guiones.',
            'archivo_igp.image' => 'Debes subir una imagen válida.',
            'archivo_igp.max' => 'La imagen no puede superar los 6 MB.',
            'imagen_principal.required' => 'Carga la imagen principal de la prenda.',
            'imagen_principal.image' => 'Debes subir una imagen válida.',
            'imagen_principal.max' => 'La imagen principal no puede superar los 6 MB.',
            'prb_pre.min' => 'El precio base debe ser mayor a 0.',
            'pve_prv.min' => 'El precio de venta debe ser mayor a 0.',
            'variant_rows.*.pve_prv.min' => 'El precio de venta debe ser mayor a 0.',
            'gallery_images.*.archivo.image' => 'Debes subir una imagen válida en la galería.',
            'gallery_images.*.archivo.max' => 'Cada imagen de galería puede pesar hasta 6 MB.',
            'variant_rows.*.stock_inicial.min' => 'La cantidad inicial debe ser al menos 1.',
            'variant_rows.*.ubi_inv.in' => 'Selecciona una ubicación válida.',
            'ubi_inv.in' => 'Selecciona una ubicación válida.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($key === 'seguridad.roles') {
            $validator->after(function ($validator) use ($request, $record) {
                $submittedName = mb_strtolower(trim((string) $request->input('nom_rol')), 'UTF-8');
                $duplicate = $this->hasNormalizedDuplicate(
                    Role::class,
                    'nom_rol',
                    $request->input('nom_rol'),
                    'id_rol',
                    $record?->id_rol,
                );

                if ($duplicate) {
                    $validator->errors()->add('nom_rol', 'Ya existe un rol con ese nombre.');
                }

                if (!$record && in_array($submittedName, Role::SYSTEM_ROLE_NAMES, true)) {
                    $validator->errors()->add('nom_rol', 'Ese rol pertenece al sistema y no puede duplicarse.');
                }

                if (!$record instanceof Role || !$record->isSystemRole()) {
                    return;
                }

                if ($submittedName !== $record->normalizedName()) {
                    $validator->errors()->add('nom_rol', 'Los roles del sistema no pueden cambiar de nombre.');
                }

                if (!$request->boolean('est_rol', (bool) $record->est_rol)) {
                    $validator->errors()->add('est_rol', 'Los roles del sistema deben permanecer activos.');
                }
            });
        }

        if (in_array($key, [
            'catalogo.categorias',
            'catalogo.marcas',
            'catalogo.colores',
            'catalogo.tallas',
            'catalogo.temporadas',
        ], true)) {
            $validator->after(function ($validator) use ($definition, $request, $record) {
                $model = new $definition['modelClass']();
                $nameField = $definition['nameField'];
                $primaryKey = $model->getKeyName();

                if ($this->hasNormalizedDuplicate(
                    $definition['modelClass'],
                    $nameField,
                    $request->input($nameField),
                    $primaryKey,
                    $record?->{$primaryKey},
                )) {
                    $validator->errors()->add($nameField, 'Ya existe un registro con ese nombre.');
                }
            });
        }

        if ($key === 'seguridad.usuarios') {
            $validator->after(function ($validator) use ($request, $record) {
                if (!$record instanceof User) {
                    return;
                }

                $authUser = $request->user();
                $isSameUser = $authUser && (int) $authUser->getAuthIdentifier() === (int) $record->id;

                // Autoprotección: el superadmin no puede desactivarse a sí mismo.
                if ($isSameUser && !$request->boolean('est_usu', (bool) $record->est_usu)) {
                    $validator->errors()->add('est_usu', 'No puedes desactivar tu propio usuario desde el panel.');
                }

                // Autoprotección: el superadmin no puede cambiar su propio rol.
                if ($isSameUser) {
                    $selectedRole = Role::query()->find($request->input('cod_roles_usu'));

                    if (!$selectedRole || strcasecmp((string) $selectedRole->nom_rol, 'superadmin') !== 0) {
                        $validator->errors()->add('cod_roles_usu', 'Tu usuario debe conservar el rol superadmin para mantener acceso al panel.');
                    }
                }

                // Protección del último superadmin activo cuando se edita a otro usuario.
                if (!$isSameUser) {
                    $record->loadMissing('rol');
                    $isCurrentlySuperadmin = $record->rol
                        && strcasecmp((string) $record->rol->nom_rol, 'superadmin') === 0;
                    $isCurrentlyActive = (bool) $record->est_usu;

                    if ($isCurrentlySuperadmin && $isCurrentlyActive) {
                        $activeSuperadminCount = User::query()
                            ->whereHas('rol', fn ($q) => $q->whereRaw('LOWER(nom_rol) = ?', ['superadmin']))
                            ->where('est_usu', true)
                            ->count();

                        if ($activeSuperadminCount <= 1) {
                            $tryingToDeactivate = !$request->boolean('est_usu', (bool) $record->est_usu);
                            if ($tryingToDeactivate) {
                                $validator->errors()->add('est_usu', 'No puedes desactivar este usuario porque es el único superadmin activo del sistema.');
                            }

                            $selectedRole = Role::query()->find($request->input('cod_roles_usu'));
                            $tryingToChangeRole = $selectedRole
                                && strcasecmp((string) $selectedRole->nom_rol, 'superadmin') !== 0;
                            if ($tryingToChangeRole) {
                                $validator->errors()->add('cod_roles_usu', 'No puedes cambiar el rol de este usuario porque es el único superadmin activo del sistema.');
                            }
                        }
                    }
                }
            });
        }

        if ($key === 'catalogo.categorias') {
            $validator->after(function ($validator) use ($request, $record) {
                if (
                    $record
                    && filled($request->input('cod_categoria_padre_cat'))
                    && (int) $request->input('cod_categoria_padre_cat') === (int) $record->id_categoria
                ) {
                    $validator->errors()->add('cod_categoria_padre_cat', 'La categoría padre no puede ser la misma categoría.');
                }
            });
        }

        if ($key === 'catalogo.variantes') {
            $validator->after(function ($validator) use ($request, $record) {
                $duplicate = PrendaVariante::query()
                    ->where('cod_prendas_prv', $request->input('cod_prendas_prv'))
                    ->where('cod_colores_prv', $request->input('cod_colores_prv'))
                    ->where('cod_tallas_prv', $request->input('cod_tallas_prv'))
                    ->when($record, fn ($query) => $query->where('id_prenda_variante', '!=', $record->id_prenda_variante))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('cod_tallas_prv', 'Ya existe una variante con esa prenda, color y talla.');
                }
            });
        }

        if ($key === 'catalogo.prendas') {
            $validator->after(function ($validator) use ($request, $record) {
                $variantRows = collect($request->input('variant_rows', []));
                $activeRows = $variantRows
                    ->reject(fn ($row) => filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL));
                $productName = $request->input('nom_pre');
                $duplicateProduct = Prenda::query()
                    ->whereRaw('UPPER(nom_pre) = ?', [mb_strtoupper(trim((string) $productName), 'UTF-8')])
                    ->where('cod_categorias_pre', $request->input('cod_categorias_pre'))
                    ->where('cod_marcas_pre', $request->input('cod_marcas_pre'))
                    ->when($record instanceof Prenda, fn ($query) => $query->where('id_prenda', '!=', $record->id_prenda))
                    ->exists();

                if ($duplicateProduct) {
                    $validator->errors()->add('nom_pre', 'Ya existe una prenda con ese nombre, categoría y marca.');
                }

                if ($activeRows->isEmpty()) {
                    $validator->errors()->add('variant_rows', 'Debes conservar al menos una variante operativa.');
                    return;
                }

                if (!$request->boolean('usa_variantes') && $activeRows->count() !== 1) {
                    $validator->errors()->add('variant_rows', 'Si la prenda es única, debe quedar una sola configuración activa.');
                }

                if ($request->boolean('usa_variantes') && $activeRows->count() < 2) {
                    $validator->errors()->add('variant_rows', 'Si la prenda tiene más tallas o colores, registra al menos dos configuraciones.');
                }

                $combinations = $activeRows
                    ->map(fn ($row) => ($row['cod_colores_prv'] ?? '') . ':' . ($row['cod_tallas_prv'] ?? ''))
                    ->filter()
                    ->values();

                if ($combinations->count() !== $combinations->unique()->count()) {
                    $validator->errors()->add('variant_rows', 'No puedes repetir la misma combinación de color y talla.');
                }

                foreach ($activeRows as $index => $row) {
                    $salePrice = (float) ($row['pve_prv'] ?? 0);
                    $cost = filled($row['cos_prv'] ?? null) ? (float) $row['cos_prv'] : null;

                    if ($cost !== null && $cost > $salePrice) {
                        $validator->errors()->add("variant_rows.$index.cos_prv", 'El costo interno no puede ser mayor al precio de venta.');
                    }

                    if (!filled($row['cob_prv'] ?? null)) {
                        continue;
                    }

                    $duplicateBarcode = PrendaVariante::query()
                        ->where('cob_prv', mb_strtoupper(trim((string) $row['cob_prv']), 'UTF-8'))
                        ->when(filled($row['id'] ?? null), fn ($query) => $query->where('id_prenda_variante', '!=', (int) $row['id']))
                        ->exists();

                    if ($duplicateBarcode) {
                        $validator->errors()->add("variant_rows.$index.cob_prv", 'Ese código adicional ya está en uso.');
                    }
                }

                if ($record instanceof Prenda) {
                    $submittedIds = $variantRows
                        ->pluck('id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $ownedIds = $record->variantes()->pluck('id_prenda_variante')->map(fn ($id) => (int) $id);
                    if ($submittedIds->diff($ownedIds)->isNotEmpty()) {
                        $validator->errors()->add('variant_rows', 'Hay variantes que no pertenecen a esta prenda.');
                    }

                    $submittedImageIds = collect($request->input('existing_images', []))
                        ->pluck('id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $ownedImageIds = $record->imagenes()->pluck('id_imagen_prenda')->map(fn ($id) => (int) $id);
                    if ($submittedImageIds->diff($ownedImageIds)->isNotEmpty()) {
                        $validator->errors()->add('existing_images', 'Hay imágenes que no pertenecen a esta prenda.');
                    }
                }

                $remainingExistingImages = collect($request->input('existing_images', []))
                    ->reject(fn ($image) => filter_var($image['remove'] ?? false, FILTER_VALIDATE_BOOL));
                $newGalleryImages = collect($request->input('gallery_images', []))
                    ->filter(fn ($image) => !empty($image['archivo']));
                $hasPrimaryExisting = $remainingExistingImages
                    ->contains(fn ($image) => filter_var($image['pri_igp'] ?? false, FILTER_VALIDATE_BOOL));
                $hasPrimaryGallery = $newGalleryImages
                    ->contains(fn ($image) => filter_var($image['pri_igp'] ?? false, FILTER_VALIDATE_BOOL));

                if (!$request->hasFile('imagen_principal') && $remainingExistingImages->isEmpty() && $newGalleryImages->isEmpty()) {
                    $validator->errors()->add('imagen_principal', 'Debes registrar una imagen principal para la prenda.');
                }

                if (!$request->hasFile('imagen_principal') && !$hasPrimaryExisting && !$hasPrimaryGallery) {
                    $validator->errors()->add('imagen_principal', 'Define una imagen principal para identificar la prenda.');
                }
            });
        }

        if ($key === 'catalogo.imagenes') {
            $validator->after(function ($validator) use ($request) {
                $variantId = $request->input('cod_prenda_variantes_igp');
                $productId = $request->input('cod_prendas_igp');

                if (!$variantId || !$productId) {
                    return;
                }

                $variantBelongsToProduct = PrendaVariante::query()
                    ->where('id_prenda_variante', $variantId)
                    ->where('cod_prendas_prv', $productId)
                    ->exists();

                if (!$variantBelongsToProduct) {
                    $validator->errors()->add('cod_prenda_variantes_igp', 'La variante seleccionada no pertenece a la prenda.');
                }
            });
        }

        if ($key === 'catalogo.variantes') {
            $validator->after(function ($validator) use ($request) {
                $salePrice = (float) $request->input('pve_prv', 0);
                $cost = filled($request->input('cos_prv')) ? (float) $request->input('cos_prv') : null;

                if ($cost !== null && $cost > $salePrice) {
                    $validator->errors()->add('cos_prv', 'El costo interno no puede ser mayor al precio de venta.');
                }
            });
        }

        if ($key === 'inventario.stock') {
            $validator->after(function ($validator) use ($request) {
                if ((int) $request->input('cre_inv', 0) > (int) $request->input('cdi_inv', 0)) {
                    $validator->errors()->add('cre_inv', 'La cantidad reservada no puede ser mayor al disponible.');
                }
            });
        }

        return $validator->validate();
    }

    private function normalizePayload(array $definition, array $validated, $record = null): array
    {
        $key = $this->definitionKey($definition);

        return match ($key) {
            'seguridad.roles' => [
                'nom_rol' => $record instanceof Role && $record->isSystemRole()
                    ? $record->nom_rol
                    : $this->normalizeUpper($validated['nom_rol']),
                'des_rol' => $this->normalizeNullableText($validated['des_rol'] ?? null),
                'est_rol' => $record instanceof Role && $record->isSystemRole()
                    ? true
                    : (bool) ($validated['est_rol'] ?? false),
                ...($record ? [] : ['fec_rol' => Carbon::now()]),
            ],
            'seguridad.usuarios' => [
                'nom_usu' => $this->normalizeUpper($validated['nom_usu']),
                'app_usu' => $this->normalizeUpper($validated['app_usu']),
                'apm_usu' => $this->normalizeUpper($validated['apm_usu'] ?? null),
                'tel_usu' => $this->normalizeNullableText($validated['tel_usu'] ?? null),
                'email' => mb_strtolower(trim($validated['email']), 'UTF-8'),
                'cod_roles_usu' => (int) $validated['cod_roles_usu'],
                'est_usu' => (bool) ($validated['est_usu'] ?? false),
                ...($record ? [] : [
                    'password' => Hash::make($validated['password']),
                    'fec_usu' => Carbon::now(),
                ]),
            ],
            'catalogo.categorias' => [
                'nom_cat' => $this->normalizeUpper($validated['nom_cat']),
                'des_cat' => $this->normalizeNullableText($validated['des_cat'] ?? null),
                'cod_categoria_padre_cat' => filled($validated['cod_categoria_padre_cat'] ?? null) ? (int) $validated['cod_categoria_padre_cat'] : null,
                'tip_tal_cat' => Schema::hasColumn('categorias', 'tip_tal_cat')
                    ? ($this->normalizeUpper($validated['tip_tal_cat'] ?? null) ?? $this->inferCategorySizeType($validated['nom_cat']))
                    : null,
                'est_cat' => (bool) ($validated['est_cat'] ?? false),
                ...($record ? [] : ['fec_cat' => Carbon::now()]),
            ],
            'catalogo.marcas' => [
                'nom_mar' => $this->normalizeUpper($validated['nom_mar']),
                'des_mar' => $this->normalizeNullableText($validated['des_mar'] ?? null),
                'est_mar' => (bool) ($validated['est_mar'] ?? false),
                ...($record ? [] : ['fec_mar' => Carbon::now()]),
            ],
            'catalogo.colores' => [
                'nom_col' => $this->normalizeUpper($validated['nom_col']),
                'fam_col' => Schema::hasColumn('colores', 'fam_col')
                    ? $this->normalizeUpper($validated['fam_col'] ?? $validated['nom_col'])
                    : null,
                'hex_col' => filled($validated['hex_col'] ?? null) ? mb_strtoupper(trim($validated['hex_col']), 'UTF-8') : null,
                'hex_dom_col' => Schema::hasColumn('colores', 'hex_dom_col')
                    ? (filled($validated['hex_dom_col'] ?? $validated['hex_col'] ?? null)
                        ? mb_strtoupper(trim((string) ($validated['hex_dom_col'] ?? $validated['hex_col'])), 'UTF-8')
                        : null)
                    : null,
                'ord_col' => Schema::hasColumn('colores', 'ord_col')
                    ? (filled($validated['ord_col'] ?? null) ? (int) $validated['ord_col'] : $this->nextColorOrder())
                    : null,
                'est_col' => (bool) ($validated['est_col'] ?? false),
                ...($record ? [] : ['fec_col' => Carbon::now()]),
            ],
            'catalogo.tallas' => [
                'nom_tal' => $this->normalizeUpper($validated['nom_tal']),
                'ord_tal' => (int) $validated['ord_tal'],
                'sis_tal' => Schema::hasColumn('tallas', 'sis_tal')
                    ? ($this->sizeMetadata((string) $validated['nom_tal'])['sis_tal'] ?? null)
                    : null,
                'gru_tal' => Schema::hasColumn('tallas', 'gru_tal')
                    ? ($this->sizeMetadata((string) $validated['nom_tal'])['gru_tal'] ?? null)
                    : null,
                'est_tal' => (bool) ($validated['est_tal'] ?? false),
                ...($record ? [] : ['fec_tal' => Carbon::now()]),
            ],
            'catalogo.temporadas' => [
                'nom_tem' => $this->normalizeUpper($validated['nom_tem']),
                'cod_tem' => Schema::hasColumn('temporadas', 'cod_tem')
                    ? ($this->normalizeUpper($validated['cod_tem'] ?? null) ?? $this->buildSeasonCode((string) $validated['nom_tem']))
                    : null,
                'des_tem' => $this->normalizeNullableText($validated['des_tem'] ?? null),
                'ord_tem' => Schema::hasColumn('temporadas', 'ord_tem')
                    ? (filled($validated['ord_tem'] ?? null) ? (int) $validated['ord_tem'] : $this->nextSeasonOrder())
                    : null,
                'est_tem' => (bool) ($validated['est_tem'] ?? false),
                ...($record ? [] : ['fec_tem' => Carbon::now()]),
            ],
            'catalogo.prendas' => [
                'nom_pre' => $this->normalizeUpper($validated['nom_pre']),
                'des_pre' => $this->normalizeNullableText($validated['des_pre'] ?? null),
                'cod_categorias_pre' => (int) $validated['cod_categorias_pre'],
                'cod_marcas_pre' => filled($validated['cod_marcas_pre'] ?? null) ? (int) $validated['cod_marcas_pre'] : null,
                'cod_temporadas_pre' => filled($validated['cod_temporadas_pre'] ?? null) ? (int) $validated['cod_temporadas_pre'] : null,
                'gen_pre' => filled($validated['gen_pre'] ?? null) ? mb_strtolower(trim((string) $validated['gen_pre']), 'UTF-8') : null,
                'prb_pre' => (float) $validated['prb_pre'],
                'est_pre' => (bool) ($validated['est_pre'] ?? false),
                ...($record ? [] : ['fec_pre' => Carbon::now()]),
            ],
            'catalogo.variantes' => [
                'cod_prendas_prv' => (int) $validated['cod_prendas_prv'],
                'cod_colores_prv' => (int) $validated['cod_colores_prv'],
                'cod_tallas_prv' => (int) $validated['cod_tallas_prv'],
                'sku_prv' => mb_strtoupper(trim((string) $validated['sku_prv']), 'UTF-8'),
                'cob_prv' => filled($validated['cob_prv'] ?? null) ? mb_strtoupper(trim((string) $validated['cob_prv']), 'UTF-8') : null,
                'pve_prv' => (float) $validated['pve_prv'],
                'cos_prv' => filled($validated['cos_prv'] ?? null) ? (float) $validated['cos_prv'] : null,
                'est_prv' => (bool) ($validated['est_prv'] ?? false),
                ...($record ? [] : ['fec_prv' => Carbon::now()]),
            ],
            'catalogo.imagenes' => [
                'cod_prendas_igp' => (int) $validated['cod_prendas_igp'],
                'cod_prenda_variantes_igp' => filled($validated['cod_prenda_variantes_igp'] ?? null) ? (int) $validated['cod_prenda_variantes_igp'] : null,
                'pri_igp' => (bool) ($validated['pri_igp'] ?? false),
                'ord_igp' => (int) $validated['ord_igp'],
                'fue_igp' => $this->normalizeNullableText($validated['fue_igp'] ?? null),
                ...($record ? [] : ['fec_igp' => Carbon::now()]),
            ],
            'inventario.stock' => [
                'cod_prenda_variantes_inv' => (int) $validated['cod_prenda_variantes_inv'],
                'cdi_inv' => (int) $validated['cdi_inv'],
                'cre_inv' => (int) $validated['cre_inv'],
                'ubi_inv' => $this->normalizeNullableText($validated['ubi_inv'] ?? null),
            ],
            default => [],
        };
    }

    private function createRecord(Request $request, array $definition, array $validated)
    {
        $key = $this->definitionKey($definition);
        $modelClass = $definition['modelClass'];
        $payload = $this->normalizePayload($definition, $validated);

        return match ($key) {
            'catalogo.prendas' => $this->createProductRecord($request, $payload, $validated),
            'catalogo.imagenes' => $this->createImageRecord($validated, $payload),
            default => $modelClass::query()->create($payload),
        };
    }

    private function updateRecord(Request $request, array $definition, array $validated, $record): void
    {
        $key = $this->definitionKey($definition);
        $payload = $this->normalizePayload($definition, $validated, $record);

        match ($key) {
            'catalogo.prendas' => $this->updateProductRecord($request, $record, $payload, $validated),
            'catalogo.imagenes' => $this->updateImageRecord($record, $validated, $payload),
            default => $record->update($payload),
        };
    }

    private function deleteRecord(array $definition, $record): void
    {
        if ($this->definitionKey($definition) === 'catalogo.imagenes') {
            $productId = $record->cod_prendas_igp;
            $wasPrimary = (bool) $record->pri_igp;
            $path = $record->rut_igp;
            $record->delete();
            $this->deleteImageFile($path);

            if ($wasPrimary) {
                $this->ensurePrimaryImage((int) $productId);
            }

            return;
        }

        $record->delete();
    }

    private function createImageRecord(array $validated, array $payload): ImagenPrenda
    {
        $storedPath = $this->storePublicProductImage(
            $validated['archivo_igp'],
            (int) $payload['cod_prendas_igp'],
            (string) ($payload['fue_igp'] ?? 'imagen'),
        );

        try {
            return DB::transaction(function () use ($payload, $storedPath) {
                $image = ImagenPrenda::query()->create([
                    ...$payload,
                    'rut_igp' => $storedPath,
                    'url_igp' => Storage::url($storedPath),
                ]);

                $this->syncPrimaryImage($image);
                $this->ensurePrimaryImage((int) $image->cod_prendas_igp);

                return $image;
            });
        } catch (\Throwable $exception) {
            $this->deleteImageFile($storedPath);

            throw $exception;
        }
    }

    private function createProductRecord(Request $request, array $payload, array $validated): Prenda
    {
        $createdPaths = [];
        try {
            return DB::transaction(function () use ($request, $payload, $validated, &$createdPaths) {
                $product = Prenda::query()->create([
                    ...$payload,
                    'cod_usuario_registra_pre' => $request->user()->getAuthIdentifier(),
                ]);

                $this->syncProductVariants($product, $validated['variant_rows'] ?? []);
                $this->replaceOrCreatePrimaryProductImage(
                    $product,
                    $validated['imagen_principal'],
                    $validated['fue_igp'] ?? 'catalogo',
                    $createdPaths,
                );
                $this->createProductGalleryImages($product, $validated['gallery_images'] ?? [], $createdPaths);
                $this->ensurePrimaryImage((int) $product->id_prenda);

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                $this->deleteImageFile($path);
            }

            throw $exception;
        }
    }

    private function updateProductRecord(Request $request, Prenda $product, array $payload, array $validated): void
    {
        $createdPaths = [];
        $deleteAfterSuccess = [];

        try {
            DB::transaction(function () use ($product, $payload, $validated, &$createdPaths, &$deleteAfterSuccess) {
                $product->update($payload);

                $this->syncProductExistingImages($product, $validated['existing_images'] ?? [], $deleteAfterSuccess);
                $this->syncProductVariants($product->fresh(['variantes.inventario', 'imagenes']), $validated['variant_rows'] ?? []);
                $this->createProductGalleryImages($product, $validated['gallery_images'] ?? [], $createdPaths);

                if (!empty($validated['imagen_principal'])) {
                    $this->replaceOrCreatePrimaryProductImage(
                        $product,
                        $validated['imagen_principal'],
                        $validated['fue_igp'] ?? 'catalogo',
                        $createdPaths,
                        $deleteAfterSuccess,
                    );
                }

                $this->ensurePrimaryImage((int) $product->id_prenda);
            });
        } catch (\Throwable $exception) {
            foreach ($createdPaths as $path) {
                $this->deleteImageFile($path);
            }

            throw $exception;
        }

        foreach ($deleteAfterSuccess as $path) {
            $this->deleteImageFile($path);
        }
    }

    private function syncProductVariants(Prenda $product, array $rows): void
    {
        $product->loadMissing('categoria', 'variantes.inventario', 'variantes.imagenes');

        $existingVariants = $product->variantes->keyBy('id_prenda_variante');
        $baseCode = $this->buildProductBaseCode($product);
        $usedCodes = [];

        foreach ($rows as $row) {
            $remove = filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL);
            $variantId = filled($row['id'] ?? null) ? (int) $row['id'] : null;

            if ($remove) {
                if ($variantId && $existingVariants->has($variantId)) {
                    $this->removeProductVariant($existingVariants->get($variantId));
                }

                continue;
            }

            $code = null;
            $inventoryPayload = [
                'cdi_inv' => (int) $row['stock_inicial'],
                'cre_inv' => 0,
                'ubi_inv' => $this->normalizeNullableText($row['ubi_inv'] ?? null),
            ];

            if ($variantId && $existingVariants->has($variantId)) {
                /** @var PrendaVariante $variant */
                $variant = $existingVariants->get($variantId);
                $code = $variant->sku_prv ?: $this->nextVariantInternalCode($baseCode, $usedCodes);

                $variant->update([
                    'cod_colores_prv' => (int) $row['cod_colores_prv'],
                    'cod_tallas_prv' => (int) $row['cod_tallas_prv'],
                    'sku_prv' => $code,
                    'cob_prv' => filled($row['cob_prv'] ?? null)
                        ? mb_strtoupper(trim((string) $row['cob_prv']), 'UTF-8')
                        : null,
                    'pve_prv' => (float) $row['pve_prv'],
                    'cos_prv' => filled($row['cos_prv'] ?? null) ? (float) $row['cos_prv'] : null,
                    'est_prv' => (bool) ($row['est_prv'] ?? true),
                ]);

                if ($variant->inventario) {
                    $variant->inventario->update($inventoryPayload);
                } else {
                    Inventario::query()->create([
                        'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
                        ...$inventoryPayload,
                    ]);
                }
            } else {
                $code = $this->nextVariantInternalCode($baseCode, $usedCodes);

                $variant = PrendaVariante::query()->create([
                    'cod_prendas_prv' => $product->id_prenda,
                    'cod_colores_prv' => (int) $row['cod_colores_prv'],
                    'cod_tallas_prv' => (int) $row['cod_tallas_prv'],
                    'sku_prv' => $code,
                    'cob_prv' => filled($row['cob_prv'] ?? null)
                        ? mb_strtoupper(trim((string) $row['cob_prv']), 'UTF-8')
                        : null,
                    'pve_prv' => (float) $row['pve_prv'],
                    'cos_prv' => filled($row['cos_prv'] ?? null) ? (float) $row['cos_prv'] : null,
                    'est_prv' => (bool) ($row['est_prv'] ?? true),
                    'fec_prv' => Carbon::now(),
                ]);

                Inventario::query()->create([
                    'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
                    ...$inventoryPayload,
                ]);
            }

            $usedCodes[] = $code;
        }
    }

    private function removeProductVariant(PrendaVariante $variant): void
    {
        if (DetalleVenta::query()->where('cod_prenda_variantes_dve', $variant->id_prenda_variante)->exists()) {
            throw ValidationException::withMessages([
                'variant_rows' => 'No puedes quitar una variante que ya tiene ventas asociadas.',
            ]);
        }

        if (DetalleReserva::query()->where('cod_prenda_variantes_dre', $variant->id_prenda_variante)->exists()) {
            throw ValidationException::withMessages([
                'variant_rows' => 'No puedes quitar una variante que ya tiene reservas asociadas.',
            ]);
        }

        $variant->loadMissing('inventario', 'imagenes');

        if ($variant->imagenes->isNotEmpty()) {
            ImagenPrenda::query()
                ->where('cod_prenda_variantes_igp', $variant->id_prenda_variante)
                ->update(['cod_prenda_variantes_igp' => null]);
        }

        $variant->inventario?->delete();
        $variant->delete();
    }

    private function syncProductExistingImages(Prenda $product, array $rows, array &$deleteAfterSuccess): void
    {
        $images = ImagenPrenda::query()
            ->where('cod_prendas_igp', $product->id_prenda)
            ->get()
            ->keyBy('id_imagen_prenda');

        foreach ($rows as $row) {
            $imageId = (int) $row['id'];
            $image = $images->get($imageId);

            if (!$image) {
                continue;
            }

            if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL)) {
                if ($image->rut_igp) {
                    $deleteAfterSuccess[] = $image->rut_igp;
                }

                $image->delete();
                continue;
            }

            $image->update([
                'pri_igp' => (bool) ($row['pri_igp'] ?? false),
                'ord_igp' => (int) ($row['ord_igp'] ?? 0),
                'fue_igp' => $this->normalizeNullableText($row['fue_igp'] ?? $image->fue_igp),
            ]);

            $this->syncPrimaryImage($image->fresh());
        }
    }

    private function createProductGalleryImages(Prenda $product, array $rows, array &$createdPaths): void
    {
        foreach ($rows as $row) {
            if (empty($row['archivo'])) {
                continue;
            }

            $storedPath = $this->storePublicProductImage(
                $row['archivo'],
                (int) $product->id_prenda,
                (string) ($row['fue_igp'] ?? $product->nom_pre ?? 'galeria'),
            );

            $createdPaths[] = $storedPath;

            $image = ImagenPrenda::query()->create([
                'cod_prendas_igp' => $product->id_prenda,
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $storedPath,
                'url_igp' => Storage::url($storedPath),
                'pri_igp' => false,
                'ord_igp' => (int) ($row['ord_igp'] ?? 1),
                'fue_igp' => $this->normalizeNullableText($row['fue_igp'] ?? 'galeria'),
                'fec_igp' => Carbon::now(),
            ]);

            $this->syncPrimaryImage($image);
        }
    }

    private function replaceOrCreatePrimaryProductImage(
        Prenda $product,
        $file,
        ?string $source,
        array &$createdPaths,
        array &$deleteAfterSuccess = [],
    ): void {
        $storedPath = $this->storePublicProductImage(
            $file,
            (int) $product->id_prenda,
            (string) ($source ?? $product->nom_pre ?? 'principal'),
        );

        $createdPaths[] = $storedPath;

        $currentPrimary = ImagenPrenda::query()
            ->where('cod_prendas_igp', $product->id_prenda)
            ->where('pri_igp', true)
            ->orderBy('ord_igp')
            ->orderBy('id_imagen_prenda')
            ->first();

        if ($currentPrimary) {
            if ($currentPrimary->rut_igp && $currentPrimary->rut_igp !== $storedPath) {
                $deleteAfterSuccess[] = $currentPrimary->rut_igp;
            }

            $currentPrimary->update([
                'cod_prenda_variantes_igp' => null,
                'rut_igp' => $storedPath,
                'url_igp' => Storage::url($storedPath),
                'pri_igp' => true,
                'ord_igp' => 0,
                'fue_igp' => $this->normalizeNullableText($source ?? 'catalogo'),
            ]);

            $this->syncPrimaryImage($currentPrimary->fresh());

            return;
        }

        $image = ImagenPrenda::query()->create([
            'cod_prendas_igp' => $product->id_prenda,
            'cod_prenda_variantes_igp' => null,
            'rut_igp' => $storedPath,
            'url_igp' => Storage::url($storedPath),
            'pri_igp' => true,
            'ord_igp' => 0,
            'fue_igp' => $this->normalizeNullableText($source ?? 'catalogo'),
            'fec_igp' => Carbon::now(),
        ]);

        $this->syncPrimaryImage($image);
    }

    private function nextVariantInternalCode(string $baseCode, array $usedCodes): string
    {
        if (!in_array($baseCode, $usedCodes, true)) {
            return $baseCode;
        }

        $position = 2;
        do {
            $candidate = $baseCode . '-' . str_pad((string) $position, 2, '0', STR_PAD_LEFT);
            $position++;
        } while (in_array($candidate, $usedCodes, true));

        return $candidate;
    }

    private function updateImageRecord(ImagenPrenda $record, array $validated, array $payload): void
    {
        $newPath = null;
        $oldPath = $record->rut_igp;
        $oldProductId = (int) $record->cod_prendas_igp;

        if (!empty($validated['archivo_igp'])) {
            $newPath = $this->storePublicProductImage(
                $validated['archivo_igp'],
                (int) $payload['cod_prendas_igp'],
                (string) ($payload['fue_igp'] ?? $record->fue_igp ?? 'imagen'),
            );

            $payload['rut_igp'] = $newPath;
            $payload['url_igp'] = Storage::url($newPath);
        } elseif (
            !empty($payload['cod_prendas_igp'])
            && $oldPath
            && (int) $payload['cod_prendas_igp'] !== $oldProductId
        ) {
            $newPath = $this->movePublicProductImage(
                $oldPath,
                (int) $payload['cod_prendas_igp'],
            );

            $payload['rut_igp'] = $newPath;
            $payload['url_igp'] = Storage::url($newPath);
        }

        try {
            DB::transaction(function () use ($record, $payload, $oldProductId) {
                $record->update($payload);
                $freshRecord = $record->fresh();
                $this->syncPrimaryImage($freshRecord);
                $this->ensurePrimaryImage($oldProductId);
                $this->ensurePrimaryImage((int) $freshRecord->cod_prendas_igp);
            });
        } catch (\Throwable $exception) {
            if ($newPath && $newPath !== $oldPath) {
                $this->deleteImageFile($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            $this->deleteImageFile($oldPath);
        }
    }

    private function syncPrimaryImage(?ImagenPrenda $image): void
    {
        if (!$image || !$image->pri_igp) {
            return;
        }

        ImagenPrenda::query()
            ->where('cod_prendas_igp', $image->cod_prendas_igp)
            ->where('id_imagen_prenda', '!=', $image->id_imagen_prenda)
            ->update(['pri_igp' => false]);
    }

    private function ensurePrimaryImage(int $productId): void
    {
        $hasPrimary = ImagenPrenda::query()
            ->where('cod_prendas_igp', $productId)
            ->where('pri_igp', true)
            ->exists();

        if ($hasPrimary) {
            return;
        }

        ImagenPrenda::query()
            ->where('cod_prendas_igp', $productId)
            ->orderBy('ord_igp')
            ->orderBy('id_imagen_prenda')
            ->limit(1)
            ->update(['pri_igp' => true]);
    }

    private function buildProductBaseCode(Prenda $product): string
    {
        $source = $product->categoria?->nom_cat ?: $product->nom_pre ?: 'PRENDA';
        $letters = preg_replace('/[^A-Z]/', '', mb_strtoupper(Str::ascii($source), 'UTF-8')) ?: 'PRE';
        $prefix = str_pad(Str::substr($letters, 0, 3), 3, 'X');

        return $prefix . '-' . str_pad((string) $product->id_prenda, 5, '0', STR_PAD_LEFT);
    }

    private function buildVariantInternalCode(string $baseCode, int $variantCount, int $position): string
    {
        return $variantCount > 1
            ? $baseCode . '-' . str_pad((string) $position, 2, '0', STR_PAD_LEFT)
            : $baseCode;
    }

    private function storePublicProductImage($file, int $productId, string $sourceLabel): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : Str::slug($sourceLabel);
        $baseName = $baseName !== '' ? $baseName : 'imagen';
        $fileName = $baseName . '-' . Str::lower(Str::random(8)) . '.' . Str::lower($extension);

        return $file->storeAs('productos/' . $productId, $fileName, 'public');
    }

    private function movePublicProductImage(string $currentPath, int $productId): string
    {
        if (!Storage::disk('public')->exists($currentPath)) {
            return $currentPath;
        }

        $directory = 'productos/' . $productId;
        $baseName = pathinfo($currentPath, PATHINFO_FILENAME);
        $extension = pathinfo($currentPath, PATHINFO_EXTENSION);
        $cleanBaseName = Str::slug($baseName) ?: 'imagen';
        $targetPath = $directory . '/' . $cleanBaseName . ($extension ? '.' . Str::lower($extension) : '');

        if ($targetPath === $currentPath) {
            return $currentPath;
        }

        if (Storage::disk('public')->exists($targetPath)) {
            $targetPath = $directory . '/' . $cleanBaseName . '-' . Str::lower(Str::random(8)) . ($extension ? '.' . Str::lower($extension) : '');
        }

        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->move($currentPath, $targetPath);

        return $targetPath;
    }

    private function deleteImageFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function deleteBlockedReason(array $definition, $record, $authUser = null): ?string
    {
        return match ($this->definitionKey($definition)) {
            'seguridad.roles' => $record->isSystemRole()
                ? 'No puedes eliminar roles del sistema.'
                : ($record->users()->exists()
                    ? 'No puedes eliminar este rol porque tiene usuarios asociados.'
                    : null),
            'seguridad.usuarios' => $this->userDeleteBlockedReason($record, $authUser),
            'catalogo.categorias' => $record->hijos()->exists()
                ? 'No puedes eliminar esta categoría porque tiene subcategorías.'
                : ($record->prendas()->exists() ? 'No puedes eliminar esta categoría porque tiene prendas asociadas.' : null),
            'catalogo.marcas' => $record->prendas()->exists()
                ? 'No puedes eliminar esta marca porque tiene prendas asociadas.'
                : null,
            'catalogo.colores' => $record->variantes()->exists()
                ? 'No puedes eliminar este color porque tiene variantes asociadas.'
                : null,
            'catalogo.tallas' => $record->variantes()->exists()
                ? 'No puedes eliminar esta talla porque tiene variantes asociadas.'
                : null,
            'catalogo.temporadas' => $record->prendas()->exists()
                ? 'No puedes eliminar esta temporada porque tiene prendas asociadas.'
                : null,
            'catalogo.prendas' => $record->variantes()->exists()
                ? 'No puedes eliminar esta prenda porque tiene variantes asociadas.'
                : ($record->imagenes()->exists()
                    ? 'No puedes eliminar esta prenda porque tiene imágenes asociadas.'
                    : ($record->favoritos()->exists()
                        ? 'No puedes eliminar esta prenda porque tiene favoritos asociados.'
                        : null)),
            'catalogo.variantes' => $record->inventario()->exists()
                ? 'No puedes eliminar esta variante porque tiene inventario asociado.'
                : ($record->imagenes()->exists()
                    ? 'No puedes eliminar esta variante porque tiene imágenes asociadas.'
                    : (DetalleVenta::query()->where('cod_prenda_variantes_dve', $record->id_prenda_variante)->exists()
                        ? 'No puedes eliminar esta variante porque tiene ventas asociadas.'
                        : (DetalleReserva::query()->where('cod_prenda_variantes_dre', $record->id_prenda_variante)->exists()
                            ? 'No puedes eliminar esta variante porque tiene reservas asociadas.'
                            : null))),
            'catalogo.imagenes' => null,
            'inventario.stock' => ((int) $record->cdi_inv > 0 || (int) $record->cre_inv > 0)
                ? 'No puedes eliminar este inventario mientras tenga cantidades registradas.'
                : null,
            default => 'Acción no disponible para este módulo.',
        };
    }

    private function userDeleteBlockedReason($record, $authUser = null): ?string
    {
        return 'Los usuarios no se eliminan del sistema. Para dar de baja a un usuario, cambia su estado a inactivo.';
    }

    private function normalizeUpper(?string $value): ?string
    {
        $normalized = $this->normalizeNullableText($value);

        return $normalized ? mb_strtoupper($normalized, 'UTF-8') : null;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === '' ? null : $normalized;
    }

    private function hasNormalizedDuplicate(string $modelClass, string $field, mixed $value, string $primaryKey, mixed $ignoreId = null): bool
    {
        $normalized = $this->normalizeUpper((string) $value);

        if (!$normalized) {
            return false;
        }

        return $modelClass::query()
            ->whereRaw('UPPER(' . $field . ') = ?', [$normalized])
            ->when($ignoreId, fn ($query) => $query->where($primaryKey, '!=', $ignoreId))
            ->exists();
    }

    private function resolveRecord(array $definition, string $record): mixed
    {
        if ($definition['kind'] === 'adjustments') {
            return $this->adjustmentTemplates()->firstWhere('id', (int) $record)
                ?? abort(404);
        }

        $query = $this->baseQuery($definition);
        abort_if(!$query, 404);

        $model = new $definition['modelClass']();
        $keyName = $model->getKeyName();

        return $query->where($keyName, $record)->firstOrFail();
    }

    private function viewData(array $definition, mixed $record = null, ?Request $request = null): array
    {
        return match ($definition['kind']) {
            'taxonomy' => $this->taxonomyViewData($definition, $record),
            'users' => $this->userViewData($record, $request),
            'products' => $this->productViewData($definition, $record),
            'variants' => $this->variantViewData($definition, $record),
            'images' => $this->imageViewData($record),
            'inventory' => $this->inventoryViewData($record),
            'adjustments' => $this->adjustmentViewData($record),
            'alerts' => $this->alertViewData($record),
            'sales' => $this->saleViewData($definition, $record),
            'reservations' => $this->reservationViewData($definition, $record),
            'favorites' => $this->favoriteViewData($record),
            default => [],
        };
    }

    private function productEditorPayload(array $definition, ?Prenda $record, Request $request, string $mode): array
    {
        $record?->loadMissing(['categoria', 'marca', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes.variante']);

        $isEdit = $mode === 'edit' && $record;
        $title = $isEdit ? ($record->nom_pre ?: 'Editar prenda') : 'Nueva prenda';

        return [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $isEdit
                ? $this->breadcrumbs($definition, $record->nom_pre, 'Editar')
                : $this->breadcrumbs($definition, 'Nueva prenda'),
            'mode' => $isEdit ? 'edit' : 'create',
            'recordLabel' => $record?->nom_pre,
            'submit' => [
                'action' => $isEdit
                    ? $this->showHref($definition, $record->id_prenda)
                    : $this->indexHref($definition),
                'method' => $isEdit ? 'put' : 'post',
            ],
            'stateAction' => $record
                ? [
                    'label' => $record->est_pre ? 'Ocultar prenda' : 'Activar prenda',
                    'value' => $record->est_pre ? '0' : '1',
                    'confirm' => $record->est_pre
                        ? 'La prenda dejará de mostrarse en el catálogo.'
                        : 'La prenda volverá a estar disponible en el catálogo.',
                ]
                : null,
            'editor' => [
                'title' => $title,
                'description' => 'Alta guiada de la prenda, su portada, configuración comercial y existencias.',
                'currentPrimaryUrl' => $record?->imagenes?->firstWhere('pri_igp', true)?->url_igp,
                'currentPrimaryLabel' => $record?->imagenes?->firstWhere('pri_igp', true)?->rut_igp
                    ? basename((string) $record->imagenes->firstWhere('pri_igp', true)?->rut_igp)
                    : null,
            ],
            'options' => [
                'categories' => $this->categoryOptions(),
                'brands' => $this->brandOptions(),
                'seasons' => $this->seasonOptions(),
                'genders' => $this->genderOptions(),
                'colors' => $this->colorOptions(),
                'sizes' => $this->sizeOptions(),
                'locations' => $this->locationOptions(),
                'colorFamilies' => $this->colorFamilyOptions(),
                'categoryProfiles' => $this->categorySizeProfiles(),
            ],
            'initialData' => $this->productEditorState($record),
        ];
    }

    private function productShowPayload(array $definition, Prenda $record): array
    {
        $record->loadMissing(['categoria', 'marca', 'temporada', 'usuarioRegistra', 'variantes.color', 'variantes.talla', 'variantes.inventario', 'imagenes.variante']);

        $gallery = collect($record->imagenes ?? [])
            ->map(fn ($image) => [
                'id' => $image->id_imagen_prenda,
                'url' => $image->url_igp,
                'label' => $image->rut_igp ? basename((string) $image->rut_igp) : ('Imagen #' . $image->id_imagen_prenda),
                'source' => $image->fue_igp ?: 'catalogo',
                'order' => (int) $image->ord_igp,
                'primary' => (bool) $image->pri_igp,
                'variantLabel' => $image->variante?->sku_prv,
                'editHref' => '/superadmin/catalogo/imagenes/' . $image->id_imagen_prenda . '/editar',
            ])
            ->values()
            ->all();

        $variants = collect($record->variantes ?? [])
            ->map(fn ($variant) => [
                'id' => $variant->id_prenda_variante,
                'label' => $variant->sku_prv ?: ('Variante #' . $variant->id_prenda_variante),
                'color' => $variant->color?->nom_col ?? 'Sin color',
                'colorSwatch' => $variant->color?->hex_dom_col ?: $variant->color?->hex_col,
                'size' => $variant->talla?->nom_tal ?? 'Sin talla',
                'price' => $this->money((float) $variant->pve_prv),
                'cost' => $variant->cos_prv !== null ? $this->money((float) $variant->cos_prv) : null,
                'stock' => $this->available($variant->inventario),
                'reserved' => (int) ($variant->inventario?->cre_inv ?? 0),
                'location' => $variant->inventario?->ubi_inv ?: 'Sin ubicación',
                'active' => (bool) $variant->est_prv,
                'editHref' => '/superadmin/catalogo/variantes/' . $variant->id_prenda_variante . '/editar',
            ])
            ->values()
            ->all();

        return [
            'meta' => $this->meta($definition),
            'breadcrumbs' => $this->breadcrumbs($definition, $record->nom_pre),
            'recordLabel' => $record->nom_pre,
            'product' => [
                'name' => $record->nom_pre,
                'description' => $record->des_pre ?: 'Sin descripción registrada.',
                'category' => $record->categoria?->nom_cat ?? 'Sin categoría',
                'brand' => $record->marca?->nom_mar ?? 'Sin marca',
                'season' => $record->temporada?->nom_tem ?? 'Sin temporada',
                'gender' => $record->gen_pre ? mb_strtoupper((string) $record->gen_pre, 'UTF-8') : 'Sin género',
                'status' => $record->est_pre ? 'Visible' : 'Oculta',
                'price' => $this->money((float) $record->prb_pre),
                'code' => $this->buildProductBaseCode($record),
                'registeredBy' => $record->usuarioRegistra?->nombreCompleto() ?? 'Sin usuario',
                'registeredAt' => $this->formatDate($record->fec_pre),
            ],
            'metrics' => [
                ['label' => 'Variantes', 'value' => (string) count($variants), 'note' => 'Configuraciones activas o históricas.', 'tone' => 'accent'],
                ['label' => 'Disponible', 'value' => (string) $this->productAvailable($record), 'note' => 'Existencias de la prenda.', 'tone' => 'success'],
                ['label' => 'Galería', 'value' => (string) count($gallery), 'note' => 'Imágenes asociadas.', 'tone' => 'neutral'],
                ['label' => 'Favoritos', 'value' => (string) ($record->favoritos_count ?? $record->favoritos()->count()), 'note' => 'Clientes que la guardaron.', 'tone' => 'warning'],
            ],
            'gallery' => $gallery,
            'variants' => $variants,
            'actions' => [
                ['label' => 'Volver al módulo', 'href' => $this->indexHref($definition), 'kind' => 'ghost'],
                ['label' => 'Editar prenda', 'href' => $this->editHref($definition, $record->id_prenda), 'kind' => 'secondary'],
            ],
        ];
    }

    private function productEditorState(?Prenda $record): array
    {
        $record?->loadMissing(['variantes.inventario', 'imagenes.variante']);

        $variantRows = $record
            ? $record->variantes
                ->sortBy('id_prenda_variante')
                ->map(fn ($variant) => [
                    'id' => (string) $variant->id_prenda_variante,
                    'sku_prv' => $variant->sku_prv ?? '',
                    'cod_colores_prv' => (string) $variant->cod_colores_prv,
                    'cod_tallas_prv' => (string) $variant->cod_tallas_prv,
                    'cob_prv' => $variant->cob_prv ?? '',
                    'pve_prv' => $variant->pve_prv !== null ? (string) $variant->pve_prv : '0',
                    'cos_prv' => $variant->cos_prv !== null ? (string) $variant->cos_prv : '',
                    'stock_inicial' => (string) ($variant->inventario?->cdi_inv ?? 1),
                    'ubi_inv' => $variant->inventario?->ubi_inv ?? 'TIENDA',
                    'est_prv' => (bool) $variant->est_prv,
                    'remove' => false,
                ])
                ->values()
                ->all()
            : [[
                'id' => '',
                'sku_prv' => '',
                'cod_colores_prv' => '',
                'cod_tallas_prv' => '',
                'cob_prv' => '',
                'pve_prv' => '',
                'cos_prv' => '',
                'stock_inicial' => '1',
                'ubi_inv' => 'TIENDA',
                'est_prv' => true,
                'remove' => false,
            ]];

        $existingImages = $record
            ? $record->imagenes
                ->sortBy([
                    ['pri_igp', 'desc'],
                    ['ord_igp', 'asc'],
                    ['id_imagen_prenda', 'asc'],
                ])
                ->map(fn ($image) => [
                    'id' => (string) $image->id_imagen_prenda,
                    'url_igp' => $image->url_igp ?? '',
                    'rut_igp' => $image->rut_igp ?? '',
                    'ord_igp' => (string) ($image->ord_igp ?? 0),
                    'pri_igp' => (bool) $image->pri_igp,
                    'fue_igp' => $image->fue_igp ?? 'catalogo',
                    'variant_label' => $image->variante?->sku_prv,
                    'remove' => false,
                ])
                ->values()
                ->all()
            : [];

        return [
            'nom_pre' => $record?->nom_pre ?? '',
            'des_pre' => $record?->des_pre ?? '',
            'cod_categorias_pre' => (string) ($record?->cod_categorias_pre ?? ''),
            'cod_marcas_pre' => (string) ($record?->cod_marcas_pre ?? ''),
            'cod_temporadas_pre' => (string) ($record?->cod_temporadas_pre ?? $this->defaultSeasonId()),
            'gen_pre' => (string) ($record?->gen_pre ?? ''),
            'prb_pre' => $record?->prb_pre !== null ? (string) $record->prb_pre : '',
            'est_pre' => $record ? (bool) $record->est_pre : true,
            'usa_variantes' => count($variantRows) > 1,
            'imagen_principal' => null,
            'fue_igp' => $record?->imagenes?->firstWhere('pri_igp', true)?->fue_igp ?? 'catalogo',
            'existing_images' => $existingImages,
            'gallery_images' => [],
            'variant_rows' => $variantRows,
        ];
    }

    private function defaultSeasonId(): ?int
    {
        return Temporada::query()
            ->whereRaw('UPPER(nom_tem) = ?', ['PERMANENTE'])
            ->value('id_temporada');
    }

    private function categorySizeProfiles(): array
    {
        if (!Schema::hasColumn('categorias', 'tip_tal_cat')) {
            return [];
        }

        return Categoria::query()
            ->select('id_categoria', 'tip_tal_cat')
            ->get()
            ->mapWithKeys(fn ($category) => [(string) $category->id_categoria => (string) ($category->tip_tal_cat ?? '')])
            ->all();
    }

    private function colorFamilyOptions(): array
    {
        if (!Schema::hasColumn('colores', 'fam_col')) {
            return [];
        }

        return Color::query()
            ->whereNotNull('fam_col')
            ->where('fam_col', '!=', '')
            ->select('fam_col')
            ->distinct()
            ->orderBy('fam_col')
            ->pluck('fam_col')
            ->map(fn ($family) => [
                'label' => (string) $family,
                'value' => (string) $family,
            ])
            ->values()
            ->all();
    }

    private function taxonomyRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $keyName = $record->getKeyName();
            $relationCount = !empty($definition['countRelation']) ? ($record->{$definition['countRelation'] . '_count'} ?? 0) : 0;
            $summary = $record->{$definition['descriptionField']} ?: 'Sin detalle adicional';

            return [
                'id' => $record->{$keyName},
                'primary' => $record->{$definition['nameField']},
                'secondary' => is_string($summary) ? $summary : 'Registro interno',
                'status' => $this->booleanStatus($record->{$definition['statusField']}),
                'cells' => [
                    'summary' => is_scalar($summary) ? (string) $summary : 'Registro interno',
                    'linked' => $relationCount . ' ' . ($definition['countLabel'] ?? 'cruces'),
                    'date' => $this->formatDate($record->{$definition['dateField']}),
                ],
                'showHref' => $this->showHref($definition, $record->{$keyName}),
                'editHref' => $this->rowEditHref($definition, $record->{$keyName}),
            ];
        })->all();
    }

    private function userColumns(?Request $request = null): array
    {
        $slug = $request ? $request->string('role_slug')->toString() : '';

        if ($slug === 'superadmin') {
            return [
                ['key' => 'email', 'label' => 'Correo'],
                ['key' => 'status_label', 'label' => 'Estado'],
                ['key' => 'date', 'label' => 'Alta', 'align' => 'right'],
            ];
        }

        if ($slug === 'vendedor' || $slug === 'cliente') {
            return [
                ['key' => 'email', 'label' => 'Correo'],
                ['key' => 'phone', 'label' => 'Teléfono'],
                ['key' => 'status_label', 'label' => 'Estado'],
                ['key' => 'date', 'label' => 'Alta', 'align' => 'right'],
            ];
        }

        // Tab "Todos": incluye columna de rol
        return [
            ['key' => 'role', 'label' => 'Rol'],
            ['key' => 'email', 'label' => 'Correo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'status_label', 'label' => 'Estado'],
            ['key' => 'date', 'label' => 'Alta', 'align' => 'right'],
        ];
    }

    private function userRows(array $definition, Collection $records, ?Request $request = null): array
    {
        $slug = $request ? $request->string('role_slug')->toString() : '';

        return $records->map(function ($record) use ($definition, $slug) {
            $rolLabel = $record->rol?->nom_rol ? $this->roleLabel((string) $record->rol->nom_rol) : 'Sin rol';
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
                'date' => $this->formatDate($record->fec_usu),
            ];

            return [
                'id' => $record->id,
                'primary' => $this->userName($record),
                'secondary' => $slug === 'superadmin' ? $record->email : ($record->rol?->nom_rol ? $this->roleLabel((string) $record->rol->nom_rol) : 'Sin rol'),
                'status' => $this->booleanStatus($record->est_usu),
                'cells' => $cells,
                'showHref' => $this->showHref($definition, $record->id),
                'editHref' => $this->rowEditHref($definition, $record->id),
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
            'status' => $this->booleanStatus($record->est_pre, 'Visible', 'Oculta'),
            'cells' => [
                'brand' => $record->marca?->nom_mar ?? 'Sin marca',
                'category' => $record->categoria?->nom_cat ?? 'Sin categoría',
                'variants' => (string) ($record->variantes_count ?? $record->variantes?->count() ?? 0),
                'stock' => (string) $this->productAvailable($record),
                'price' => $this->money((float) $record->prb_pre),
            ],
            'showHref' => $this->showHref($definition, $record->id_prenda),
            'editHref' => $this->rowEditHref($definition, $record->id_prenda),
        ])->all();
    }

    private function variantRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_prenda_variante,
            'primary' => $record->sku_prv ?: 'Variante #' . $record->id_prenda_variante,
            'secondary' => $record->prenda?->nom_pre ?? 'Sin prenda',
            'status' => $this->booleanStatus($record->est_prv, 'Activa', 'Pausada'),
            'cells' => [
                'product' => $record->prenda?->nom_pre ?? 'Sin prenda',
                'color' => $record->color?->nom_col ?? 'Sin color',
                'size' => $record->talla?->nom_tal ?? 'Sin talla',
                'stock' => (string) $this->available($record->inventario),
            ],
            'showHref' => $this->showHref($definition, $record->id_prenda_variante),
            'editHref' => $this->rowEditHref($definition, $record->id_prenda_variante),
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
            'showHref' => $this->showHref($definition, $record->id_imagen_prenda),
            'editHref' => $this->rowEditHref($definition, $record->id_imagen_prenda),
        ])->all();
    }

    private function inventoryRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $available = $this->available($record);

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
                'showHref' => $this->showHref($definition, $record->id_inventario),
                'editHref' => $this->rowEditHref($definition, $record->id_inventario),
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
            'showHref' => $this->showHref($definition, $record['id']),
            'editHref' => null,
        ])->all();
    }

    private function alertRows(array $definition, Collection $records): array
    {
        return $records->map(function ($record) use ($definition) {
            $available = $this->available($record);

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
                'showHref' => $this->showHref($definition, $record->id_inventario),
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
            'status' => $this->textStatus($record->est_ven, 'Activa'),
            'cells' => [
                'client' => $this->userName($record->cliente) ?: 'Sin cliente',
                'type' => $record->tiv_ven ?: 'Sin tipo',
                'total' => $this->money((float) $record->tot_ven),
                'date' => $this->formatDate($record->fec_ven),
            ],
            'showHref' => $this->showHref($definition, $record->id_venta),
            'editHref' => null,
        ])->all();
    }

    private function reservationRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_reserva,
            'primary' => $record->nre_res ?: 'Reserva #' . $record->id_reserva,
            'secondary' => $this->userName($record->responsable) ?: 'Sin responsable',
            'status' => $this->textStatus($record->est_res, 'Seguimiento'),
            'cells' => [
                'client' => $this->userName($record->cliente) ?: 'Sin cliente',
                'due' => $this->formatDate($record->ven_res),
                'items' => (string) ($record->detalles_count ?? $record->detalles?->count() ?? 0),
                'date' => $this->formatDate($record->fec_res),
            ],
            'showHref' => $this->showHref($definition, $record->id_reserva),
            'editHref' => null,
        ])->all();
    }

    private function favoriteRows(array $definition, Collection $records): array
    {
        return $records->map(fn ($record) => [
            'id' => $record->id_favorito,
            'primary' => $record->prenda?->nom_pre ?? 'Favorito #' . $record->id_favorito,
            'secondary' => $this->userName($record->usuario) ?: 'Sin usuario',
            'status' => ['label' => 'Interes', 'tone' => 'accent'],
            'cells' => [
                'client' => $this->userName($record->usuario) ?: 'Sin cliente',
                'brand' => $record->prenda?->marca?->nom_mar ?? 'Sin marca',
                'category' => $record->prenda?->categoria?->nom_cat ?? 'Sin categoría',
                'date' => $this->formatDate($record->fec_fav),
            ],
            'showHref' => $this->showHref($definition, $record->id_favorito),
            'editHref' => null,
        ])->all();
    }

    private function taxonomyViewData(array $definition, $record = null): array
    {
        $key = $this->definitionKey($definition);
        $name = $record?->{$definition['nameField']} ?? '';
        $statusField = $definition['statusField'];
        $dateField = $definition['dateField'];
        $status = $record ? ($record->{$statusField} ? '1' : '0') : '1';
        $statusLabel = $record ? ($record->{$statusField} ? 'Activo' : 'Inactivo') : 'Nuevo';
        $isSystemRole = $key === 'seguridad.roles' && $record instanceof Role && $record->isSystemRole();
        $nameHint = match ($key) {
            'seguridad.roles' => 'Se guarda en MAYÚSCULAS. Solo letras y espacios.',
            'catalogo.categorias' => 'Se guarda en MAYÚSCULAS. No admite números.',
            'catalogo.marcas' => 'Se guarda en MAYÚSCULAS. Evita signos innecesarios.',
            'catalogo.colores' => 'Se guarda en MAYÚSCULAS. Usa nombres simples de color, sin números.',
            'catalogo.tallas' => 'Usa letras o números sin espacios: XS, M, 38 o UNITALLA.',
            'catalogo.temporadas' => 'Se guarda en MAYÚSCULAS. No admite números.',
            default => 'Se guarda en MAYÚSCULAS.',
        };
        $disallowNumbers = in_array($key, [
            'seguridad.roles',
            'catalogo.categorias',
            'catalogo.colores',
            'catalogo.temporadas',
        ], true);
        $count = $record && !empty($definition['countRelation'])
            ? ($record->{$definition['countRelation'] . '_count'} ?? $record->{$definition['countRelation']}()->count())
            : 0;

        $mainFields = [
            [
                'name' => $definition['nameField'],
                'label' => 'Nombre',
                'value' => $name,
                'required' => true,
                'placeholder' => 'Nombre del registro',
                'disabled' => $isSystemRole,
                'transform' => 'uppercase',
                'disallowNumbers' => $disallowNumbers,
                'hint' => $isSystemRole ? 'Este rol pertenece al sistema y su nombre no puede modificarse.' : $nameHint,
            ],
        ];

        $detailLabel = 'Descripción';
        $detailValue = (string) ($record?->{$definition['descriptionField']} ?? '');

        if ($key === 'catalogo.categorias') {
            $mainFields[] = [
                'name' => 'des_cat',
                'label' => 'Descripción',
                'value' => (string) ($record?->des_cat ?? ''),
                'type' => 'textarea',
                'placeholder' => 'Descripción de la categoría',
            ];
            $mainFields[] = [
                'name' => 'cod_categoria_padre_cat',
                'label' => 'Categoría padre',
                'value' => (string) ($record?->cod_categoria_padre_cat ?? ''),
                'type' => 'select',
                'options' => $this->categoryParentOptions($record),
            ];
            $detailLabel = 'Categoría padre';
            $detailValue = $record?->padre?->nom_cat ?? 'Sin categoría padre';
        } elseif ($key === 'catalogo.colores') {
            $mainFields[] = [
                'name' => 'hex_col',
                'label' => 'HEX',
                'value' => (string) ($record?->hex_col ?? ''),
                'placeholder' => '#1A1A1A',
                'transform' => 'uppercase',
                'maxLength' => 7,
                'required' => true,
                'pattern' => '^#[A-F0-9]{6}$',
                'invalidMessage' => 'Usa un HEX válido: #1A1A1A.',
                'hint' => 'Define el color visual real. Debe iniciar con # y tener 6 caracteres.',
            ];
            $detailLabel = 'HEX';
            $detailValue = $record?->hex_col ?: 'Sin HEX';
        } elseif ($key === 'catalogo.tallas') {
            $mainFields[] = [
                'name' => 'ord_tal',
                'label' => 'Orden',
                'value' => (string) ($record?->ord_tal ?? 0),
                'type' => 'number',
                'required' => true,
                'placeholder' => '0',
                'min' => 0,
                'hint' => 'Ordena la talla de menor a mayor. Ejemplo: XS 30, S 40, 38 138.',
            ];
            $detailLabel = 'Orden';
            $detailValue = (string) ($record?->ord_tal ?? 0);
        } else {
            $mainFields[] = [
                'name' => $definition['descriptionField'],
                'label' => 'Descripción',
                'value' => (string) ($record?->{$definition['descriptionField']} ?? ''),
                'type' => 'textarea',
                'placeholder' => 'Descripción del registro',
            ];
        }

        return [
            'recordLabel' => $name ?: ucfirst($definition['singular']),
            'formSections' => [
                [
                    'title' => 'Datos principales',
                    'description' => 'Campos principales del registro.',
                    'fields' => $mainFields,
                ],
                [
                    'title' => 'Estado',
                    'description' => 'Estado y registros asociados.',
                    'fields' => [
                        [
                            'name' => $statusField,
                            'label' => 'Estado',
                            'value' => $status,
                            'type' => 'toggle',
                            'disabled' => $isSystemRole,
                            'hint' => $isSystemRole ? 'Los roles del sistema deben permanecer activos.' : null,
                        ],
                        ['name' => 'linked_count', 'label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'disabled' => true],
                        ['name' => $dateField, 'label' => 'Fecha de alta', 'value' => $this->formatDate($record?->{$dateField}), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Estado', 'value' => $statusLabel, 'note' => 'Estado del registro.', 'tone' => $record && !$record->{$statusField} ? 'danger' : 'success'],
                ['label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'note' => 'Registros asociados.', 'tone' => 'accent'],
                ['label' => $detailLabel, 'value' => $detailValue, 'note' => 'Dato principal.', 'tone' => 'neutral'],
                ['label' => 'Fecha', 'value' => $this->formatDate($record?->{$dateField}), 'note' => 'Fecha de alta.', 'tone' => 'warning'],
            ],
            'detailSections' => [[
                'title' => 'Detalle',
                'description' => 'Información principal del registro.',
                'fields' => [
                    ['label' => 'Nombre', 'value' => $name ?: 'Sin nombre', 'tone' => 'accent'],
                    ['label' => $detailLabel, 'value' => $detailValue ?: 'Sin dato', 'tone' => 'neutral'],
                    ['label' => 'Estado', 'value' => $statusLabel, 'tone' => $record && !$record->{$statusField} ? 'danger' : 'success'],
                    ['label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'tone' => 'warning'],
                ],
            ]],
            'timeline' => [
                ['title' => 'Fecha de alta', 'note' => 'Fecha registrada.', 'date' => $this->formatDate($record?->{$dateField}), 'tone' => 'accent'],
                ['title' => 'Estado', 'note' => 'Estado actual.', 'date' => $statusLabel, 'tone' => 'success'],
                ['title' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'note' => 'Registros asociados.', 'date' => (string) $count, 'tone' => 'warning'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function userViewData($record = null, ?Request $request = null): array
    {
        $authUser = $request?->user();
        $isSelfEdit = $authUser && $record && (int) $authUser->getAuthIdentifier() === (int) $record->id;

        $name = $record ? $this->userName($record) : 'Nuevo usuario';
        $presetRole = !$record ? $this->activeManagedUserRole($request) : null;
        $selectedRoleId = $record?->cod_roles_usu ?? $presetRole['id'] ?? '';
        $selectedRoleLabel = $record?->rol?->nom_rol
            ? $this->roleLabel((string) $record->rol->nom_rol)
            : ($presetRole ? $this->roleLabel((string) $presetRole['singular']) : 'Usuario');
        $stateLabel = $record ? ($record->est_usu ? 'Activo' : 'Inactivo') : 'Activo';

        // Al crear: solo superadmin y vendedor. Al editar: los tres roles.
        $roleOptions = $record
            ? $this->managedUserRoleOptions($record->rol)
            : $this->creatableUserRoleOptions();

        $formSections = [
            [
                'title' => 'Datos personales',
                'description' => 'Nombre completo, teléfono y correo del usuario.',
                'fields' => [
                    ['name' => 'nom_usu', 'label' => 'Nombre', 'value' => $record?->nom_usu ?? '', 'required' => true, 'placeholder' => 'NOMBRE', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'app_usu', 'label' => 'Apellido paterno', 'value' => $record?->app_usu ?? '', 'required' => true, 'placeholder' => 'APELLIDO PATERNO', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'apm_usu', 'label' => 'Apellido materno', 'value' => $record?->apm_usu ?? '', 'required' => false, 'placeholder' => 'APELLIDO MATERNO (opcional)', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'tel_usu', 'label' => 'Teléfono', 'value' => $record?->tel_usu ?? '', 'type' => 'tel', 'required' => true, 'placeholder' => '70000000', 'transform' => 'digits', 'maxLength' => 8],
                    ['name' => 'email', 'label' => 'Correo', 'value' => $record?->email ?? '', 'type' => 'email', 'required' => true, 'placeholder' => 'correo@dominio.com', 'transform' => 'lowercase', 'maxLength' => 255],
                ],
            ],
            [
                'title' => 'Acceso y rol',
                'description' => $isSelfEdit
                    ? 'Estás editando tu propia cuenta. El rol y el estado no pueden modificarse desde aquí.'
                    : 'Rol asignado y estado de la cuenta.',
                'fields' => [
                    [
                        'name' => 'cod_roles_usu',
                        'label' => 'Rol',
                        'value' => (string) $selectedRoleId,
                        'type' => 'select',
                        'options' => $roleOptions,
                        'required' => true,
                        'disabled' => $isSelfEdit,
                        'hint' => $isSelfEdit ? 'No puedes cambiar el rol de tu propia cuenta.' : null,
                    ],
                    [
                        'name' => 'est_usu',
                        'label' => 'Estado',
                        'value' => $record ? ($record->est_usu ? '1' : '0') : '1',
                        'type' => 'toggle',
                        'disabled' => $isSelfEdit,
                        'hint' => $isSelfEdit
                            ? 'No puedes cambiar el estado de tu propia cuenta.'
                            : 'Controla si el usuario puede ingresar al sistema.',
                    ],
                    ['name' => 'fec_usu', 'label' => 'Fecha de alta', 'value' => $this->formatDate($record?->fec_usu), 'disabled' => true],
                ],
            ],
        ];

        // Bloque de contraseña: SOLO al crear un usuario nuevo.
        // Al editar, la contraseña la cambia el propio usuario desde su perfil.
        if (!$record) {
            $formSections[] = [
                'title' => 'Contraseña de acceso',
                'description' => 'Credenciales iniciales de la cuenta. El usuario puede cambiarla desde su perfil.',
                'fields' => [
                    ['name' => 'password', 'label' => 'Contraseña', 'value' => '', 'type' => 'password', 'required' => true, 'hint' => 'Debe incluir una mayúscula, una minúscula y un número.'],
                    ['name' => 'password_confirmation', 'label' => 'Confirmar contraseña', 'value' => '', 'type' => 'password', 'required' => true],
                ],
            ];
        }

        return [
            'recordLabel' => $name,
            'formTitle' => $record
                ? 'Editar usuario'
                : ($presetRole ? 'Nuevo ' . $presetRole['singular'] : 'Nuevo usuario'),
            'formDescription' => $record
                ? 'Edita los datos del usuario. La contraseña se gestiona desde el perfil del propio usuario.'
                : ($presetRole
                    ? 'Registra un nuevo ' . $presetRole['singular'] . ' desde el panel.'
                    : 'Registra una nueva cuenta desde el panel administrativo.'),
            'formSections' => $formSections,
            'summaryMetrics' => [
                ['label' => 'Rol', 'value' => $selectedRoleLabel, 'note' => 'Rol asignado.', 'tone' => 'accent'],
                ['label' => 'Estado', 'value' => $stateLabel, 'note' => 'Estado del usuario.', 'tone' => $record && !$record->est_usu ? 'danger' : 'success'],
                ['label' => 'Alta', 'value' => $this->formatDate($record?->fec_usu), 'note' => 'Fecha de alta.', 'tone' => 'neutral'],
                ['label' => 'Teléfono', 'value' => $record?->tel_usu ?: 'Sin teléfono', 'note' => 'Teléfono registrado.', 'tone' => 'warning'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha del usuario',
                    'description' => 'Resumen administrativo de la cuenta.',
                    'fields' => [
                        ['label' => 'Nombre', 'value' => $name, 'tone' => 'accent'],
                        ['label' => 'Correo', 'value' => $record?->email ?? 'Sin correo', 'tone' => 'neutral'],
                        ['label' => 'Rol', 'value' => $selectedRoleLabel, 'tone' => 'warning'],
                        ['label' => 'Estado', 'value' => $stateLabel, 'tone' => $record && !$record->est_usu ? 'danger' : 'success'],
                        ['label' => 'Teléfono', 'value' => $record?->tel_usu ?: 'Sin teléfono', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de cuenta', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_usu), 'tone' => 'accent'],
                ['title' => 'Rol', 'note' => 'Rol actual.', 'date' => $selectedRoleLabel, 'tone' => 'success'],
                ['title' => 'Correo', 'note' => 'Correo registrado.', 'date' => $record?->email ?? 'Sin correo', 'tone' => 'neutral'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function roleOptions(): array
    {
        return Role::query()
            ->orderBy('nom_rol')
            ->get()
            ->map(fn ($role) => [
                'label' => $role->nom_rol,
                'value' => (string) $role->id_rol,
            ])
            ->values()
            ->all();
    }

    private function managedUserRoleOptions($currentRole = null): array
    {
        $allowed = $this->managedUserRoles()
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $roles = Role::query()
            ->when($allowed !== [], fn ($query) => $query->whereIn('id_rol', $allowed))
            ->orderBy('nom_rol')
            ->get();

        if ($currentRole && !$roles->contains(fn ($role) => (int) $role->id_rol === (int) $currentRole->id_rol)) {
            $roles->push($currentRole);
        }

        return $roles
            ->sortBy('nom_rol', SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($role) => [
                'label' => $this->roleLabel((string) $role->nom_rol),
                'value' => (string) $role->id_rol,
            ])
            ->values()
            ->all();
    }

    private function creatableUserRoleOptions(): array
    {
        $creatableSlugs = ['superadmin', 'vendedor'];

        return $this->managedUserRoles()
            ->filter(fn (array $role) => in_array($role['slug'], $creatableSlugs, true))
            ->map(fn (array $role) => [
                'label' => $this->roleLabel((string) $role['slug']),
                'value' => (string) $role['id'],
            ])
            ->values()
            ->all();
    }

    private function managedUserRoles(): Collection
    {
        $roleMap = Role::query()
            ->orderBy('nom_rol')
            ->get()
            ->keyBy(fn ($role) => mb_strtolower((string) $role->nom_rol, 'UTF-8'));

        $counts = DB::table('users')
            ->join('roles', 'roles.id_rol', '=', 'users.cod_roles_usu')
            ->selectRaw('LOWER(roles.nom_rol) as slug, COUNT(*) as total')
            ->groupByRaw('LOWER(roles.nom_rol)')
            ->pluck('total', 'slug');

        return collect([
            ['slug' => 'superadmin', 'label' => 'Superadmins', 'singular' => 'superadmin'],
            ['slug' => 'vendedor', 'label' => 'Vendedores', 'singular' => 'vendedor'],
            ['slug' => 'cliente', 'label' => 'Clientes', 'singular' => 'cliente'],
        ])->map(function (array $role) use ($roleMap, $counts) {
            $model = $roleMap->get($role['slug']);

            return [
                ...$role,
                'id' => $model?->id_rol,
                'count' => (int) ($counts[$role['slug']] ?? 0),
            ];
        })->filter(fn (array $role) => $role['id'] !== null)->values();
    }

    private function activeManagedUserRole(?Request $request): ?array
    {
        if (!$request) {
            return null;
        }

        $slug = $request->string('role_slug')->toString();

        if ($slug === '') {
            return null;
        }

        return $this->managedUserRoles()->firstWhere('slug', $slug);
    }

    private function countUsersByRoleSlug(string $slug): int
    {
        return User::query()
            ->whereHas('rol', function ($query) use ($slug) {
                $query->whereRaw('LOWER(nom_rol) = ?', [mb_strtolower($slug, 'UTF-8')]);
            })
            ->count();
    }

    private function roleLabel(string $value): string
    {
        return match (mb_strtolower($value, 'UTF-8')) {
            'superadmin' => 'Superadmin',
            'vendedor' => 'Vendedor',
            'cliente' => 'Cliente',
            default => $value,
        };
    }

    private function categoryParentOptions($record = null): array
    {
        $query = Categoria::query()->orderBy('nom_cat');

        if ($record) {
            $query->where('id_categoria', '!=', $record->id_categoria);
        }

        return collect([
            ['label' => 'Sin categoría padre', 'value' => ''],
        ])->merge(
            $query->get()->map(fn ($category) => [
                'label' => $category->nom_cat,
                'value' => (string) $category->id_categoria,
            ]),
        )->values()->all();
    }

    private function categoryOptions(): array
    {
        return Categoria::query()
            ->orderBy('nom_cat')
            ->get()
            ->map(fn ($category) => [
                'label' => $category->nom_cat,
                'value' => (string) $category->id_categoria,
            ])
            ->values()
            ->all();
    }

    private function brandOptions(): array
    {
        return Marca::query()
            ->orderBy('nom_mar')
            ->get()
            ->map(fn ($brand) => [
                'label' => $brand->nom_mar,
                'value' => (string) $brand->id_marca,
            ])
            ->values()
            ->all();
    }

    private function seasonOptions(): array
    {
        $query = Temporada::query();

        if (Schema::hasColumn('temporadas', 'ord_tem')) {
            $query->orderBy('ord_tem');
        }

        $query->orderBy('nom_tem');

        return $query
            ->get()
            ->map(fn ($season) => [
                'label' => $season->nom_tem,
                'value' => (string) $season->id_temporada,
            ])
            ->values()
            ->all();
    }

    private function inlineOption(array $definition, $record): array
    {
        return match ($this->definitionKey($definition)) {
            'catalogo.categorias' => [
                'label' => $record->nom_cat,
                'value' => (string) $record->id_categoria,
            ],
            'catalogo.marcas' => [
                'label' => $record->nom_mar,
                'value' => (string) $record->id_marca,
            ],
            'catalogo.colores' => [
                'label' => $record->nom_col,
                'value' => (string) $record->id_color,
                'swatch' => $record->hex_dom_col ?: $record->hex_col,
            ],
            'catalogo.tallas' => [
                'label' => $record->nom_tal,
                'value' => (string) $record->id_talla,
            ],
            'catalogo.temporadas' => [
                'label' => $record->nom_tem,
                'value' => (string) $record->id_temporada,
            ],
            default => [
                'label' => (string) $this->recordKey($record),
                'value' => (string) $this->recordKey($record),
            ],
        };
    }

    private function suggestSizeOrder(string $name): int
    {
        $value = mb_strtoupper(trim($name), 'UTF-8');
        $alphaMap = [
            'UNITALLA' => 10,
            'XXS' => 20,
            'XS' => 30,
            'S' => 40,
            'M' => 50,
            'L' => 60,
            'XL' => 70,
            'XXL' => 80,
        ];

        if (isset($alphaMap[$value])) {
            return $alphaMap[$value];
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return 100 + (int) $value;
        }

        return (int) (Talla::query()->max('ord_tal') ?? 0) + 10;
    }

    private function genderOptions(): array
    {
        return [
            ['label' => 'MUJER', 'value' => 'mujer'],
            ['label' => 'HOMBRE', 'value' => 'hombre'],
            ['label' => 'UNISEX', 'value' => 'unisex'],
        ];
    }

    private function productOptions(): array
    {
        return Prenda::query()
            ->orderBy('nom_pre')
            ->get()
            ->map(fn ($product) => [
                'label' => $product->nom_pre,
                'value' => (string) $product->id_prenda,
            ])
            ->values()
            ->all();
    }

    private function colorOptions(): array
    {
        $query = Color::query();

        if (Schema::hasColumn('colores', 'ord_col')) {
            $query->orderBy('ord_col');
        }

        $query->orderBy('nom_col');

        return $query
            ->get()
            ->map(fn ($color) => [
                'label' => $color->nom_col,
                'value' => (string) $color->id_color,
                'swatch' => $color->hex_dom_col ?: $color->hex_col,
            ])
            ->values()
            ->all();
    }

    private function sizeOptions(): array
    {
        return Talla::query()
            ->orderBy('ord_tal')
            ->orderBy('nom_tal')
            ->get()
            ->map(fn ($size) => [
                'label' => $size->nom_tal,
                'value' => (string) $size->id_talla,
            ])
            ->values()
            ->all();
    }

    private function inferCategorySizeType(string $name): string
    {
        $normalized = mb_strtoupper(Str::ascii(trim($name)), 'UTF-8');

        if (str_contains($normalized, 'PANTAL')) {
            return 'NUMERICA';
        }

        if (str_contains($normalized, 'ACCESOR')) {
            return 'UNITALLA';
        }

        return 'ALFA';
    }

    private function sizeMetadata(string $name): array
    {
        $normalized = mb_strtoupper(trim($name), 'UTF-8');

        if ($normalized === 'UNITALLA') {
            return ['sis_tal' => 'UNICA', 'gru_tal' => 'GENERAL'];
        }

        if (preg_match('/^\d+$/', $normalized) === 1) {
            return ['sis_tal' => 'NUMERICA', 'gru_tal' => 'INFERIOR'];
        }

        return ['sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR'];
    }

    private function buildSeasonCode(string $name): string
    {
        $parts = collect(preg_split('/[\s\-]+/u', mb_strtoupper(Str::ascii(trim($name)), 'UTF-8')) ?: [])
            ->filter()
            ->values();

        $code = $parts
            ->map(fn ($part) => Str::substr($part, 0, 1))
            ->implode('');

        if ($code === '') {
            $code = Str::substr(preg_replace('/[^A-Z]/', '', mb_strtoupper(Str::ascii($name), 'UTF-8')) ?: 'TMP', 0, 3);
        }

        return Str::substr($code, 0, 6);
    }

    private function nextSeasonOrder(): int
    {
        return (int) (Temporada::query()->max('ord_tem') ?? 0) + 10;
    }

    private function nextColorOrder(): int
    {
        return (int) (Color::query()->max('ord_col') ?? 0) + 10;
    }

    private function locationOptions(): array
    {
        return [
            ['label' => 'DEPÓSITO', 'value' => 'DEPOSITO'],
            ['label' => 'TIENDA', 'value' => 'TIENDA'],
        ];
    }

    private function variantOptions(): array
    {
        return PrendaVariante::query()
            ->with(['prenda', 'color', 'talla'])
            ->orderBy('sku_prv')
            ->get()
            ->map(fn ($variant) => [
                'label' => trim(implode(' · ', array_filter([
                    $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                    $variant->prenda?->nom_pre,
                    $variant->color?->nom_col,
                    $variant->talla?->nom_tal,
                ]))),
                'value' => (string) $variant->id_prenda_variante,
            ])
            ->values()
            ->all();
    }

    private function inventoryVariantOptions($record = null): array
    {
        $query = PrendaVariante::query()
            ->with(['prenda', 'color', 'talla'])
            ->whereDoesntHave('inventario')
            ->orderBy('sku_prv');

        if ($record?->cod_prenda_variantes_inv) {
            $query->orWhere('id_prenda_variante', $record->cod_prenda_variantes_inv);
        }

        return $query->get()
            ->map(fn ($variant) => [
                'label' => trim(implode(' · ', array_filter([
                    $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                    $variant->prenda?->nom_pre,
                    $variant->color?->nom_col,
                    $variant->talla?->nom_tal,
                ]))),
                'value' => (string) $variant->id_prenda_variante,
            ])
            ->values()
            ->all();
    }

    private function productViewData(array $definition, $record = null): array
    {
        $currentUser = auth()->user();
        $relatedRows = $record
            ? collect($record->variantes ?? [])->map(fn ($variant) => [
                'id' => $variant->id_prenda_variante,
                'primary' => $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                'secondary' => trim(implode(' · ', array_filter([$variant->color?->nom_col, $variant->talla?->nom_tal]))),
                'status' => $this->booleanStatus($variant->est_prv, 'Activa', 'Pausada'),
                'cells' => [
                    'product' => $record->nom_pre,
                    'color' => $variant->color?->nom_col ?? 'Sin color',
                    'size' => $variant->talla?->nom_tal ?? 'Sin talla',
                    'stock' => (string) $this->available($variant->inventario),
                ],
                'showHref' => '/superadmin/catalogo/variantes/' . $variant->id_prenda_variante,
                'editHref' => '/superadmin/catalogo/variantes/' . $variant->id_prenda_variante . '/editar',
            ])->take(6)->all()
            : [];

        return [
            'recordLabel' => $record?->nom_pre ?? 'Nueva prenda',
            'formSections' => [
                [
                    'title' => 'Datos de la prenda',
                    'description' => 'Nombre, descripción, género y precio base.',
                    'fields' => [
                        ['name' => 'nom_pre', 'label' => 'Nombre', 'value' => $record?->nom_pre ?? '', 'required' => true, 'placeholder' => 'NOMBRE DE LA PRENDA'],
                        ['name' => 'des_pre', 'label' => 'Descripción', 'value' => $record?->des_pre ?? '', 'type' => 'textarea', 'placeholder' => 'Descripción comercial de la prenda'],
                        ['name' => 'gen_pre', 'label' => 'Género', 'value' => (string) ($record?->gen_pre ?? 'mujer'), 'type' => 'select', 'options' => $this->genderOptions()],
                        ['name' => 'prb_pre', 'label' => 'Precio de venta (Bs.)', 'value' => $record ? (string) $record->prb_pre : '0', 'type' => 'number', 'required' => true, 'placeholder' => '0.00'],
                    ],
                ],
                [
                    'title' => 'Clasificación',
                    'description' => 'Categoría, marca, temporada y estado.',
                    'fields' => [
                        ['name' => 'cod_categorias_pre', 'label' => 'Categoría', 'value' => (string) ($record?->cod_categorias_pre ?? ''), 'type' => 'select', 'options' => $this->categoryOptions(), 'required' => true],
                        ['name' => 'cod_marcas_pre', 'label' => 'Marca', 'value' => (string) ($record?->cod_marcas_pre ?? ''), 'type' => 'select', 'options' => $this->brandOptions()],
                        ['name' => 'cod_temporadas_pre', 'label' => 'Temporada', 'value' => (string) ($record?->cod_temporadas_pre ?? ''), 'type' => 'select', 'options' => $this->seasonOptions()],
                        ['name' => 'est_pre', 'label' => 'Estado', 'value' => $record ? ($record->est_pre ? '1' : '0') : '1', 'type' => 'toggle'],
                    ],
                ],
                [
                    'title' => 'Registro',
                    'description' => 'Referencia del usuario y fecha de alta.',
                    'fields' => [
                        ['name' => 'cod_usuario_registra_pre', 'label' => 'Registrado por', 'value' => $record?->usuarioRegistra?->nombreCompleto() ?? $currentUser?->nombreCompleto() ?? 'Sin usuario', 'disabled' => true],
                        ['name' => 'fec_pre', 'label' => 'Fecha de alta', 'value' => $this->formatDate($record?->fec_pre), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Precio base', 'value' => $record ? $this->money((float) $record->prb_pre) : 'Bs 0.00', 'note' => 'Precio registrado.', 'tone' => 'accent'],
                ['label' => 'Variantes', 'value' => (string) ($record?->variantes_count ?? $record?->variantes?->count() ?? 0), 'note' => 'Variantes asociadas.', 'tone' => 'success'],
                ['label' => 'Imágenes', 'value' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0), 'note' => 'Imágenes cargadas.', 'tone' => 'neutral'],
                ['label' => 'Favoritos', 'value' => (string) ($record?->favoritos_count ?? $record?->favoritos?->count() ?? 0), 'note' => 'Favoritos registrados.', 'tone' => 'warning'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha de producto',
                    'description' => 'Datos principales de la prenda.',
                    'fields' => [
                        ['label' => 'Nombre', 'value' => $record?->nom_pre ?? 'Sin nombre', 'tone' => 'accent'],
                        ['label' => 'Marca', 'value' => $record?->marca?->nom_mar ?? 'Sin marca', 'tone' => 'neutral'],
                        ['label' => 'Categoría', 'value' => $record?->categoria?->nom_cat ?? 'Sin categoría', 'tone' => 'warning'],
                        ['label' => 'Temporada', 'value' => $record?->temporada?->nom_tem ?? 'Sin temporada', 'tone' => 'success'],
                    ],
                ],
                [
                    'title' => 'Operación',
                    'fields' => [
                        ['label' => 'Género', 'value' => ucfirst((string) ($record?->gen_pre ?? 'sin género')), 'tone' => 'neutral'],
                        ['label' => 'Precio base', 'value' => $record ? $this->money((float) $record->prb_pre) : 'Bs 0.00', 'tone' => 'accent'],
                        ['label' => 'Estado', 'value' => $record ? ($record->est_pre ? 'Visible' : 'Oculta') : 'Nuevo', 'tone' => $record && !$record->est_pre ? 'danger' : 'success'],
                        ['label' => 'Descripción', 'value' => $record?->des_pre ?: 'Sin descripción', 'tone' => 'warning'],
                    ],
                ],
                [
                    'title' => 'Registro',
                    'fields' => [
                        ['label' => 'Usuario', 'value' => $record?->usuarioRegistra?->nombreCompleto() ?? 'Sin usuario', 'tone' => 'neutral'],
                        ['label' => 'Fecha de alta', 'value' => $this->formatDate($record?->fec_pre), 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de prenda', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_pre), 'tone' => 'accent'],
                ['title' => 'Imágenes', 'note' => 'Imágenes asociadas.', 'date' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0) . ' imágenes', 'tone' => 'success'],
                ['title' => 'Favoritos', 'note' => 'Favoritos asociados.', 'date' => (string) ($record?->favoritos_count ?? $record?->favoritos?->count() ?? 0) . ' favoritos', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? $this->columns(['kind' => 'variants']) : [],
            'relatedRows' => $relatedRows,
        ];
    }

    private function variantViewData(array $definition, $record = null): array
    {
        $relatedRows = $record
            ? collect($record->imagenes ?? [])->map(fn ($image) => [
                'id' => $image->id_imagen_prenda,
                'primary' => $image->rut_igp ?: ($image->url_igp ?: 'Imagen #' . $image->id_imagen_prenda),
                'secondary' => $record->sku_prv ?: 'Variante',
                'status' => $image->pri_igp ? ['label' => 'Principal', 'tone' => 'accent'] : ['label' => 'Soporte', 'tone' => 'neutral'],
                'cells' => [
                    'product' => $record->prenda?->nom_pre ?? 'Sin prenda',
                    'variant' => $record->sku_prv ?: 'Sin código',
                    'priority' => $image->pri_igp ? 'Alta' : 'Media',
                    'source' => $image->fue_igp ?: 'Biblioteca',
                ],
                'showHref' => '/superadmin/catalogo/imagenes/' . $image->id_imagen_prenda,
                'editHref' => '/superadmin/catalogo/imagenes/' . $image->id_imagen_prenda . '/editar',
            ])->take(6)->all()
            : [];

        return [
            'recordLabel' => $record?->sku_prv ?: 'Nueva variante',
            'formSections' => [
                [
                    'title' => 'Datos de la variante',
                    'description' => 'Prenda, color, talla y códigos de control.',
                    'fields' => [
                        ['name' => 'cod_prendas_prv', 'label' => 'Prenda', 'value' => (string) ($record?->cod_prendas_prv ?? ''), 'type' => 'select', 'options' => $this->productOptions(), 'required' => true],
                        ['name' => 'cod_colores_prv', 'label' => 'Color', 'value' => (string) ($record?->cod_colores_prv ?? ''), 'type' => 'select', 'options' => $this->colorOptions(), 'required' => true],
                        ['name' => 'cod_tallas_prv', 'label' => 'Talla', 'value' => (string) ($record?->cod_tallas_prv ?? ''), 'type' => 'select', 'options' => $this->sizeOptions(), 'required' => true],
                        ['name' => 'sku_prv', 'label' => 'Código interno', 'value' => $record?->sku_prv ?? '', 'required' => true, 'placeholder' => 'BLA-00015', 'transform' => 'uppercase', 'maxLength' => 50, 'pattern' => '^[A-Z0-9_-]+$', 'invalidMessage' => 'Usa solo letras, números, guion o guion bajo.'],
                        ['name' => 'cob_prv', 'label' => 'Código adicional', 'value' => $record?->cob_prv ?? '', 'placeholder' => 'Referencia opcional', 'transform' => 'uppercase', 'maxLength' => 100, 'pattern' => '^[A-Z0-9-]*$', 'invalidMessage' => 'Usa solo letras, números y guiones.'],
                    ],
                ],
                [
                    'title' => 'Precio y estado',
                    'description' => 'Costo, precio de venta y estado.',
                    'fields' => [
                        ['name' => 'pve_prv', 'label' => 'Precio de venta (Bs.)', 'value' => $record ? (string) $record->pve_prv : '0', 'type' => 'number', 'required' => true, 'placeholder' => '0.00', 'min' => 0.01, 'hint' => 'Debe ser mayor a 0.'],
                        ['name' => 'cos_prv', 'label' => 'Costo interno (Bs.)', 'value' => $record?->cos_prv !== null ? (string) $record->cos_prv : '', 'type' => 'number', 'placeholder' => '0.00', 'min' => 0, 'hint' => 'Opcional. No debe superar el precio de venta.'],
                        ['name' => 'est_prv', 'label' => 'Estado', 'value' => $record ? ($record->est_prv ? '1' : '0') : '1', 'type' => 'toggle'],
                        ['name' => 'fec_prv', 'label' => 'Fecha de alta', 'value' => $this->formatDate($record?->fec_prv), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Disponible', 'value' => (string) $this->available($record?->inventario), 'note' => 'Existencias disponibles.', 'tone' => 'success'],
                ['label' => 'Reservado', 'value' => (string) ($record?->inventario?->cre_inv ?? 0), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Precio', 'value' => $record ? $this->money((float) $record->pve_prv) : 'Bs 0.00', 'note' => 'Precio de venta.', 'tone' => 'accent'],
                ['label' => 'Imágenes', 'value' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0), 'note' => 'Imágenes asociadas.', 'tone' => 'neutral'],
            ],
            'detailSections' => [
                [
                    'title' => 'Cruce de variante',
                    'fields' => [
                        ['label' => 'Código interno', 'value' => $record?->sku_prv ?: 'Sin código', 'tone' => 'accent'],
                        ['label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'neutral'],
                        ['label' => 'Color', 'value' => $record?->color?->nom_col ?? 'Sin color', 'tone' => 'warning'],
                        ['label' => 'Talla', 'value' => $record?->talla?->nom_tal ?? 'Sin talla', 'tone' => 'success'],
                    ],
                ],
                [
                    'title' => 'Lectura comercial',
                    'fields' => [
                        ['label' => 'Código adicional', 'value' => $record?->cob_prv ?: 'Sin código', 'tone' => 'neutral'],
                        ['label' => 'Precio de venta', 'value' => $record ? $this->money((float) $record->pve_prv) : 'Bs 0.00', 'tone' => 'accent'],
                        ['label' => 'Costo', 'value' => $record && $record->cos_prv !== null ? $this->money((float) $record->cos_prv) : 'Sin costo', 'tone' => 'warning'],
                        ['label' => 'Estado', 'value' => $record ? ($record->est_prv ? 'Activa' : 'Inactiva') : 'Nueva', 'tone' => $record && !$record->est_prv ? 'danger' : 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de variante', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_prv), 'tone' => 'accent'],
                ['title' => 'Existencias', 'note' => 'Disponible actual.', 'date' => (string) $this->available($record?->inventario) . ' disponibles', 'tone' => 'success'],
                ['title' => 'Imágenes', 'note' => 'Imágenes asociadas.', 'date' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0) . ' imágenes', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? $this->columns(['kind' => 'images']) : [],
            'relatedRows' => $relatedRows,
        ];
    }

    private function imageViewData($record = null): array
    {
        return [
            'recordLabel' => $record ? basename((string) $record->rut_igp) : 'Nueva imagen',
            'formSections' => [
                [
                    'title' => 'Archivo y asignación',
                    'description' => 'Carga el archivo y define la relación con la prenda.',
                    'fields' => [
                        ['name' => 'cod_prendas_igp', 'label' => 'Prenda', 'value' => (string) ($record?->cod_prendas_igp ?? ''), 'type' => 'select', 'options' => $this->productOptions(), 'required' => true],
                        ['name' => 'cod_prenda_variantes_igp', 'label' => 'Variante', 'value' => (string) ($record?->cod_prenda_variantes_igp ?? ''), 'type' => 'select', 'options' => $this->variantOptions()],
                        ['name' => 'archivo_igp', 'label' => 'Archivo', 'value' => $record?->rut_igp ?? '', 'type' => 'file', 'required' => !$record, 'accept' => 'image/*', 'hint' => $record ? 'Sube un nuevo archivo solo si deseas reemplazar la imagen actual.' : 'Se usará como imagen del producto y quedará disponible en la galería.'],
                        ['name' => 'fue_igp', 'label' => 'Fuente', 'value' => $record?->fue_igp ?? 'catalogo', 'placeholder' => 'catalogo', 'hint' => 'Origen interno de la imagen. Ejemplo: catálogo, galería o producción.'],
                    ],
                ],
                [
                    'title' => 'Orden y prioridad',
                    'fields' => [
                        ['name' => 'ord_igp', 'label' => 'Orden', 'value' => (string) ($record?->ord_igp ?? 0), 'type' => 'number', 'required' => true, 'placeholder' => '0', 'min' => 0],
                        ['name' => 'pri_igp', 'label' => 'Imagen principal', 'value' => $record ? ($record->pri_igp ? '1' : '0') : '0', 'type' => 'toggle'],
                        ['name' => 'rut_igp', 'label' => 'Ruta interna', 'value' => $record?->rut_igp ?? 'Se definirá al guardar', 'disabled' => true],
                        ['name' => 'url_igp', 'label' => 'URL pública', 'value' => $record?->url_igp ?? 'Se definirá al guardar', 'disabled' => true],
                        ['name' => 'fec_igp', 'label' => 'Fecha de alta', 'value' => $this->formatDate($record?->fec_igp), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Prioridad', 'value' => $record?->pri_igp ? 'Principal' : 'Secundaria', 'note' => 'Prioridad actual.', 'tone' => $record?->pri_igp ? 'accent' : 'neutral'],
                ['label' => 'Orden', 'value' => (string) ($record?->ord_igp ?? 0), 'note' => 'Orden actual.', 'tone' => 'warning'],
                ['label' => 'Fuente', 'value' => $record?->fue_igp ?: 'Biblioteca', 'note' => 'Origen registrado.', 'tone' => 'success'],
                ['label' => 'Fecha', 'value' => $this->formatDate($record?->fec_igp), 'note' => 'Fecha de alta.', 'tone' => 'neutral'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha visual',
                    'fields' => [
                        ['label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'accent'],
                        ['label' => 'Variante', 'value' => $record?->variante?->sku_prv ?? 'Sin variante', 'tone' => 'neutral'],
                        ['label' => 'Fuente', 'value' => $record?->fue_igp ?: 'Biblioteca', 'tone' => 'warning'],
                        ['label' => 'Ruta', 'value' => $record?->rut_igp ?: 'Sin origen cargado', 'tone' => 'success'],
                        ['label' => 'URL pública', 'value' => $record?->url_igp ?: 'Sin URL pública', 'tone' => 'neutral'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de imagen', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_igp), 'tone' => 'accent'],
                ['title' => 'Orden', 'note' => 'Posicion actual.', 'date' => 'Orden ' . (string) ($record?->ord_igp ?? 0), 'tone' => 'warning'],
                ['title' => 'Prenda', 'note' => 'Prenda asociada.', 'date' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'success'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function inventoryViewData($record = null): array
    {
        $available = $this->available($record);

        return [
            'recordLabel' => $record?->variante?->prenda?->nom_pre ?? 'Nuevo registro de stock',
            'formSections' => [
                [
                    'title' => 'Ubicación operativa',
                    'description' => 'Variante asociada y ubicación.',
                    'fields' => [
                        ['name' => 'cod_prenda_variantes_inv', 'label' => 'Variante', 'value' => (string) ($record?->cod_prenda_variantes_inv ?? ''), 'type' => 'select', 'options' => $this->inventoryVariantOptions($record), 'required' => true, 'disabled' => (bool) $record],
                        ['name' => 'ubi_inv', 'label' => 'Ubicación', 'value' => (string) ($record?->ubi_inv ?? 'TIENDA'), 'type' => 'select', 'options' => $this->locationOptions(), 'required' => true],
                        ['name' => 'sku_view', 'label' => 'Código interno', 'value' => $record?->variante?->sku_prv ?? 'Se mostrará al guardar', 'disabled' => true],
                        ['name' => 'prenda_view', 'label' => 'Prenda', 'value' => $record?->variante?->prenda?->nom_pre ?? 'Se mostrará al guardar', 'disabled' => true],
                    ],
                ],
                [
                    'title' => 'Disponible y reserva',
                    'fields' => [
                        ['name' => 'cdi_inv', 'label' => 'Disponible', 'value' => (string) ($record?->cdi_inv ?? 0), 'type' => 'number', 'required' => true, 'placeholder' => '0', 'min' => 0, 'hint' => 'Cantidad total registrada para esta variante.'],
                        ['name' => 'cre_inv', 'label' => 'Reservado', 'value' => (string) ($record?->cre_inv ?? 0), 'type' => 'number', 'required' => true, 'placeholder' => '0', 'min' => 0, 'hint' => 'No puede ser mayor al disponible.'],
                        ['name' => 'available_view', 'label' => 'Saldo actual', 'value' => (string) $available, 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Disponible', 'value' => (string) $available, 'note' => 'Existencias disponibles.', 'tone' => $available <= 0 ? 'danger' : 'success'],
                ['label' => 'Reservado', 'value' => (string) ($record?->cre_inv ?? 0), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Ubicación', 'value' => $record?->ubi_inv ?: 'Sin dato', 'note' => 'Ubicación registrada.', 'tone' => 'neutral'],
                ['label' => 'Código interno', 'value' => $record?->variante?->sku_prv ?? 'Sin código', 'note' => 'Variante asociada.', 'tone' => 'accent'],
            ],
            'detailSections' => [
                [
                    'title' => 'Lectura de inventario',
                    'fields' => [
                        ['label' => 'Prenda', 'value' => $record?->variante?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'accent'],
                        ['label' => 'Color', 'value' => $record?->variante?->color?->nom_col ?? 'Sin color', 'tone' => 'neutral'],
                        ['label' => 'Talla', 'value' => $record?->variante?->talla?->nom_tal ?? 'Sin talla', 'tone' => 'warning'],
                        ['label' => 'Ubicación', 'value' => $record?->ubi_inv ?: 'Sin ubicación', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Disponible', 'note' => 'Existencias disponibles.', 'date' => (string) $available . ' disponibles', 'tone' => 'accent'],
                ['title' => 'Reservado', 'note' => 'Existencias reservadas.', 'date' => (string) ($record?->cre_inv ?? 0) . ' reservados', 'tone' => 'warning'],
                ['title' => 'Código interno', 'note' => 'Variante asociada.', 'date' => $record?->variante?->sku_prv ?? 'Sin código', 'tone' => 'neutral'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function adjustmentViewData($record = null): array
    {
        $record = $record ?: $this->adjustmentTemplates()->first();

        return [
            'recordLabel' => $record['primary'],
            'formSections' => $record['formSections'],
            'summaryMetrics' => $record['summaryMetrics'],
            'detailSections' => $record['detailSections'],
            'timeline' => $record['timeline'],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function alertViewData($record = null): array
    {
        $available = $this->available($record);

        return [
            'recordLabel' => $record?->variante?->prenda?->nom_pre ?? 'Alerta',
            'formSections' => [
                [
                    'title' => 'Detalle de alerta',
                    'description' => 'Prenda, código interno y ubicación.',
                    'fields' => [
                        ['name' => 'prenda', 'label' => 'Prenda', 'value' => $record?->variante?->prenda?->nom_pre ?? 'Sin prenda', 'disabled' => true],
                        ['name' => 'sku', 'label' => 'Código interno', 'value' => $record?->variante?->sku_prv ?? 'Sin código', 'disabled' => true],
                        ['name' => 'ubicacion', 'label' => 'Ubicación', 'value' => $record?->ubi_inv ?? 'Sin ubicacion'],
                        ['name' => 'estado', 'label' => 'Seguimiento', 'value' => $available <= 0 ? '0' : '1', 'type' => 'toggle'],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Disponible', 'value' => (string) $available, 'note' => 'Existencias disponibles.', 'tone' => $available <= 0 ? 'danger' : 'warning'],
                ['label' => 'Reservado', 'value' => (string) ($record?->cre_inv ?? 0), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Ubicación', 'value' => $record?->ubi_inv ?: 'Sin dato', 'note' => 'Ubicación registrada.', 'tone' => 'neutral'],
                ['label' => 'Estado', 'value' => $available <= 0 ? 'Urgente' : 'Vigilar', 'note' => 'Prioridad actual.', 'tone' => $available <= 0 ? 'danger' : 'accent'],
            ],
            'detailSections' => [
                [
                    'title' => 'Alerta activa',
                    'fields' => [
                        ['label' => 'Prenda', 'value' => $record?->variante?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'accent'],
                        ['label' => 'Variante', 'value' => trim(implode(' · ', array_filter([$record?->variante?->color?->nom_col, $record?->variante?->talla?->nom_tal]))) ?: 'Sin variante', 'tone' => 'neutral'],
                        ['label' => 'Disponible', 'value' => (string) $available, 'tone' => $available <= 0 ? 'danger' : 'warning'],
                        ['label' => 'Ubicación', 'value' => $record?->ubi_inv ?: 'Sin ubicacion', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Estado', 'note' => 'Prioridad actual.', 'date' => $available <= 0 ? 'Urgente' : 'Vigilar', 'tone' => $available <= 0 ? 'danger' : 'warning'],
                ['title' => 'Reservado', 'note' => 'Cantidad retenida.', 'date' => (string) ($record?->cre_inv ?? 0) . ' reservados', 'tone' => 'neutral'],
                ['title' => 'Código interno', 'note' => 'Variante asociada.', 'date' => $record?->variante?->sku_prv ?? 'Sin código', 'tone' => 'accent'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function saleViewData(array $definition, $record = null): array
    {
        $relatedRows = $record
            ? collect($record->detalles ?? [])->map(fn ($detail) => [
                'id' => $detail->id_detalle_venta,
                'primary' => $detail->variante?->prenda?->nom_pre ?? 'Detalle #' . $detail->id_detalle_venta,
                'secondary' => trim(implode(' · ', array_filter([$detail->variante?->color?->nom_col, $detail->variante?->talla?->nom_tal]))),
                'status' => ['label' => $detail->can_dve . ' unidades', 'tone' => 'accent'],
                'cells' => [
                    'client' => $detail->variante?->sku_prv ?? 'Sin código interno',
                    'type' => (string) $detail->can_dve,
                    'total' => $this->money((float) $detail->sub_dve),
                    'date' => $this->formatDate($detail->fec_dve),
                ],
                'showHref' => $this->showHref($definition, $record->id_venta),
                'editHref' => null,
            ])->take(6)->all()
            : [];

        return [
            'recordLabel' => $record?->nve_ven ?: 'Nueva venta',
            'formSections' => [
                [
                    'title' => 'Cabecera',
                    'description' => 'Código, cliente, responsable y estado.',
                    'fields' => [
                        ['name' => 'codigo', 'label' => 'Código', 'value' => $record?->nve_ven ?? ''],
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->userName($record?->cliente) ?: 'Sin cliente', 'type' => 'select', 'options' => [['label' => $this->userName($record?->cliente) ?: 'Sin cliente', 'value' => $this->userName($record?->cliente) ?: '']]],
                        ['name' => 'vendedor', 'label' => 'Responsable', 'value' => $this->userName($record?->vendedor) ?: 'Sin responsable', 'type' => 'select', 'options' => [['label' => $this->userName($record?->vendedor) ?: 'Sin responsable', 'value' => $this->userName($record?->vendedor) ?: '']]],
                        ['name' => 'estado', 'label' => 'Estado', 'value' => $record && strtolower((string) $record->est_ven) !== 'cancelada' ? '1' : '0', 'type' => 'toggle'],
                    ],
                ],
                [
                    'title' => 'Monto y cierre',
                    'fields' => [
                        ['name' => 'tipo', 'label' => 'Tipo', 'value' => $record?->tiv_ven ?? 'Sin tipo'],
                        ['name' => 'metodo', 'label' => 'Metodo', 'value' => $record?->mpa_ven ?? 'Sin metodo'],
                        ['name' => 'subtotal', 'label' => 'Subtotal', 'value' => $record ? $this->money((float) $record->sub_ven) : 'Bs 0.00'],
                        ['name' => 'total', 'label' => 'Total', 'value' => $record ? $this->money((float) $record->tot_ven) : 'Bs 0.00'],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Total', 'value' => $record ? $this->money((float) $record->tot_ven) : 'Bs 0.00', 'note' => 'Monto final.', 'tone' => 'success'],
                ['label' => 'Items', 'value' => (string) ($record?->detalles_count ?? $record?->detalles?->count() ?? 0), 'note' => 'Items registrados.', 'tone' => 'accent'],
                ['label' => 'Metodo', 'value' => $record?->mpa_ven ?: 'Sin metodo', 'note' => 'Metodo de pago.', 'tone' => 'neutral'],
                ['label' => 'Estado', 'value' => $record?->est_ven ?: 'Activa', 'note' => 'Estado de la venta.', 'tone' => 'warning'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha comercial',
                    'fields' => [
                        ['label' => 'Código', 'value' => $record?->nve_ven ?: 'Sin código', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->userName($record?->cliente) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Tipo', 'value' => $record?->tiv_ven ?: 'Sin tipo', 'tone' => 'warning'],
                        ['label' => 'Metodo', 'value' => $record?->mpa_ven ?: 'Sin metodo', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de venta', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_ven), 'tone' => 'accent'],
                ['title' => 'Total', 'note' => 'Monto final.', 'date' => $record ? $this->money((float) $record->tot_ven) : 'Bs 0.00', 'tone' => 'success'],
                ['title' => 'Items', 'note' => 'Detalle asociado.', 'date' => (string) ($record?->detalles_count ?? $record?->detalles?->count() ?? 0) . ' items', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? $this->columns(['kind' => 'sales']) : [],
            'relatedRows' => $relatedRows,
        ];
    }

    private function reservationViewData(array $definition, $record = null): array
    {
        $relatedRows = $record
            ? collect($record->detalles ?? [])->map(fn ($detail) => [
                'id' => $detail->id_detalle_reserva,
                'primary' => $detail->variante?->prenda?->nom_pre ?? 'Detalle #' . $detail->id_detalle_reserva,
                'secondary' => trim(implode(' · ', array_filter([$detail->variante?->color?->nom_col, $detail->variante?->talla?->nom_tal]))),
                'status' => ['label' => $detail->can_dre . ' unidades', 'tone' => 'accent'],
                'cells' => [
                    'client' => $detail->variante?->sku_prv ?? 'Sin código interno',
                    'due' => $this->formatDate($detail->fec_dre),
                    'items' => (string) $detail->can_dre,
                    'date' => $this->formatDate($detail->fec_dre),
                ],
                'showHref' => $this->showHref($definition, $record->id_reserva),
                'editHref' => null,
            ])->take(6)->all()
            : [];

        return [
            'recordLabel' => $record?->nre_res ?: 'Nueva reserva',
            'formSections' => [
                [
                    'title' => 'Cabecera',
                    'description' => 'Código, cliente, responsable y estado.',
                    'fields' => [
                        ['name' => 'codigo', 'label' => 'Código', 'value' => $record?->nre_res ?? ''],
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->userName($record?->cliente) ?: 'Sin cliente', 'type' => 'select', 'options' => [['label' => $this->userName($record?->cliente) ?: 'Sin cliente', 'value' => $this->userName($record?->cliente) ?: '']]],
                        ['name' => 'responsable', 'label' => 'Responsable', 'value' => $this->userName($record?->responsable) ?: 'Sin responsable', 'type' => 'select', 'options' => [['label' => $this->userName($record?->responsable) ?: 'Sin responsable', 'value' => $this->userName($record?->responsable) ?: '']]],
                        ['name' => 'estado', 'label' => 'Estado', 'value' => $record && strtolower((string) $record->est_res) !== 'cancelada' ? '1' : '0', 'type' => 'toggle'],
                    ],
                ],
                [
                    'title' => 'Seguimiento',
                    'fields' => [
                        ['name' => 'vence', 'label' => 'Vence', 'value' => $this->formatDate($record?->ven_res), 'type' => 'date'],
                        ['name' => 'observacion', 'label' => 'Observacion', 'value' => $record?->obs_res ?? '', 'type' => 'textarea'],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Items', 'value' => (string) ($record?->detalles_count ?? $record?->detalles?->count() ?? 0), 'note' => 'Items reservados.', 'tone' => 'accent'],
                ['label' => 'Vence', 'value' => $this->formatDate($record?->ven_res), 'note' => 'Fecha de vencimiento.', 'tone' => 'warning'],
                ['label' => 'Responsable', 'value' => $this->userName($record?->responsable) ?: 'Sin responsable', 'note' => 'Usuario responsable.', 'tone' => 'neutral'],
                ['label' => 'Estado', 'value' => $record?->est_res ?: 'Seguimiento', 'note' => 'Estado de la reserva.', 'tone' => 'success'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha de reserva',
                    'fields' => [
                        ['label' => 'Código', 'value' => $record?->nre_res ?: 'Sin código', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->userName($record?->cliente) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Responsable', 'value' => $this->userName($record?->responsable) ?: 'Sin responsable', 'tone' => 'warning'],
                        ['label' => 'Observacion', 'value' => $record?->obs_res ?: 'Sin observacion', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de reserva', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_res), 'tone' => 'accent'],
                ['title' => 'Vencimiento', 'note' => 'Fecha limite.', 'date' => $this->formatDate($record?->ven_res), 'tone' => 'warning'],
                ['title' => 'Items', 'note' => 'Detalle asociado.', 'date' => (string) ($record?->detalles_count ?? $record?->detalles?->count() ?? 0) . ' items', 'tone' => 'neutral'],
            ],
            'relatedColumns' => $relatedRows ? $this->columns(['kind' => 'reservations']) : [],
            'relatedRows' => $relatedRows,
        ];
    }

    private function favoriteViewData($record = null): array
    {
        return [
            'recordLabel' => $record?->prenda?->nom_pre ?? 'Favorito',
            'formSections' => [
                [
                    'title' => 'Relacion',
                    'description' => 'Cliente, prenda, marca y categoria.',
                    'fields' => [
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->userName($record?->usuario) ?: 'Sin cliente', 'disabled' => true],
                        ['name' => 'prenda', 'label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'disabled' => true],
                        ['name' => 'marca', 'label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'disabled' => true],
                        ['name' => 'categoria', 'label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Cliente', 'value' => $this->userName($record?->usuario) ?: 'Sin cliente', 'note' => 'Cliente asociado.', 'tone' => 'accent'],
                ['label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'note' => 'Marca asociada.', 'tone' => 'neutral'],
                ['label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'note' => 'Categoría de la prenda.', 'tone' => 'warning'],
                ['label' => 'Fecha', 'value' => $this->formatDate($record?->fec_fav), 'note' => 'Fecha de registro.', 'tone' => 'success'],
            ],
            'detailSections' => [
                [
                    'title' => 'Lectura comercial',
                    'fields' => [
                        ['label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->userName($record?->usuario) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'tone' => 'warning'],
                        ['label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de alta', 'note' => 'Fecha de registro.', 'date' => $this->formatDate($record?->fec_fav), 'tone' => 'accent'],
                ['title' => 'Prenda', 'note' => 'Prenda asociada.', 'date' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'neutral'],
                ['title' => 'Marca', 'note' => 'Marca asociada.', 'date' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'tone' => 'warning'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function adjustmentTemplates(): Collection
    {
        $lowStock = Inventario::query()->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')->count();
        $ventas = Venta::query()->count();
        $reservas = Reserva::query()->count();

        return collect([
            [
                'id' => 1,
                'primary' => 'Rebalancear stock sensible',
                'secondary' => 'Inventario con margen corto',
                'status' => ['label' => 'Prioritario', 'tone' => 'warning'],
                'cells' => [
                    'area' => 'Inventario',
                    'focus' => $lowStock . ' registros a revisar',
                    'cut' => 'Corte del día',
                    'updated' => 'Hoy',
                ],
                'summaryMetrics' => [
                    ['label' => 'Existencias críticas', 'value' => (string) $lowStock, 'note' => 'Registros por revisar.', 'tone' => 'warning'],
                    ['label' => 'Ventas', 'value' => (string) $ventas, 'note' => 'Ventas registradas.', 'tone' => 'success'],
                    ['label' => 'Reservas', 'value' => (string) $reservas, 'note' => 'Reservas activas.', 'tone' => 'accent'],
                    ['label' => 'Corte', 'value' => 'Hoy', 'note' => 'Fecha del ajuste.', 'tone' => 'neutral'],
                ],
                'formSections' => [
                    [
                        'title' => 'Ajuste sugerido',
                        'description' => 'Motivo y criterio del ajuste.',
                        'fields' => [
                            ['name' => 'titulo', 'label' => 'Título', 'value' => 'Rebalancear existencias sensibles'],
                            ['name' => 'criterio', 'label' => 'Criterio', 'value' => $lowStock . ' registros con margen corto', 'type' => 'textarea'],
                            ['name' => 'estado', 'label' => 'Seguimiento', 'value' => '1', 'type' => 'toggle'],
                        ],
                    ],
                ],
                'detailSections' => [
                    [
                        'title' => 'Resumen del ajuste',
                        'fields' => [
                            ['label' => 'Área', 'value' => 'Inventario', 'tone' => 'accent'],
                            ['label' => 'Enfoque', 'value' => $lowStock . ' registros con margen corto', 'tone' => 'warning'],
                            ['label' => 'Corte', 'value' => 'Hoy', 'tone' => 'neutral'],
                            ['label' => 'Pulso comercial', 'value' => $ventas . ' ventas / ' . $reservas . ' reservas', 'tone' => 'success'],
                        ],
                    ],
                ],
                'timeline' => [
                    ['title' => 'Fecha', 'note' => 'Fecha del ajuste.', 'date' => 'Hoy', 'tone' => 'accent'],
                    ['title' => 'Cruce comercial', 'note' => 'Ventas y reservas relacionadas.', 'date' => $ventas . ' / ' . $reservas, 'tone' => 'success'],
                    ['title' => 'Prioridad', 'note' => 'Nivel actual.', 'date' => 'Alta', 'tone' => 'warning'],
                ],
            ],
            [
                'id' => 2,
                'primary' => 'Conciliar reservas activas',
                'secondary' => 'Seguimiento entre apartados y margen disponible',
                'status' => ['label' => 'Seguimiento', 'tone' => 'accent'],
                'cells' => [
                    'area' => 'Comercial',
                    'focus' => $reservas . ' reservas en observacion',
                    'cut' => 'Semana actual',
                    'updated' => 'Ahora',
                ],
                'summaryMetrics' => [
                    ['label' => 'Reservas', 'value' => (string) $reservas, 'note' => 'Reservas activas.', 'tone' => 'accent'],
                    ['label' => 'Existencias críticas', 'value' => (string) $lowStock, 'note' => 'Cruce con inventario.', 'tone' => 'warning'],
                    ['label' => 'Ventas', 'value' => (string) $ventas, 'note' => 'Ventas del periodo.', 'tone' => 'success'],
                    ['label' => 'Corte', 'value' => 'Semana', 'note' => 'Periodo de revisión.', 'tone' => 'neutral'],
                ],
                'formSections' => [
                    [
                        'title' => 'Conciliación operativa',
                        'description' => 'Motivo y criterio de revisión.',
                        'fields' => [
                            ['name' => 'titulo', 'label' => 'Título', 'value' => 'Conciliar reservas activas'],
                            ['name' => 'criterio', 'label' => 'Criterio', 'value' => $reservas . ' reservas visibles en la semana actual', 'type' => 'textarea'],
                            ['name' => 'estado', 'label' => 'Seguimiento', 'value' => '1', 'type' => 'toggle'],
                        ],
                    ],
                ],
                'detailSections' => [
                    [
                        'title' => 'Resumen del ajuste',
                        'fields' => [
                            ['label' => 'Área', 'value' => 'Comercial', 'tone' => 'accent'],
                            ['label' => 'Enfoque', 'value' => $reservas . ' reservas visibles', 'tone' => 'warning'],
                            ['label' => 'Ventana', 'value' => 'Semana actual', 'tone' => 'neutral'],
                            ['label' => 'Cruce', 'value' => $lowStock . ' registros con margen corto', 'tone' => 'success'],
                        ],
                    ],
                ],
                'timeline' => [
                    ['title' => 'Periodo', 'note' => 'Ventana de revisión.', 'date' => 'Semana', 'tone' => 'accent'],
                    ['title' => 'Existencias críticas', 'note' => 'Registros relacionados.', 'date' => (string) $lowStock, 'tone' => 'warning'],
                    ['title' => 'Estado', 'note' => 'Seguimiento actual.', 'date' => 'Activo', 'tone' => 'success'],
                ],
            ],
        ]);
    }

    private function available($inventory): int
    {
        if (!$inventory) {
            return 0;
        }

        if (method_exists($inventory, 'stockDisponible')) {
            return $inventory->stockDisponible();
        }

        return max(0, (int) ($inventory->cdi_inv ?? 0) - (int) ($inventory->cre_inv ?? 0));
    }

    private function latestDate(Builder $query, string $field): string
    {
        $value = (clone $query)->orderByDesc($field)->value($field);

        return $this->formatDate($value);
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value->timezone(config('app.timezone'))->format('d M Y');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }

    private function booleanStatus(?bool $value, string $positive = 'Activo', string $negative = 'Inactivo'): array
    {
        return $value
            ? ['label' => $positive, 'tone' => 'success']
            : ['label' => $negative, 'tone' => 'danger'];
    }

    private function textStatus(?string $value, string $fallback): array
    {
        $label = $value ?: $fallback;
        $tone = match (strtolower($label)) {
            'cancelada', 'inactivo' => 'danger',
            'pendiente', 'seguimiento' => 'warning',
            'activa', 'entregada', 'completada' => 'success',
            default => 'accent',
        };

        return ['label' => $label, 'tone' => $tone];
    }

    private function userName($user): string
    {
        if (!$user) {
            return '';
        }

        return trim(implode(' ', array_filter([$user->nom_usu, $user->app_usu, $user->apm_usu]))) ?: ($user->email ?? '');
    }
}
