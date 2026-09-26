<?php

namespace App\Services\Superadmin\Resources;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceViewDataService
{
    public function __construct(
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceQueryService $queries,
        private readonly AdminFormattingService $formatting,
        private readonly ResourceOptionsService $options,
    ) {}

    public function headerActions(array $definition, Request $request): array
    {
        if ($this->definitions->definitionKey($definition) === 'seguridad.usuarios') {
            $creatableSlugs = ['superadmin', 'vendedor'];

            return $this->options->managedUserRoles()
                ->filter(fn (array $role) => in_array($role['slug'], $creatableSlugs, true))
                ->map(fn (array $role) => [
                    'label' => 'Nuevo ' . ucfirst($role['singular']),
                    'href' => $this->definitions->createHref($definition) . '?role_slug=' . urlencode($role['slug']),
                    'kind' => $role['slug'] === $request->string('role_slug')->toString() ? 'primary' : 'secondary',
                ])
                ->values()
                ->all();
        }

        if (!$this->definitions->supportsCreate($definition)) {
            return [];
        }

        $createLabel = match ($this->definitions->definitionKey($definition)) {
            'catalogo.patrones' => 'Nuevo patrón',
            'catalogo.texturas' => 'Nueva textura',
            'catalogo.materiales' => 'Nuevo material',
            'catalogo.tallas' => 'Nueva talla',
            'catalogo.temporadas' => 'Nueva temporada',
            'comercial.ventas' => 'Nueva venta',
            'comercial.reservas' => 'Nueva reserva',
            default => 'Nuevo ' . $definition['singular'],
        };

        return [[
            'label' => $createLabel,
            'href' => $this->definitions->createHref($definition),
            'kind' => 'primary',
        ]];
    }

