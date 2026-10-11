<?php

namespace App\Services\AI;

use App\Models\RagDocument;
use App\Models\RagModelVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RagGateV2Evidence
{
    public function __construct(private RagTextClient $client, private RagCorpusStore $corpusStore, private RagGateV2Features $features) {}

    public function context(): array
    {
        $profile = $this->client->profile();
        if ($profile['model_id'] !== 'intfloat/multilingual-e5-small'
            || $profile['revision'] !== '614241f622f53c4eeff9890bdc4f31cfecc418b3'
            || $profile['dimensions'] !== 384) throw new RuntimeException('GATE_TEXT_CONTEXT_INVALID');
        $paths = [
            'config/rag_gate_v2.php', 'app/Http/Controllers/Superadmin/RagController.php',
            'app/Services/AI/RagGateV2Features.php', 'app/Services/AI/RagGateV2Router.php',
            'app/Services/AI/RagGateV2Evidence.php', 'app/Services/AI/RagGateV2Model.php',
            'app/Services/AI/RagGateV2Retriever.php', 'app/Services/AI/RagTextClient.php',
            'app/Services/AI/RagRetrievalPolicy.php', 'app/Services/AI/RagCorpusStore.php',
            'services/mati-ai/scripts/gate_v2/extract_features.php',
        ];
        $hashes = [];
        foreach ($paths as $path) $hashes[$path] = $this->hash(base_path($path));
        ksort($hashes);
        return [
            'corpus_sha256' => $this->hash(config('rag.corpus_path')),
            'profile_sha256' => $this->hash(config('rag.profile_path')),
            'text_space_key' => $this->client->spaceKey($profile),
            'feature_schema_sha256' => $this->hash(config('rag_gate_v2.feature_schema_path')),
            'routing_rules_sha256' => $this->hash(config('rag_gate_v2.routing_rules_path')),
            'runtime_bundle_sha256' => hash('sha256', json_encode($hashes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];
    }

    public function extract(string $query): array
    {
        $started = microtime(true);
        $normalized = $this->features->query($query);
        if (DB::connection()->getDriverName() !== 'pgsql') throw new RuntimeException('GATE_STORAGE_UNAVAILABLE');
        $profile = $this->client->profile();
        $context = $this->context();
        $corpus = $this->corpusStore->corpus();
        $model = RagModelVersion::where('space_key', $this->client->spaceKey($profile))->first();
        if (!$model || $model->dimensions !== 384) throw new RuntimeException('GATE_TEXT_SPACE_UNAVAILABLE');
        $this->scope($corpus, $model->id);
        $embedStarted = microtime(true);
        $entry = $this->client->embed([$query], 'query', $profile)[0];
        $embedMs = (microtime(true) - $embedStarted) * 1000;
        $vectorStarted = microtime(true);
        $vector = $this->vectorRows($entry['vector'], $model->id);
        $vectorMs = (microtime(true) - $vectorStarted) * 1000;
        $lexicalStarted = microtime(true);
        $lexical = $this->lexicalRows($query, $model->id);
        $lexicalMs = (microtime(true) - $lexicalStarted) * 1000;
        $featuresStarted = microtime(true);
        $values = $this->features->compute($query, $vector, $lexical, $corpus['documents']);
        $featureMs = (microtime(true) - $featuresStarted) * 1000;
        return [
            'schema_version' => RagGateV2Features::VERSION, 'feature_order' => RagGateV2Features::ORDER,
            'query_sha256' => hash('sha256', $query), 'normalized_query' => $normalized, 'context' => $context,
            'query_vector' => $entry['vector'], 'features' => $values, 'vector_top3' => $vector, 'lexical_top3' => $lexical,
            'timing' => ['embed_ms' => $embedMs, 'vector_sql_ms' => $vectorMs, 'lexical_sql_ms' => $lexicalMs,
                'features_ms' => $featureMs, 'gate_ms' => 0.0, 'total_ms' => (microtime(true) - $started) * 1000],
        ];
    }

    public function vectorRows(array $vector, int $modelId): array
    {
        $this->client->validateVector($vector, 384);
        $encoded = json_encode($vector, JSON_THROW_ON_ERROR);
        $sql = $this->compatibleSql()." SELECT *,1-(embedding <=> CAST(? AS vector)) AS similarity
            FROM compatible ORDER BY embedding <=> CAST(? AS vector),chunk_id LIMIT 3";
        return $this->rows(DB::select($sql, [$modelId, $encoded, $encoded]));
    }

    public function lexicalRows(string $query, int $modelId): array
    {
        $this->features->query($query);
        $sql = $this->compatibleSql().", lexical AS (
            SELECT *,ts_rank_cd(to_tsvector('spanish',search_text),websearch_to_tsquery('spanish',?)) AS lexical_score
            FROM compatible) SELECT * FROM lexical WHERE lexical_score>0 ORDER BY lexical_score DESC,chunk_id LIMIT 3";
        return $this->rows(DB::select($sql, [$modelId, $query]));
    }

    private function compatibleSql(): string
    {
        return "WITH compatible AS MATERIALIZED (
            SELECT e.embedding,c.id AS chunk_id,c.chunk_key,c.section,c.content,c.content_hash,c.search_text,
                d.id AS document_id,d.document_key,d.document_type,d.title,d.source,d.source_version,d.corpus_version,d.metadata
            FROM rag_embeddings e JOIN rag_chunks c ON c.id=e.chunk_id JOIN rag_documents d ON d.id=c.document_id
            WHERE e.model_version_id=? AND e.dimensions=384 AND e.status='active' AND d.status='active'
        )";
    }

    private function rows(array $rows): array
    {
        return array_map(function ($row) {
            $result = (array) $row;
            unset($result['embedding']);
            $result['chunk_id'] = (int) $result['chunk_id']; $result['document_id'] = (int) $result['document_id'];
            $result['metadata'] = is_array($result['metadata']) ? $result['metadata'] : json_decode($result['metadata'], true, 32, JSON_THROW_ON_ERROR);
            foreach (['similarity', 'lexical_score'] as $key) if (isset($result[$key])) $result[$key] = (float) $result[$key];
            return $result;
        }, $rows);
    }

    private function hash(string $path): string
    {
        if (!is_file($path)) throw new RuntimeException('GATE_CONTEXT_UNAVAILABLE');
        return hash_file('sha256', $path);
    }

    private function scope(array $corpus, int $modelId): void
    {
        if (count($corpus['documents']) !== 23 || count($this->corpusStore->prepare($corpus)) !== 23) throw new RuntimeException('GATE_CORPUS_SCOPE_INVALID');
        $stored = RagDocument::where('status', 'active')->get();
        $expected = collect($corpus['documents'])->keyBy('id');
        if ($stored->count() !== 23 || !$stored->every(fn ($doc) => $doc->corpus_version === $corpus['corpus_version']
            && $expected->has($doc->document_key) && $doc->snapshot_hash === $this->corpusStore->canonicalHash($expected[$doc->document_key]))) throw new RuntimeException('GATE_CORPUS_SCOPE_INVALID');
        $flat = $this->corpusStore->prepare($corpus);
        $units = [];
        foreach ($flat as $unit) $units[$unit['document']['id']."\0".$unit['chunk']['id']] = $unit;
        $chunks = DB::table('rag_chunks AS c')->join('rag_documents AS d', 'd.id', '=', 'c.document_id')
            ->where('d.status', 'active')->get(['d.document_key', 'c.chunk_key', 'c.chunk_index', 'c.section', 'c.content', 'c.content_hash', 'c.search_text']);
        if ($chunks->count() !== 23) throw new RuntimeException('GATE_CORPUS_SCOPE_INVALID');
        foreach ($chunks as $chunk) {
            $unit = $units[$chunk->document_key."\0".$chunk->chunk_key] ?? null;
            if (!$unit || (int) $chunk->chunk_index !== $unit['index'] || $chunk->section !== $unit['chunk']['section']
                || $chunk->content !== $unit['chunk']['content'] || $chunk->content_hash !== $unit['chunk']['content_hash']
                || hash('sha256', $chunk->content) !== $chunk->content_hash
                || $chunk->search_text !== $unit['document']['title'].' '.implode(' ', $unit['document']['metadata']['aliases'] ?? []).' '.$unit['chunk']['section'].' '.$unit['chunk']['content']) throw new RuntimeException('GATE_CORPUS_SCOPE_INVALID');
        }
        $count = DB::table('rag_embeddings AS e')->join('rag_chunks AS c', 'c.id', '=', 'e.chunk_id')
            ->join('rag_documents AS d', 'd.id', '=', 'c.document_id')->where('e.model_version_id', $modelId)
            ->where('e.dimensions', 384)->where('e.status', 'active')->where('d.status', 'active')->count();
        if ($count !== 23) throw new RuntimeException('GATE_CORPUS_SCOPE_INVALID');
    }
}
