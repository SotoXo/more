<?php

namespace App\Services;

use App\Models\Prenda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ComplementaryProductService
{
    private const CANDIDATE_LIMIT = 80;
    private const NEUTRAL_FAMILIES = ['NEGRO', 'BLANCO', 'GRIS', 'BEIGE', 'CAMEL', 'MARRON'];

    /**
     * Returns product models plus internal score/reasons for an explainable ranking.
     * Only mapped products should be sent to the public frontend.
     */
    public function recommend(Prenda $source, int $limit = 4, array $excludeIds = []): Collection
    {
        $source->loadMissing(['categoria', 'temporada', 'variantes.inventario', 'variantes.color', 'variantes.colores']);
        if (! $source->est_pre || ! $source->categoria?->est_cat) {
            return collect();
        }

        $query = Prenda::query()
            ->join('categoria_complementarias as complement', function ($join) use ($source) {
                $join->on('complement.cod_categoria_destino_cc', '=', 'prendas.cod_categorias_pre')
                    ->where('complement.cod_categoria_origen_cc', $source->cod_categorias_pre)
                    ->where('complement.est_cc', true);
            })
            ->where('prendas.est_pre', true)
            ->where('prendas.id_prenda', '!=', $source->id_prenda)
            ->whereNotIn('prendas.id_prenda', $excludeIds)
            ->whereHas('categoria', fn (Builder $category) => $category->where('est_cat', true))
            ->whereHas('variantes', fn (Builder $variant) => $variant
                ->where('est_prv', true)
                ->whereHas('inventario', fn (Builder $inventory) => $inventory->where('cdi_inv', '>', 0)));

        if (in_array($source->gen_pre, ['hombre', 'mujer'], true)) {
            $query->whereIn('prendas.gen_pre', [$source->gen_pre, 'unisex']);
        }

        $candidates = ProductMapper::withRelations($query)
            ->with('variantes.colores')
            ->select('prendas.*', 'complement.pri_cc as complement_priority')
            ->orderBy('complement.pri_cc')
            ->orderByDesc('prendas.fec_pre')
            ->orderByDesc('prendas.id_prenda')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $sourceFamilies = $this->families($source);
        $ranked = $candidates
            ->map(function (Prenda $candidate) use ($source, $sourceFamilies) {
                $priority = (int) $candidate->complement_priority;
                // 30 points between priorities; all secondary signals together add at most 26.
                $score = 200 - 30 * ($priority - 1);
                $reasons = ["Categoría complementaria, prioridad {$priority}"];

                if ($source->cod_temporadas_pre && $source->cod_temporadas_pre === $candidate->cod_temporadas_pre) {
                    $score += 12;
                    $reasons[] = 'Misma temporada';
                } elseif ($candidate->temporada && (
                    strtolower((string) $candidate->temporada->cod_tem) === 'per'
                    || str_contains(strtolower((string) $candidate->temporada->nom_tem), 'permanente')
                )) {
                    $score += 5;
                    $reasons[] = 'Temporada permanente';
                }

                $candidateFamilies = $this->families($candidate, true);
                $neutral = $candidateFamilies->intersect(self::NEUTRAL_FAMILIES)->isNotEmpty();
                $shared = $sourceFamilies->intersect($candidateFamilies)->isNotEmpty();
                if ($neutral) {
                    $score += 8;
                    $reasons[] = 'Color neutro';
                } elseif ($shared) {
                    $score += $sourceFamilies->count() > 2 ? 4 : 6;
                    $reasons[] = 'Familia de color compatible';
                }

                $stock = $candidate->variantes->sum(fn ($variant) => $variant->est_prv
                    ? max(0, (int) ($variant->inventario?->cdi_inv ?? 0)) : 0);
                if ($stock >= 3) {
                    $score += 4;
                    $reasons[] = 'Buena disponibilidad';
                }
                if ($candidate->fec_pre?->isAfter(now()->subDays(45))) {
                    $score += 2;
                    $reasons[] = 'Prenda reciente';
                }

                return ['product' => $candidate, 'score' => $score, 'priority' => $priority, 'reasons' => $reasons];
            })
            ->sort(fn (array $left, array $right) => ($right['score'] <=> $left['score'])
                ?: ($left['priority'] <=> $right['priority'])
                ?: ($right['product']->id_prenda <=> $left['product']->id_prenda))
            ->values();

        $chosen = collect();
        $categoryCounts = [];
        foreach ($ranked as $item) {
            $categoryId = $item['product']->cod_categorias_pre;
            if (($categoryCounts[$categoryId] ?? 0) >= 2) continue;
            $chosen->push($item);
            $categoryCounts[$categoryId] = ($categoryCounts[$categoryId] ?? 0) + 1;
            if ($chosen->count() >= $limit) return $chosen;
        }

        // Fill remaining slots when there are not enough different categories.
        foreach ($ranked as $item) {
            if ($chosen->contains(fn (array $selected) => $selected['product']->id_prenda === $item['product']->id_prenda)) continue;
            $chosen->push($item);
            if ($chosen->count() >= $limit) break;
        }

        return $chosen;
    }

    private function families(Prenda $product, bool $availableOnly = false): Collection
    {
        return $product->variantes
            ->filter(fn ($variant) => $variant->est_prv
                && (! $availableOnly || (int) ($variant->inventario?->cdi_inv ?? 0) > 0))
            ->flatMap(function ($variant) {
                $palette = $variant->colores->isNotEmpty()
                    ? $variant->colores
                    : collect([$variant->color])->filter();

                return $palette->pluck('fam_col');
            })
            ->filter()
            ->map(fn ($family) => Str::upper(Str::ascii((string) $family)))
            ->unique()
            ->values();
    }
}