    public function segmentedFilters(array $definition, Request $request): array
    {
        if ($this->definitions->definitionKey($definition) === 'comercial.ventas') {
            $activeStatus = $request->string('status')->toString();
            $baseHref = $this->definitions->indexHref($definition);
            $query = collect($request->query())->except(['status'])->all();
            $allHref = $query ? $baseHref . '?' . http_build_query($query) : $baseHref;
            $segments = [
                ['label' => 'En revisión', 'value' => 'en_revision'],
                ['label' => 'Confirmadas', 'value' => 'registrada'],
                ['label' => 'Canceladas', 'value' => 'cancelada'],
                ['label' => 'Incompletas', 'value' => 'incompleta'],
            ];

            return collect([[
                'label' => 'Todas',
                'href' => $allHref,
                'active' => $activeStatus === '',
                'count' => (string) Venta::query()->count(),
            ]])->merge(collect($segments)->map(fn (array $segment) => [
                'label' => $segment['label'],
                'href' => $baseHref . '?' . http_build_query([...$query, 'status' => $segment['value']]),
                'active' => $activeStatus === $segment['value'],
                'count' => (string) Venta::query()->where('est_ven', $segment['value'])->count(),
            ]))->values()->all();
        }

        if ($this->definitions->definitionKey($definition) === 'comercial.reservas') {
            $activeStatus = $request->string('status')->toString();
            $baseHref = $this->definitions->indexHref($definition);
            $query = collect($request->query())->except(['status'])->all();
            $allHref = $query ? $baseHref . '?' . http_build_query($query) : $baseHref;
            $segments = [
                ['label' => 'Por procesar', 'value' => 'por_procesar'],
                ['label' => 'Confirmadas', 'value' => 'confirmada'],
                ['label' => 'Listas para recojo', 'value' => 'lista_recojo'],
                ['label' => 'Vencidas', 'value' => 'vencida'],
                ['label' => 'Completadas', 'value' => 'completada'],
                ['label' => 'Canceladas', 'value' => 'cancelada'],
                ['label' => 'Rechazadas', 'value' => 'rechazada'],
            ];

            return collect([[
                'label' => 'Todas',
                'href' => $allHref,
                'active' => $activeStatus === '',
                'count' => (string) Reserva::query()->count(),
            ]])->merge(collect($segments)->map(fn (array $segment) => [
                'label' => $segment['label'],
                'href' => $baseHref . '?' . http_build_query([...$query, 'status' => $segment['value']]),
                'active' => $activeStatus === $segment['value'],
                'count' => (string) Reserva::query()->where('est_res', $segment['value'])->count(),
            ]))->values()->all();
        }

        if ($this->definitions->definitionKey($definition) !== 'seguridad.usuarios') {
            return [];
        }

        $activeSlug = $request->string('role_slug')->toString();
        $baseHref = $this->definitions->indexHref($definition);
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
            $this->options->managedUserRoles()
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

    public function filterAction(array $definition, Request $request): string
    {
        if ($this->definitions->definitionKey($definition) !== 'seguridad.usuarios') {
            return $this->definitions->indexHref($definition);
        }

        $query = [];

        if ($request->string('role_slug')->toString() !== '') {
            $query['role_slug'] = $request->string('role_slug')->toString();
        }

        return $query === []
            ? $this->definitions->indexHref($definition)
            : $this->definitions->indexHref($definition) . '?' . http_build_query($query);
    }

    public function emptyState(array $definition, Request $request): array
    {
        if ($this->definitions->definitionKey($definition) === 'seguridad.usuarios') {
            $activeRole = $this->options->activeManagedUserRole($request);

            return [
                'title' => $activeRole
                    ? 'Sin ' . mb_strtolower($activeRole['label'], 'UTF-8')
                    : 'Sin usuarios',
                'description' => $activeRole
                    ? 'No hay cuentas registradas en ese grupo por ahora.'
                    : 'No hay usuarios registrados en este módulo.',
                'actionLabel' => $activeRole ? 'Nuevo ' . $activeRole['singular'] : 'Nuevo usuario',
                'actionHref' => $activeRole
                    ? $this->definitions->createHref($definition) . '?role_slug=' . urlencode($activeRole['slug'])
                    : $this->definitions->createHref($definition),
            ];
        }

        return [
            'title' => 'Sin registros',
            'description' => 'No hay registros disponibles en ' . mb_strtolower($definition['title']) . '.',
            'actionLabel' => $this->definitions->supportsCreate($definition)
                ? match ($this->definitions->definitionKey($definition)) {
                    'catalogo.patrones' => 'Nuevo patrón',
                    'catalogo.texturas' => 'Nueva textura',
                    'catalogo.materiales' => 'Nuevo material',
                    'catalogo.tallas' => 'Nueva talla',
                    'catalogo.temporadas' => 'Nueva temporada',
                    'comercial.ventas' => 'Nueva venta',
                    'comercial.reservas' => 'Nueva reserva',
                    default => 'Nuevo ' . $definition['singular'],
                }
                : null,
            'actionHref' => $this->definitions->supportsCreate($definition) ? $this->definitions->createHref($definition) : null,
        ];
    }

    public function columns(array $definition, ?Request $request = null): array
    {
        if ($this->definitions->definitionKey($definition) === 'catalogo.tallas') {
            return [
                ['key' => 'segment', 'label' => 'Segmento'],
                ['key' => 'group', 'label' => 'Grupo'],
                ['key' => 'system', 'label' => 'Sistema'],
                ['key' => 'order', 'label' => 'Orden', 'align' => 'center'],
                ['key' => 'linked', 'label' => 'Variantes', 'align' => 'center'],
            ];
        }

        if ($this->definitions->definitionKey($definition) === 'catalogo.colores') {
            return [
                ['key' => 'exact', 'label' => 'Color exacto'],
                ['key' => 'family', 'label' => 'Familia'],
                ['key' => 'dominant', 'label' => 'Dominante'],
                ['key' => 'hex', 'label' => 'HEX'],
                ['key' => 'order', 'label' => 'Orden', 'align' => 'center'],
                ['key' => 'linked', 'label' => 'Variantes', 'align' => 'center'],
            ];
        }

        if ($this->definitions->definitionKey($definition) === 'catalogo.temporadas') {
            return [
                ['key' => 'code', 'label' => 'Código'],
                ['key' => 'period', 'label' => 'Periodo'],
                ['key' => 'period_status', 'label' => 'Vigencia'],
                ['key' => 'state', 'label' => 'Estado'],
                ['key' => 'order', 'label' => 'Orden', 'align' => 'center'],
                ['key' => 'linked', 'label' => 'Prendas', 'align' => 'center'],
            ];
        }

        return match ($definition['kind']) {
            'taxonomy' => [
                ['key' => 'summary', 'label' => 'Resumen'],
                ['key' => 'linked', 'label' => 'Cruces', 'align' => 'center'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'users' => $this->options->userColumns($request),
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
            'inventory' => $this->inventoryColumns(),
            'adjustments' => [
                ['key' => 'area', 'label' => 'Área'],
                ['key' => 'focus', 'label' => 'Enfoque'],
                ['key' => 'cut', 'label' => 'Corte'],
                ['key' => 'updated', 'label' => 'Actualizado', 'align' => 'right'],
            ],
            'alerts' => $this->alertColumns(),
            'sales' => [
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
                ['key' => 'state', 'label' => 'Estado venta'],
                ['key' => 'pickup', 'label' => 'Estado recojo'],
                ['key' => 'date', 'label' => 'Fecha', 'align' => 'right'],
            ],
            'reservations' => [
                ['key' => 'client', 'label' => 'Cliente'],
                ['key' => 'payment', 'label' => 'Pago'],
                ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
                ['key' => 'declared', 'label' => 'Declarado', 'align' => 'right'],
                ['key' => 'confirmed', 'label' => 'Confirmado', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Saldo', 'align' => 'right'],
                ['key' => 'reservation', 'label' => 'Reserva'],
                ['key' => 'pickup', 'label' => 'Recojo'],
                ['key' => 'items', 'label' => 'Artículos', 'align' => 'center'],
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

    public function metrics(array $definition): array
    {
        return match ($definition['kind']) {
            'taxonomy' => $this->taxonomyMetrics($definition),
            'users' => [
                ['label' => 'Usuarios', 'value' => (string) User::query()->count(), 'note' => 'Cuentas registradas.', 'tone' => 'accent'],
                ['label' => 'Superadmins', 'value' => (string) $this->options->countUsersByRoleSlug('superadmin'), 'note' => 'Acceso total al panel.', 'tone' => 'danger'],
                ['label' => 'Vendedores', 'value' => (string) $this->options->countUsersByRoleSlug('vendedor'), 'note' => 'Operación comercial.', 'tone' => 'warning'],
                ['label' => 'Clientes', 'value' => (string) $this->options->countUsersByRoleSlug('cliente'), 'note' => 'Cuentas del ecommerce.', 'tone' => 'success'],
            ],
            'products' => [
                ['label' => 'Prendas', 'value' => (string) Prenda::query()->count(), 'note' => 'Prendas registradas.', 'tone' => 'accent'],
                ['label' => 'Activas', 'value' => (string) Prenda::query()->where('est_pre', true)->count(), 'note' => 'Prendas visibles.', 'tone' => 'success'],
                ['label' => 'Variantes', 'value' => (string) PrendaVariante::query()->count(), 'note' => 'Cruces comerciales cargados.', 'tone' => 'neutral'],
                ['label' => 'Disponible', 'value' => (string) (int) Inventario::query()->sum('cdi_inv'), 'note' => 'Existencias disponibles reales.', 'tone' => 'warning'],
            ],
            'variants' => [
                ['label' => 'Variantes', 'value' => (string) PrendaVariante::query()->count(), 'note' => 'Variantes registradas.', 'tone' => 'accent'],
                ['label' => 'Activas', 'value' => (string) PrendaVariante::query()->where('est_prv', true)->count(), 'note' => 'Variantes habilitadas.', 'tone' => 'success'],
                ['label' => 'Con existencias', 'value' => (string) PrendaVariante::query()->has('inventario')->count(), 'note' => 'Variantes con inventario.', 'tone' => 'neutral'],
                ['label' => 'Última alta', 'value' => $this->queries->latestDate(PrendaVariante::query(), 'fec_prv'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'images' => [
                ['label' => 'Imágenes', 'value' => (string) ImagenPrenda::query()->count(), 'note' => 'Imágenes registradas.', 'tone' => 'accent'],
                ['label' => 'Principales', 'value' => (string) ImagenPrenda::query()->where('pri_igp', true)->count(), 'note' => 'Imágenes principales.', 'tone' => 'success'],
                ['label' => 'Por variante', 'value' => (string) ImagenPrenda::query()->whereNotNull('cod_prenda_variantes_igp')->count(), 'note' => 'Imágenes ligadas a variantes.', 'tone' => 'neutral'],
                ['label' => 'Última alta', 'value' => $this->queries->latestDate(ImagenPrenda::query(), 'fec_igp'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'inventory' => [
                ['label' => 'Líneas', 'value' => (string) Inventario::query()->count(), 'note' => 'Registros de inventario.', 'tone' => 'accent'],
                ['label' => 'Disponible', 'value' => (string) (Inventario::query()->sum('cdi_inv')), 'note' => 'Existencias disponibles.', 'tone' => 'success'],
                ['label' => 'Reservado', 'value' => (string) Inventario::query()->sum('cre_inv'), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Existencias críticas', 'value' => (string) Inventario::query()->where('cdi_inv', '>', 0)->where('cdi_inv', '<=', 3)->count(), 'note' => 'Registros por revisar.', 'tone' => 'danger'],
            ],
            'adjustments' => [
                ['label' => 'Ajustes', 'value' => (string) $this->queries->adjustmentTemplates()->count(), 'note' => 'Ajustes sugeridos.', 'tone' => 'accent'],
                ['label' => 'Existencias críticas', 'value' => (string) Inventario::query()->where('cdi_inv', '>', 0)->where('cdi_inv', '<=', 3)->count(), 'note' => 'Registros por revisar.', 'tone' => 'warning'],
                ['label' => 'Ventas', 'value' => (string) Venta::query()->count(), 'note' => 'Ventas registradas.', 'tone' => 'success'],
                ['label' => 'Reservas', 'value' => (string) Reserva::query()->count(), 'note' => 'Reservas activas.', 'tone' => 'neutral'],
            ],
            'alerts' => [
                ['label' => 'Sin stock', 'value' => (string) Inventario::query()->where('cdi_inv', 0)->count(), 'note' => 'Registros sin disponible.', 'tone' => 'danger'],
                ['label' => 'Bajo stock', 'value' => (string) Inventario::query()->where('cdi_inv', '>', 0)->where('cdi_inv', '<=', 3)->count(), 'note' => 'Registros con margen corto.', 'tone' => 'warning'],
                ['label' => 'Sin inventario', 'value' => (string) PrendaVariante::query()->doesntHave('inventario')->count(), 'note' => 'Variantes sin linea de inventario.', 'tone' => 'accent'],
                ['label' => 'Sin imagen', 'value' => (string) Prenda::query()->doesntHave('imagenes')->count(), 'note' => 'Prendas sin soporte visual.', 'tone' => 'neutral'],
            ],
            'sales' => [
                ['label' => 'Ventas', 'value' => (string) Venta::query()->count(), 'note' => 'Operaciones registradas.', 'tone' => 'accent'],
                ['label' => 'En revisión', 'value' => (string) Venta::query()->where('est_ven', 'en_revision')->count(), 'note' => 'Comprobantes pendientes.', 'tone' => 'warning'],
                ['label' => 'Facturación confirmada', 'value' => $this->formatting->money((float) Venta::query()->where('est_ven', 'registrada')->sum('tot_ven')), 'note' => 'Solo ventas confirmadas.', 'tone' => 'success'],
                ['label' => 'Detalles', 'value' => (string) \App\Models\DetalleVenta::query()->count(), 'note' => 'Líneas de venta.', 'tone' => 'neutral'],
                ['label' => 'Última venta', 'value' => $this->queries->latestDate(Venta::query(), 'fec_ven'), 'note' => 'Último registro.', 'tone' => 'warning'],
            ],
            'reservations' => [
                ['label' => 'Reservas', 'value' => (string) Reserva::query()->count(), 'note' => 'Apartados registrados.', 'tone' => 'accent'],
                ['label' => 'Por procesar', 'value' => (string) Reserva::query()->where('est_res', 'por_procesar')->count(), 'note' => 'Comprobantes pendientes.', 'tone' => 'warning'],
                ['label' => 'Listas para recojo', 'value' => (string) Reserva::query()->where('est_res', 'lista_recojo')->count(), 'note' => 'Pendientes de entrega.', 'tone' => 'success'],
                ['label' => 'Vencidas', 'value' => (string) Reserva::query()->whereIn('est_res', ['por_procesar', 'confirmada', 'lista_recojo'])->whereNotNull('fec_limite_recojo_res')->where('fec_limite_recojo_res', '<', now())->count(), 'note' => 'Requieren accion humana.', 'tone' => 'danger'],
            ],
            'favorites' => [
                ['label' => 'Favoritos', 'value' => (string) Favorito::query()->count(), 'note' => 'Favoritos registrados.', 'tone' => 'accent'],
                ['label' => 'Clientes', 'value' => (string) Favorito::query()->distinct('cod_usuarios_fav')->count('cod_usuarios_fav'), 'note' => 'Clientes con favoritos.', 'tone' => 'success'],
                ['label' => 'Prendas', 'value' => (string) Favorito::query()->distinct('cod_prendas_fav')->count('cod_prendas_fav'), 'note' => 'Prendas guardadas.', 'tone' => 'neutral'],
                ['label' => 'Último registro', 'value' => $this->queries->latestDate(Favorito::query(), 'fec_fav'), 'note' => 'Último movimiento.', 'tone' => 'warning'],
            ],
            default => [],
        };
    }

    public function filterControls(array $definition, Request $request): array
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
                $this->selectFilter('role', 'Rol', $request, $this->options->managedUserRoleOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Activos', 'Inactivos')),
            ],
            'products' => [
                $this->selectFilter('category', 'Categoría', $request, $this->options->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->options->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->options->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->options->genderOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Visibles', 'Ocultas')),
                $this->selectFilter('stock', 'Disponibilidad', $request, $this->stockFilterOptions()),
                $this->numberFilter('price_min', 'Precio desde', $request),
                $this->numberFilter('price_max', 'Precio hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'variants' => [
                $this->selectFilter('category', 'Categoría', $request, $this->options->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->options->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->options->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->options->genderOptions()),
                $this->selectFilter('color', 'Color', $request, $this->options->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->options->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->booleanSelectOptions('Activas', 'Pausadas')),
                $this->selectFilter('stock', 'Disponibilidad', $request, $this->stockFilterOptions()),
                $this->numberFilter('price_min', 'Precio desde', $request),
                $this->numberFilter('price_max', 'Precio hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'inventory', 'alerts' => [
                $this->selectFilter('category', 'Categoría', $request, $this->options->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->options->brandOptions()),
                $this->selectFilter('season', 'Temporada', $request, $this->options->seasonOptions()),
                $this->selectFilter('gender', 'Género', $request, $this->options->genderOptions()),
                $this->selectFilter('color', 'Color', $request, $this->options->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->options->sizeOptions()),
                $this->selectFilter('location', 'Ubicacion', $request, $this->options->inventoryLocationOptions()),
                $this->selectFilter('stock', 'Estado de stock', $request, $this->stockFilterOptions()),
            ],
            'sales' => [
                $this->selectFilter('category', 'Categoría', $request, $this->options->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->options->brandOptions()),
                $this->selectFilter('color', 'Color', $request, $this->options->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->options->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->distinctTextOptions('ventas', 'est_ven')),
                $this->numberFilter('price_min', 'Total desde', $request),
                $this->numberFilter('price_max', 'Total hasta', $request),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            'reservations' => [
                $this->selectFilter('category', 'Categoría', $request, $this->options->categoryOptions()),
                $this->selectFilter('brand', 'Marca', $request, $this->options->brandOptions()),
                $this->selectFilter('color', 'Color', $request, $this->options->colorOptions()),
                $this->selectFilter('size', 'Talla', $request, $this->options->sizeOptions()),
                $this->selectFilter('status', 'Estado', $request, $this->distinctTextOptions('reservas', 'est_res')),
                $this->selectFilter('payment_status', 'Estado de pago', $request, $this->distinctTextOptions('reservas', 'est_pago_res')),
                $this->dateFilter('date_from', 'Fecha desde', $request),
                $this->dateFilter('date_to', 'Fecha hasta', $request),
            ],
            default => [],
        };
    }

    public function sortOptions(array $definition): array
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
                ['label' => 'Ubicacion', 'value' => 'location'],
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

    public function appliedFilters(array $controls, array $sortOptions, Request $request): array
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
            $role = $this->options->managedUserRoles()
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

    private function inventoryColumns(): array
    {
        return [
            ['key' => 'product', 'label' => 'Prenda'],
            ['key' => 'variant', 'label' => 'Variante'],
            ['key' => 'color', 'label' => 'Color'],
            ['key' => 'size', 'label' => 'Talla'],
            ['key' => 'sku', 'label' => 'Codigo interno'],
            ['key' => 'available', 'label' => 'Disponible', 'align' => 'right'],
            ['key' => 'reserved', 'label' => 'Reservado', 'align' => 'right'],
            ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
            ['key' => 'location', 'label' => 'Ubicacion', 'align' => 'right'],
        ];
    }

    private function alertColumns(): array
    {
        return [
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'sku', 'label' => 'Codigo interno'],
            ['key' => 'available', 'label' => 'Disponible', 'align' => 'right'],
            ['key' => 'reserved', 'label' => 'Reservado', 'align' => 'right'],
            ['key' => 'location', 'label' => 'Ubicacion'],
            ['key' => 'follow', 'label' => 'Seguimiento', 'align' => 'right'],
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
            ['label' => 'Reservado', 'value' => 'reservado'],
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
            ['label' => 'Última alta', 'value' => $this->queries->latestDate($query, $definition['dateField']), 'note' => 'Último registro.', 'tone' => 'warning'],
        ];
    }


}
