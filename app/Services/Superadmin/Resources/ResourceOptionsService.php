<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Patron;
use App\Models\Textura;
use App\Models\Material;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Role;
use App\Models\Talla;
use App\Models\Temporada;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceOptionsService
{
    public function __construct(
        private readonly ResourceDefinitionService $definitions,
    ) {
    }

    public function userColumns(?Request $request = null): array
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
                ['key' => 'phone', 'label' => 'Celular'],
                ['key' => 'status_label', 'label' => 'Estado'],
                ['key' => 'date', 'label' => 'Alta', 'align' => 'right'],
            ];
        }

        // Tab "Todos": incluye columna de rol
        return [
            ['key' => 'role', 'label' => 'Rol'],
            ['key' => 'email', 'label' => 'Correo'],
            ['key' => 'phone', 'label' => 'Celular'],
            ['key' => 'status_label', 'label' => 'Estado'],
            ['key' => 'date', 'label' => 'Alta', 'align' => 'right'],
        ];
    }

    public function roleOptions(): array
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

    public function managedUserRoleOptions($currentRole = null): array
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

    public function creatableUserRoleOptions(): array
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

    public function managedUserRoles(): Collection
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

    public function activeManagedUserRole(?Request $request): ?array
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

    public function countUsersByRoleSlug(string $slug): int
    {
        return User::query()
            ->whereHas('rol', function ($query) use ($slug) {
                $query->whereRaw('LOWER(nom_rol) = ?', [mb_strtolower($slug, 'UTF-8')]);
            })
            ->count();
    }

    public function roleLabel(string $value): string
    {
        return match (mb_strtolower($value, 'UTF-8')) {
            'superadmin' => 'Superadmin',
            'vendedor' => 'Vendedor',
            'cliente' => 'Cliente',
            default => $value,
        };
    }

    public function categoryParentOptions($record = null): array
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

    public function categorySizeSystemOptions(): array
    {
        return collect(['ALFA', 'NUMERICA', 'UNICA'])
            ->map(fn (string $value) => ['label' => $value, 'value' => $value])
            ->all();
    }

    public function categorySizeGroupOptions(): array
    {
        return collect(['SUPERIOR', 'INFERIOR', 'GENERAL', 'ACCESORIOS', 'CALZADO'])
            ->map(fn (string $value) => ['label' => $value, 'value' => $value])
            ->all();
    }

    public function sizeSystemOptions(): array
    {
        return collect(['ALFA', 'NUMERICA', 'UNICA', 'CALZADO_US', 'CALZADO_EU', 'CALZADO_UK', 'EDAD', 'ALTURA_CM', 'MESES'])
            ->map(fn (string $value) => ['label' => $value, 'value' => $value])
            ->all();
    }

    public function sizeSegmentOptions(): array
    {
        return collect(['ADULTO', 'INFANTIL', 'BEBE'])
            ->map(fn (string $value) => ['label' => $value, 'value' => $value])
            ->all();
    }

    public function sizeGroupOptions(): array
    {
        return collect(['SUPERIOR', 'INFERIOR', 'GENERAL', 'ACCESORIOS', 'CALZADO'])
            ->map(fn (string $value) => ['label' => $value, 'value' => $value])
            ->all();
    }

    public function colorFamilyOptions(): array
    {
        return collect($this->colorFamilyPalette())
            ->map(fn (string $hex, string $family) => [
                'label' => $family,
                'value' => $family,
                'swatch' => $hex,
            ])
            ->values()
            ->all();
    }

    public function colorFamilyHex(?string $family): ?string
    {
        $family = mb_strtoupper(trim((string) $family), 'UTF-8');

        return $this->colorFamilyPalette()[$family] ?? null;
    }

    private function colorFamilyPalette(): array
    {
        return [
            'NEGRO' => '#111111',
            'BLANCO' => '#F7F6F2',
            'GRIS' => '#8A8F98',
            'BEIGE' => '#D8C3A5',
            'CAMEL' => '#B88956',
            'MARRON' => '#6F4E37',
            'AZUL' => '#1D4ED8',
            'CELESTE' => '#6F9FD8',
            'VERDE' => '#16A34A',
            'ROJO' => '#B91C1C',
            'BORGONA' => '#8A2332',
            'ROSA' => '#D28FA0',
            'AMARILLO' => '#EAB308',
            'NARANJA' => '#EA580C',
            'MORADO' => '#7E22CE',
            'DORADO' => '#C8A96A',
            'PLATEADO' => '#C0C0C0',
        ];
    }

    public function categoryOptions(): array
    {
        return Categoria::query()
            ->orderBy('nom_cat')
            ->get()
            ->map(fn ($category) => [
                'label' => $category->nom_cat,
                'value' => (string) $category->id_categoria,
                'segment' => 'ADULTO',
                'system' => Schema::hasColumn('categorias', 'tip_tal_cat') ? ($category->tip_tal_cat ?: null) : null,
                'group' => Schema::hasColumn('categorias', 'gru_tal_cat') ? ($category->gru_tal_cat ?: null) : null,
            ])
            ->values()
            ->all();
    }

    public function brandOptions(): array
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

    public function seasonOptions(): array
    {
        $query = Temporada::query();

        if (Schema::hasColumn('temporadas', 'ord_tem')) {
            $query->orderBy('ord_tem');
        }

        $query->orderBy('nom_tem');

        return $query
            ->get()
            ->map(fn ($season) => [
                'label' => $this->seasonOptionLabel($season),
                'value' => (string) $season->id_temporada,
            ])
            ->values()
            ->all();
    }

    public function inlineOption(array $definition, $record): array
    {
        return match ($this->definitions->definitionKey($definition)) {
            'catalogo.categorias' => [
                'label' => $record->nom_cat,
                'value' => (string) $record->id_categoria,
                'segment' => 'ADULTO',
                'system' => Schema::hasColumn('categorias', 'tip_tal_cat') ? ($record->tip_tal_cat ?: null) : null,
                'group' => Schema::hasColumn('categorias', 'gru_tal_cat') ? ($record->gru_tal_cat ?: null) : null,
            ],
            'catalogo.marcas' => [
                'label' => $record->nom_mar,
                'value' => (string) $record->id_marca,
            ],
            'catalogo.colores' => [
                'label' => $record->nom_col,
                'value' => (string) $record->id_color,
                'swatch' => $record->hex_col ?: $record->hex_dom_col,
                'dominantSwatch' => $record->hex_dom_col ?: null,
                'hex' => $record->hex_col ?: null,
                'group' => $record->fam_col ?: null,
            ],
            'catalogo.tallas' => [
                'label' => $record->nom_tal,
                'value' => (string) $record->id_talla,
                'segment' => Schema::hasColumn('tallas', 'seg_tal') ? ($record->seg_tal ?: null) : null,
                'system' => Schema::hasColumn('tallas', 'sis_tal') ? ($record->sis_tal ?: null) : null,
                'group' => Schema::hasColumn('tallas', 'gru_tal') ? ($record->gru_tal ?: null) : null,
                'active' => (bool) $record->est_tal,
            ],
            'catalogo.temporadas' => [
                'label' => $this->seasonOptionLabel($record),
                'value' => (string) $record->id_temporada,
            ],
            default => [
                'label' => (string) $this->definitions->recordKey($record),
                'value' => (string) $this->definitions->recordKey($record),
            ],
        };
    }

    private function seasonOptionLabel(Temporada $season): string
    {
        $code = Schema::hasColumn('temporadas', 'cod_tem') && $season->cod_tem
            ? ' (' . $season->cod_tem . ')'
            : '';
        $period = $this->seasonPeriodLabel($season);

        return trim($season->nom_tem . $code . ($period ? ' · ' . $period : ''));
    }

    private function seasonPeriodLabel(Temporada $season): ?string
    {
        if (!Schema::hasColumn('temporadas', 'fec_ini_tem') || !Schema::hasColumn('temporadas', 'fec_fin_tem')) {
            return null;
        }

        if (!$season->fec_ini_tem || !$season->fec_fin_tem) {
            return null;
        }

        return $season->fec_ini_tem->format('d/m/Y') . ' - ' . $season->fec_fin_tem->format('d/m/Y');
    }

    public function suggestSizeOrder(string $name, ?string $system = null, ?string $segment = null): int
    {
        $value = mb_strtoupper(trim(str_replace('Ú', 'U', $name)), 'UTF-8');
        $normalizedSystem = mb_strtoupper(trim((string) $system), 'UTF-8');
        $alphaMap = [
            'XXXS' => 5,
            'XXS' => 10,
            'XS' => 20,
            'S' => 30,
            'M' => 40,
            'L' => 50,
            'XL' => 60,
            'XXL' => 70,
            'XXXL' => 80,
            'XXXXL' => 90,
            '5XL' => 100,
            'UNITALLA' => 10,
            'UNICA' => 10,
        ];

        if (isset($alphaMap[$value])) {
            return $alphaMap[$value];
        }

        if ($normalizedSystem === 'UNICA') {
            return 10;
        }

        if ($normalizedSystem === 'NUMERICA' && preg_match('/^\d+$/', $value) === 1) {
            return 1000 + ((int) $value * 10);
        }

        if ($normalizedSystem === 'CALZADO_US') {
            $decimal = str_replace(' 1/2', '.5', preg_replace('/\s*US$/', '', $value));
            if (is_numeric($decimal)) {
                return 3000 + (int) round(((float) $decimal) * 10);
            }
        }

        if ($normalizedSystem === 'CALZADO_EU' && preg_match('/^(\d+)\s*EU$/', $value, $matches) === 1) {
            return 4000 + (int) $matches[1];
        }

        if ($normalizedSystem === 'EDAD' && preg_match('/^(\d+)A$/', $value, $matches) === 1) {
            return 5000 + ((int) $matches[1] * 10);
        }

        if ($normalizedSystem === 'ALTURA_CM' && preg_match('/^(\d+)\s*CM$/', $value, $matches) === 1) {
            return 6000 + (int) $matches[1];
        }

        if ($normalizedSystem === 'MESES' && preg_match('/^\d+-(\d+)M$/', $value, $matches) === 1) {
            return 7000 + (int) $matches[1];
        }

        return 9999;
    }

    public function genderOptions(): array
    {
        return [
            ['label' => 'MUJER', 'value' => 'mujer'],
            ['label' => 'HOMBRE', 'value' => 'hombre'],
            ['label' => 'UNISEX', 'value' => 'unisex'],
        ];
    }

    public function productOptions(): array
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

    public function patternOptions(): array
    {
        return Patron::query()->orderBy('ord_pat')->orderBy('nom_pat')->get()
            ->map(fn ($item) => ['label' => $item->nom_pat, 'value' => (string) $item->id_patron, 'active' => (bool) $item->est_pat])->all();
    }

    public function textureOptions(): array
    {
        return Textura::query()->orderBy('ord_tex')->orderBy('nom_tex')->get()
            ->map(fn ($item) => ['label' => $item->nom_tex, 'value' => (string) $item->id_textura, 'active' => (bool) $item->est_tex])->all();
    }

    public function materialOptions(): array
    {
        return Material::query()->orderBy('nom_mat')->get()
            ->map(fn ($item) => ['label' => $item->nom_mat, 'value' => (string) $item->id_material, 'active' => (bool) $item->est_mat])->all();
    }

    public function colorOptions(): array
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
                'swatch' => $color->hex_col ?: $color->hex_dom_col,
                'dominantSwatch' => $color->hex_dom_col ?: null,
                'hex' => $color->hex_col ?: null,
                'group' => $color->fam_col ?: null,
                'active' => (bool) $color->est_col,
            ])
            ->values()
            ->all();
    }

    public function sizeOptions(): array
    {
        return Talla::query()
            ->orderBy('ord_tal')
            ->orderBy('nom_tal')
            ->get()
            ->map(fn ($size) => [
                'label' => $size->nom_tal,
                'value' => (string) $size->id_talla,
                'segment' => Schema::hasColumn('tallas', 'seg_tal') ? ($size->seg_tal ?: null) : null,
                'system' => Schema::hasColumn('tallas', 'sis_tal') ? ($size->sis_tal ?: null) : null,
                'group' => Schema::hasColumn('tallas', 'gru_tal') ? ($size->gru_tal ?: null) : null,
                'active' => (bool) $size->est_tal,
            ])
            ->values()
            ->all();
    }

    public function locationOptions(): array
    {
        return [
            ['label' => 'DEPÓSITO', 'value' => 'DEPOSITO'],
            ['label' => 'TIENDA', 'value' => 'TIENDA'],
        ];
    }

    public function inventoryLocationOptions(): array
    {
        return Inventario::query()
            ->whereNotNull('ubi_inv')
            ->where('ubi_inv', '!=', '')
            ->select('ubi_inv')
            ->distinct()
            ->orderBy('ubi_inv')
            ->pluck('ubi_inv')
            ->map(fn ($location) => [
                'label' => (string) $location,
                'value' => (string) $location,
            ])
            ->values()
            ->all();
    }

    public function variantOptions(): array
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

    public function inventoryVariantOptions($record = null): array
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
}
