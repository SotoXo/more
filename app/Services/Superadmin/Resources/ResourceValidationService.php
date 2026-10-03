<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Role;
use App\Models\User;
use App\Support\UserValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResourceValidationService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly ResourceDefinitionService $definitions,
    ) {
    }

    public function validateWrite(Request $request, array $definition, $record = null): array
    {
        $key = $this->definitions->definitionKey($definition);
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
                'tip_tal_cat' => ['nullable', 'string', Rule::in(['ALFA', 'NUMERICA', 'UNICA', 'UNITALLA'])],
                'gru_tal_cat' => ['nullable', 'string', Rule::in(['SUPERIOR', 'INFERIOR', 'GENERAL', 'ACCESORIOS', 'CALZADO'])],
                'est_cat' => ['nullable', 'boolean'],
            ],
            'catalogo.marcas' => [
                'nom_mar' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s&.\-]+$/u', Rule::unique('marcas', 'nom_mar')->ignore($record?->id_marca, 'id_marca')],
                'des_mar' => ['nullable', 'string', 'max:255'],
                'est_mar' => ['nullable', 'boolean'],
            ],
            'catalogo.colores' => [
                'nom_col' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u'],
                'fam_col' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s]+$/u'],
                'hex_col' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
                'hex_dom_col' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
                'ord_col' => ['nullable', 'integer', 'min:0'],
                'est_col' => ['nullable', 'boolean'],
            ],
            'catalogo.patrones' => [
                'nom_pat' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M}\s-]+$/u', Rule::unique('patrones', 'nom_pat')->ignore($record?->id_patron, 'id_patron')],
                'des_pat' => ['nullable', 'string', 'max:255'],
                'est_pat' => ['nullable', 'boolean'],
                'ord_pat' => ['nullable', 'integer', 'min:0'],
            ],
            'catalogo.texturas' => [
                'nom_tex' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M}\s-]+$/u', Rule::unique('texturas', 'nom_tex')->ignore($record?->id_textura, 'id_textura')],
                'des_tex' => ['nullable', 'string', 'max:255'],
                'est_tex' => ['nullable', 'boolean'],
                'ord_tex' => ['nullable', 'integer', 'min:0'],
            ],
            'catalogo.materiales' => [
                'nom_mat' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M}\s-]+$/u', Rule::unique('materiales', 'nom_mat')->ignore($record?->id_material, 'id_material')],
                'des_mat' => ['nullable', 'string', 'max:255'],
                'est_mat' => ['nullable', 'boolean'],
            ],
            'catalogo.tallas' => [
                'nom_tal' => ['required', 'string', 'max:20', 'regex:/^[\p{L}0-9.\-\s\/]+$/u', Rule::unique('tallas', 'nom_tal')->ignore($record?->id_talla, 'id_talla')],
                'seg_tal' => ['required', 'string', Rule::in(['ADULTO', 'INFANTIL', 'BEBE'])],
                'ord_tal' => ['nullable', 'integer', 'min:0'],
                'sis_tal' => ['required', 'string', Rule::in(['ALFA', 'NUMERICA', 'UNICA', 'CALZADO_US', 'CALZADO_EU', 'CALZADO_UK', 'EDAD', 'ALTURA_CM', 'MESES'])],
                'gru_tal' => ['required', 'string', Rule::in(['SUPERIOR', 'INFERIOR', 'GENERAL', 'ACCESORIOS', 'CALZADO'])],
                'est_tal' => ['nullable', 'boolean'],
            ],
            'catalogo.temporadas' => [
                'nom_tem' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-]+$/u', Rule::unique('temporadas', 'nom_tem')->ignore($record?->id_temporada, 'id_temporada')],
                'cod_tem' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('temporadas', 'cod_tem')->ignore($record?->id_temporada, 'id_temporada')],
                'des_tem' => ['nullable', 'string', 'max:255'],
                'fec_ini_tem' => ['required', 'date'],
                'fec_fin_tem' => ['required', 'date', 'after_or_equal:fec_ini_tem'],
                'ord_tem' => ['nullable', 'integer', 'min:0'],
                'est_tem' => ['nullable', 'boolean'],
            ],
            'catalogo.prendas' => [
                'nom_pre' => ['required', 'string', 'max:150', 'regex:/^[\p{L}\p{N}\s\-_.]+$/u'],
                'des_pre' => ['nullable', 'string'],
                'modelo_pre' => ['nullable', 'string', 'max:150'],
                'ai_run_ids' => ['sometimes', 'array', 'max:50'],
                'ai_run_ids.*' => ['uuid', 'distinct'],
                'ai_image_tokens' => ['sometimes', 'array', 'max:20'],
                'ai_image_tokens.*' => ['uuid', 'distinct'],
                'cod_categorias_pre' => ['required', 'integer', Rule::exists('categorias', 'id_categoria')],
                'cod_marcas_pre' => ['required', 'integer', Rule::exists('marcas', 'id_marca')],
                'cod_temporadas_pre' => ['required', 'integer', Rule::exists('temporadas', 'id_temporada')],
                'gen_pre' => ['required', 'string', Rule::in(['mujer', 'hombre', 'unisex'])],
                'prb_pre' => ['required', 'numeric', 'min:0.01'],
                'est_pre' => ['nullable', 'boolean'],
                'usa_variantes' => ['nullable', 'boolean'],
                'patron_ids' => ['sometimes', 'array'],
                'patron_ids.*' => ['required', 'integer', 'distinct', Rule::exists('patrones', 'id_patron')],
                'textura_ids' => ['sometimes', 'array'],
                'textura_ids.*' => ['required', 'integer', 'distinct', Rule::exists('texturas', 'id_textura')],
                'material_rows' => ['sometimes', 'array'],
                'material_rows.*.cod_material' => ['required', 'integer', 'distinct', Rule::exists('materiales', 'id_material')],
                'material_rows.*.porcentaje' => ['nullable', 'numeric', 'gt:0', 'lte:100', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
                'imagen_principal' => [$record ? 'nullable' : 'required_without:ai_image_tokens', 'file', 'image', 'max:6144'],
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
                'variant_rows.*.tip_pal_prv' => ['nullable', Rule::in(['dominante', 'equilibrada'])],
                'variant_rows.*.cod_colores_prv' => ['required', 'integer', Rule::exists('colores', 'id_color')],
                'variant_rows.*.colores_adicionales' => ['sometimes', 'array'],
                'variant_rows.*.colores_adicionales.*' => ['required', 'integer', 'distinct', Rule::exists('colores', 'id_color')],
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
                'cdi_inv_original' => [$record ? 'required' : 'nullable', 'integer', 'min:0'],
                'cre_inv_original' => [$record ? 'required' : 'nullable', 'integer', 'min:0'],
                'ubi_inv_original' => ['nullable', 'string', 'max:80'],
                'ubi_inv' => ['nullable', 'string', 'max:80'],
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
            'nom_col.required' => 'Ingresa el nombre del color.',
            'fam_col.required' => 'Selecciona la familia de color.',
            'fam_col.regex' => 'La familia del color solo puede contener letras y espacios.',
            'nom_tal.regex' => 'La talla solo puede contener letras, números, espacios, puntos, barras o guiones.',
            'nom_tal.required' => 'Ingresa el nombre de la talla.',
            'seg_tal.required' => 'Selecciona el segmento de la talla.',
            'seg_tal.in' => 'Selecciona el segmento de la talla.',
            'sis_tal.required' => 'Selecciona el sistema de talla.',
            'sis_tal.in' => 'Selecciona el sistema de talla.',
            'gru_tal.required' => 'Selecciona el grupo de uso.',
            'gru_tal.in' => 'Selecciona el grupo de uso.',
            'ord_tal.required' => 'Ingresa un orden válido.',
            'ord_tal.integer' => 'Ingresa un orden válido.',
            'ord_tal.min' => 'Ingresa un orden válido.',
            'nom_tem.required' => 'Ingresa el nombre de la temporada.',
            'nom_tem.regex' => 'La temporada solo puede contener letras, espacios y guiones; se guardará en MAYÚSCULAS.',
            'cod_tem.required' => 'Ingresa el código de la temporada.',
            'cod_tem.unique' => 'El código de temporada ya está registrado.',
            'cod_tem.regex' => 'El código de temporada solo puede contener letras, números y guiones.',
            'fec_ini_tem.required' => 'Selecciona la fecha de inicio.',
            'fec_ini_tem.date' => 'Selecciona la fecha de inicio.',
            'fec_fin_tem.required' => 'Selecciona la fecha de fin.',
            'fec_fin_tem.date' => 'Selecciona la fecha de fin.',
            'fec_fin_tem.after_or_equal' => 'La fecha de fin no puede ser menor que la fecha de inicio.',
            'ord_tem.integer' => 'Ingresa un orden válido.',
            'ord_tem.min' => 'Ingresa un orden válido.',
            'nom_pre.regex' => 'El nombre de la prenda solo puede contener letras, números, espacios, guiones, puntos o guion bajo.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'tel_usu.regex' => 'Ingresa un teléfono celular válido.',
            'hex_col.regex' => 'El color debe usar formato HEX, por ejemplo #1A1A1A.',
            'hex_col.required' => 'Selecciona el color exacto.',
            'hex_dom_col.required' => 'Selecciona la familia de color.',
            'hex_dom_col.regex' => 'La base dominante debe usar formato HEX, por ejemplo #1A1A1A.',
            'ord_col.integer' => 'Ingresa un orden válido.',
            'ord_col.min' => 'Ingresa un orden válido.',
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
            'catalogo.patrones',
            'catalogo.texturas',
            'catalogo.materiales',
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
                foreach ([
                    ['patron_ids', 'patrones', 'id_patron', 'est_pat', 'patrones'],
                    ['textura_ids', 'texturas', 'id_textura', 'est_tex', 'texturas'],
                    ['material_rows', 'materiales', 'id_material', 'est_mat', 'materiales'],
                ] as [$field, $table, $idField, $statusField, $relation]) {
                    $submitted = $request->input($field, []);
                    $submitted = is_array($submitted) ? $submitted : [];
                    $requested = $field === 'material_rows'
                        ? collect($submitted)->filter('is_array')->pluck('cod_material')
                        : collect($submitted);
                    $existing = $record instanceof Prenda ? $record->{$relation}()->pluck($idField)->map(fn ($id) => (int) $id)->all() : [];
                    $newIds = $requested->filter(fn ($id) => is_numeric($id) && !in_array((int) $id, $existing, true))->map(fn ($id) => (int) $id)->all();
                    if ($newIds && DB::table($table)->whereIn($idField, $newIds)->where($statusField, false)->exists()) {
                        $validator->errors()->add($field, 'No puedes agregar registros inactivos de este catálogo.');
                    }
                }
                $knownPercentage = 0;
                foreach ((array) $request->input('material_rows', []) as $index => $material) {
                    if (!is_array($material)) continue;
                    if (filled($material['porcentaje'] ?? null) && is_numeric($material['porcentaje'])) {
                        $knownPercentage += (int) round((float) $material['porcentaje'] * 100);
                    }
                }
                if ($knownPercentage > 10000) {
                    $validator->errors()->add('material_rows', 'Los porcentajes conocidos de materiales no pueden superar el 100 %.');
                }
                foreach ((array) $request->input('variant_rows', []) as $index => $row) {
                    if (!is_array($row)) continue;
                    $additional = $row['colores_adicionales'] ?? [];
                    if (($row['tip_pal_prv'] ?? 'dominante') === 'equilibrada' && is_array($additional) && count(array_unique(array_map('strval', $additional))) < 1 && !filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL)) {
                        $validator->errors()->add("variant_rows.$index.colores_adicionales", 'Una paleta equilibrada necesita al menos dos colores.');
                    }
                    if (is_array($additional) && in_array((string) ($row['cod_colores_prv'] ?? ''), array_map('strval', $additional), true)) {
                        $validator->errors()->add("variant_rows.$index.colores_adicionales", 'El color de referencia no puede repetirse en la paleta.');
                    }
                }
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

                $hasPreparedImage = false;
                foreach ((array) $request->input('ai_image_tokens', []) as $token) {
                    if (!is_string($token) || !\Illuminate\Support\Str::isUuid($token)) continue;
                    try {
                        app(\App\Services\AI\AiPublicationService::class)->approvedRun($token, (int) $request->user()?->getAuthIdentifier());
                        $hasPreparedImage = true;
                    } catch (\Throwable) {
                        $validator->errors()->add('ai_image_tokens', 'La imagen preparada caducó o no está aprobada. Vuelve a prepararla o utiliza una imagen propia.');
                    }
                }

                if (!$request->hasFile('imagen_principal') && !$hasPreparedImage && $remainingExistingImages->isEmpty() && $newGalleryImages->isEmpty()) {
                    $validator->errors()->add('imagen_principal', 'Debes registrar una imagen principal para la prenda.');
                }

                if (!$request->hasFile('imagen_principal') && !$hasPreparedImage && !$hasPrimaryExisting && !$hasPrimaryGallery) {
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
            $validator->after(function ($validator) use ($request, $record) {
                if ($record && (int) $request->input('cre_inv', 0) !== (int) $record->cre_inv) {
                    $validator->errors()->add('cre_inv', 'La cantidad reservada se actualiza desde la lógica de reservas.');
                }
            });
        }

        return $validator->validate();
    }

    private function hasNormalizedDuplicate(string $modelClass, string $field, mixed $value, string $primaryKey, mixed $ignoreId = null): bool
    {
        $normalized = $this->formatting->normalizeUpper((string) $value);

        if (!$normalized) {
            return false;
        }

        return $modelClass::query()
            ->whereRaw('UPPER(' . $field . ') = ?', [$normalized])
            ->when($ignoreId, fn ($query) => $query->where($primaryKey, '!=', $ignoreId))
            ->exists();
    }
}
