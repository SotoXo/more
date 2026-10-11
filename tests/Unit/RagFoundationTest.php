<?php

namespace Tests\Unit;

use App\Services\AI\RagCorpusStore;
use App\Services\AI\RagRetrievalPolicy;
use App\Services\AI\RagTextClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RagFoundationTest extends TestCase
{
    private function profile(): array
    {
        return ['approved' => true, 'vector_space' => 'text', 'model_id' => 'fixture/text',
            'revision' => str_repeat('a', 40), 'pipeline_version' => 'text-1', 'dimensions' => 3,
            'normalization' => 'l2', 'preprocess_version' => 'text-fixture-1', 'minimum_cosine' => .74];
    }

    private function document(string $content = 'Una definición semántica.'): array
    {
        $title = 'CAMISAS'; $section = 'definition';
        return ['id' => 'category:3', 'document_type' => 'taxonomy_category', 'title' => $title,
            'source' => 'mati-taxonomy-v1', 'source_version' => '1.0.0', 'metadata' => ['aliases' => ['camisa']],
            'content_hash' => hash('sha256', $title."\n".$section."\n".$content),
            'chunks' => [['id' => 'category:3#definition', 'section' => $section, 'content' => $content,
                'content_hash' => hash('sha256', $content), 'metadata' => []]]];
    }

    public function test_semantic_units_have_deterministic_content_and_snapshot_hashes(): void
    {
        $store = app(RagCorpusStore::class);
        $document = $this->document();
        $corpus = ['schema_version' => 'mati-rag-corpus-1', 'corpus_version' => '1.0.0', 'documents' => [$document]];
        $flat = $store->prepare($corpus);
        $this->assertCount(1, $flat);
        $this->assertSame(hash('sha256', $flat[0]['input']), $flat[0]['input_hash']);
        $reordered = array_reverse($document, true);
        $this->assertSame($store->canonicalHash($document), $store->canonicalHash($reordered));
        $changed = $document; $changed['source'] = 'different-provenance';
        $this->assertNotSame($store->canonicalHash($document), $store->canonicalHash($changed));
    }

    public function test_ingest_never_reads_an_arbitrary_filesystem_path(): void
    {
        config(['rag.corpus_path' => '/etc/passwd']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CURATED_CORPUS_UNAVAILABLE');
        app(RagCorpusStore::class)->corpus();
    }

    public function test_stale_text_hash_is_rejected(): void
    {
        $document = $this->document();
        $document['chunks'][0]['content'] = 'Cambio sin reindexar.';
        $this->expectException(InvalidArgumentException::class);
        app(RagCorpusStore::class)->prepare(['schema_version' => 'mati-rag-corpus-1', 'corpus_version' => '1', 'documents' => [$document]]);
    }

    public function test_inactive_source_cannot_be_silently_reactivated(): void
    {
        $document = $this->document(); $document['status'] = 'inactive';
        $this->expectException(\InvalidArgumentException::class);
        app(RagCorpusStore::class)->prepare(['schema_version' => 'mati-rag-corpus-1', 'corpus_version' => '1', 'documents' => [$document]]);
    }

    public function test_duplicate_document_identity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(RagCorpusStore::class)->prepare(['schema_version' => 'mati-rag-corpus-1', 'corpus_version' => '1', 'documents' => [$this->document(), $this->document()]]);
    }

    public function test_vector_identity_is_separate_even_when_dimensions_match_visual(): void
    {
        $client = app(RagTextClient::class);
        $a = $this->profile(); $a['dimensions'] = 768;
        $b = $a; $b['preprocess_version'] = 'other-text-profile';
        $this->assertNotSame($client->spaceKey($a), $client->spaceKey($b));
        $a['vector_space'] = 'visual'; config(['rag.profile' => $a]);
        $this->expectException(\RuntimeException::class);
        $client->profile();
    }

    public static function invalidVectors(): iterable
    {
        yield 'wrong dimension' => [[1, 0], 3];
        yield 'zero' => [[0, 0, 0], 3];
        yield 'nonfinite' => [[INF, 0, 0], 3];
        yield 'numeric string' => [['1', 0, 0], 3];
        yield 'wrong norm' => [[2, 0, 0], 3];
    }

    #[DataProvider('invalidVectors')]
    public function test_invalid_vectors_fail_closed(array $vector, int $dimensions): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(RagTextClient::class)->validateVector($vector, $dimensions);
    }

    public static function dynamicQueries(): iterable
    {
        foreach (['¿Quedan blusas talla M?', 'precio actual', 'estado de una reserva', 'payment status', 'stock de ZARA', 'inventario hoy'] as $query) yield [$query];
    }

    #[DataProvider('dynamicQueries')]
    public function test_dynamic_facts_require_tools(string $query): void
    {
        $this->assertTrue(app(RagRetrievalPolicy::class)->toolRequired($query));
        Http::preventStrayRequests();
        $result = app(\App\Services\AI\RagRetriever::class)->search($query);
        $this->assertSame('insufficient_evidence', $result['status']);
        $this->assertTrue($result['tool_required']);
        $this->assertSame([], $result['chunks']);
        Http::assertNothingSent();
    }

    public function test_console_prototype_evaluation_requires_explicit_config_and_does_not_change_model_approval(): void
    {
        $profile = $this->profile(); $profile['retrieval_approved'] = false;
        config(['rag.profile' => $profile, 'rag.evaluation_mode' => false,
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (new \ReflectionProperty(\Illuminate\Foundation\Application::class, 'isRunningInConsole'))->setValue(app(), true);
        Http::preventStrayRequests();
        $retriever = app(\App\Services\AI\RagRetriever::class);
        $this->assertSame('RAG_GATE_NOT_APPROVED', $retriever->search('blusa')['reason']);
        config(['rag.evaluation_mode' => true]);
        $this->assertSame('RAG_UNAVAILABLE', $retriever->search('blusa')['reason']);
        $profile['retrieval_approved'] = true;
        config(['rag.profile' => $profile, 'rag.evaluation_mode' => false]);
        $this->assertSame('RAG_UNAVAILABLE', $retriever->search('blusa')['reason']);
        Http::assertNothingSent();
    }

    private function row(float $similarity, string $type = 'taxonomy_category'): array
    {
        return ['chunk_id' => 1, 'document_type' => $type, 'title' => 'BLUSAS',
            'metadata' => ['aliases' => ['blusa']], 'similarity' => $similarity, 'lexical_score' => 1.0];
    }

    public function test_lexical_word_overlap_does_not_rescue_weak_evidence(): void
    {
        $policy = app(RagRetrievalPolicy::class);
        $this->assertSame([], $policy->rank([$this->row(.20)], 'blusas medicina astronauta', .74, 'hybrid', 3));
        $this->assertCount(1, $policy->rank([$this->row(.20)], 'BLÚSAS', .74, 'hybrid', 3));
        $this->assertSame([], $policy->rank([$this->row(.20)], 'BLUSAS', .74, 'vector', 3));
        $this->assertSame([], $policy->rank([$this->row(.20, 'technical_explanation')], 'BLUSAS', .74, 'hybrid', 3));
    }

    public function test_ties_are_stable_and_similarities_are_not_probabilities(): void
    {
        $a = $this->row(.8); $b = $a; $b['chunk_id'] = 2;
        $ranked = app(RagRetrievalPolicy::class)->rank([$b, $a], 'diferencia camisa y blusa', .74, 'hybrid', 3);
        $this->assertSame([1, 2], array_column($ranked, 'chunk_id'));
        $this->assertSame(.8, $ranked[0]['similarity']);
        $this->assertLessThan(1.0, $ranked[0]['ranking_score']);
    }

    public static function responseCases(): iterable
    {
        yield 'valid' => [null, true];
        foreach (['visual_space', 'wrong_revision', 'wrong_dimensions', 'wrong_hash', 'wrong_index', 'truncated', 'missing_norm', 'missing_input_type', 'wrong_input_type'] as $case) yield $case => [$case, false];
    }

    #[DataProvider('responseCases')]
    public function test_text_transport_contract(?string $case, bool $valid): void
    {
        $profile = $this->profile();
        config(['rag.profile' => $profile, 'mati_ai.enabled' => true, 'mati_ai.token' => 'fixture-token', 'mati_ai.url' => 'http://rag.test']);
        Http::preventStrayRequests();
        Http::fake(function (Request $request) use ($profile, $case) {
            $model = app(RagTextClient::class)->identity($profile);
            $inputType = $request['input_type'];
            $entry = ['index' => 0, 'vector' => [1, 0, 0], 'dimensions' => 3, 'norm' => 1.0,
                'text_sha256' => hash('sha256', $request['texts'][0]), 'truncated' => false];
            switch ($case) {
                case 'visual_space': $model['vector_space'] = 'visual'; break;
                case 'wrong_revision': $model['revision'] = str_repeat('b', 40); break;
                case 'wrong_dimensions': $entry['dimensions'] = 768; break;
                case 'wrong_hash': $entry['text_sha256'] = str_repeat('0', 64); break;
                case 'wrong_index': $entry['index'] = 1; break;
                case 'truncated': $entry['truncated'] = true; break;
                case 'missing_norm': unset($entry['norm']); break;
                case 'missing_input_type': $inputType = null; break;
                case 'wrong_input_type': $inputType = 'document'; break;
            }
            return Http::response(['request_id' => $request['request_id'], 'contract_version' => '1', 'status' => 'completed',
                'model' => $model, 'data' => ['model' => $model, 'input_type' => $inputType, 'embeddings' => [$entry]], 'warnings' => []]);
        });
        if (!$valid) $this->expectException(\Throwable::class);
        $entries = app(RagTextClient::class)->embed(['¿Qué es una blusa?'], 'query');
        $this->assertCount(1, $entries);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://rag.test/api/v1/rag/embed'
            && $request->hasHeader('Authorization', 'Bearer fixture-token') && $request['input_type'] === 'query');
    }
}
