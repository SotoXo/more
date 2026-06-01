<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Color;
use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Role;
use App\Models\Temporada;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ResourceWriteService
{
    public function __construct(
        private readonly AdminFormattingService $formatting,
        private readonly ResourceDefinitionService $definitions,
    ) {
    }

    public function normalizePayload(array $definition, array $validated, $record = null): array
    {
        $key = $this->definitions->definitionKey($definition);

        return match ($key) {
            'seguridad.roles' => [
                'nom_rol' => $record instanceof Role && $record->isSystemRole()
                    ? $record->nom_rol
                    : $this->formatting->normalizeUpper($validated['nom_rol']),
                'des_rol' => $this->formatting->normalizeNullableText($validated['des_rol'] ?? null),
                'est_rol' => $record instanceof Role && $record->isSystemRole()
                    ? true
                    : (bool) ($validated['est_rol'] ?? false),
                ...($record ? [] : ['fec_rol' => Carbon::now()]),
            ],
            'seguridad.usuarios' => [
                'nom_usu' => $this->formatting->normalizeUpper($validated['nom_usu']),
                'app_usu' => $this->formatting->normalizeUpper($validated['app_usu']),
                'apm_usu' => $this->formatting->normalizeUpper($validated['apm_usu'] ?? null),
                'tel_usu' => $this->formatting->normalizeNullableText($validated['tel_usu'] ?? null),
                'email' => mb_strtolower(trim($validated['email']), 'UTF-8'),
                'cod_roles_usu' => (int) $validated['cod_roles_usu'],
                'est_usu' => (bool) ($validated['est_usu'] ?? false),
                ...($record ? [] : [
                    'password' => Hash::make($validated['password']),
                    'fec_usu' => Carbon::now(),
                ]),
            ],
            'catalogo.categorias' => [
                'nom_cat' => $this->formatting->normalizeUpper($validated['nom_cat']),
                'des_cat' => $this->formatting->normalizeNullableText($validated['des_cat'] ?? null),
                'cod_categoria_padre_cat' => filled($validated['cod_categoria_padre_cat'] ?? null) ? (int) $validated['cod_categoria_padre_cat'] : null,
                'tip_tal_cat' => Schema::hasColumn('categorias', 'tip_tal_cat')
                    ? $this->normalizeCategorySizeSystem($validated['tip_tal_cat'] ?? null, $validated['nom_cat'])
                    : null,
                'gru_tal_cat' => Schema::hasColumn('categorias', 'gru_tal_cat')
                    ? ($this->formatting->normalizeUpper($validated['gru_tal_cat'] ?? null) ?? $this->inferCategorySizeGroup($validated['nom_cat']))
                    : null,
                'est_cat' => (bool) ($validated['est_cat'] ?? false),
                ...($record ? [] : ['fec_cat' => Carbon::now()]),
            ],
            'catalogo.marcas' => [
                'nom_mar' => $this->formatting->normalizeUpper($validated['nom_mar']),
                'des_mar' => $this->formatting->normalizeNullableText($validated['des_mar'] ?? null),
                'est_mar' => (bool) ($validated['est_mar'] ?? false),
                ...($record ? [] : ['fec_mar' => Carbon::now()]),
            ],
            'catalogo.colores' => [
                'nom_col' => $this->formatting->normalizeUpper($validated['nom_col']),
                'fam_col' => Schema::hasColumn('colores', 'fam_col')
                    ? $this->formatting->normalizeUpper($validated['fam_col'] ?? $validated['nom_col'])
                    : null,
                'hex_col' => filled($validated['hex_col'] ?? null) ? mb_strtoupper(trim($validated['hex_col']), 'UTF-8') : null,
                'hex_dom_col' => Schema::hasColumn('colores', 'hex_dom_col')
                    ? $this->normalizeColorDominant($validated['hex_dom_col'] ?? null, $validated['fam_col'] ?? null, $validated['hex_col'] ?? null)
                    : null,
                'ord_col' => Schema::hasColumn('colores', 'ord_col')
                    ? (filled($validated['ord_col'] ?? null) ? (int) $validated['ord_col'] : $this->nextColorOrder())
                    : null,
                'est_col' => (bool) ($validated['est_col'] ?? false),
                ...($record ? [] : ['fec_col' => Carbon::now()]),
            ],
            'catalogo.tallas' => [
                'nom_tal' => $this->formatting->normalizeUpper($validated['nom_tal']),
                'seg_tal' => Schema::hasColumn('tallas', 'seg_tal')
                    ? ($this->formatting->normalizeUpper($validated['seg_tal'] ?? null) ?? 'ADULTO')
                    : null,
                'ord_tal' => $this->calculateSizeOrder(
                    (string) $validated['nom_tal'],
                    (string) ($validated['sis_tal'] ?? ''),
                    (string) ($validated['seg_tal'] ?? 'ADULTO'),
                ),
                'sis_tal' => Schema::hasColumn('tallas', 'sis_tal')
                    ? $this->normalizeSizeSystem($validated['sis_tal'] ?? null, (string) $validated['nom_tal'])
                    : null,
                'gru_tal' => Schema::hasColumn('tallas', 'gru_tal')
                    ? ($this->formatting->normalizeUpper($validated['gru_tal'] ?? null) ?? $this->sizeMetadata((string) $validated['nom_tal'])['gru_tal'] ?? null)
                    : null,
                'est_tal' => (bool) ($validated['est_tal'] ?? false),
                ...($record ? [] : ['fec_tal' => Carbon::now()]),
            ],
            'catalogo.temporadas' => [
                'nom_tem' => $this->formatting->normalizeUpper($validated['nom_tem']),
                'cod_tem' => Schema::hasColumn('temporadas', 'cod_tem')
                    ? ($this->formatting->normalizeUpper($validated['cod_tem'] ?? null) ?? $this->buildSeasonCode((string) $validated['nom_tem']))
                    : null,
                'des_tem' => $this->formatting->normalizeNullableText($validated['des_tem'] ?? null),
                'fec_ini_tem' => Schema::hasColumn('temporadas', 'fec_ini_tem')
                    ? Carbon::parse($validated['fec_ini_tem'])->toDateString()
                    : null,
                'fec_fin_tem' => Schema::hasColumn('temporadas', 'fec_fin_tem')
                    ? Carbon::parse($validated['fec_fin_tem'])->toDateString()
                    : null,
                'ord_tem' => Schema::hasColumn('temporadas', 'ord_tem')
                    ? (filled($validated['ord_tem'] ?? null) ? (int) $validated['ord_tem'] : $this->nextSeasonOrder())
                    : null,
                'est_tem' => (bool) ($validated['est_tem'] ?? false),
                ...($record ? [] : ['fec_tem' => Carbon::now()]),
            ],
            'catalogo.prendas' => [
                'nom_pre' => $this->formatting->normalizeUpper($validated['nom_pre']),
                'des_pre' => $this->formatting->normalizeNullableText($validated['des_pre'] ?? null),
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
                'fue_igp' => $this->formatting->normalizeNullableText($validated['fue_igp'] ?? null),
                ...($record ? [] : ['fec_igp' => Carbon::now()]),
            ],
            'inventario.stock' => [
                'cod_prenda_variantes_inv' => (int) $validated['cod_prenda_variantes_inv'],
                'cdi_inv' => (int) $validated['cdi_inv'],
                'cre_inv' => $record ? (int) $record->cre_inv : (int) $validated['cre_inv'],
                'ubi_inv' => $this->formatting->normalizeUpper($validated['ubi_inv'] ?? null),
            ],
            default => [],
        };
    }

    public function createRecord(
        Request $request,
        array $definition,
        array $validated,
        callable $createProductRecord,
        callable $createImageRecord,
    ) {
        $key = $this->definitions->definitionKey($definition);
        $modelClass = $definition['modelClass'];
        $payload = $this->normalizePayload($definition, $validated);

        return match ($key) {
            'catalogo.prendas' => $createProductRecord($request, $payload, $validated),
            'catalogo.imagenes' => $createImageRecord($validated, $payload),
            default => $modelClass::query()->create($payload),
        };
    }

    public function updateRecord(
        Request $request,
        array $definition,
        array $validated,
        $record,
        callable $updateProductRecord,
        callable $updateImageRecord,
    ): void {
        $key = $this->definitions->definitionKey($definition);
        $payload = $this->normalizePayload($definition, $validated, $record);

        match ($key) {
            'catalogo.prendas' => $updateProductRecord($request, $record, $payload, $validated),
            'catalogo.imagenes' => $updateImageRecord($record, $validated, $payload),
            default => $record->update($payload),
        };
    }

    public function deleteRecord(array $definition, $record, callable $deleteImageRecord): void
    {
        if ($this->definitions->definitionKey($definition) === 'catalogo.imagenes') {
            $deleteImageRecord($record);
            return;
        }

        $record->delete();
    }

    public function deleteBlockedReason(array $definition, $record, $authUser = null): ?string
    {
        return match ($this->definitions->definitionKey($definition)) {
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

    private function inferCategorySizeType(string $name): string
    {
        $normalized = mb_strtoupper(Str::ascii(trim($name)), 'UTF-8');

        if (str_contains($normalized, 'PANTAL')) {
            return 'NUMERICA';
        }

        if (str_contains($normalized, 'ACCESOR')) {
            return 'UNICA';
        }

        return 'ALFA';
    }

    private function inferCategorySizeGroup(string $name): string
    {
        $normalized = mb_strtoupper(Str::ascii(trim($name)), 'UTF-8');

        if (str_contains($normalized, 'PANTAL') || str_contains($normalized, 'SHORT') || str_contains($normalized, 'JEAN')) {
            return 'INFERIOR';
        }

        if (str_contains($normalized, 'ACCESOR')) {
            return 'GENERAL';
        }

        return 'SUPERIOR';
    }

    private function normalizeCategorySizeSystem(mixed $value, string $name): ?string
    {
        $system = $this->formatting->normalizeUpper($value ?? null) ?? $this->inferCategorySizeType($name);

        return $system === 'UNITALLA' ? 'UNICA' : $system;
    }

    public function sizeMetadataFor(string $name): array
    {
        return $this->sizeMetadata($name);
    }

    public function colorMetadataFor(string $hex, ?string $family = null, mixed $record = null): array
    {
        $normalizedHex = $this->normalizeHex($hex);
        $exact = $normalizedHex ?? mb_strtoupper(trim($hex), 'UTF-8');
        $analysisHex = $normalizedHex ?? '#000000';
        $resolvedFamily = $family
            ? mb_strtoupper(trim($family), 'UTF-8')
            : $this->closestColorFamily($analysisHex);
        $dominant = $this->colorFamilyPalette()[$resolvedFamily] ?? $analysisHex;

        return [
            'nom_col' => $this->uniqueColorName($this->suggestColorName($analysisHex, $resolvedFamily), $record),
            'fam_col' => $resolvedFamily,
            'hex_col' => $exact,
            'hex_dom_col' => $dominant,
            'ord_col' => $this->colorOrderFor($resolvedFamily, $record),
        ];
    }

    public function reusableColorFor(string $hex, ?string $family = null): ?Color
    {
        $normalizedHex = $this->normalizeHex($hex);

        if (!$normalizedHex) {
            return null;
        }

        $exact = Color::query()
            ->whereRaw('UPPER(hex_col) = ?', [$normalizedHex])
            ->first();

        if ($exact) {
            return $exact;
        }

        $resolvedFamily = $family ? mb_strtoupper(trim($family), 'UTF-8') : $this->closestColorFamily($normalizedHex);
        $candidate = Color::query()
            ->where('fam_col', $resolvedFamily)
            ->whereNotNull('hex_col')
            ->get()
            ->map(fn (Color $color) => [
                'record' => $color,
                'distance' => $this->colorDistance($normalizedHex, (string) $color->hex_col),
            ])
            ->sortBy('distance')
            ->first();

        return ($candidate && $candidate['distance'] <= 24) ? $candidate['record'] : null;
    }

    public function calculateSizeOrder(string $name, ?string $system = null, ?string $segment = null): int
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

    private function normalizeSizeSystem(mixed $value, string $name): ?string
    {
        $system = $this->formatting->normalizeUpper($value ?? null) ?? $this->sizeMetadata($name)['sis_tal'] ?? null;

        return $system === 'UNITALLA' ? 'UNICA' : $system;
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

    private function normalizeColorDominant(?string $dominant, ?string $family, ?string $exact): ?string
    {
        $familyHex = $this->colorFamilyPalette()[mb_strtoupper(trim((string) $family), 'UTF-8')] ?? null;
        $value = $dominant ?: $familyHex ?: $exact;

        return filled($value) ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
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

    private function normalizeHex(?string $value): ?string
    {
        $hex = mb_strtoupper(ltrim(trim((string) $value), '#'), 'UTF-8');

        return preg_match('/^[A-F0-9]{6}$/', $hex) ? '#' . $hex : null;
    }

    private function closestColorFamily(string $hex): string
    {
        $hsl = $this->hexHsl($hex);

        if ($hsl['s'] >= 20) {
            return match (true) {
                $hsl['h'] >= 15 && $hsl['h'] < 35 && $hsl['l'] < 38 => 'MARRON',
                $hsl['h'] >= 15 && $hsl['h'] < 35 && $hsl['s'] < 65 => 'CAMEL',
                $hsl['h'] >= 15 && $hsl['h'] < 35 => 'NARANJA',
                $hsl['h'] >= 35 && $hsl['h'] < 65 && $hsl['l'] >= 75 => 'BEIGE',
                $hsl['h'] >= 35 && $hsl['h'] < 75 => 'AMARILLO',
                $hsl['h'] >= 75 && $hsl['h'] < 165 => 'VERDE',
                $hsl['h'] >= 165 && $hsl['h'] < 195 => 'CELESTE',
                $hsl['h'] >= 195 && $hsl['h'] < 255 => 'AZUL',
                $hsl['h'] >= 255 && $hsl['h'] < 310 => 'MORADO',
                $hsl['h'] >= 310 && $hsl['h'] < 345 => 'ROSA',
                default => 'ROJO',
            };
        }

        $best = collect($this->colorFamilyPalette())
            ->map(fn (string $familyHex, string $family) => [
                'family' => $family,
                'distance' => $this->colorDistance($hex, $familyHex),
            ])
            ->sortBy('distance')
            ->first();

        return $best['family'] ?? 'AZUL';
    }

    private function colorDistance(string $left, string $right): float
    {
        $a = $this->hexRgb($left);
        $b = $this->hexRgb($right);

        return sqrt((($a['r'] - $b['r']) ** 2) + (($a['g'] - $b['g']) ** 2) + (($a['b'] - $b['b']) ** 2));
    }

    private function hexRgb(string $hex): array
    {
        $normalized = $this->normalizeHex($hex) ?? '#000000';

        return [
            'r' => hexdec(substr($normalized, 1, 2)),
            'g' => hexdec(substr($normalized, 3, 2)),
            'b' => hexdec(substr($normalized, 5, 2)),
        ];
    }

    private function suggestColorName(string $hex, string $family): string
    {
        $knownTone = $this->closestKnownTone($hex, $family);

        if ($knownTone) {
            return $knownTone;
        }

        $hsl = $this->hexHsl($hex);
        $modifier = match (true) {
            $hsl['l'] <= 24 => 'OSCURO',
            $hsl['l'] >= 78 => 'CLARO',
            $hsl['s'] <= 18 => 'SUAVE',
            $hsl['s'] >= 70 && $hsl['l'] >= 35 && $hsl['l'] <= 65 => 'INTENSO',
            default => '',
        };

        if (in_array($family, ['NEGRO', 'BLANCO', 'GRIS', 'BEIGE', 'CAMEL', 'MARRON', 'DORADO', 'PLATEADO'], true) && $modifier !== 'OSCURO' && $modifier !== 'CLARO') {
            return $family;
        }

        return trim($family . ' ' . $modifier);
    }

    private function hexHsl(string $hex): array
    {
        $rgb = $this->hexRgb($hex);
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;
        $delta = $max - $min;
        $saturation = $delta === 0.0 ? 0 : $delta / (1 - abs((2 * $lightness) - 1));

        $hue = 0;

        if ($delta !== 0.0) {
            $hue = match ($max) {
                $r => 60 * fmod((($g - $b) / $delta), 6),
                $g => 60 * ((($b - $r) / $delta) + 2),
                default => 60 * ((($r - $g) / $delta) + 4),
            };
        }

        if ($hue < 0) {
            $hue += 360;
        }

        return [
            'h' => (int) round($hue),
            's' => (int) round($saturation * 100),
            'l' => (int) round($lightness * 100),
        ];
    }

    private function closestKnownTone(string $hex, string $family): ?string
    {
        $tones = $this->knownColorTones();
        $candidates = $tones[$family] ?? [];

        if ($family === 'BORGONA') {
            $candidates = array_merge($candidates, $tones['ROJO'] ?? []);
        }

        if ($candidates === []) {
            return null;
        }

        $best = collect($candidates)
            ->map(fn (string $toneHex, string $name) => [
                'name' => $name,
                'distance' => $this->colorDistance($hex, $toneHex),
            ])
            ->sortBy('distance')
            ->first();

        return ($best && $best['distance'] <= 95) ? $best['name'] : null;
    }

    private function knownColorTones(): array
    {
        return [
            'VERDE' => [
                'VERDE BOSQUE OSCURO' => '#0D2608',
                'VERDE BOTELLA' => '#0B3D2E',
                'VERDE PINO OSCURO' => '#174A2A',
                'VERDE OLIVA' => '#66704A',
                'VERDE ESMERALDA' => '#047857',
                'VERDE MENTA' => '#98D8B7',
                'VERDE SALVIA' => '#9CAF88',
                'VERDE LIMON' => '#B7D93D',
            ],
            'AZUL' => [
                'AZUL MARINO' => '#1F2A44',
                'AZUL PETROLEO' => '#0F4C5C',
                'AZUL NOCHE' => '#0B1F3A',
                'AZUL REY' => '#1D4ED8',
                'AZUL CIELO' => '#7DB7E8',
                'CELESTE' => '#6F9FD8',
                'TURQUESA' => '#14B8A6',
            ],
            'CELESTE' => [
                'CELESTE' => '#6F9FD8',
                'AZUL CIELO' => '#7DB7E8',
                'TURQUESA' => '#14B8A6',
            ],
            'ROJO' => [
                'ROJO VINO' => '#6F1D2C',
                'ROJO CEREZA' => '#B91C1C',
                'ROJO RUBI' => '#9B111E',
                'ROJO LADRILLO' => '#8B3A2E',
                'ROJO INTENSO' => '#DC2626',
                'BORGONA' => '#8A2332',
            ],
            'BORGONA' => [
                'BORGONA' => '#8A2332',
                'ROJO VINO' => '#6F1D2C',
            ],
            'ROSA' => [
                'ROSA PALO' => '#D8A7B1',
                'ROSA PASTEL' => '#F4B6C2',
                'ROSA FUCSIA' => '#C026D3',
                'ROSA EMPOLVADO' => '#C894A2',
            ],
            'BEIGE' => [
                'CREMA' => '#F2E8D8',
                'BEIGE ARENA' => '#D8C3A5',
                'BEIGE CLARO' => '#E8D8C3',
                'MARFIL' => '#F7F0DF',
            ],
            'CAMEL' => [
                'CAMEL' => '#B88956',
                'BEIGE ARENA' => '#D8C3A5',
            ],
            'MARRON' => [
                'MARRON CHOCOLATE' => '#4E2F1E',
                'MARRON CAFE' => '#6F4E37',
                'MARRON TABACO' => '#8B5A2B',
            ],
            'GRIS' => [
                'GRIS PERLA' => '#D1D5DB',
                'GRIS CEMENTO' => '#9CA3AF',
                'GRIS ACERO' => '#64748B',
                'GRIS OSCURO' => '#374151',
            ],
            'MORADO' => [
                'LILA' => '#C8A2C8',
                'LAVANDA' => '#BFA7E6',
                'MORADO UVA' => '#6D28D9',
                'MORADO OSCURO' => '#3B0764',
            ],
            'NEGRO' => [
                'NEGRO' => '#111111',
                'NEGRO CARBON' => '#1F1F1F',
            ],
            'BLANCO' => [
                'BLANCO' => '#F7F6F2',
                'BLANCO HUESO' => '#EDE6D6',
                'MARFIL' => '#F7F0DF',
            ],
            'AMARILLO' => [
                'AMARILLO' => '#EAB308',
                'AMARILLO MOSTAZA' => '#C99700',
            ],
            'NARANJA' => [
                'NARANJA' => '#EA580C',
                'NARANJA TERRACOTA' => '#C65A2E',
            ],
            'DORADO' => [
                'DORADO' => '#C8A96A',
            ],
            'PLATEADO' => [
                'PLATEADO' => '#C0C0C0',
            ],
        ];
    }

    private function uniqueColorName(string $name, mixed $record = null): string
    {
        return mb_strtoupper(trim($name), 'UTF-8') ?: 'COLOR';
    }

    private function colorOrderFor(string $family, mixed $record = null): int
    {
        $base = $this->colorOrderBase()[$family] ?? 1600;

        if ($record && $record->fam_col === $family && $record->ord_col !== null) {
            return (int) $record->ord_col;
        }

        $max = (int) (Color::query()
            ->where('fam_col', $family)
            ->max('ord_col') ?? 0);

        return $max >= $base ? $max + 10 : $base;
    }

    private function colorOrderBase(): array
    {
        return [
            'NEGRO' => 100,
            'BLANCO' => 200,
            'GRIS' => 300,
            'BEIGE' => 400,
            'CAMEL' => 500,
            'MARRON' => 600,
            'AZUL' => 700,
            'CELESTE' => 750,
            'VERDE' => 800,
            'ROJO' => 900,
            'BORGONA' => 950,
            'ROSA' => 1000,
            'AMARILLO' => 1100,
            'NARANJA' => 1200,
            'MORADO' => 1300,
            'DORADO' => 1400,
            'PLATEADO' => 1500,
        ];
    }
}
