<?php
// Explicit offline-controlled evaluation. Writes private evidence, never commercial data.
declare(strict_types=1);
use App\Services\AI\RagRetriever;
use App\Services\AI\RagTextClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
// Authorized CLI prototype evaluation; never a public request bypass.
config(['rag.evaluation_mode' => true]);
if (!in_array('--execute-explicit', $argv, true)) throw new RuntimeException('Explicit evaluation required');
if (DB::connection()->getDriverName() !== 'pgsql' || DB::selectOne('SELECT current_database() AS name')->name !== 'laravel'
    || config('database.connections.pgsql.host') !== 'pgsql') throw new RuntimeException('Expected local RAG database only');
$base = base_path('services/mati-ai/resources/rag/');
$protocolHash = 'f60608c413e19989cbdee9cb3391a1ad724b5ab9fd870c1c21e55f3f61068836';
if (hash_file('sha256', $base.'benchmark-protocol-v1.json') !== $protocolHash) throw new RuntimeException('Frozen protocol changed');
$protocol = json_decode(file_get_contents($base.'benchmark-protocol-v1.json'), true, 64, JSON_THROW_ON_ERROR);
foreach (['evaluation', 'corpus'] as $input) {
    if (hash_file('sha256', base_path($protocol[$input.'_path'])) !== $protocol[$input.'_sha256']) throw new RuntimeException('Frozen input changed');
}
$evaluation = json_decode(file_get_contents(base_path($protocol['evaluation_path'])), true, 64, JSON_THROW_ON_ERROR);
$client = app(RagTextClient::class); $profile = $client->profile();
$model = DB::table('rag_model_versions')->where('space_key', $client->spaceKey($profile))->first();
if (!$model) throw new RuntimeException('Approved text model not indexed');
$retriever = app(RagRetriever::class);
$private = storage_path('app/ai-semantic/');
if (!is_dir($private) || file_exists($private.'retrieval-results.json')) throw new RuntimeException('Private directory missing or results already exist');
$http = []; $current = null; $lastVector = null;
Event::listen(ResponseReceived::class, function ($event) use (&$http, &$current, &$lastVector) {
    $http[] = ['case' => $current, 'endpoint' => '/api/v1/rag/embed', 'status' => $event->response->status()];
    $lastVector = $event->response->json('data.embeddings.0.vector');
});
$results = []; $sqlQueries = []; $started = microtime(true);
foreach (['vector', 'hybrid'] as $mode) {
    foreach (array_merge($evaluation['queries'], $evaluation['negatives']) as $query) {
        $current = $mode.':'.$query['id']; $lastVector = null;
        $result = $retriever->search($query['text'], 3, $mode);
        $results[] = ['query_id' => $query['id'], 'text' => $query['text'], 'language' => $query['language'],
            'type' => $query['type'], 'negative' => $query['is_negative'], 'mode' => $mode, 'result' => $result];
        if ($mode === 'vector' && !$query['is_negative'] && is_array($lastVector)) {
            $nearest = $retriever->nearest($lastVector, $model->id, $profile['dimensions'], $query['text']);
            $sqlQueries[] = ['query_id' => $query['id'], 'vector' => $lastVector,
                'sql_ranked' => array_map(fn ($row) => ['chunk_key' => $row['chunk_key'], 'similarity' => $row['similarity']], $nearest)];
        }
    }
}
$current = 'filter:type';
$type = $retriever->search('CAMISAS', 3, 'hybrid', ['document_type' => 'taxonomy_category']);
$current = 'filter:missing_version';
$missing = $retriever->search('CAMISAS', 3, 'hybrid', ['source_version' => 'not-indexed-version']);
$current = 'filter:corpus_version';
$version = $retriever->search('CAMISAS', 3, 'hybrid', ['corpus_version' => '1.0.0']);
$filters = ['type_only_taxonomy' => $type['chunks'] && collect($type['chunks'])->every(fn ($row) => $row['document_type'] === 'taxonomy_category'),
    'missing_version_abstains' => $missing['status'] === 'insufficient_evidence' && $missing['chunks'] === [],
    'corpus_version_exact' => $version['chunks'] && collect($version['chunks'])->every(fn ($row) => $row['corpus_version'] === '1.0.0')];
$documents = DB::table('rag_documents')->select('document_key', 'status', 'corpus_version', 'source_version')->orderBy('id')->get();
$chunks = DB::table('rag_embeddings AS e')->join('rag_chunks AS c', 'c.id', '=', 'e.chunk_id')->join('rag_documents AS d', 'd.id', '=', 'c.document_id')
    ->where('e.model_version_id', $model->id)->where('e.status', 'active')->where('d.status', 'active')
    ->select('c.chunk_key', DB::raw('e.embedding::text AS vector'))->orderBy('c.id')->get()->map(fn ($row) => ['chunk_key' => $row->chunk_key, 'vector' => json_decode($row->vector, true, 512, JSON_THROW_ON_ERROR)])->all();
$evidence = ['schema_version' => 'mati-rag-evaluation-result-1', 'created_at_utc' => gmdate('c'),
    'protocol_sha256' => $protocolHash, 'evaluation_sha256' => $protocol['evaluation_sha256'], 'corpus_sha256' => $protocol['corpus_sha256'],
    'profile_sha256' => hash_file('sha256', $base.'model-profile.json'), 'model' => $client->identity($profile),
    'counts' => ['documents' => $documents->count(), 'chunks' => count($chunks), 'text_model_versions' => DB::table('rag_model_versions')->count()],
    'document_versions' => $documents->all(), 'cases' => $results, 'filters' => $filters, 'http' => $http,
    'wall_ms' => (microtime(true) - $started) * 1000, 'commercial_mutations' => false, 'generated_answer' => false];
foreach (['retrieval-results.json' => $evidence, 'sql-numpy-inputs.json' => ['chunks' => $chunks, 'queries' => $sqlQueries]] as $name => $value) {
    $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    if (file_put_contents($private.$name, $payload) === false) throw new RuntimeException('Private output failed');
    chmod($private.$name, 0600);
}
echo json_encode(['cases' => count($results), 'http_statuses' => array_count_values(array_column($http, 'status')), 'sql_numpy_queries' => count($sqlQueries), 'filters' => $filters])."\n";
