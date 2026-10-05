<?php
// Capture already persisted REAL CLI embeddings and exact production search, no inserts/inference.
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use App\Models\AiRun;
use App\Models\ImagenPrenda;
use App\Services\AI\CatalogEmbeddingStore;
use App\Services\AI\CatalogImageReader;
use Illuminate\Support\Facades\DB;

$options = getopt('', ['name:', 'max-sample:']);
$name = $options['name'] ?? 'pgvector-sample';
$maximum = filter_var($options['max-sample'] ?? 8, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 30]]);
if ($maximum === false) throw new RuntimeException('P1_SAMPLE_SIZE_INVALID');
$connection = p1Connection();
$data = p1ReadOnly(function () use ($connection, $maximum): array {
    $rows = DB::select("SELECT e.*,e.embedding::text AS vector_values,m.model_id,m.model_revision,m.pipeline_version,i.rut_igp AS image_path FROM ai_product_embeddings e JOIN ai_model_versions m ON m.id=e.ai_model_version_id JOIN imagenes_prenda i ON i.id_imagen_prenda=e.image_id AND i.cod_prendas_igp=e.prenda_id JOIN prendas p ON p.id_prenda=e.prenda_id WHERE p.est_pre=true ORDER BY e.id");
    if (count($rows) < 2 || count($rows) > $maximum) throw new RuntimeException('P1_EXPECT_SMALL_SAMPLE_INDEX_FIRST');
    $store = app(CatalogEmbeddingStore::class);
    $reader = app(CatalogImageReader::class);
    $samples = [];
    $spaces = [];
    foreach ($rows as $row) {
        $run = AiRun::findOrFail($row->source_run_id);
        if ($run->operation !== 'index-catalog') throw new RuntimeException('P1_SAMPLE_NOT_NORMAL_INDEX_COMMAND');
        $embedding = $run->embedding;
        $model = $run->result['model'] ?? [];
        $prepared = $store->prepare($embedding, $model);
        $image = ImagenPrenda::findOrFail($row->image_id);
        $sha = hash('sha256', $reader->read($image));
        $values = p1Json($row->vector_values);
        $actualNorm = sqrt(array_sum(array_map(fn ($value) => $value * $value, $values)));
        $maxValueError = 0.0;
        foreach ($values as $index => $value) {
            if (!isset($prepared['values'][$index])) throw new RuntimeException('P1_PERSISTED_VALUES_DIMENSION_MISMATCH');
            $expectedFloat32 = unpack('fvalue', pack('f', (float) $prepared['values'][$index]))['value'];
            $maxValueError = max($maxValueError, abs((float) $value - $expectedFloat32));
        }
        if ($maxValueError > 1e-7) throw new RuntimeException('P1_PERSISTED_VALUES_DIFFER_FROM_REAL_RUN');
        if ($sha !== $row->content_hash || $sha !== $run->input_hash || (int) $run->prenda_id !== (int) $row->prenda_id
            || (int) $run->model_version_id !== (int) $row->ai_model_version_id
            || (int) data_get($run->input_metadata, 'image_id') !== (int) $row->image_id
            || $row->normalization !== 'l2'
            || $prepared['dimensions'] !== 768 || (int) $row->dimensions !== 768 || count($values) !== 768
            || abs($actualNorm - 1.0) > .001 || $prepared['geometry_key'] !== $row->geometry_key
            || $prepared['model_id'] !== $row->model_id || $prepared['model_revision'] !== $row->model_revision
            || $prepared['pipeline_version'] !== $row->pipeline_version
            || $prepared['embedding_version'] !== $row->embedding_version || $prepared['preprocess_version'] !== $row->preprocess_version
            || data_get($row->metadata ? p1Json($row->metadata) : [], 'image_path') !== $row->image_path) {
            throw new RuntimeException('P1_REAL_EMBEDDING_PROVENANCE_ASSERTION_FAILED');
        }
        $existing = $store->existingForProduct((int) $row->prenda_id, $sha, $embedding, $model);
        if (!$existing || (int) $existing->id !== (int) $row->id) throw new RuntimeException('P1_CANONICAL_IDEMPOTENCE_LOOKUP_FAILED');
        $spaces[$row->model_id.'|'.$row->model_revision.'|'.$row->pipeline_version.'|'.$row->geometry_key] = true;
        $samples[] = [
            'embedding_id' => (int) $row->id, 'product_id' => (int) $row->prenda_id,
            'image_id' => (int) $row->image_id, 'image_path' => $row->image_path, 'sha256' => $sha,
            'source_run_id' => $row->source_run_id, 'source_operation' => $run->operation,
            'model' => $model, 'dimension' => (int) $row->dimensions, 'geometry_key' => $row->geometry_key,
            'embedding_version' => $row->embedding_version, 'preprocess_version' => $row->preprocess_version,
            'normalization' => $row->normalization, 'persisted_norm' => $actualNorm, 'max_persisted_source_float32_value_error' => $maxValueError,
            'persisted_values' => $values, 'query_prepared_values' => $prepared['values'],
            'query_embedding' => $embedding,
        ];
    }
    if (count($spaces) !== 1) throw new RuntimeException('P1_SAMPLE_MIXED_SPACES');
    $queries = [];
    $captured = null;
    DB::listen(function ($event) use (&$captured) {
        if (str_contains($event->sql, 'WITH eligible AS MATERIALIZED') && !str_starts_with($event->sql, 'EXPLAIN')) $captured = [$event->sql, $event->bindings];
    });
    foreach ($samples as $sample) {
        $filters = ['exclude_prenda_id' => $sample['product_id'], 'limit' => min(30, count($samples)), 'min_similarity' => -1];
        $result = $store->search($sample['query_embedding'], $filters, $sample['model']);
        if ($result['status'] !== 'completed' || $result['search_mode'] !== 'exact') throw new RuntimeException('P1_SAMPLE_SEARCH_NOT_READY');
        $queries[] = ['query_embedding_id' => $sample['embedding_id'], 'query_product_id' => $sample['product_id'], 'filters' => $filters, 'total_candidates' => $result['total_candidates'], 'matches' => $result['matches']];
    }
    $plan = DB::select('EXPLAIN (ANALYZE,BUFFERS,FORMAT JSON) '.$captured[0], $captured[1]);
    return [
        'utc' => gmdate('c'), 'connection' => $connection, 'sample_n' => count($samples), 'read_only' => true,
        'persistence_route' => 'Production mati-ai:index-catalog normal image mode executed before this capture',
        'sample' => $samples, 'queries' => $queries, 'explain' => p1Json($plan[0]->{'QUERY PLAN'}),
        'assertions' => ['real_image_sha_matches_run_and_vector' => true, 'dimensions_768_l2' => true, 'immutable_model_identity_consistent' => true, 'same_pinned_feature_space' => true, 'current_path_product_and_image_consistent' => true, 'canonical_existing_lookup_same_id' => true, 'persisted_values_match_real_source_run_float32' => true],
    ];
});
$path = p1Output($name, $data);
echo json_encode(['report' => $path, 'sample_n' => $data['sample_n'], 'connection' => $data['connection'], 'assertions' => $data['assertions']], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
