<?php

namespace Tests\Feature;

use App\Models\RagEmbedding;
use App\Services\AI\RagCorpusStore;
use App\Services\AI\RagRetriever;
use App\Services\AI\RagRetrievalPolicy;
use App\Services\AI\RagTextClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RagPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $expectedDb = getenv('RAG_TEST_EXPECT_DATABASE');
        $expectedHost = getenv('RAG_TEST_EXPECT_HOST');
        if (!$expectedDb || !$expectedHost) $this->markTestSkipped('Explicit isolated PostgreSQL identity required.');
        $connection = DB::connection();
        $identity = DB::selectOne('SELECT current_database() AS name');
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertMatchesRegularExpression('/^(rag_|semantic_|p1_migration_)/', $expectedDb);
        $this->assertNotContains($expectedHost, ['pgsql', 'more-pgsql-1', 'localhost', '127.0.0.1']);
        $this->assertSame($expectedDb, $identity->name);
        $this->assertSame($expectedHost, $connection->getConfig('host'));
        $this->assertTrue(Schema::hasTable('rag_embeddings'));
        DB::beginTransaction();
        foreach (['rag_embeddings', 'rag_chunks', 'rag_documents', 'rag_model_versions'] as $table) DB::table($table)->delete();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel()) DB::rollBack();
        parent::tearDown();
    }

    private function profile(): array
    {
        return ['approved' => true, 'vector_space' => 'text', 'model_id' => 'fixture/text',
            'revision' => str_repeat('a', 40), 'pipeline_version' => 'text-1', 'dimensions' => 3,
            'normalization' => 'l2', 'preprocess_version' => 'text-fixture-1', 'minimum_cosine' => .74];
    }

    private function corpus(string $version = '1.0.0', string $suffix = ''): array
    {
        $documents = [];
        foreach (['alpha', 'beta', 'gamma'] as $key) {
            $title = strtoupper($key); $section = 'definition'; $content = 'Concepto '.$key.$suffix;
            $documents[] = ['id' => 'technical:'.$key, 'document_type' => 'technical_explanation', 'title' => $title,
                'source' => 'fixture-curated-source', 'source_version' => $version, 'metadata' => ['aliases' => []],
                'content_hash' => hash('sha256', $title."\n".$section."\n".$content),
                'chunks' => [['id' => 'technical:'.$key.'#definition', 'section' => $section, 'content' => $content,
                    'content_hash' => hash('sha256', $content), 'metadata' => []]]];
        }
        return ['schema_version' => 'mati-rag-corpus-1', 'corpus_version' => $version, 'documents' => $documents];
    }

    private function client(int $calls): RagTextClient
    {
        $client = \Mockery::mock(RagTextClient::class)->makePartial();
        $client->shouldReceive('profile')->andReturn($this->profile());
        $client->shouldReceive('embed')->times($calls)->andReturnUsing(function ($texts) {
            $entries = [];
            foreach ($texts as $index => $text) {
                $vector = str_contains($text, 'alpha') ? [1.0, 0.0, 0.0] : (str_contains($text, 'beta') ? [.8, .6, 0.0] : [0.0, 1.0, 0.0]);
                $entries[] = ['index' => $index, 'vector' => $vector, 'dimensions' => 3, 'norm' => 1.0,
                    'text_sha256' => hash('sha256', $text), 'truncated' => false];
            }
            return $entries;
        });
        return $client;
    }

    public function test_reindex_is_idempotent_and_version_change_deactivates_stale_snapshot(): void
    {
        $store = new RagCorpusStore($this->client(2));
        $first = $store->ingest($this->corpus());
        $repeat = $store->ingest($this->corpus());
        $this->assertSame(3, $first['indexed']);
        $this->assertSame(0, $repeat['indexed']);
        $this->assertSame(3, $repeat['skipped']);
        $this->assertSame(3, DB::table('rag_documents')->count());
        $this->assertSame(3, DB::table('rag_embeddings')->count());
        $next = $store->ingest($this->corpus('1.0.1', ' versión nueva'));
        $this->assertSame(3, $next['indexed']);
        $this->assertSame(3, DB::table('rag_documents')->where('status', 'active')->count());
        $this->assertSame(3, DB::table('rag_documents')->where('status', 'inactive')->count());
        $this->assertSame(3, DB::table('rag_embeddings')->where('status', 'active')->count());
        $this->assertSame(3, DB::table('rag_embeddings')->where('status', 'inactive')->count());
        $retriever = new RagRetriever(new RagTextClient(), new RagRetrievalPolicy());
        $modelId = DB::table('rag_model_versions')->value('id');
        $this->assertSame([], $retriever->nearest([1, 0, 0], $modelId, 3, 'alpha', ['corpus_version' => '1.0.0']));
        $rows = $retriever->nearest([1, 0, 0], $modelId, 3, 'alpha');
        $this->assertCount(3, $rows);
        $this->assertSame(['1.0.1'], array_values(array_unique(array_column($rows, 'corpus_version'))));
    }

    public function test_content_change_requires_new_version_and_failure_preserves_active_corpus(): void
    {
        $store = new RagCorpusStore($this->client(1));
        $store->ingest($this->corpus());
        try {
            $store->ingest($this->corpus('1.0.0', ' modified'));
            $this->fail('Same version must be immutable.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('IMMUTABLE_CORPUS_VERSION_CHANGED', $exception->getMessage());
        }
        $failedClient = \Mockery::mock(RagTextClient::class)->makePartial();
        $failedClient->shouldReceive('profile')->andReturn($this->profile());
        $failedClient->shouldReceive('embed')->once()->andThrow(new \RuntimeException('fixture unavailable'));
        try {
            (new RagCorpusStore($failedClient))->ingest($this->corpus('1.0.2'));
            $this->fail('Failed embedding must not activate.');
        } catch (\RuntimeException) {}
        $this->assertSame(3, DB::table('rag_documents')->count());
        $this->assertSame(3, DB::table('rag_embeddings')->where('status', 'active')->count());
        $this->assertSame(['1.0.0'], DB::table('rag_documents')->where('status', 'active')->distinct()->pluck('corpus_version')->all());
    }

    public function test_document_membership_cannot_change_within_an_existing_corpus_version(): void
    {
        $store = new RagCorpusStore($this->client(1));
        $corpus = $this->corpus();
        $store->ingest($corpus);
        $removed = $corpus;
        array_pop($removed['documents']);
        $added = $corpus;
        $extra = $corpus['documents'][0];
        $extra['id'] = 'technical:delta';
        $extra['chunks'][0]['id'] = 'technical:delta#definition';
        $added['documents'][] = $extra;
        foreach ([$removed, $added] as $changed) {
            try {
                $store->ingest($changed);
                $this->fail('Document membership requires a new corpus version.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame('IMMUTABLE_CORPUS_VERSION_CHANGED', $exception->getMessage());
            }
        }
        $this->assertSame(3, DB::table('rag_documents')->count());
        $this->assertSame(3, DB::table('rag_documents')->where('status', 'active')->count());
        $this->assertSame(3, DB::table('rag_embeddings')->where('status', 'active')->count());
    }

    public function test_exact_sql_cosines_and_dimension_constraints_and_export_for_numpy(): void
    {
        (new RagCorpusStore($this->client(1)))->ingest($this->corpus());
        $modelId = DB::table('rag_model_versions')->value('id');
        $retriever = new RagRetriever(new RagTextClient(), new RagRetrievalPolicy());
        $rows = $retriever->nearest([1, 0, 0], $modelId, 3, 'alpha');
        $this->assertEqualsWithDelta(1.0, $rows[0]['similarity'], 1e-6);
        $this->assertEqualsWithDelta(.8, $rows[1]['similarity'], 1e-6);
        $this->assertEqualsWithDelta(0.0, $rows[2]['similarity'], 1e-6);
        $this->assertSame([], $retriever->nearest([1, 0], $modelId, 2, 'alpha'));
        $this->assertSame([], $retriever->nearest([1, 0, 0], $modelId, 3, 'alpha', ['source_version' => "'; DROP TABLE rag_documents; --"]));
        $this->assertSame(3, DB::table('rag_documents')->count());
        $fixture = ['read_scope' => 'isolated_transaction_rollback', 'model' => $this->profile(), 'query' => [1, 0, 0],
            'vectors' => DB::table('rag_embeddings')->orderBy('chunk_id')->get(['chunk_id', 'embedding'])->map(fn ($row) => [
                'chunk_id' => $row->chunk_id, 'vector' => json_decode($row->embedding, true, 32, JSON_THROW_ON_ERROR),
            ])->all(), 'sql_top_k' => array_map(fn ($row) => ['chunk_id' => $row['chunk_id'], 'similarity' => $row['similarity']], $rows)];
        $dir = storage_path('app/ai-rag'); if (!is_dir($dir)) mkdir($dir, 0700, true);
        file_put_contents($dir.'/sql-isolated-fixture.json', json_encode($fixture, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        DB::statement('SAVEPOINT wrong_dimensions');
        try {
            DB::statement("UPDATE rag_embeddings SET embedding='[1,0]'::vector WHERE id=?", [RagEmbedding::first()->id]);
            $this->fail('Vector length must be constrained.');
        } catch (\Illuminate\Database\QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT wrong_dimensions');
        }
        DB::statement('SAVEPOINT wrong_norm');
        try {
            DB::statement("UPDATE rag_embeddings SET embedding='[2,0,0]'::vector WHERE id=?", [RagEmbedding::first()->id]);
            $this->fail('L2 normalization must be constrained.');
        } catch (\Illuminate\Database\QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT wrong_norm');
        }
        $this->assertSame(3, DB::table('rag_embeddings')->count());
    }
}
