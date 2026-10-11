<?php

namespace App\Services\AI;

use App\Models\RagModelVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RagRetriever
{
    public function __construct(private RagTextClient $client, private RagRetrievalPolicy $policy) {}

    public function search(string $query, int $limit = 3, string $mode = 'hybrid', array $filters = []): array
    {
        if (trim($query) === '' || !mb_check_encoding($query, 'UTF-8') || mb_strlen($query) > config('rag.max_query_chars', 1000)
            || $limit < 1 || $limit > config('rag.max_limit', 10) || !in_array($mode, ['vector', 'hybrid'], true)) {
            throw new InvalidArgumentException('RAG_QUERY_INVALID');
        }
        $id = (string) Str::uuid();
        $start = microtime(true);
        $base = ['query_id' => $id, 'status' => 'insufficient_evidence', 'chunks' => [], 'mode' => $mode,
            'score_kind' => 'cosine_similarity_uncalibrated', 'ranking_score_kind' => $mode === 'hybrid' ? 'reciprocal_rank_fusion' : 'cosine_similarity',
            'generated_answer' => false, 'tool_required' => false];
        if ($this->policy->toolRequired($query)) {
            $base['tool_required'] = true;
            return $base + ['reason' => 'DYNAMIC_FACT_REQUIRES_TOOL', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000], 'warnings' => ['Consulta los datos actuales mediante Laravel/DB.']];
        }
        try {
            $profile = $this->client->profile();
            $evaluation = config('rag.evaluation_mode') === true && app()->runningInConsole();
            if (($profile['retrieval_approved'] ?? false) !== true && !$evaluation) {
                return $base + ['reason' => 'RAG_GATE_NOT_APPROVED', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000],
                    'warnings' => ['El recuperador no ha superado el gate de calidad; no se presentan fragmentos.']];
            }
            if (DB::connection()->getDriverName() !== 'pgsql' || !Schema::hasTable('rag_embeddings')) throw new \RuntimeException('RAG_STORAGE_UNAVAILABLE');
            $model = RagModelVersion::where('space_key', $this->client->spaceKey($profile))->first();
            if (!$model) throw new \RuntimeException('TEXT_SPACE_NOT_INDEXED');
            $embedStart = microtime(true);
            $embedding = $this->client->embed([$query], 'query', $profile)[0];
            $embedMs = (microtime(true) - $embedStart) * 1000;
            $searchStart = microtime(true);
            $rows = $this->nearest($embedding['vector'], $model->id, $profile['dimensions'], $query, $filters);
            $ranked = $this->policy->rank($rows, $query, (float) $profile['minimum_cosine'], $mode, $limit);
            foreach ($ranked as $rank => $row) {
                $base['chunks'][] = [
                    'document_id' => $row['document_id'], 'document_key' => $row['document_key'], 'chunk_id' => $row['chunk_id'],
                    'chunk_key' => $row['chunk_key'], 'document_type' => $row['document_type'],
                    'title' => $row['title'], 'excerpt' => mb_substr($row['content'], 0, 1600),
                    'source' => $row['source'], 'source_version' => $row['source_version'],
                    'corpus_version' => $row['corpus_version'], 'section' => $row['section'],
                    'content_hash' => $row['content_hash'], 'review_status' => $row['metadata']['review_status'] ?? 'unspecified',
                    'similarity' => $row['similarity'],
                    'rank' => $rank + 1, 'ranking_score' => $row['ranking_score'],
                ];
            }
            $base['status'] = $base['chunks'] ? 'completed' : 'insufficient_evidence';
            return $base + ['reason' => $base['chunks'] ? null : 'NO_RELEVANT_EVIDENCE', 'model' => $this->client->identity($profile),
                'timing' => ['embed_ms' => $embedMs, 'search_ms' => (microtime(true) - $searchStart) * 1000, 'total_ms' => (microtime(true) - $start) * 1000], 'warnings' => []];
        } catch (\Throwable) {
            // No query text, source body, secrets or exception messages in logs/responses.
            return $base + ['reason' => 'RAG_UNAVAILABLE', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000], 'warnings' => ['No hay evidencia recuperable disponible.']];
        }
    }

    public function nearest(array $vector, int $modelId, int $dimensions, string $query, array $filters = []): array
    {
        $this->client->validateVector($vector, $dimensions);
        if (array_diff(array_keys($filters), ['document_type', 'source_version', 'corpus_version'])) throw new InvalidArgumentException('RAG_FILTER_INVALID');
        $conditions = ["e.model_version_id=?", "e.dimensions=?", "e.status='active'", "d.status='active'"];
        $bindings = [$modelId, $dimensions];
        foreach ($filters as $key => $value) {
            if (!is_string($value) || $value === '' || mb_strlen($value) > 100) throw new InvalidArgumentException('RAG_FILTER_INVALID');
            $conditions[] = "d.$key=?";
            $bindings[] = $value;
        }
        $sql = "WITH compatible AS MATERIALIZED (
            SELECT e.embedding,c.id AS chunk_id,c.chunk_key,c.section,c.content,c.content_hash,c.search_text,
                d.id AS document_id,d.document_key,d.document_type,d.title,d.source,d.source_version,d.corpus_version,d.metadata
            FROM rag_embeddings e JOIN rag_chunks c ON c.id=e.chunk_id JOIN rag_documents d ON d.id=c.document_id
            WHERE ".implode(' AND ', $conditions)."
        ) SELECT *,1-(embedding <=> CAST(? AS vector)) AS similarity,
            ts_rank_cd(to_tsvector('spanish',search_text),websearch_to_tsquery('spanish',?)) AS lexical_score
        FROM compatible ORDER BY embedding <=> CAST(? AS vector),chunk_id";
        $encoded = json_encode($vector, JSON_THROW_ON_ERROR);
        $rows = DB::select($sql, [...$bindings, $encoded, $query, $encoded]);
        return array_map(function ($row) {
            $result = (array) $row;
            unset($result['embedding'], $result['search_text']);
            $result['metadata'] = is_array($result['metadata']) ? $result['metadata'] : json_decode($result['metadata'], true, 64, JSON_THROW_ON_ERROR);
            $result['similarity'] = (float) $result['similarity'];
            $result['lexical_score'] = (float) $result['lexical_score'];
            return $result;
        }, $rows);
    }
}
