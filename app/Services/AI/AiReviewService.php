<?php

namespace App\Services\AI;

use App\Models\AiHumanReview;
use App\Models\AiRun;
use App\Models\AiSuggestion;
use App\Models\ImagenPrenda;
use App\Models\Prenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AiReviewService
{
    public function persist(string $operation, array $result, ?int $actor, array $input = []): AiRun
    {
        $embedding = data_get($result, 'data.embedding');
        unset($result['data']['embedding']);
        $model = $result['model'];
        $identity = ['model_id' => $model['model_id'], 'model_revision' => $model['model_revision'], 'pipeline_version' => $model['pipeline_version']];
        DB::table('ai_model_versions')->insertOrIgnore([...$identity, 'created_at' => now(), 'updated_at' => now()]);
        $versionId = DB::table('ai_model_versions')->where($identity)->value('id');
        return AiRun::create([
            'id' => $result['request_id'], 'request_id' => $result['request_id'], 'created_by' => $actor,
            'operation' => $operation, 'status' => $result['status'], 'model_version_id' => $versionId,
            'input_hash' => $input['sha256'] ?? null, 'input_metadata' => $input,
            'result' => $result, 'embedding' => $embedding,
            'error_code' => data_get($result, 'error.code'),
            'duration_ms' => max(0, (int) data_get($result, 'timing_ms.total', 0)),
        ]);
    }

    public function suggestions(AiRun $run, array $taxonomies): array
    {
        $data = $run->result['data'];
        $suggestions = [];
        $add = function (string $field, mixed $value, ?string $label, string $source, mixed $score = null, array $evidence = []) use ($run, &$suggestions) {
            if ($value === null || $value === '' || !$label) {
                return null;
            }
            $suggestion = AiSuggestion::create([
                'run_id' => $run->id, 'field' => $field, 'value' => $value,
                'label' => mb_substr($label, 0, 255), 'source' => $source,
                'confidence_score' => is_numeric($score) ? max(-1, min(1, (float) $score)) : null, 'evidence' => $evidence,
            ]);
            $item = $suggestion->only(['id', 'field', 'value', 'label', 'source', 'confidence_score']);
            $suggestions[] = $item;
            return $item;
        };
        $map = function ($kind, $id) use ($taxonomies) {
            return collect($taxonomies[$kind] ?? [])->first(fn ($entry) => (string) $entry['id'] === (string) $id);
        };
        if ($this->visualSuggestionApplicable($run, 'category')) {
            foreach (data_get($data, 'classification.top_k', []) as $item) {
                if ($entry = $map('categories', $item['category_id'] ?? null)) {
                    $add('category', $entry['id'], $entry['name'], 'vision', $item['similarity_score'] ?? null);
                }
            }
        }
        // Geometric foreground confidence does not verify the target garment.
        if (is_array($data['colorimetry'] ?? null)
            && data_get($data, 'colorimetry.commercial_color_status') === 'completed'
            && data_get($data, 'colorimetry.evidence.target_garment_identity_verified') === true) {
            $colorimetry = $data['colorimetry'];
            $seenColors = [];
            foreach ($colorimetry['palette'] ?? [] as $item) {
                $id = $item['catalog_color_id'] ?? null;
                if ($id === null || isset($seenColors[(string) $id])
                    || data_get($item, 'evidence.catalog_match_accepted') !== true
                    || !($entry = $map('colors', $id))) continue;
                $seenColors[(string) $id] = true;
                $role = data_get($colorimetry, 'primary_color.hex') === $item['hex'] ? 'primary'
                    : (collect($colorimetry['accent_colors'] ?? [])->contains('hex', $item['hex']) ? 'accent' : 'secondary');
                $add('color', $entry['id'], $entry['name'], 'colorimetry', $item['confidence_score'] ?? null, [
                    ...$item, 'color_role' => $role,
                    'foreground_confidence' => $colorimetry['foreground_confidence'],
                    'colorimetry_version' => $colorimetry['version'],
                    'colorimetry_warnings' => $colorimetry['warnings'],
                    'human_review_required' => true,
                ]);
            }
        }
        // Legacy color observations carry no proof of target identity and stay informational.
        foreach (['brand' => 'brands', 'size' => 'sizes'] as $field => $kind) {
            $item = $data[$field.'_candidate'] ?? [];
            if ($entry = $map($kind, $item['id'] ?? null)) {
                $add($field, $entry['id'], $entry['name'], 'label_ocr', $item['confidence_score'] ?? null);
            }
        }
        if ($this->visualSuggestionApplicable($run, 'pattern')
            && ($entry = $map('patterns', data_get($data, 'attributes.pattern.id')))) {
            $add('pattern', $entry['id'], $entry['name'], 'vision', data_get($data, 'attributes.pattern.similarity_score'));
        }
        $model = $data['model_code_candidate'] ?? null;
        if (is_string($model)) {
            $add('model_code', $model, $model, 'label_ocr');
        }
        $codes = [...($data['codes'] ?? []), ...array_map(fn ($code) => is_array($code) ? $code : ['normalized_value' => $code], $data['gtin_candidates'] ?? [])];
        foreach ($codes as $code) {
            $value = $code['normalized_value'] ?? '';
            if (!($code['contains_url'] ?? false) && preg_match('/^[A-Za-z0-9-]{1,100}$/', $value) && ($code['checksum_valid'] ?? null) !== false) {
                $add('gtin', $value, $value, $run->operation === 'label' ? 'label_ocr' : 'barcode');
            }
        }
        foreach ($data['candidates'] ?? [] as $index => $candidate) {
            $candidateSuggestions = [];
            foreach (['name' => 'title', 'description' => 'description', 'model_code' => 'model_code', 'gtin' => 'gtin'] as $field => $key) {
                if (is_string($candidate[$key] ?? null)) {
                    $item = $add($field, $candidate[$key], $candidate[$key], 'external', $candidate['final_score'] ?? null, ['candidate_index' => $index, 'source' => $candidate['source_id'] ?? $candidate['source'] ?? null]);
                    if ($item) $candidateSuggestions[] = $item;
                }
            }
            $brand = collect($taxonomies['brands'])->first(fn ($v) => mb_strtoupper($v['name']) === mb_strtoupper((string) ($candidate['brand'] ?? '')));
            if ($brand && ($item = $add('brand', $brand['id'], $brand['name'], 'external', $candidate['final_score'] ?? null, ['candidate_index' => $index]))) {
                $candidateSuggestions[] = $item;
            }
            $data['candidates'][$index]['suggestions'] = $candidateSuggestions;
        }
        return ['suggestions' => $suggestions, 'data' => $data];
    }

    private function visualSuggestionApplicable(AiRun $run, string $field): bool
    {
        if (!in_array($run->status, ['completed', 'partial'], true)) return false;
        if ($field === 'category') {
            return data_get($run->result, 'data.classification.abstained') === false;
        }
        $pattern = data_get($run->result, 'data.attributes.pattern');
        return is_array($pattern) && ($pattern['abstained'] ?? false) !== true
            && data_get($run->result, 'data.attributes.abstained') !== true;
    }

    public function attachSafelyToProduct(Prenda $product, array $validated, ?int $actor): void
    {
        if (empty($validated['ai_run_ids']) || !$actor) return;
        try {
            // Nested transaction uses a savepoint, restoring PostgreSQL after any audit SQL failure.
            DB::transaction(fn () => $this->attachToProduct($product, $validated, $actor));
        } catch (\Throwable) {
            Log::warning('mati_ai.review_attachment_failed', ['prenda_id' => $product->id_prenda]);
        }
    }

    public function indexSavedEmbeddingSafely(AiRun $run, ?int $imageId = null): void
    {
        if (!$run->prenda_id || !$run->embedding || !is_string($run->input_hash)
            || !preg_match('/^[a-f0-9]{64}$/i', $run->input_hash)) return;
        try {
            // A vector failure must not roll back the commercial save or its review audit.
            DB::transaction(function () use ($run, $imageId) {
                $store = app(CatalogEmbeddingStore::class);
                if (!$store->available()) return;
                $images = ImagenPrenda::where('cod_prendas_igp', $run->prenda_id)
                    ->when($imageId, fn ($query) => $query->whereKey($imageId))
                    ->orderBy('pri_igp', 'desc')->orderBy('id_imagen_prenda')->get();
                foreach ($images as $image) {
                    try {
                        $bytes = app(CatalogImageReader::class)->read($image);
                    } catch (\Throwable) {
                        continue;
                    }
                    // Link only the exact analyzed bytes which actually survived the save.
                    if (!hash_equals(strtolower($run->input_hash), hash('sha256', $bytes))) continue;
                    $store->storeRun($run, (int) $image->id_imagen_prenda, ['saved_image_hash_verified' => true]);
                    return;
                }
            });
        } catch (\Throwable) {
            Log::warning('mati_ai.saved_embedding_failed', ['run_id' => $run->id, 'prenda_id' => $run->prenda_id]);
        }
    }

    public function recordFeedback(AiSuggestion $suggestion, array $data, int $actor): void
    {
        DB::transaction(function () use ($suggestion, $data, $actor) {
            // Product attachment takes the same lock, so feedback has a defined order with saving.
            $run = AiRun::whereKey($suggestion->run_id)->where('created_by', $actor)->lockForUpdate()->firstOrFail();
            if ($suggestion->source === 'vision' && in_array($suggestion->field, ['category', 'pattern'], true)
                && $data['action'] !== 'rejected' && !$this->visualSuggestionApplicable($run, $suggestion->field)) {
                throw ValidationException::withMessages([
                    'suggestion_id' => 'Esta coincidencia visual es informativa. Selecciona el valor manualmente.',
                ]);
            }
            $review = AiHumanReview::create([
                'suggestion_id' => $suggestion->id, 'reviewer_id' => $actor, 'human_action' => $data['action'],
                'prediction' => $suggestion->value, 'confirmed_value' => $data['confirmed_value'],
                'variant_index' => $data['variant_index'] ?? null, 'stage' => 'form',
            ]);
            if ($run->prenda_id && ($product = Prenda::find($run->prenda_id))) {
                $this->recordSavedDecision($run, $product, $suggestion, $review);
            }
        });
    }

    public function attachToProduct(Prenda $product, array $validated, ?int $actor): void
    {
        if (empty($validated['ai_run_ids']) || !$actor || !Schema::hasTable('ai_runs')) return;
        $runs = AiRun::whereIn('id', $validated['ai_run_ids'])->where('created_by', $actor)
            ->where(fn ($q) => $q->whereNull('prenda_id')->orWhere('prenda_id', $product->id_prenda))
            ->orderBy('id')->lockForUpdate()->get();
        foreach ($runs as $run) {
            $variantIds = [];
            foreach ($validated['variant_rows'] ?? [] as $index => $row) {
                if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOL)) continue;
                $variantIds[(string) $index] = $product->variantes()
                    ->when(!empty($row['id']), fn ($q) => $q->whereKey($row['id']),
                        fn ($q) => $q->where('cod_colores_prv', $row['cod_colores_prv'] ?? null)->where('cod_tallas_prv', $row['cod_tallas_prv'] ?? null))
                    ->value('id_prenda_variante');
            }
            $run->update(['prenda_id' => $product->id_prenda, 'input_metadata' => [
                ...($run->input_metadata ?? []), 'saved_variant_ids' => $variantIds,
            ]]);
            $this->indexSavedEmbeddingSafely($run);
            foreach ($run->suggestions as $suggestion) {
                $reviews = $suggestion->reviews()->where('reviewer_id', $actor)->where('stage', 'form')->latest('id')->get()
                    ->unique(fn ($review) => in_array($suggestion->field, ['color', 'size', 'gtin'], true) ? ($review->variant_index ?? 0) : 'product');
                foreach ($reviews as $review) $this->recordSavedDecision($run, $product, $suggestion, $review);
            }
        }
    }

    private function recordSavedDecision(AiRun $run, Prenda $product, AiSuggestion $suggestion, AiHumanReview $review): void
    {
        if (AiHumanReview::where('form_review_id', $review->id)->exists()) return;
        $variantId = data_get($run->input_metadata, 'saved_variant_ids.'.($review->variant_index ?? 0));
        $variant = $variantId ? $product->variantes()->whereKey($variantId)->first() : null;
        $value = match ($suggestion->field) {
            'category' => $product->cod_categorias_pre, 'brand' => $product->cod_marcas_pre,
            'name' => $product->nom_pre, 'description' => $product->des_pre, 'model_code' => $product->modelo_pre,
            'color' => $variant?->cod_colores_prv, 'size' => $variant?->cod_tallas_prv, 'gtin' => $variant?->cob_prv,
            'pattern' => $product->patrones()->where('id_patron', $suggestion->value)->exists() ? $suggestion->value : null,
            default => null,
        };
        $action = $review->human_action === 'rejected' ? 'rejected'
            : ((string) $value === (string) $suggestion->value ? 'accepted' : 'corrected');
        AiHumanReview::create([
            'suggestion_id' => $suggestion->id, 'reviewer_id' => $review->reviewer_id,
            'human_action' => $action, 'prediction' => $suggestion->value,
            'confirmed_value' => $value, 'variant_index' => $review->variant_index,
            'stage' => 'saved', 'form_review_id' => $review->id,
        ]);
    }
}
