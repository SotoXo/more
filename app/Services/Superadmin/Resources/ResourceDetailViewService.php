<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Categoria;
use App\Models\Color;
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
use App\Services\Superadmin\InventoryStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceDetailViewService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceQueryService $queries,
        private readonly ResourceViewDataService $viewDataService,
        private readonly ResourceOptionsService $options,
        private readonly InventoryStatusService $inventoryStatus,
    ) {
    }

    public function viewData(array $definition, mixed $record = null, ?Request $request = null): array
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

    private function taxonomyViewData(array $definition, $record = null): array
    {
        $key = $this->definitions->definitionKey($definition);
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
        $nameLabel = match ($key) {
            'catalogo.tallas' => 'Nombre de talla',
            'catalogo.colores' => 'Nombre del color',
            'catalogo.temporadas' => 'Nombre de temporada',
            default => 'Nombre',
        };
        $namePlaceholder = match ($key) {
            'catalogo.tallas' => 'Ej.: M, L, 38 o UNITALLA',
            'catalogo.colores' => 'Ej.: AZUL MARINO',
            'catalogo.temporadas' => 'Ej.: OTOÑO-INVIERNO',
            default => 'Nombre del registro',
        };
        $nameHint = $key === 'catalogo.tallas' ? 'Usa el nombre visible de la talla.' : $nameHint;
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
                'label' => $nameLabel,
                'value' => $name,
                'required' => true,
                'placeholder' => $namePlaceholder,
                'disabled' => $isSystemRole,
                'transform' => 'uppercase',
                'disallowNumbers' => $disallowNumbers,
                'hint' => $isSystemRole ? 'Este rol pertenece al sistema y su nombre no puede modificarse.' : $nameHint,
            ],
        ];

        $detailLabel = 'Descripción';
        $detailValue = (string) ($record?->{$definition['descriptionField']} ?? '');
        $sizeSystem = '';
        $sizeGroup = '';
        $sizeProfileLabel = '';
        $periodLabel = 'Sin periodo definido';
        $periodStatus = ['label' => 'Sin periodo definido', 'tone' => 'neutral'];

        if ($key === 'catalogo.categorias') {
            $sizeSystem = Schema::hasColumn('categorias', 'tip_tal_cat') ? (string) ($record?->tip_tal_cat ?? '') : '';
            $sizeGroup = Schema::hasColumn('categorias', 'gru_tal_cat') ? (string) ($record?->gru_tal_cat ?? '') : '';
            $sizeProfileLabel = trim(implode(' / ', array_filter([$sizeSystem, $sizeGroup]))) ?: 'Sin sugerencia';
            $mainFields[] = [
                'name' => 'des_cat',
                'label' => 'Descripción',
                'value' => (string) ($record?->des_cat ?? ''),
                'type' => 'textarea',
                'placeholder' => 'Descripción de la categoría',
            ];
            $mainFields[] = [
                'name' => 'tip_tal_cat',
                'label' => 'Sistema de talla',
                'value' => $sizeSystem,
                'type' => 'select',
                'options' => $this->options->categorySizeSystemOptions(),
                'hint' => 'Define qué grupo de tallas se sugerirá al registrar variantes.',
            ];
            $mainFields[] = [
                'name' => 'gru_tal_cat',
                'label' => 'Grupo de talla',
                'value' => $sizeGroup,
                'type' => 'select',
                'options' => $this->options->categorySizeGroupOptions(),
                'hint' => 'Define qué grupo de tallas se sugerirá al registrar variantes.',
            ];
            $mainFields[] = [
                'name' => 'cod_categoria_padre_cat',
                'label' => 'Categoría padre',
                'value' => (string) ($record?->cod_categoria_padre_cat ?? ''),
                'type' => 'select',
                'options' => $this->options->categoryParentOptions($record),
            ];
            $detailLabel = 'Categoría padre';
            $detailValue = $record?->padre?->nom_cat ?? 'Sin categoría padre';
        } elseif ($key === 'catalogo.colores') {
            $family = (string) ($record?->fam_col ?? 'AZUL');
            $mainFields = [];
            $mainFields[] = [
                'name' => 'nom_col',
                'label' => 'Nombre sugerido',
                'value' => (string) ($record?->nom_col ?? ''),
                'type' => 'hidden',
            ];
            $mainFields[] = [
                'name' => 'hex_col',
                'label' => 'Color exacto',
                'value' => (string) ($record?->hex_col ?? '#1D4ED8'),
                'type' => 'color',
                'required' => true,
                'hint' => 'Selecciona el color específico desde la paleta visual.',
            ];
            $mainFields[] = [
                'name' => 'fam_col',
                'label' => 'Familia de color',
                'value' => $family,
                'type' => 'color-family',
                'options' => $this->options->colorFamilyOptions(),
                'required' => true,
                'hint' => 'Selecciona la familia principal usada para filtros.',
            ];
            $mainFields[] = [
                'name' => 'hex_dom_col',
                'label' => 'Color dominante/base',
                'value' => (string) ($record?->hex_dom_col ?? $this->options->colorFamilyHex($family) ?? '#1D4ED8'),
                'type' => 'hidden',
            ];
            $mainFields[] = [
                'name' => 'ord_col',
                'label' => 'Orden automático',
                'value' => (string) ($record?->ord_col ?? 0),
                'type' => 'hidden',
            ];
            $detailLabel = 'Familia';
            $detailValue = $family ?: 'Sin familia';
        } elseif ($key === 'catalogo.tallas') {
            $sizeSegment = Schema::hasColumn('tallas', 'seg_tal') ? (string) ($record?->seg_tal ?? 'ADULTO') : 'ADULTO';
            $sizeSystem = Schema::hasColumn('tallas', 'sis_tal') ? (string) ($record?->sis_tal ?? 'ALFA') : 'ALFA';
            $sizeGroup = Schema::hasColumn('tallas', 'gru_tal') ? (string) ($record?->gru_tal ?? 'SUPERIOR') : 'SUPERIOR';
            $sizeProfileLabel = trim(implode(' / ', array_filter([$sizeSegment, $sizeGroup, $sizeSystem]))) ?: 'Sin clasificación';
            $nameField = [
                ...$mainFields[0],
                'suggestions' => $this->sizeNameSuggestions(),
            ];
            $mainFields = [[
                'name' => 'seg_tal',
                'label' => 'Segmento',
                'value' => $sizeSegment,
                'type' => 'select',
                'options' => $this->options->sizeSegmentOptions(),
                'required' => true,
                'hint' => 'Define si la talla pertenece a adulto, infantil o bebé.',
            ], [
                'name' => 'gru_tal',
                'label' => 'Grupo de uso',
                'value' => $sizeGroup,
                'type' => 'select',
                'options' => $this->options->sizeGroupOptions(),
                'required' => true,
                'hint' => 'Ej.: SUPERIOR para poleras, INFERIOR para pantalones, GENERAL para prendas únicas.',
            ], [
                'name' => 'sis_tal',
                'label' => 'Sistema de talla',
                'value' => $sizeSystem,
                'type' => 'select',
                'options' => $this->options->sizeSystemOptions(),
                'required' => true,
                'hint' => 'Define el sistema usado para interpretar la talla.',
            ], $nameField, [
                'name' => 'ord_tal',
                'label' => 'Orden automático',
                'value' => (string) ($record?->ord_tal ?? 0),
                'type' => 'number',
                'disabled' => true,
                'placeholder' => 'Se calcula al guardar',
                'min' => 0,
                'hint' => 'El orden se genera automáticamente.',
            ]];
            $detailLabel = 'Sistema / grupo';
            $detailValue = $sizeProfileLabel;
        } elseif ($key === 'catalogo.temporadas') {
            $periodLabel = $this->seasonPeriodLabel($record);
            $periodStatus = $this->seasonPeriodStatus($record);
            $mainFields[] = [
                'name' => 'cod_tem',
                'label' => 'Código corto',
                'value' => (string) ($record?->cod_tem ?? ''),
                'placeholder' => 'Ej.: OI, PV, RES, CAP, PER',
                'transform' => 'uppercase',
                'maxLength' => 10,
                'required' => true,
            ];
            $mainFields[] = [
                'name' => 'des_tem',
                'label' => 'Descripción',
                'value' => (string) ($record?->des_tem ?? ''),
                'type' => 'textarea',
                'placeholder' => 'Descripción de la temporada',
            ];
            $mainFields[] = [
                'name' => 'fec_ini_tem',
                'label' => 'Fecha de inicio',
                'value' => $this->formDate($record?->fec_ini_tem),
                'type' => 'date',
                'required' => true,
                'hint' => 'Define desde qué fecha estará vigente la temporada.',
            ];
            $mainFields[] = [
                'name' => 'fec_fin_tem',
                'label' => 'Fecha de fin',
                'value' => $this->formDate($record?->fec_fin_tem),
                'type' => 'date',
                'required' => true,
                'hint' => 'Define hasta qué fecha estará vigente la temporada.',
            ];
            $mainFields[] = [
                'name' => 'ord_tem',
                'label' => 'Orden de visualización',
                'value' => (string) ($record?->ord_tem ?? 0),
                'type' => 'number',
                'min' => 0,
                'hint' => 'El orden controla cómo aparece en listas y selectores.',
            ];
            $detailLabel = 'Periodo';
            $detailValue = $periodLabel;
        } else {
            $mainFields[] = [
                'name' => $definition['descriptionField'],
                'label' => 'Descripción',
                'value' => (string) ($record?->{$definition['descriptionField']} ?? ''),
                'type' => 'textarea',
                'placeholder' => 'Descripción del registro',
            ];
        }

        $statusFields = [
            [
                'name' => $statusField,
                'label' => 'Estado',
                'value' => $status,
                'type' => 'toggle',
                'disabled' => $isSystemRole,
                'hint' => $isSystemRole ? 'Los roles del sistema deben permanecer activos.' : null,
            ],
        ];

        if (!in_array($key, ['catalogo.tallas', 'catalogo.colores'], true)) {
            $statusFields[] = ['name' => 'linked_count', 'label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'disabled' => true];
            $statusFields[] = ['name' => $dateField, 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->{$dateField}), 'disabled' => true];
        } elseif ($record && $key === 'catalogo.tallas') {
            $statusFields[] = ['name' => $dateField, 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->{$dateField}), 'disabled' => true];
        }

        return [
            'recordLabel' => match (true) {
                $key === 'catalogo.tallas' && !$record => 'Nueva talla',
                $key === 'catalogo.colores' && !$record => 'Nuevo color',
                $key === 'catalogo.temporadas' && !$record => 'Nueva temporada',
                default => $name ?: ucfirst($definition['singular']),
            },
            'formTitle' => match ($key) {
                'catalogo.tallas' => $record ? 'Editar talla' : 'Nueva talla',
                'catalogo.colores' => $record ? 'Editar color' : 'Nuevo color',
                'catalogo.temporadas' => $record ? 'Editar temporada' : 'Nueva temporada',
                default => null,
            },
            'formDescription' => match ($key) {
                'catalogo.tallas' => 'Registra una talla para clasificar variantes de prendas.',
                'catalogo.colores' => 'Registra un color desde una paleta visual y asigna su familia.',
                'catalogo.temporadas' => 'Registra una temporada con su periodo de vigencia.',
                default => null,
            },
            'formSections' => [
                [
                    'title' => 'Datos principales',
                    'description' => 'Campos principales del registro.',
                    'fields' => $mainFields,
                ],
                [
                    'title' => 'Estado',
                    'description' => 'Estado y registros asociados.',
                    'fields' => $statusFields,
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Estado', 'value' => $statusLabel, 'note' => 'Estado del registro.', 'tone' => $record && !$record->{$statusField} ? 'danger' : 'success'],
                ['label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'note' => 'Registros asociados.', 'tone' => 'accent'],
                ['label' => $key === 'catalogo.categorias' ? 'Grupo de talla' : ($key === 'catalogo.tallas' ? 'Sistema / grupo' : $detailLabel), 'value' => in_array($key, ['catalogo.categorias', 'catalogo.tallas'], true) ? $sizeProfileLabel : $detailValue, 'note' => $key === 'catalogo.temporadas' ? ($periodStatus['label'] ?? 'Estado visual') : 'Dato principal.', 'tone' => $key === 'catalogo.temporadas' ? ($periodStatus['tone'] ?? 'neutral') : 'neutral'],
                ['label' => 'Fecha', 'value' => $this->formatting->formatDate($record?->{$dateField}), 'note' => 'Fecha de alta.', 'tone' => 'warning'],
            ],
            'detailSections' => [[
                'title' => 'Detalle',
                'description' => 'Información principal del registro.',
                'fields' => [
                    ['label' => 'Nombre', 'value' => $name ?: 'Sin nombre', 'tone' => 'accent'],
                    ['label' => $detailLabel, 'value' => $detailValue ?: 'Sin dato', 'tone' => 'neutral'],
                    ...($key === 'catalogo.categorias' ? [
                        ['label' => 'Sistema de talla', 'value' => $sizeSystem ?: 'Sin dato', 'tone' => 'accent'],
                        ['label' => 'Grupo de talla', 'value' => $sizeGroup ?: 'Sin dato', 'tone' => 'warning'],
                    ] : []),
                    ...($key === 'catalogo.colores' ? [
                        ['label' => 'Familia', 'value' => $record?->fam_col ?: 'Sin familia', 'tone' => 'accent'],
                        ['label' => 'Color exacto', 'value' => $record?->hex_col ?: 'Sin color', 'tone' => 'neutral'],
                        ['label' => 'Color dominante', 'value' => $record?->hex_dom_col ?: 'Sin dominante', 'tone' => 'neutral'],
                        ['label' => 'Orden', 'value' => (string) ($record?->ord_col ?? 0), 'tone' => 'warning'],
                    ] : []),
                    ...($key === 'catalogo.tallas' ? [
                        ['label' => 'Segmento', 'value' => $sizeSegment ?: 'Sin dato', 'tone' => 'success'],
                        ['label' => 'Sistema de talla', 'value' => $sizeSystem ?: 'Sin dato', 'tone' => 'accent'],
                        ['label' => 'Grupo de uso', 'value' => $sizeGroup ?: 'Sin dato', 'tone' => 'warning'],
                        ['label' => 'Orden', 'value' => (string) ($record?->ord_tal ?? 0), 'tone' => 'neutral'],
                    ] : []),
                    ...($key === 'catalogo.temporadas' ? [
                        ['label' => 'Código', 'value' => $record?->cod_tem ?: 'Sin código', 'tone' => 'accent'],
                        ['label' => 'Periodo', 'value' => $periodLabel, 'tone' => 'neutral'],
                        ['label' => 'Vigencia', 'value' => $periodStatus['label'], 'tone' => $periodStatus['tone']],
                        ['label' => 'Orden', 'value' => (string) ($record?->ord_tem ?? 0), 'tone' => 'warning'],
                    ] : []),
                    ['label' => 'Estado', 'value' => $statusLabel, 'tone' => $record && !$record->{$statusField} ? 'danger' : 'success'],
                    ['label' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'value' => (string) $count, 'tone' => 'warning'],
                ],
            ]],
            'timeline' => [
                ['title' => 'Fecha de alta', 'note' => 'Fecha registrada.', 'date' => $this->formatting->formatDate($record?->{$dateField}), 'tone' => 'accent'],
                ['title' => 'Estado', 'note' => 'Estado actual.', 'date' => $statusLabel, 'tone' => 'success'],
                ['title' => ucfirst((string) ($definition['countLabel'] ?? 'Registros')), 'note' => 'Registros asociados.', 'date' => (string) $count, 'tone' => 'warning'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function formDate($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
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

    private function userViewData($record = null, ?Request $request = null): array
    {
        $authUser = $request?->user();
        $isSelfEdit = $authUser && $record && (int) $authUser->getAuthIdentifier() === (int) $record->id;

        $name = $record ? $this->formatting->userName($record) : 'Nuevo usuario';
        $presetRole = !$record ? $this->options->activeManagedUserRole($request) : null;
        $selectedRoleId = $record?->cod_roles_usu ?? $presetRole['id'] ?? '';
        $selectedRoleLabel = $record?->rol?->nom_rol
            ? $this->options->roleLabel((string) $record->rol->nom_rol)
            : ($presetRole ? $this->options->roleLabel((string) $presetRole['singular']) : 'Usuario');
        $stateLabel = $record ? ($record->est_usu ? 'Activo' : 'Inactivo') : 'Activo';

        // Al crear: solo superadmin y vendedor. Al editar: los tres roles.
        $roleOptions = $record
            ? $this->options->managedUserRoleOptions($record->rol)
            : $this->options->creatableUserRoleOptions();

        $formSections = [
            [
                'title' => 'Datos personales',
                'description' => 'Nombre completo, teléfono celular y correo del usuario.',
                'fields' => [
                    ['name' => 'nom_usu', 'label' => 'Nombre', 'value' => $record?->nom_usu ?? '', 'required' => true, 'placeholder' => 'NOMBRE', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'app_usu', 'label' => 'Apellido paterno', 'value' => $record?->app_usu ?? '', 'required' => true, 'placeholder' => 'APELLIDO PATERNO', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'apm_usu', 'label' => 'Apellido materno', 'value' => $record?->apm_usu ?? '', 'required' => false, 'placeholder' => 'APELLIDO MATERNO (opcional)', 'transform' => 'uppercase', 'maxLength' => 100, 'hint' => 'Se guarda en MAYÚSCULAS. No admite números.'],
                    ['name' => 'tel_usu', 'label' => 'Teléfono celular', 'value' => $record?->tel_usu ?? '', 'type' => 'tel', 'required' => true, 'placeholder' => 'Ej.: 70000000', 'transform' => 'digits', 'maxLength' => 8, 'hint' => 'Debe contener un número celular válido.'],
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
                    ['name' => 'fec_usu', 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->fec_usu), 'disabled' => true],
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
                ['label' => 'Alta', 'value' => $this->formatting->formatDate($record?->fec_usu), 'note' => 'Fecha de alta.', 'tone' => 'neutral'],
                ['label' => 'Celular', 'value' => $record?->tel_usu ?: 'Sin celular', 'note' => 'Teléfono celular registrado.', 'tone' => 'warning'],
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
                        ['label' => 'Celular', 'value' => $record?->tel_usu ?: 'Sin celular', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de cuenta', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_usu), 'tone' => 'accent'],
                ['title' => 'Rol', 'note' => 'Rol actual.', 'date' => $selectedRoleLabel, 'tone' => 'success'],
                ['title' => 'Correo', 'note' => 'Correo registrado.', 'date' => $record?->email ?? 'Sin correo', 'tone' => 'neutral'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function productViewData(array $definition, $record = null): array
    {
        $currentUser = auth()->user();
        $relatedRows = $record
            ? collect($record->variantes ?? [])->map(fn ($variant) => [
                'id' => $variant->id_prenda_variante,
                'primary' => $variant->sku_prv ?: 'Variante #' . $variant->id_prenda_variante,
                'secondary' => trim(implode(' · ', array_filter([$variant->color?->nom_col, $variant->talla?->nom_tal]))),
                'status' => $this->formatting->booleanStatus($variant->est_prv, 'Activa', 'Pausada'),
                'cells' => [
                    'product' => $record->nom_pre,
                    'color' => $variant->color?->nom_col ?? 'Sin color',
                    'size' => $variant->talla?->nom_tal ?? 'Sin talla',
                    'stock' => (string) $this->formatting->available($variant->inventario),
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
                        ['name' => 'gen_pre', 'label' => 'Género', 'value' => (string) ($record?->gen_pre ?? 'mujer'), 'type' => 'select', 'options' => $this->options->genderOptions()],
                        ['name' => 'prb_pre', 'label' => 'Precio de venta (Bs.)', 'value' => $record ? (string) $record->prb_pre : '0', 'type' => 'number', 'required' => true, 'placeholder' => '0.00'],
                    ],
                ],
                [
                    'title' => 'Clasificación',
                    'description' => 'Categoría, marca, temporada y estado.',
                    'fields' => [
                        ['name' => 'cod_categorias_pre', 'label' => 'Categoría', 'value' => (string) ($record?->cod_categorias_pre ?? ''), 'type' => 'select', 'options' => $this->options->categoryOptions(), 'required' => true],
                        ['name' => 'cod_marcas_pre', 'label' => 'Marca', 'value' => (string) ($record?->cod_marcas_pre ?? ''), 'type' => 'select', 'options' => $this->options->brandOptions()],
                        ['name' => 'cod_temporadas_pre', 'label' => 'Temporada', 'value' => (string) ($record?->cod_temporadas_pre ?? ''), 'type' => 'select', 'options' => $this->options->seasonOptions()],
                        ['name' => 'est_pre', 'label' => 'Estado', 'value' => $record ? ($record->est_pre ? '1' : '0') : '1', 'type' => 'toggle'],
                    ],
                ],
                [
                    'title' => 'Registro',
                    'description' => 'Referencia del usuario y fecha de alta.',
                    'fields' => [
                        ['name' => 'cod_usuario_registra_pre', 'label' => 'Registrado por', 'value' => $record?->usuarioRegistra?->nombreCompleto() ?? $currentUser?->nombreCompleto() ?? 'Sin usuario', 'disabled' => true],
                        ['name' => 'fec_pre', 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->fec_pre), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Precio base', 'value' => $record ? $this->formatting->money((float) $record->prb_pre) : 'Bs 0.00', 'note' => 'Precio registrado.', 'tone' => 'accent'],
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
                        ['label' => 'Precio base', 'value' => $record ? $this->formatting->money((float) $record->prb_pre) : 'Bs 0.00', 'tone' => 'accent'],
                        ['label' => 'Estado', 'value' => $record ? ($record->est_pre ? 'Visible' : 'Oculta') : 'Nuevo', 'tone' => $record && !$record->est_pre ? 'danger' : 'success'],
                        ['label' => 'Descripción', 'value' => $record?->des_pre ?: 'Sin descripción', 'tone' => 'warning'],
                    ],
                ],
                [
                    'title' => 'Registro',
                    'fields' => [
                        ['label' => 'Usuario', 'value' => $record?->usuarioRegistra?->nombreCompleto() ?? 'Sin usuario', 'tone' => 'neutral'],
                        ['label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->fec_pre), 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de prenda', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_pre), 'tone' => 'accent'],
                ['title' => 'Imágenes', 'note' => 'Imágenes asociadas.', 'date' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0) . ' imágenes', 'tone' => 'success'],
                ['title' => 'Favoritos', 'note' => 'Favoritos asociados.', 'date' => (string) ($record?->favoritos_count ?? $record?->favoritos?->count() ?? 0) . ' favoritos', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? $this->viewDataService->columns(['kind' => 'variants']) : [],
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
                        ['name' => 'cod_prendas_prv', 'label' => 'Prenda', 'value' => (string) ($record?->cod_prendas_prv ?? ''), 'type' => 'select', 'options' => $this->options->productOptions(), 'required' => true],
                        ['name' => 'cod_colores_prv', 'label' => 'Color', 'value' => (string) ($record?->cod_colores_prv ?? ''), 'type' => 'select', 'options' => $this->options->colorOptions(), 'required' => true],
                        ['name' => 'cod_tallas_prv', 'label' => 'Talla', 'value' => (string) ($record?->cod_tallas_prv ?? ''), 'type' => 'select', 'options' => $this->options->sizeOptions(), 'required' => true],
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
                        ['name' => 'fec_prv', 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->fec_prv), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Disponible', 'value' => (string) $this->formatting->available($record?->inventario), 'note' => 'Existencias disponibles.', 'tone' => 'success'],
                ['label' => 'Reservado', 'value' => (string) ($record?->inventario?->cre_inv ?? 0), 'note' => 'Existencias reservadas.', 'tone' => 'warning'],
                ['label' => 'Precio', 'value' => $record ? $this->formatting->money((float) $record->pve_prv) : 'Bs 0.00', 'note' => 'Precio de venta.', 'tone' => 'accent'],
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
                        ['label' => 'Precio de venta', 'value' => $record ? $this->formatting->money((float) $record->pve_prv) : 'Bs 0.00', 'tone' => 'accent'],
                        ['label' => 'Costo', 'value' => $record && $record->cos_prv !== null ? $this->formatting->money((float) $record->cos_prv) : 'Sin costo', 'tone' => 'warning'],
                        ['label' => 'Estado', 'value' => $record ? ($record->est_prv ? 'Activa' : 'Inactiva') : 'Nueva', 'tone' => $record && !$record->est_prv ? 'danger' : 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Alta de variante', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_prv), 'tone' => 'accent'],
                ['title' => 'Existencias', 'note' => 'Disponible actual.', 'date' => (string) $this->formatting->available($record?->inventario) . ' disponibles', 'tone' => 'success'],
                ['title' => 'Imágenes', 'note' => 'Imágenes asociadas.', 'date' => (string) ($record?->imagenes_count ?? $record?->imagenes?->count() ?? 0) . ' imágenes', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? $this->viewDataService->columns(['kind' => 'images']) : [],
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
                        ['name' => 'cod_prendas_igp', 'label' => 'Prenda', 'value' => (string) ($record?->cod_prendas_igp ?? ''), 'type' => 'select', 'options' => $this->options->productOptions(), 'required' => true],
                        ['name' => 'cod_prenda_variantes_igp', 'label' => 'Variante', 'value' => (string) ($record?->cod_prenda_variantes_igp ?? ''), 'type' => 'select', 'options' => $this->options->variantOptions()],
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
                        ['name' => 'fec_igp', 'label' => 'Fecha de alta', 'value' => $this->formatting->formatDate($record?->fec_igp), 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Prioridad', 'value' => $record?->pri_igp ? 'Principal' : 'Secundaria', 'note' => 'Prioridad actual.', 'tone' => $record?->pri_igp ? 'accent' : 'neutral'],
                ['label' => 'Orden', 'value' => (string) ($record?->ord_igp ?? 0), 'note' => 'Orden actual.', 'tone' => 'warning'],
                ['label' => 'Fuente', 'value' => $record?->fue_igp ?: 'Biblioteca', 'note' => 'Origen registrado.', 'tone' => 'success'],
                ['label' => 'Fecha', 'value' => $this->formatting->formatDate($record?->fec_igp), 'note' => 'Fecha de alta.', 'tone' => 'neutral'],
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
                ['title' => 'Alta de imagen', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_igp), 'tone' => 'accent'],
                ['title' => 'Orden', 'note' => 'Posicion actual.', 'date' => 'Orden ' . (string) ($record?->ord_igp ?? 0), 'tone' => 'warning'],
                ['title' => 'Prenda', 'note' => 'Prenda asociada.', 'date' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'success'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function inventoryViewData($record = null): array
    {
        $available = $this->inventoryStatus->available($record);

        return [
            'recordLabel' => $record?->variante?->prenda?->nom_pre ?? 'Nuevo registro de stock',
            'formSections' => [
                [
                    'title' => 'Ubicación operativa',
                    'description' => 'Variante asociada y ubicación.',
                    'fields' => [
                        ['name' => 'cod_prenda_variantes_inv', 'label' => 'Variante', 'value' => (string) ($record?->cod_prenda_variantes_inv ?? ''), 'type' => 'select', 'options' => $this->options->inventoryVariantOptions($record), 'required' => true, 'disabled' => (bool) $record],
                        ['name' => 'ubi_inv', 'label' => 'Ubicacion', 'value' => (string) ($record?->ubi_inv ?? ''), 'placeholder' => 'Ej.: TIENDA, DEPOSITO o estante A1', 'hint' => 'Opcional. Se normaliza en mayusculas al guardar.'],
                        ['name' => 'sku_view', 'label' => 'Código interno', 'value' => $record?->variante?->sku_prv ?? 'Se mostrará al guardar', 'disabled' => true],
                        ['name' => 'prenda_view', 'label' => 'Prenda', 'value' => $record?->variante?->prenda?->nom_pre ?? 'Se mostrará al guardar', 'disabled' => true],
                    ],
                ],
                [
                    'title' => 'Disponible y reserva',
                    'fields' => [
                        ['name' => 'cdi_inv', 'label' => 'Disponible', 'value' => (string) ($record?->cdi_inv ?? 0), 'type' => 'number', 'required' => true, 'placeholder' => '0', 'min' => 0, 'hint' => 'Stock disponible real para vender o reservar.'],
                        ['name' => 'cre_inv', 'label' => 'Reservado', 'value' => (string) ($record?->cre_inv ?? 0), 'type' => 'number', 'required' => true, 'placeholder' => '0', 'min' => 0, 'disabled' => true, 'hint' => 'Solo lectura: las reservas actualizan esta cantidad automaticamente.'],
                        ['name' => 'total_view', 'label' => 'Stock total calculado', 'value' => (string) $this->inventoryStatus->total($record), 'disabled' => true],
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
        $record = $record ?: $this->queries->adjustmentTemplates()->first();

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
        $available = $this->formatting->available($record);

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
        $saleStatusLabel = $this->saleStatusLabel($record?->est_ven);
        $saleStatusTone = $this->saleStatusTone($record?->est_ven);
        $pickupStatusLabel = $this->salePickupStatusLabel($record?->est_logistico_ven);
        $pickupStatusTone = $this->salePickupStatusTone($record?->est_logistico_ven);
        $saleTypeLabel = $record?->tiv_ven === 'reserva' ? 'Desde reserva' : ($record?->tiv_ven ? mb_convert_case(str_replace('_', ' ', (string) $record->tiv_ven), MB_CASE_TITLE, 'UTF-8') : 'Sin tipo');
        $paymentMethodLabel = $record?->mpa_ven ? mb_convert_case(str_replace('_', ' ', (string) $record->mpa_ven), MB_CASE_TITLE, 'UTF-8') : 'Sin metodo';
        $latestPayment = $record?->pagos?->sortByDesc('id_venta_pago')->first();
        $paymentStatusLabel = match ($latestPayment?->est_vpg) {
            'confirmado' => 'Confirmado',
            'rechazado' => 'Rechazado',
            default => 'Pendiente de revision',
        };
        $paymentStatusTone = match ($latestPayment?->est_vpg) {
            'confirmado' => 'success',
            'rechazado' => 'danger',
            default => 'warning',
        };
        $relatedRows = $record
            ? collect($record->detalles ?? [])->map(fn ($detail) => [
                'id' => $detail->id_detalle_venta,
                'primary' => $detail->variante?->prenda?->nom_pre ?? 'Detalle #' . $detail->id_detalle_venta,
                'secondary' => trim(implode(' - ', array_filter([$detail->variante?->color?->nom_col, $detail->variante?->talla?->nom_tal]))),
                'image' => $detail->variante?->prenda?->imagenes?->sortByDesc('pri_igp')->sortBy('ord_igp')->first()?->url_igp,
                'status' => ['label' => $detail->can_dve . ' unidades', 'tone' => 'accent'],
                'cells' => [
                    'product_code' => (string) ($detail->variante?->prenda?->id_prenda ?? 'Sin codigo'),
                    'sku' => $detail->variante?->sku_prv ?? 'Sin codigo interno',
                    'color' => $detail->variante?->color?->nom_col ?? 'Sin color',
                    'size' => $detail->variante?->talla?->nom_tal ?? 'Sin talla',
                    'quantity' => (string) $detail->can_dve,
                    'unit' => $this->formatting->money((float) $detail->pun_dve),
                    'subtotal' => $this->formatting->money((float) $detail->sub_dve),
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_venta),
                'editHref' => null,
            ])->all()
            : [];

        return [
            'recordLabel' => $record?->nve_ven ?: 'Nueva venta',
            'formSections' => [
                [
                    'title' => 'Cabecera',
                    'description' => 'Código, cliente, responsable y estado.',
                    'fields' => [
                        ['name' => 'codigo', 'label' => 'Código', 'value' => $record?->nve_ven ?? ''],
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'type' => 'select', 'options' => [['label' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'value' => $this->formatting->userName($record?->cliente) ?: '']]],
                        ['name' => 'vendedor', 'label' => 'Responsable', 'value' => $this->formatting->userName($record?->vendedor) ?: 'Sin responsable', 'type' => 'select', 'options' => [['label' => $this->formatting->userName($record?->vendedor) ?: 'Sin responsable', 'value' => $this->formatting->userName($record?->vendedor) ?: '']]],
                        ['name' => 'estado', 'label' => 'Estado', 'value' => $saleStatusLabel],
                    ],
                ],
                [
                    'title' => 'Monto y cierre',
                    'fields' => [
                        ['name' => 'tipo', 'label' => 'Tipo', 'value' => ($record?->tiv_ven === 'reserva' ? 'Desde reserva' : ($record?->tiv_ven ?? 'Sin tipo'))],
                        ['name' => 'metodo', 'label' => 'Método', 'value' => $record?->mpa_ven ?? 'Sin método'],
                        ['name' => 'subtotal', 'label' => 'Subtotal', 'value' => $record ? $this->formatting->money((float) $record->sub_ven) : 'Bs 0.00'],
                        ['name' => 'total', 'label' => 'Total', 'value' => $record ? $this->formatting->money((float) $record->tot_ven) : 'Bs 0.00'],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Total', 'value' => $record ? $this->formatting->money((float) $record->tot_ven) : 'Bs 0.00', 'note' => 'Monto final.', 'tone' => 'success'],
                ['label' => 'Prendas', 'value' => (string) collect($record?->detalles ?? [])->sum(fn ($detail) => (int) $detail->can_dve), 'note' => 'Unidades vendidas.', 'tone' => 'accent'],
                ['label' => 'Estado venta', 'value' => $saleStatusLabel, 'note' => 'Pago y confirmación.', 'tone' => $saleStatusTone],
                ['label' => 'Estado recojo', 'value' => $pickupStatusLabel, 'note' => 'Seguimiento logistico.', 'tone' => $pickupStatusTone],
            ],
            'detailSections' => [
                [
                    'title' => 'Resumen de venta',
                    'fields' => [
                        ['label' => 'Numero de venta', 'value' => $record?->nve_ven ?: 'Sin codigo', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Vendedor / verificador', 'value' => $this->formatting->userName($record?->vendedor) ?: 'Sin vendedor', 'tone' => 'accent'],
                        ['label' => 'Fecha', 'value' => $this->formatting->formatDate($record?->fec_ven), 'tone' => 'neutral'],
                        ['label' => 'Tipo de venta', 'value' => $saleTypeLabel, 'tone' => $record?->tiv_ven === 'reserva' ? 'accent' : 'warning'],
                        ['label' => 'Estado comercial', 'value' => $saleStatusLabel, 'tone' => $saleStatusTone],
                        ['label' => 'Estado logistico', 'value' => $pickupStatusLabel, 'tone' => $pickupStatusTone],
                        ['label' => 'Subtotal', 'value' => $record ? $this->formatting->money((float) $record->sub_ven) : 'Bs 0.00', 'tone' => 'neutral'],
                        ['label' => 'Descuento', 'value' => $record ? $this->formatting->money((float) $record->des_ven) : 'Bs 0.00', 'tone' => 'warning'],
                        ['label' => 'Total', 'value' => $record ? $this->formatting->money((float) $record->tot_ven) : 'Bs 0.00', 'tone' => 'success'],
                    ],
                ],
                [
                    'title' => 'Pago',
                    'fields' => [
                        ['label' => 'Metodo de pago', 'value' => $latestPayment?->metodo_pago_vpg ? mb_convert_case(str_replace('_', ' ', (string) $latestPayment->metodo_pago_vpg), MB_CASE_TITLE, 'UTF-8') : $paymentMethodLabel, 'tone' => 'neutral'],
                        ['label' => 'Monto declarado', 'value' => $latestPayment ? $this->formatting->money((float) $latestPayment->monto_declarado_vpg) : 'Sin pago registrado', 'tone' => 'warning'],
                        ['label' => 'Monto verificado', 'value' => $latestPayment ? $this->formatting->money((float) $latestPayment->monto_verificado_vpg) : 'Se ingresa al revisar la venta', 'tone' => 'accent'],
                        ['label' => 'Total requerido', 'value' => $record ? $this->formatting->money((float) $record->tot_ven) : 'Bs 0.00', 'tone' => 'success'],
                        ['label' => 'Estado del pago', 'value' => $paymentStatusLabel, 'tone' => $paymentStatusTone],
                        ['label' => 'Comprobante subido por el cliente', 'value' => $latestPayment?->comprobante_url_vpg ?: 'Sin comprobante registrado', 'tone' => $latestPayment?->comprobante_url_vpg ? 'accent' : 'danger'],
                        ['label' => 'Observacion de revision', 'value' => $latestPayment?->obs_vpg ?: 'Sin observacion', 'tone' => 'neutral'],
                    ],
                ],
                [
                    'title' => 'Recojo / entrega',
                    'fields' => [
                        ['label' => 'Origen de stock', 'value' => $record?->origen_stock_ven ?: 'Sin origen registrado', 'tone' => 'accent'],
                        ['label' => 'Punto de recojo', 'value' => $record?->punto_recojo_ven ?: 'Sin punto registrado', 'tone' => 'neutral'],
                        ['label' => 'Fecha disponible', 'value' => $this->formatting->formatDate($record?->fec_disponible_recojo_ven), 'tone' => 'warning'],
                        ['label' => 'Fecha limite', 'value' => $this->formatting->formatDate($record?->fec_limite_recojo_ven), 'tone' => 'warning'],
                        ['label' => 'Fecha de entrega', 'value' => $record?->fec_entrega_ven ? $this->formatting->formatDate($record->fec_entrega_ven) : 'Aun no entregada', 'tone' => $record?->fec_entrega_ven ? 'success' : 'neutral'],
                        ['label' => 'Usuario que entrego', 'value' => $this->formatting->userName($record?->usuarioEntrega) ?: 'Sin entrega registrada', 'tone' => $record?->usuarioEntrega ? 'success' : 'neutral'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de venta', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_ven), 'tone' => 'accent'],
                ['title' => 'Total', 'note' => 'Monto final.', 'date' => $record ? $this->formatting->money((float) $record->tot_ven) : 'Bs 0.00', 'tone' => 'success'],
                ['title' => 'Items', 'note' => 'Detalle asociado.', 'date' => (string) ($record?->detalles_count ?? $record?->detalles?->count() ?? 0) . ' items', 'tone' => 'warning'],
            ],
            'relatedColumns' => $relatedRows ? [
                ['key' => 'product_code', 'label' => 'Codigo prenda'],
                ['key' => 'sku', 'label' => 'Codigo interno'],
                ['key' => 'color', 'label' => 'Color'],
                ['key' => 'size', 'label' => 'Talla'],
                ['key' => 'quantity', 'label' => 'Cantidad', 'align' => 'center'],
                ['key' => 'unit', 'label' => 'Unitario', 'align' => 'right'],
                ['key' => 'subtotal', 'label' => 'Subtotal', 'align' => 'right'],
            ] : [],
            'relatedRows' => $relatedRows,
        ];
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

    private function reservationViewData(array $definition, $record = null): array
    {
        $isExpired = $record
            && in_array($record->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)
            && $record->fec_limite_recojo_res
            && $record->fec_limite_recojo_res->lt(Carbon::now());
        $latestPayment = $record?->pagos?->sortByDesc('id_reserva_pago')->first();
        $generatedSale = $this->saleForReservation($record);
        $isCompletedLike = in_array($record?->est_res, ['completada', 'recogida'], true);
        $totalAmount = round((float) ($record?->tot_res ?? 0), 2);
        $confirmedAmount = round((float) ($record?->monto_pagado_res ?? 0), 2);
        $realBalance = max(0, round($totalAmount - $confirmedAmount, 2));
        $hasCompletedInconsistency = $isCompletedLike
            && ($realBalance > 0 || ($record?->est_pago_res ?? null) !== 'pagado_total' || !$generatedSale);
        $visibleStatus = $isExpired
            ? 'Vencida'
            : ($hasCompletedInconsistency
                ? 'Recogida con saldo pendiente o venta faltante'
                : $this->reservationStatusLabel($record?->est_res));
        $visiblePaymentStatus = $this->reservationPaymentLabel($record?->est_pago_res);
        $minimumPayment = $record ? ((float) $record->tot_res) * 0.5 : 0;
        $relatedRows = $record
            ? collect($record->detalles ?? [])->map(fn ($detail) => [
                'id' => $detail->id_detalle_reserva,
                'primary' => $detail->variante?->prenda?->nom_pre ?? 'Detalle #' . $detail->id_detalle_reserva,
                'secondary' => trim(implode(' - ', array_filter([$detail->variante?->color?->nom_col, $detail->variante?->talla?->nom_tal]))),
                'status' => ['label' => $detail->can_dre . ' unidades', 'tone' => 'accent'],
                'cells' => [
                    'sku' => $detail->variante?->sku_prv ?? 'Sin codigo interno',
                    'quantity' => (string) $detail->can_dre,
                    'unit' => $this->formatting->money((float) $detail->pun_dre),
                    'subtotal' => $this->formatting->money((float) $detail->sub_dre),
                ],
                'showHref' => $this->definitions->showHref($definition, $record->id_reserva),
                'editHref' => null,
            ])->all()
            : [];
        $paymentRows = $record
            ? collect($record->pagos ?? [])->sortByDesc('id_reserva_pago')->map(fn ($payment) => [
                'id' => 'pago-' . $payment->id_reserva_pago,
                'primary' => 'Pago ' . ($payment->metodo_pago_rpg ?: 'sin metodo'),
                'secondary' => $payment->comprobante_url_rpg ?: 'Sin comprobante',
                'status' => [
                    'label' => match ($payment->est_rpg) {
                        'confirmado' => 'Confirmado',
                        'rechazado' => 'Rechazado',
                        default => 'En revision',
                    },
                    'tone' => $payment->est_rpg === 'confirmado' ? 'success' : ($payment->est_rpg === 'rechazado' ? 'danger' : 'warning'),
                ],
                'cells' => [
                    'sku' => 'Comprobante',
                    'quantity' => $this->formatting->money((float) $payment->monto_declarado_rpg),
                    'unit' => $this->formatting->money((float) $payment->monto_confirmado_rpg),
                    'subtotal' => number_format((float) $payment->porcentaje_rpg, 2) . '%',
                ],
                'showHref' => $payment->comprobante_url_rpg,
                'editHref' => null,
            ])->values()->all()
            : [];

        return [
            'recordLabel' => $record?->nre_res ?: 'Nueva reserva',
            'formSections' => [
                [
                    'title' => 'Cabecera',
                    'description' => 'Codigo, cliente, responsable y estado.',
                    'fields' => [
                        ['name' => 'codigo', 'label' => 'Codigo', 'value' => $record?->nre_res ?? ''],
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'type' => 'select', 'options' => [['label' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'value' => $this->formatting->userName($record?->cliente) ?: '']]],
                        ['name' => 'responsable', 'label' => 'Responsable', 'value' => $this->formatting->userName($record?->responsable) ?: 'Sin responsable', 'type' => 'select', 'options' => [['label' => $this->formatting->userName($record?->responsable) ?: 'Sin responsable', 'value' => $this->formatting->userName($record?->responsable) ?: '']]],
                        ['name' => 'estado', 'label' => 'Estado de reserva', 'value' => $visibleStatus],
                    ],
                ],
                [
                    'title' => 'Pago y recojo',
                    'fields' => [
                        ['name' => 'total', 'label' => 'Total', 'value' => $this->formatting->money($totalAmount)],
                        ['name' => 'minimo', 'label' => 'Anticipo minimo requerido', 'value' => $this->formatting->money($minimumPayment)],
                        ['name' => 'pagado', 'label' => 'Monto pagado confirmado', 'value' => $this->formatting->money($confirmedAmount)],
                        ['name' => 'saldo', 'label' => 'Saldo pendiente', 'value' => $this->formatting->money($realBalance)],
                        ['name' => 'estado_pago', 'label' => 'Estado de pago', 'value' => $visiblePaymentStatus],
                        ['name' => 'disponible', 'label' => 'Fecha disponible de recojo', 'value' => $this->formatting->formatDate($record?->fec_disponible_recojo_res), 'type' => 'date'],
                        ['name' => 'limite', 'label' => 'Fecha limite de recojo', 'value' => $this->formatting->formatDate($record?->fec_limite_recojo_res), 'type' => 'date'],
                        ['name' => 'punto', 'label' => 'Punto de recojo', 'value' => $record?->punto_recojo_res ?? 'Recojo en tienda Mati Boutique'],
                        ['name' => 'observacion', 'label' => 'Observacion', 'value' => $record?->obs_res ?? '', 'type' => 'textarea'],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Total', 'value' => $this->formatting->money($totalAmount), 'note' => 'Monto congelado.', 'tone' => 'accent'],
                ['label' => 'Saldo', 'value' => $this->formatting->money($realBalance), 'note' => 'Pendiente de pago.', 'tone' => $realBalance <= 0 ? 'success' : 'warning'],
                ['label' => 'Pago', 'value' => $visiblePaymentStatus, 'note' => 'Estado de pago.', 'tone' => $record?->est_pago_res === 'rechazado' ? 'danger' : 'success'],
                ['label' => 'Estado', 'value' => $visibleStatus, 'note' => 'Estado de la reserva.', 'tone' => ($isExpired || $hasCompletedInconsistency) ? 'danger' : 'success'],
            ],
            'detailSections' => [
                [
                    'title' => 'Ficha de reserva',
                    'fields' => array_values(array_filter([
                        ['label' => 'Codigo', 'value' => $record?->nre_res ?: 'Sin codigo', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->formatting->userName($record?->cliente) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Responsable', 'value' => $this->formatting->userName($record?->responsable) ?: 'Sin responsable', 'tone' => 'warning'],
                        $hasCompletedInconsistency ? ['label' => 'Alerta operativa', 'value' => 'Esta reserva figura como recogida, pero tiene saldo pendiente, pago incompleto o venta faltante.', 'tone' => 'danger'] : null,
                        ['label' => 'Estado de reserva', 'value' => $visibleStatus, 'tone' => ($isExpired || $hasCompletedInconsistency) ? 'danger' : 'success'],
                        ['label' => 'Estado de pago', 'value' => $visiblePaymentStatus, 'tone' => $record?->est_pago_res === 'rechazado' ? 'danger' : 'accent'],
                        ['label' => 'Venta generada', 'value' => $generatedSale?->nve_ven ?: 'Sin venta registrada', 'tone' => $generatedSale ? 'success' : 'warning'],
                        ['label' => 'Total de reserva', 'value' => $this->formatting->money($totalAmount), 'tone' => 'success'],
                        ['label' => 'Anticipo minimo requerido', 'value' => $this->formatting->money($minimumPayment), 'tone' => 'warning'],
                        ['label' => 'Monto declarado', 'value' => $latestPayment ? $this->formatting->money((float) $latestPayment->monto_declarado_rpg) : 'Bs 0.00', 'tone' => 'warning'],
                        ['label' => 'Monto confirmado', 'value' => $this->formatting->money($confirmedAmount), 'tone' => 'success'],
                        ['label' => 'Saldo pendiente', 'value' => $this->formatting->money($realBalance), 'tone' => $realBalance <= 0 ? 'success' : 'warning'],
                        ['label' => 'Comprobante subido por el cliente', 'value' => $latestPayment?->comprobante_url_rpg ?: 'Sin comprobante', 'tone' => 'accent'],
                        ['label' => 'Disponible desde', 'value' => $this->formatting->formatDate($record?->fec_disponible_recojo_res), 'tone' => 'neutral'],
                        ['label' => 'Fecha limite de recojo', 'value' => $this->formatting->formatDate($record?->fec_limite_recojo_res), 'tone' => $isExpired ? 'danger' : 'warning'],
                        ['label' => 'Punto de recojo', 'value' => $record?->punto_recojo_res ?: 'Recojo en tienda Mati Boutique', 'tone' => 'neutral'],
                        ['label' => 'Origen de stock', 'value' => $record?->origen_stock_res ?: 'TIENDA', 'tone' => 'accent'],
                        ['label' => 'Observacion de pago', 'value' => $record?->obs_pago_res ?: 'Sin observacion', 'tone' => 'neutral'],
                        ['label' => 'Observacion', 'value' => $record?->obs_res ?: 'Sin observacion', 'tone' => 'success'],
                    ])),
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de reserva', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_res), 'tone' => 'accent'],
                ['title' => 'Disponible', 'note' => 'Fecha disponible de recojo.', 'date' => $this->formatting->formatDate($record?->fec_disponible_recojo_res), 'tone' => 'success'],
                ['title' => 'Limite', 'note' => 'Fecha limite de recojo.', 'date' => $this->formatting->formatDate($record?->fec_limite_recojo_res), 'tone' => $isExpired ? 'danger' : 'warning'],
            ],
            'relatedColumns' => ($relatedRows || $paymentRows) ? [
                ['key' => 'sku', 'label' => 'Codigo / tipo'],
                ['key' => 'quantity', 'label' => 'Cantidad / declarado', 'align' => 'center'],
                ['key' => 'unit', 'label' => 'Unitario / confirmado', 'align' => 'right'],
                ['key' => 'subtotal', 'label' => 'Subtotal / porcentaje', 'align' => 'right'],
            ] : [],
            'relatedRows' => array_merge($relatedRows, $paymentRows),
        ];
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

    private function saleForReservation(?Reserva $reservation): ?Venta
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

    private function favoriteViewData($record = null): array
    {
        return [
            'recordLabel' => $record?->prenda?->nom_pre ?? 'Favorito',
            'formSections' => [
                [
                    'title' => 'Relacion',
                    'description' => 'Cliente, prenda, marca y categoria.',
                    'fields' => [
                        ['name' => 'cliente', 'label' => 'Cliente', 'value' => $this->formatting->userName($record?->usuario) ?: 'Sin cliente', 'disabled' => true],
                        ['name' => 'prenda', 'label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'disabled' => true],
                        ['name' => 'marca', 'label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'disabled' => true],
                        ['name' => 'categoria', 'label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'disabled' => true],
                    ],
                ],
            ],
            'summaryMetrics' => [
                ['label' => 'Cliente', 'value' => $this->formatting->userName($record?->usuario) ?: 'Sin cliente', 'note' => 'Cliente asociado.', 'tone' => 'accent'],
                ['label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'note' => 'Marca asociada.', 'tone' => 'neutral'],
                ['label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'note' => 'Categoría de la prenda.', 'tone' => 'warning'],
                ['label' => 'Fecha', 'value' => $this->formatting->formatDate($record?->fec_fav), 'note' => 'Fecha de registro.', 'tone' => 'success'],
            ],
            'detailSections' => [
                [
                    'title' => 'Lectura comercial',
                    'fields' => [
                        ['label' => 'Prenda', 'value' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'accent'],
                        ['label' => 'Cliente', 'value' => $this->formatting->userName($record?->usuario) ?: 'Sin cliente', 'tone' => 'neutral'],
                        ['label' => 'Marca', 'value' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'tone' => 'warning'],
                        ['label' => 'Categoría', 'value' => $record?->prenda?->categoria?->nom_cat ?? 'Sin categoria', 'tone' => 'success'],
                    ],
                ],
            ],
            'timeline' => [
                ['title' => 'Fecha de alta', 'note' => 'Fecha de registro.', 'date' => $this->formatting->formatDate($record?->fec_fav), 'tone' => 'accent'],
                ['title' => 'Prenda', 'note' => 'Prenda asociada.', 'date' => $record?->prenda?->nom_pre ?? 'Sin prenda', 'tone' => 'neutral'],
                ['title' => 'Marca', 'note' => 'Marca asociada.', 'date' => $record?->prenda?->marca?->nom_mar ?? 'Sin marca', 'tone' => 'warning'],
            ],
            'relatedColumns' => [],
            'relatedRows' => [],
        ];
    }

    private function sizeNameSuggestions(): array
    {
        return [
            ['segment' => 'ADULTO', 'group' => 'SUPERIOR', 'system' => 'ALFA', 'values' => ['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']],
            ['segment' => 'ADULTO', 'group' => 'INFERIOR', 'system' => 'NUMERICA', 'values' => ['32', '34', '36', '38', '40', '42', '44', '46', '48']],
            ['segment' => 'ADULTO', 'group' => 'CALZADO', 'system' => 'CALZADO_US', 'values' => ['6 US', '6.5 US', '7 US', '7.5 US', '8 US', '8.5 US', '9 US']],
            ['segment' => 'ADULTO', 'group' => 'CALZADO', 'system' => 'CALZADO_EU', 'values' => ['36 EU', '37 EU', '38 EU', '39 EU', '40 EU']],
            ['segment' => 'INFANTIL', 'group' => 'GENERAL', 'system' => 'EDAD', 'values' => ['2A', '3A', '4A', '5A', '6A', '8A', '10A', '12A']],
            ['segment' => 'INFANTIL', 'group' => 'GENERAL', 'system' => 'ALTURA_CM', 'values' => ['92 CM', '98 CM', '104 CM', '110 CM', '116 CM', '122 CM', '128 CM']],
            ['segment' => 'BEBE', 'group' => 'GENERAL', 'system' => 'MESES', 'values' => ['0-3M', '3-6M', '6-9M', '9-12M', '12-18M', '18-24M']],
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


}
