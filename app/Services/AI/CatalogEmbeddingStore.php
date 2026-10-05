<?php

namespace App\Services\AI;

use App\Models\AiProductEmbedding;
use App\Models\AiRun;
use App\Models\ImagenPrenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class CatalogEmbeddingStore
{
    public function available(): bool
    {
        if (!config('mati_ai.vectors.enabled', true)) return false;
        try {
            if (DB::connection()->getDriverName() !== 'pgsql') return false;
            return DB::transaction(function () {
                if (!Schema::hasTable('ai_product_embeddings') || !Schema::hasTable('ai_model_versions')) return false;
                $extension = DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname='vector') AS installed");
                return in_array($extension->installed, [true, 1, '1', 't'], true);
            });
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * A real vector determines its dimensions. Model, revision, pipeline and feature geometry
     * must agree; equal dimensions alone never make two embedding spaces compatible.
     */
    public function prepare(array $embedding, array $model = []): array
    {
        $values = $embedding['values'] ?? null;
        if (!is_array($values) || !array_is_list($values) || count($values) < 1 || count($values) > min(16000, max(1, (int) config('mati_ai.vectors.max_dimensions', 16000)))) {
            throw new InvalidArgumentException('INVALID_EMBEDDING_VALUES');
        }
        foreach ($values as $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                throw new InvalidArgumentException('INVALID_EMBEDDING_VALUES');
            }
        }
        $dimensions = count($values);
        $declared = $embedding['dimensions'] ?? $embedding['dimension'] ?? null;
        if ($declared !== null && (!is_int($declared) || $declared !== $dimensions)) {
            throw new InvalidArgumentException('EMBEDDING_DIMENSION_MISMATCH');
        }
        $identity = [];
        foreach (['model_id', 'model_revision', 'pipeline_version'] as $key) {
            $value = $embedding[$key] ?? $model[$key] ?? null;
            if (!is_string($value) || trim($value) === '' || mb_strlen($value) > 255
                || (isset($embedding[$key], $model[$key]) && $embedding[$key] !== $model[$key])) {
                throw new InvalidArgumentException('INCOMPLETE_EMBEDDING_IDENTITY');
            }
            $identity[$key] = $value;
        }
        // Mutable aliases such as main cannot identify a reproducible vector space.
        if (!preg_match('/^(?:[a-f0-9]{40}|[a-f0-9]{64})$/i', $identity['model_revision'])
            || in_array($identity['model_id'], ['deterministic', 'unavailable'], true)) {
            throw new InvalidArgumentException('UNPINNED_EMBEDDING_REVISION');
        }
        $legacy = !isset($embedding['embedding_version']) || !isset($embedding['preprocess_version']);
        // Historical ai_runs had no feature-profile fields. Keep them in a separate space
        // based on the recorded pipeline instead of pretending they match a newer endpoint.
        $legacyProfile = substr(hash('sha256', $identity['pipeline_version']), 0, 16);
        $embeddingVersion = $embedding['embedding_version'] ?? 'legacy-image-'.$legacyProfile;
        $preprocessVersion = $embedding['preprocess_version'] ?? 'legacy-preprocess-'.$legacyProfile;
        foreach ([$embeddingVersion, $preprocessVersion] as $version) {
            if (!is_string($version) || trim($version) === '' || mb_strlen($version) > 100) {
                throw new InvalidArgumentException('INVALID_EMBEDDING_GEOMETRY');
            }
        }
        if (isset($embedding['normalization']) && $embedding['normalization'] !== 'l2') {
            throw new InvalidArgumentException('UNSUPPORTED_EMBEDDING_NORMALIZATION');
        }
        $scale = max(array_map(fn ($value) => abs((float) $value), $values));
        if ($scale == 0) throw new InvalidArgumentException('ZERO_EMBEDDING_VECTOR');
        $scaled = array_map(fn ($value) => $value / $scale, $values);
        $scaledNorm = sqrt(array_sum(array_map(fn ($value) => $value * $value, $scaled)));
        $inputNorm = $scale * $scaledNorm;
        if (!is_finite($inputNorm) || $inputNorm <= 0) throw new InvalidArgumentException('INVALID_EMBEDDING_NORM');
        $unit = array_map(fn ($value) => $value / $scaledNorm, $scaled);
        $norm = sqrt(array_sum(array_map(fn ($value) => $value * $value, $unit)));
        $geometry = [
            'embedding_version' => $embeddingVersion, 'preprocess_version' => $preprocessVersion,
            'normalization' => 'l2',
        ];
        return [
            ...$identity, ...$geometry, 'dimensions' => $dimensions, 'values' => $unit,
            'norm' => $norm, 'input_norm' => $inputNorm, 'normalized' => true,
            'geometry_key' => hash('sha256', json_encode($geometry, JSON_THROW_ON_ERROR)),
            'vector_literal' => json_encode($unit, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
            'identity_source' => $legacy ? 'legacy_pipeline_profile' : 'explicit_feature_profile',
        ];
    }

    public function storeRun(AiRun $run, ?int $imageId = null, array $metadata = [], bool $force = false): ?AiProductEmbedding
    {
        if (!$run->prenda_id || !is_array($run->embedding) || !$this->available()) return null;

        // A nested transaction creates a savepoint when called during commercial saving.
        return DB::transaction(function () use ($run, $imageId, $metadata, $force) {
            $prepared = $this->prepare($run->embedding, $run->result['model'] ?? []);
            if (!$run->model_version_id || !DB::table('ai_model_versions')
                ->where('id', $run->model_version_id)
                ->where('model_id', $prepared['model_id'])->where('model_revision', $prepared['model_revision'])
                ->where('pipeline_version', $prepared['pipeline_version'])->exists()) {
                throw new InvalidArgumentException('EMBEDDING_MODEL_VERSION_MISMATCH');
            }
            $hash = $run->input_hash;
            if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/i', $hash)) {
                throw new InvalidArgumentException('EMBEDDING_CONTENT_HASH_REQUIRED');
            }
            $imageId ??= data_get($run->input_metadata, 'image_id');
            $image = $imageId !== null ? ImagenPrenda::whereKey($imageId)->where('cod_prendas_igp', $run->prenda_id)->first() : null;
            if ($imageId !== null && !$image) throw new InvalidArgumentException('EMBEDDING_IMAGE_PRODUCT_MISMATCH');
            $key = [
                'prenda_id' => $run->prenda_id, 'content_hash' => strtolower($hash),
                'ai_model_version_id' => $run->model_version_id, 'dimensions' => $prepared['dimensions'],
                'geometry_key' => $prepared['geometry_key'],
            ];
            $evidence = [
                ...array_intersect_key($run->input_metadata ?? [], array_flip(['image_id', 'pixel_sha256', 'width', 'height', 'mime'])),
                ...$metadata, 'model_id' => $prepared['model_id'], 'model_revision' => $prepared['model_revision'],
                'pipeline_version' => $prepared['pipeline_version'],
                'embedding_version' => $prepared['embedding_version'], 'preprocess_version' => $prepared['preprocess_version'],
                'dimensions' => $prepared['dimensions'], 'identity_source' => $prepared['identity_source'],
                'metric' => 'cosine_similarity', 'normalized' => true,
                'original_preserved' => true, 'source_operation' => $run->operation,
                ...($image ? ['image_id' => (int) $image->id_imagen_prenda, 'image_path' => $image->rut_igp] : []),
            ];
            $now = now()->toDateTimeString();
            $encodedMetadata = json_encode($evidence, JSON_THROW_ON_ERROR);
            $inserted = DB::affectingStatement(
                'INSERT INTO ai_product_embeddings (prenda_id,image_id,ai_model_version_id,source_run_id,content_hash,dimensions,geometry_key,embedding_version,preprocess_version,normalization,norm,input_norm,metadata,embedding,created_at,updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CAST(? AS json), CAST(? AS vector), ?, ?)
                 ON CONFLICT (prenda_id,content_hash,ai_model_version_id,dimensions,geometry_key) DO NOTHING',
                [
                    $key['prenda_id'], $imageId, $key['ai_model_version_id'], $run->id,
                    $key['content_hash'], $key['dimensions'], $key['geometry_key'],
                    $prepared['embedding_version'], $prepared['preprocess_version'], 'l2',
                    $prepared['norm'], $prepared['input_norm'], $encodedMetadata, $prepared['vector_literal'], $now, $now,
                ],
            );
            $stored = AiProductEmbedding::where($key)->firstOrFail();
            if (!$inserted && $force) {
                $encodedMetadata = json_encode([...$stored->metadata, ...$evidence], JSON_THROW_ON_ERROR);
                DB::update(
                    'UPDATE ai_product_embeddings SET embedding=CAST(? AS vector),norm=?,input_norm=?,metadata=CAST(? AS json),source_run_id=?,image_id=COALESCE(?,image_id),updated_at=? WHERE id=?',
                    [$prepared['vector_literal'], $prepared['norm'], $prepared['input_norm'], $encodedMetadata, $run->id, $imageId, $now, $stored->id],
                );
                $stored->refresh();
            } elseif ($imageId && ((int) $stored->image_id !== (int) $imageId || data_get($stored->metadata, 'image_path') !== $image->rut_igp)) {
                $stored->update(['image_id' => $imageId, 'metadata' => [...$stored->metadata, 'image_id' => $imageId, 'image_path' => $image->rut_igp]]);
            }
            $stored->wasRecentlyCreated = $inserted > 0;
            return $stored;
        });
    }

    /** Identity lookup after inference: never guess the current revision to skip a real image. */
    public function existingForProduct(int $prendaId, string $hash, array $embedding, array $model = []): ?AiProductEmbedding
    {
        if (!$this->available()) return null;
        $prepared = $this->prepare($embedding, $model);
        return $this->currentProductEmbeddings($prendaId)->where('content_hash', strtolower($hash))
            ->where('dimensions', $prepared['dimensions'])->where('geometry_key', $prepared['geometry_key'])
            ->whereIn('ai_model_version_id', DB::table('ai_model_versions')->select('id')
                ->where('model_id', $prepared['model_id'])->where('model_revision', $prepared['model_revision'])
                ->where('pipeline_version', $prepared['pipeline_version']))
            ->first();
    }

    public function latestForProduct(int $prendaId): ?AiProductEmbedding
    {
        if (!$this->available()) return null;
        return $this->currentProductEmbeddings($prendaId)->orderByDesc('updated_at')->orderByDesc('id')->first();
    }

    public function latestCompatibleForProduct(int $prendaId, array $embedding, array $model = []): ?AiProductEmbedding
    {
        if (!$this->available()) return null;
        $prepared = $this->prepare($embedding, $model);
        return $this->currentProductEmbeddings($prendaId)
            ->where('dimensions', $prepared['dimensions'])->where('geometry_key', $prepared['geometry_key'])
            ->where('normalization', 'l2')
            ->whereIn('ai_model_version_id', DB::table('ai_model_versions')->select('id')
                ->where('model_id', $prepared['model_id'])->where('model_revision', $prepared['model_revision'])
                ->where('pipeline_version', $prepared['pipeline_version']))
            ->orderByDesc('updated_at')->orderByDesc('id')->first();
    }

    private function currentProductEmbeddings(int $prendaId)
    {
        return AiProductEmbedding::where('prenda_id', $prendaId)
            ->whereHas('prenda', fn ($query) => $query->where('est_pre', true))
            ->whereRaw($this->currentImageCondition('ai_product_embeddings'));
    }

    private function currentImageCondition(string $alias): string
    {
        // Deleting an image nulls its FK; retained provenance distinguishes it from
        // a genuinely unlinked original. A replaced path invalidates the old vector.
        return "(({$alias}.image_id IS NULL AND {$alias}.metadata->>'image_id' IS NULL AND {$alias}.metadata->>'image_path' IS NULL)
            OR EXISTS (SELECT 1 FROM imagenes_prenda image_ref
                WHERE image_ref.id_imagen_prenda={$alias}.image_id AND image_ref.cod_prendas_igp={$alias}.prenda_id
                AND ({$alias}.metadata->>'image_path' IS NULL OR image_ref.rut_igp={$alias}.metadata->>'image_path')))";
    }

    /**
     * Exact cosine search, one stable best image per active garment.
     * PostgreSQL first materializes the compatible geometry; mixed dimensions never
     * reach the vector operator, regardless of predicate evaluation order.
     */
    public function search(array $embedding, array $filters = [], array $model = []): array
    {
        $prepared = $this->prepare($embedding, $model);
        $result = [
            'status' => 'completed', 'matches' => [], 'total_candidates' => 0, 'total_indexed' => 0,
            ...array_intersect_key($prepared, array_flip([
                'dimensions', 'model_id', 'model_revision', 'pipeline_version', 'embedding_version', 'preprocess_version',
            ])),
            'metric' => 'cosine_similarity', 'search_mode' => 'exact', 'warnings' => [],
        ];
        if (!$this->available()) return [...$result, 'status' => 'degraded', 'warnings' => ['VECTOR_STORE_UNAVAILABLE']];

        $limit = filter_var($filters['limit'] ?? config('mati_ai.vectors.default_limit', 12), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => min(30, max(1, (int) config('mati_ai.vectors.max_limit', 30)))]]);
        $minimum = $filters['min_similarity'] ?? $filters['min_score'] ?? 0;
        $duplicateThreshold = $filters['duplicate_similarity'] ?? $filters['duplicate_threshold'] ?? config('mati_ai.vectors.duplicate_similarity_threshold', 0.98);
        if ($limit === false || !is_numeric($minimum) || !is_finite((float) $minimum) || $minimum < -1 || $minimum > 1
            || !is_numeric($duplicateThreshold) || !is_finite((float) $duplicateThreshold) || $duplicateThreshold < -1 || $duplicateThreshold > 1) {
            throw new InvalidArgumentException('INVALID_EMBEDDING_SEARCH_FILTER');
        }
        $conditions = [
            'p.est_pre = true', 'mv.model_id = ?', 'mv.model_revision = ?', 'mv.pipeline_version = ?',
            'e.dimensions = ?', 'e.geometry_key = ?', "e.normalization = 'l2'", $this->currentImageCondition('e'),
        ];
        $bindings = [
            $prepared['model_id'], $prepared['model_revision'], $prepared['pipeline_version'],
            $prepared['dimensions'], $prepared['geometry_key'],
        ];
        foreach ([
            'exclude_prenda_id' => 'p.id_prenda <> ?', 'category_id' => 'p.cod_categorias_pre = ?',
            'brand_id' => 'p.cod_marcas_pre = ?', 'season_id' => 'p.cod_temporadas_pre = ?',
        ] as $filter => $condition) {
            if (!isset($filters[$filter])) continue;
            $id = filter_var($filters[$filter], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new InvalidArgumentException('INVALID_EMBEDDING_SEARCH_FILTER');
            $conditions[] = $condition;
            $bindings[] = $id;
        }
        foreach (['color_id' => 'pv.cod_colores_prv', 'size_id' => 'pv.cod_tallas_prv'] as $filter => $column) {
            if (!isset($filters[$filter])) continue;
            $id = filter_var($filters[$filter], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new InvalidArgumentException('INVALID_EMBEDDING_SEARCH_FILTER');
            $conditions[] = "EXISTS (SELECT 1 FROM prenda_variantes pv WHERE pv.cod_prendas_prv=p.id_prenda AND pv.est_prv=true AND {$column}=?)";
            $bindings[] = $id;
        }
        $compatibility = [
            'mv.model_id = ?', 'mv.model_revision = ?', 'mv.pipeline_version = ?',
            'e.dimensions = ?', 'e.geometry_key = ?', "e.normalization = 'l2'",
        ];
        $eligibleWhere = implode(' AND ', array_values(array_diff($conditions, $compatibility)));
        $compatibleWhere = implode(' AND ', $compatibility);
        $sql = <<<SQL
            WITH eligible AS MATERIALIZED (
                SELECT e.id AS embedding_id,e.prenda_id,e.image_id,e.content_hash,e.embedding,p.nom_pre AS title,
                       e.ai_model_version_id,e.dimensions,e.geometry_key,e.normalization
                FROM ai_product_embeddings e
                JOIN prendas p ON p.id_prenda=e.prenda_id
                WHERE {$eligibleWhere}
            ), compatible AS MATERIALIZED (
                SELECT e.embedding_id,e.prenda_id,e.image_id,e.content_hash,e.embedding,e.title
                FROM eligible e
                JOIN ai_model_versions mv ON mv.id=e.ai_model_version_id
                WHERE {$compatibleWhere}
            ), scored AS MATERIALIZED (
                SELECT embedding_id,prenda_id,image_id,content_hash,title,
                       embedding <=> CAST(? AS vector) AS distance
                FROM compatible
            ), ranked AS (
                SELECT *, ROW_NUMBER() OVER (PARTITION BY prenda_id ORDER BY distance,embedding_id) AS garment_rank
                FROM scored
            ), selected AS (
                SELECT embedding_id,prenda_id,image_id,content_hash,title,distance
                FROM ranked WHERE garment_rank=1 AND 1-distance >= ?
                ORDER BY distance,prenda_id,embedding_id LIMIT ?
            )
            SELECT (SELECT COUNT(DISTINCT prenda_id) FROM eligible) AS total_indexed,
                   (SELECT COUNT(DISTINCT prenda_id) FROM compatible) AS total_candidates,
                   COALESCE(json_agg(selected ORDER BY distance,prenda_id,embedding_id),'[]'::json) AS matches
            FROM selected
            SQL;
        try {
            $row = DB::transaction(fn () => DB::selectOne($sql, [...array_slice($bindings, 5), ...array_slice($bindings, 0, 5), $prepared['vector_literal'], (float) $minimum, $limit]));
            $result['total_candidates'] = (int) $row->total_candidates;
            $result['total_indexed'] = (int) $row->total_indexed;
            $matches = json_decode((string) $row->matches, true, 512, JSON_THROW_ON_ERROR);
            $queryHash = $filters['content_hash'] ?? $filters['query_content_hash'] ?? null;
            foreach ($matches as $match) {
                $distance = (float) $match['distance'];
                if (!is_finite($distance)) continue;
                $similarity = max(-1, min(1, 1 - $distance));
                $sameContent = is_string($queryHash) && preg_match('/^[a-f0-9]{64}$/i', $queryHash)
                    && hash_equals(strtolower($queryHash), $match['content_hash']);
                $highSimilarity = $similarity >= (float) $duplicateThreshold;
                $result['matches'][] = [
                    'embedding_id' => (int) $match['embedding_id'], 'prenda_id' => (int) $match['prenda_id'],
                    'image_id' => $match['image_id'] !== null ? (int) $match['image_id'] : null,
                    'title' => $match['title'], 'distance' => $distance, 'similarity_score' => $similarity,
                    'possible_duplicate' => $sameContent || $highSimilarity,
                    'duplicate_evidence' => ['same_content_hash' => (bool) $sameContent, 'high_visual_similarity' => $highSimilarity],
                ];
            }
            if (!$result['total_candidates']) {
                $result['status'] = 'insufficient_evidence';
                $result['warnings'][] = $result['total_indexed'] ? 'VECTOR_COMPATIBLE_INDEX_EMPTY' : 'VECTOR_INDEX_EMPTY';
            } elseif (!$result['matches']) {
                $result['warnings'][] = 'NO_SIMILAR_PRODUCTS_ABOVE_THRESHOLD';
            }
            $result['warnings'][] = 'VISUAL_SIMILARITY_DOES_NOT_CONFIRM_IDENTITY';
            return $result;
        } catch (\Throwable) {
            Log::warning('mati_ai.vector_search_unavailable', ['dimensions' => $prepared['dimensions']]);
            return [...$result, 'status' => 'degraded', 'matches' => [], 'warnings' => ['VECTOR_SEARCH_UNAVAILABLE']];
        }
    }
}
