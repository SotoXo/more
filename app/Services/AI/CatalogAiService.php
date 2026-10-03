<?php

namespace App\Services\AI;

use App\Models\AiRun;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\ImagenPrenda;
use App\Models\Marca;
use App\Models\Patron;
use App\Models\PrendaVariante;
use App\Models\Talla;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class CatalogAiService
{
    public function taxonomies(): array
    {
        return [
            'categories' => Categoria::where('est_cat', true)->get()->map(fn ($v) => ['id' => $v->id_categoria, 'name' => $v->nom_cat])->all(),
            'brands' => Marca::where('est_mar', true)->get()->map(fn ($v) => ['id' => $v->id_marca, 'name' => $v->nom_mar])->all(),
            'colors' => Color::where('est_col', true)->get()->map(fn ($v) => ['id' => $v->id_color, 'name' => $v->nom_col, 'hex' => $v->hex_dom_col ?: $v->hex_col])->all(),
            'sizes' => Talla::where('est_tal', true)->get()->map(fn ($v) => ['id' => $v->id_talla, 'name' => $v->nom_tal])->all(),
            'patterns' => Patron::where('est_pat', true)->get()->map(fn ($v) => ['id' => $v->id_patron, 'name' => $v->nom_pat])->all(),
        ];
    }

    public function candidates(array $query): array
    {
        $base = PrendaVariante::with(['prenda.categoria', 'prenda.marca', 'color', 'talla'])->whereHas('prenda');
        // Exact identifiers are searched over the complete catalog before the bounded soft-candidate set.
        $exact = (clone $base)->where(function ($builder) use ($query) {
            $builder->whereRaw('1 = 0');
            foreach (['sku' => 'sku_prv', 'gtin' => 'cob_prv'] as $key => $column) {
                if (filled($query[$key] ?? null)) {
                    $value = mb_strtoupper(trim($query[$key]));
                    $builder->orWhereRaw('UPPER('.$column.') = ?', [$value]);
                    // A typed/scanned code can also be an existing internal SKU.
                    $builder->orWhereRaw('UPPER(sku_prv) = ?', [$value]);
                    $builder->orWhereRaw('UPPER(cob_prv) = ?', [$value]);
                    if (ctype_digit($value) && in_array(strlen($value), [8, 12, 13, 14], true)) {
                        $normalized = str_pad(ltrim($value, '0'), 14, '0', STR_PAD_LEFT);
                        $variants = array_unique([$normalized, substr($normalized, -13), substr($normalized, -12), substr($normalized, -8)]);
                        foreach ($variants as $code) {
                            if (str_pad($code, 14, '0', STR_PAD_LEFT) === $normalized) $builder->orWhere('cob_prv', $code);
                        }
                    }
                }
            }
            if (Schema::hasColumn('prendas', 'modelo_pre') && filled($query['model_code'] ?? null)) {
                $builder->orWhereHas('prenda', fn ($q) => $q->whereRaw('UPPER(modelo_pre) = ?', [mb_strtoupper(trim($query['model_code']))]));
            }
        })->limit(100)->get();
        $soft = (clone $base)->when(filled($query['category_id'] ?? null), fn ($q) => $q->whereHas('prenda', fn ($p) => $p->where('cod_categorias_pre', $query['category_id'])))
            ->limit(config('mati_ai.candidate_limit', 100))->get();
        return $exact->concat($soft)->unique('id_prenda_variante')->map(function ($v) {
            $embedding = Schema::hasTable('ai_runs') ? AiRun::where('prenda_id', $v->cod_prendas_prv)->whereNotNull('embedding')->latest()->value('embedding') : null;
            if (is_string($embedding)) {
                $embedding = json_decode($embedding, true);
            }
            return [
                'candidate_id' => (string) $v->id_prenda_variante,
                'prenda_id' => $v->cod_prendas_prv,
                'title' => $v->prenda->nom_pre, 'text' => mb_substr(trim($v->prenda->nom_pre.' '.$v->prenda->des_pre), 0, 4000),
                'sku' => $v->sku_prv, 'gtin' => $v->cob_prv, 'model_code' => $v->prenda->modelo_pre,
                'brand' => $v->prenda->marca?->nom_mar,
                'category_id' => $v->prenda->cod_categorias_pre,
                'color_id' => $v->cod_colores_prv,
                'embedding' => $embedding,
                'href' => '/superadmin/catalogo/prendas/'.$v->cod_prendas_prv,
            ];
        })->values()->all();
    }

    public function exactMatches(array $query, array $candidates): array
    {
        $matches = [];
        foreach ($candidates as $candidate) {
            foreach (['sku', 'gtin', 'model_code'] as $field) {
                $value = mb_strtoupper(trim((string) ($query[$field] ?? '')));
                $right = mb_strtoupper((string) ($candidate[$field] ?? ''));
                $sameGtin = $field !== 'model_code' && ctype_digit($value) && ctype_digit((string) ($candidate['gtin'] ?? ''))
                    && in_array(strlen($value), [8, 12, 13, 14], true)
                    && in_array(strlen((string) $candidate['gtin']), [8, 12, 13, 14], true)
                    && str_pad($value, 14, '0', STR_PAD_LEFT) === str_pad($candidate['gtin'], 14, '0', STR_PAD_LEFT);
                if ($value !== '' && ($value === $right || $sameGtin
                    || ($field !== 'model_code' && in_array($value, [mb_strtoupper((string) ($candidate['sku'] ?? '')), mb_strtoupper((string) ($candidate['gtin'] ?? ''))], true)))) {
                    $conflicts = [];
                    if (filled($query['brand'] ?? null) && filled($candidate['brand'] ?? null) && mb_strtoupper($query['brand']) !== mb_strtoupper($candidate['brand'])) $conflicts[] = 'BRAND_CONFLICT';
                    if ($field !== 'gtin' && filled($query['gtin'] ?? null) && filled($candidate['gtin'] ?? null)
                        && str_pad($query['gtin'], 14, '0', STR_PAD_LEFT) !== str_pad($candidate['gtin'], 14, '0', STR_PAD_LEFT)) $conflicts[] = 'GTIN_CONFLICT';
                    $matches[] = $this->displayMatch($candidate, ['score' => $conflicts ? 0.05 : ($field === 'model_code' ? 0.86 : 1), 'components' => [$field => 1], 'conflicts' => $conflicts, 'exact_identifier' => $field !== 'model_code']);
                    break;
                }
            }
        }
        return $matches;
    }

    public function displayMatch(array $candidate, array $rank): array
    {
        return [
            'id' => $candidate['candidate_id'], 'title' => $candidate['title'], 'sku' => $candidate['sku'],
            'href' => $candidate['href'], 'score' => $rank['score'] ?? 0,
            'components' => $rank['components'] ?? [], 'conflicts' => $rank['conflicts'] ?? [],
            'warnings' => $rank['warnings'] ?? [],
            'exact_identifier' => $rank['exact_identifier'] ?? false,
        ];
    }

    public function duplicates(string $hash): array
    {
        $duplicates = [];
        foreach (ImagenPrenda::with('prenda')->whereNotNull('rut_igp')->limit(200)->get() as $image) {
            $path = $image->rut_igp;
            // Only local existing catalog objects are inspected; never URLs.
            if (!$path || str_contains($path, '..') || !str_starts_with($path, 'productos/') || !Storage::disk('public')->exists($path)) {
                continue;
            }
            $stream = Storage::disk('public')->readStream($path);
            if (!is_resource($stream)) {
                continue;
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            if (hash_equals($hash, hash_final($context))) {
                $duplicates[] = ['image_id' => $image->id_imagen_prenda, 'prenda_id' => $image->cod_prendas_igp,
                    'title' => $image->prenda?->nom_pre, 'type' => 'exact_sha256',
                    'href' => '/superadmin/catalogo/prendas/'.$image->cod_prendas_igp];
            }
        }
        return $duplicates;
    }
}
