<?php
// Private read-only feature extraction. Never sends labels or split metadata to retrieval or a gate.
declare(strict_types=1);

use App\Services\AI\RagGateV2Evidence;
use App\Services\AI\RagGateV2Features;
use App\Services\AI\RagGateV2Model;
use App\Services\AI\RagGateV2Router;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$options = getopt('', ['execute-explicit', 'stage:', 'input:', 'output:', 'models::', 'holdout-authorization::']);
if (!array_key_exists('execute-explicit', $options) || !app()->runningInConsole()) throw new RuntimeException('Explicit console evaluation required.');
$stage = $options['stage'] ?? '';
if (!in_array($stage, ['train-cal', 'critical', 'train-repeat', 'holdout'], true)) throw new RuntimeException('Explicit evaluation stage required.');
$private = realpath(storage_path('app/ai-rag-gate-v2'));
$input = realpath($options['input'] ?? '');
$output = $options['output'] ?? '';
if (!$private || !$input || !str_starts_with($input, $private.DIRECTORY_SEPARATOR) || is_link($options['input'])
    || realpath(dirname($output)) !== $private || !preg_match('/^[a-zA-Z0-9._-]+\.json$/', basename($output))
    || file_exists($output)) throw new RuntimeException('Private controlled paths required; outputs cannot be overwritten.');
if (DB::connection()->getDriverName() !== 'pgsql' || config('database.connections.pgsql.host') !== 'pgsql'
    || DB::selectOne('SELECT current_database() AS name')->name !== 'laravel') throw new RuntimeException('Expected local read-only RAG database.');
