<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Categoria;
use App\Models\Color;
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

class ResourceDefinitionService
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

    public function definition(string $section, string $resource): array
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
                'title' => 'Existencias',
                'singular' => 'registro de inventario',
                'description' => 'Revisión de disponible, reservado y ubicación por variante.',
                'kind' => 'inventory',
                'modelClass' => Inventario::class,
                'filters' => ['Crítico', 'Reservado', 'Disponible'],
            ],
            'inventario.ajustes' => [
                'sectionKey' => 'inventario',
                'sectionLabel' => 'Inventario',
                'resourceKey' => 'ajustes',
                'title' => 'Plan de ajustes',
                'singular' => 'ajuste',
                'description' => 'Vista informativa para revisar posibles ajustes de inventario.',
                'kind' => 'adjustments',
                'filters' => ['Prioritario', 'Inventario', 'Comercial'],
            ],
            'inventario.alertas' => [
                'sectionKey' => 'inventario',
                'sectionLabel' => 'Inventario',
                'resourceKey' => 'alertas',
                'title' => 'Alertas de stock',
                'singular' => 'alerta',
                'description' => 'Seguimiento de existencias bajas, críticas o con reserva pendiente.',
                'kind' => 'alerts',
                'modelClass' => Inventario::class,
                'filters' => ['Crítico', 'Seguimiento', 'Ubicación'],
            ],
            'comercial.ventas' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'ventas',
                'title' => 'Ventas',
                'singular' => 'venta',
                'description' => 'Revisión de ventas registradas, estado comercial y cierre.',
                'kind' => 'sales',
                'modelClass' => Venta::class,
                'dateField' => 'fec_ven',
                'filters' => ['Recientes', 'Pago', 'Cierre'],
            ],
            'comercial.reservas' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'reservas',
                'title' => 'Reservas',
                'singular' => 'reserva',
                'description' => 'Seguimiento de reservas activas, vencimientos y responsables.',
                'kind' => 'reservations',
                'modelClass' => Reserva::class,
                'dateField' => 'fec_res',
                'filters' => ['Vigentes', 'Por vencer', 'Seguimiento'],
            ],
            'comercial.favoritos' => [
                'sectionKey' => 'comercial',
                'sectionLabel' => 'Comercial',
                'resourceKey' => 'favoritos',
                'title' => 'Favoritos',
                'singular' => 'favorito',
                'description' => 'Lectura de prendas guardadas por clientes.',
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

    public function meta(array $definition): array
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

    public function breadcrumbs(array $definition, ?string $last = null, ?string $mode = null): array
    {
        $crumbs = [
            ['label' => 'Panel administrativo', 'href' => '/superadmin'],
            ['label' => $definition['sectionLabel']],
            ['label' => $definition['title'], 'href' => $this->indexHref($definition)],
        ];

        if ($last) {
            $crumbs[] = ['label' => $mode ? $mode . ' · ' . $last : $last];
        }

        return $crumbs;
    }

    public function indexHref(array $definition): string
    {
        return '/superadmin/' . $definition['sectionKey'] . '/' . $definition['resourceKey'];
    }

    public function createHref(array $definition): string
    {
        return $this->indexHref($definition) . '/nuevo';
    }

    public function showHref(array $definition, string|int $record): string
    {
        return $this->indexHref($definition) . '/' . urlencode((string) $record);
    }

    public function editHref(array $definition, string|int $record): string
    {
        return $this->showHref($definition, $record) . '/editar';
    }

    public function rowEditHref(array $definition, string|int $record): ?string
    {
        return $this->supportsCrud($definition) ? $this->editHref($definition, $record) : null;
    }

    public function definitionKey(array $definition): string
    {
        return $definition['sectionKey'] . '.' . $definition['resourceKey'];
    }

    public function supportsCrud(array $definition): bool
    {
        $key = $this->definitionKey($definition);

        return in_array($key, self::CRUD_RESOURCES, true)
            || in_array($key, self::SECONDARY_CRUD_RESOURCES, true);
    }

    public function moduleStatus(array $definition): array
    {
        $key = $this->definitionKey($definition);

        if (in_array($key, self::CRUD_RESOURCES, true)) {
            return [
                'status' => 'crud',
                'label' => 'Gestión completa',
                'description' => 'Este módulo permite crear, consultar, editar y guardar registros.',
                'secondary' => false,
            ];
        }

        if (in_array($key, self::SECONDARY_CRUD_RESOURCES, true)) {
            return [
                'status' => 'crud',
                'label' => 'Gestión complementaria',
                'description' => 'Este módulo guarda registros relacionados y depende de la ficha de prendas.',
                'secondary' => true,
            ];
        }

        if (in_array($key, self::NOT_READY_RESOURCES, true)) {
            return [
                'status' => 'not_ready',
                'label' => 'En preparación',
                'description' => 'Esta vista es solo informativa y no ejecuta cambios operativos.',
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

    public function supportsInlineCreate(array $definition): bool
    {
        return in_array($this->definitionKey($definition), [
            'catalogo.categorias',
            'catalogo.marcas',
            'catalogo.colores',
            'catalogo.tallas',
            'catalogo.temporadas',
        ], true);
    }

    public function recordKey($record): string|int
    {
        return method_exists($record, 'getKey') ? $record->getKey() : $record['id'];
    }
}
