<?php
// Only the new RAG migration/corpus, with an explicit guard on the local database.
declare(strict_types=1);
use App\Services\AI\RagCorpusStore;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Client\Events\ResponseReceived;
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
if (!in_array('--execute-explicit', $argv, true) || DB::connection()->getDriverName() !== 'pgsql'
 || DB::selectOne('SELECT current_database() AS name')->name !== 'laravel'
 || config('database.connections.pgsql.host') !== 'pgsql') throw new RuntimeException('Expected authorized local database only');
$out = storage_path('app/ai-semantic/');
$tables = ['rag_model_versions','rag_documents','rag_chunks','rag_embeddings'];
if (in_array('--schema', $argv, true)) {
 if (file_exists($out.'rag-migration.json') || collect($tables)->contains(fn ($table) => Schema::hasTable($table))) throw new RuntimeException('RAG schema already exists');
 $exit = Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_05_000001_create_rag_tables.php','--force' => true,'--no-interaction' => true]);
 $report = ['migration' => '2026_10_05_000001_create_rag_tables','exit' => $exit,'only_new_rag_migration' => true,
  'tables' => array_combine($tables,array_map(fn ($table) => Schema::hasTable($table),$tables))];
 file_put_contents($out.'rag-migration.json',json_encode($report,JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");chmod($out.'rag-migration.json',0600);
 echo json_encode($report)."\n";exit($exit);
}
if (!in_array('--index', $argv, true) || file_exists($out.'rag-index-idempotence.json')) throw new RuntimeException('Explicit first index only');
$http = [];
Event::listen(ResponseReceived::class, function ($event) use (&$http) {$http[] = ['endpoint' => '/api/v1/rag/embed','status' => $event->response->status()];});
$counts = fn () => array_combine($tables,array_map(fn ($table) => DB::table($table)->count(),$tables));
$fingerprints = fn () => array_combine($tables,array_map(fn ($table) => hash('sha256',json_encode(DB::table($table)->orderBy('id')->get()->all(),JSON_THROW_ON_ERROR)),$tables));
$before = $counts();if (array_sum($before)!==0) throw new RuntimeException('Expected empty new RAG tables');
$store = app(RagCorpusStore::class);$first = $store->ingest($store->corpus());$firstCounts = $counts();$firstHash = $fingerprints();
$repeat = $store->ingest($store->corpus());$secondCounts = $counts();$secondHash = $fingerprints();
$report = ['first' => $first,'repeat' => $repeat,'counts_before' => $before,'counts_first' => $firstCounts,'counts_repeat' => $secondCounts,
 'row_fingerprints_first' => $firstHash,'row_fingerprints_repeat' => $secondHash,'counts_identical' => $firstCounts === $secondCounts,
 'rows_identical' => $firstHash === $secondHash,'http' => $http,'commercial_mutations' => false,
 'passed' => $first['indexed'] === 23 && $repeat['indexed'] === 0 && $repeat['skipped'] === 23 && $firstCounts === $secondCounts && $firstHash === $secondHash];
file_put_contents($out.'rag-index-idempotence.json',json_encode($report,JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");chmod($out.'rag-index-idempotence.json',0600);
echo json_encode($report)."\n";exit($report['passed'] ? 0 : 1);