if ($stage === 'holdout' && file_exists($private.'/holdout-execution-ledger.json')) throw new RuntimeException('Holdout already executed.');
if ($stage !== 'holdout') {
    $seal = json_decode(file_get_contents($private.'/preregistration-seal.json'), true, 32, JSON_THROW_ON_ERROR);
    $expectedName = $stage === 'critical' ? 'critical-suite.json' : 'train-cal-queries.json';
    if (($seal['schema_version'] ?? null) !== 'rag-gate-v2-preregistration-seal-1' || basename($input) !== $expectedName
        || ($seal['input_sha256'][$expectedName] ?? null) !== hash_file('sha256', $input)
        || ($seal['protocol_sha256'] ?? null) !== hash_file('sha256', __DIR__.'/protocol.json')) throw new RuntimeException('Preregistered input mismatch.');
}
$raw = file_get_contents($input);
$dataset = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
if (!is_array($dataset['queries'] ?? null) || !array_is_list($dataset['queries']) || !$dataset['queries']) throw new RuntimeException('Query envelope invalid.');
$evidence = app(RagGateV2Evidence::class); $router = app(RagGateV2Router::class); $model = app(RagGateV2Model::class);
$context = $evidence->context(); $models = []; $modelHash = null;
if (!empty($options['models'])) {
    $path = realpath($options['models']);
    if (!$path || !str_starts_with($path, $private.DIRECTORY_SEPARATOR) || is_link($options['models'])) throw new RuntimeException('Private model path required.');
    $modelRaw = file_get_contents($path); $modelHash = hash('sha256', $modelRaw);
    $bundle = json_decode($modelRaw, true, 64, JSON_THROW_ON_ERROR);
    $models = isset($bundle['models']) ? $bundle['models'] : [$bundle];
    if (!array_is_list($models) || !$models || count($models) > 3) throw new RuntimeException('Frozen model bundle invalid.');
    foreach ($models as $artifact) $model->validate($artifact, $context);
}
if (in_array($stage, ['critical', 'train-repeat'], true) && !$models) throw new RuntimeException('Frozen models required for critical evaluation.');
if ($stage === 'holdout') {
    $releasePath = $private.'/holdout-release.json';
    if (!$models || !is_file($releasePath)) throw new RuntimeException('Frozen model and explicit holdout release required.');
    $release = json_decode(file_get_contents($releasePath), true, 32, JSON_THROW_ON_ERROR);
    if (($options['holdout-authorization'] ?? null) !== hash_file('sha256', $releasePath)
        || ($release['allow_holdout'] ?? false) !== true || ($release['input_sha256'] ?? null) !== hash('sha256', $raw)
        || ($release['model_bundle_sha256'] ?? null) !== $modelHash || ($release['runtime_bundle_sha256'] ?? null) !== $context['runtime_bundle_sha256']) throw new RuntimeException('Holdout release mismatch.');
    $ledger = fopen($private.'/holdout-execution-ledger.json', 'x');
    if (!$ledger) throw new RuntimeException('Holdout already executed.');
    fwrite($ledger, json_encode(['started_at_utc' => gmdate('c'), 'input_sha256' => hash('sha256', $raw), 'model_bundle_sha256' => $modelHash, 'context' => $context], JSON_THROW_ON_ERROR));
    fclose($ledger); chmod($private.'/holdout-execution-ledger.json', 0600);
}
$http = []; $current = null;
Event::listen(ResponseReceived::class, function ($event) use (&$http, &$current) {
    $http[] = ['query_id' => $current, 'endpoint' => '/api/v1/rag/embed', 'status' => $event->response->status()];
});
$rows = []; $sanity = []; $ids = []; $passed = false; $failure = null;
DB::beginTransaction(); DB::statement('SET TRANSACTION READ ONLY');
try {
    foreach ($dataset['queries'] as $query) {
        $id = $query['id'] ?? $query['query_id'] ?? null;
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9._:-]{1,160}$/', $id) || isset($ids[$id]) || !is_string($query['query'] ?? null)) throw new RuntimeException('Query identity invalid.');
        $split = $query['split'] ?? null;
        if ($stage === 'train-repeat' && (strtolower((string) $split) !== 'train' || count($rows) >= 5)) continue;
        if (in_array($stage, ['train-cal', 'train-repeat'], true) && !in_array(strtolower((string) $split), ['train', 'calibration', 'cal'], true)) throw new RuntimeException('Train/cal stage forbids other splits.');
        if ($stage === 'holdout' && strtolower((string) $split) !== 'holdout') throw new RuntimeException('Holdout stage requires only holdout.');
        $ids[$id] = true; $current = $id; $start = microtime(true);
        // These are the only query fields given to the feature/routing services.
        $result = $evidence->extract($query['query']); $routing = $router->route($query['query']);
        if ($result['context'] !== $context) throw new RuntimeException('Source context changed during extraction.');
        $predictions = []; $gateStart = microtime(true);
        foreach ($models as $artifact) {
            $decision = $model->evaluate($artifact, $result['features'], count($result['vector_top3']), $context);
            $finalRoute = $routing['route'] === 'candidate_rag' ? ($decision['decision'] === 'answerable' ? 'rag' : 'required_information') : $routing['route'];
            $predictions[] = ['policy_kind' => $artifact['policy_kind'], 'policy_version' => $artifact['policy_version'],
                'binary' => $decision, 'route' => $finalRoute, 'chunks' => $finalRoute === 'rag' ? $result['vector_top3'] : []];
        }
        $result['timing']['gate_ms'] = $models ? (microtime(true) - $gateStart) * 1000 : null;
        $result['timing']['total_ms'] = (microtime(true) - $start) * 1000;
        if ($stage === 'train-cal' && strtolower((string) $split) === 'train' && count($sanity) < 5) {
            $sanity[] = ['query_id' => $id, 'query_vector' => $result['query_vector'],
                'sql_top3' => array_map(fn ($row) => ['chunk_id' => $row['chunk_id'], 'chunk_key' => $row['chunk_key'], 'similarity' => $row['similarity']], $result['vector_top3'])];
        }
        unset($result['query_vector'], $result['normalized_query']);
        // Ground truth is report-only metadata, copied after features and all predictions.
        $metadata = array_intersect_key($query, array_flip(['split', 'ground_truth_class', 'language', 'semantic_family',
            'group_id', 'difficulty', 'expected_document_ids', 'expected_chunk_ids', 'justification']));
        $rows[] = ['id' => $id, 'query' => $query['query']] + $metadata + $result
            + ['router_decision' => $routing, 'predictions' => $predictions, 'gate_evaluated' => (bool) $models];
    }
    if ($sanity) {
        $vectors = DB::table('rag_embeddings AS e')->join('rag_chunks AS c', 'c.id', '=', 'e.chunk_id')->join('rag_documents AS d', 'd.id', '=', 'c.document_id')
            ->where('e.model_version_id', DB::table('rag_model_versions')->where('space_key', $context['text_space_key'])->value('id'))
            ->where('e.status', 'active')->where('d.status', 'active')->where('e.dimensions', 384)
            ->select('c.id AS chunk_id', 'c.chunk_key', DB::raw('e.embedding::text AS vector'))->orderBy('c.id')->get()
            ->map(fn ($row) => ['chunk_id' => (int) $row->chunk_id, 'chunk_key' => $row->chunk_key, 'vector' => json_decode($row->vector, true, 512, JSON_THROW_ON_ERROR)])->all();
        if (count($vectors) !== 23) throw new RuntimeException('Sanity corpus space mismatch.');
        $sanityPath = $private.'/sql-numpy-sanity-input.json';
        if (file_exists($sanityPath)) throw new RuntimeException('Sanity evidence cannot be overwritten.');
        file_put_contents($sanityPath, json_encode(['schema_version' => 'mati-rag-gate-sql-numpy-2', 'context' => $context, 'corpus_vectors' => $vectors, 'queries' => $sanity], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($sanityPath, 0600);
    }
    $passed = true;
} catch (Throwable) {
    $failure = 'RAG_GATE_V2_EXTRACTION_FAILED';
} finally {
    DB::rollBack();
}
$payload = ['schema_version' => 'mati-rag-gate-feature-matrix-2', 'created_at_utc' => gmdate('c'),
    'input_sha256' => hash('sha256', $raw), 'model_bundle_sha256' => $modelHash, 'context' => $context,
    'feature_order' => RagGateV2Features::ORDER, 'feature_schema_version' => RagGateV2Features::VERSION,
    'rows' => $rows, 'http' => $http, 'passed' => $passed, 'failure' => $failure,
    'commercial_mutations' => false, 'generated_answer' => false];
file_put_contents($output, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
chmod($output, 0600);
echo json_encode(['rows' => count($rows), 'passed' => $passed, 'http_statuses' => array_count_values(array_column($http, 'status')), 'output' => basename($output)]), PHP_EOL;
exit($passed ? 0 : 1);
