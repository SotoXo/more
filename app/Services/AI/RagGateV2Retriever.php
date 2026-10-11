<?php

namespace App\Services\AI;

use Illuminate\Support\Str;
use InvalidArgumentException;

class RagGateV2Retriever
{
    public function __construct(private RagGateV2Evidence $evidence, private RagGateV2Features $features,
        private RagGateV2Router $router, private RagGateV2Model $model) {}

    public function search(string $query, int $limit = 3, string $mode = 'hybrid', array $filters = []): array
    {
        $this->features->query($query);
        if ($limit < 1 || $limit > 10 || !in_array($mode, ['vector', 'hybrid'], true)) throw new InvalidArgumentException('RAG_QUERY_INVALID');
        $start = microtime(true);
        $base = ['schema_version' => 'mati-rag-search-2', 'query_id' => (string) Str::uuid(),
            'status' => 'insufficient_evidence', 'route' => 'required_information', 'mode' => $mode, 'chunks' => [],
            'score_kind' => 'cosine_similarity_uncalibrated', 'ranking_score_kind' => 'cosine_similarity',
            'generated_answer' => false, 'tool_required' => false,
            'answerability' => ['decision' => 'not_answerable', 'score' => null, 'policy_version' => null]];
        try {
            $context = $this->evidence->context();
            $artifact = $this->model->approved($context);
        } catch (\Throwable) {
            return $base + ['reason' => 'RAG_GATE_NOT_APPROVED', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000],
                'warnings' => ['El gate de relevancia V2 no está aprobado para este contexto.']];
        }
        $base['answerability']['policy_version'] = $artifact['policy_version'];
        if ($limit !== 3 || $filters) return $base + ['reason' => 'GATE_REQUEST_SCOPE_UNSUPPORTED', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000], 'warnings' => []];
        try {
            $routing = $this->router->route($query);
            if ($routing['route'] !== 'candidate_rag') {
                $base['route'] = $routing['route']; $base['tool_required'] = $routing['route'] === 'tool';
                $base['status'] = $base['tool_required'] ? 'requires_tool' : 'insufficient_evidence';
                return $base + ['reason' => $routing['reason'], 'required_tool' => $routing['required_tool'],
                    'timing' => ['total_ms' => (microtime(true) - $start) * 1000], 'warnings' => []];
            }
            $result = $this->evidence->extract($query);
            if ($result['context'] !== $context) throw new \RuntimeException('GATE_CONTEXT_STALE');
            $gateStart = microtime(true);
            $decision = $this->model->evaluate($artifact, $result['features'], count($result['vector_top3']), $context);
            $result['timing']['gate_ms'] = (microtime(true) - $gateStart) * 1000;
            $base['answerability'] = $decision + ['feature_schema_version' => RagGateV2Features::VERSION, 'evidence' => $result['features']];
            if ($decision['decision'] === 'answerable') {
                $base['route'] = 'rag'; $base['status'] = 'completed';
                foreach ($result['vector_top3'] as $rank => $row) $base['chunks'][] = $this->chunk($row, $rank + 1);
            }
            $result['timing']['total_ms'] = (microtime(true) - $start) * 1000;
            return $base + ['reason' => $base['chunks'] ? null : 'NO_RELEVANT_EVIDENCE', 'timing' => $result['timing'], 'warnings' => []];
        } catch (\Throwable) {
            $base['route'] = 'required_information'; $base['status'] = 'insufficient_evidence';
            $base['chunks'] = []; $base['tool_required'] = false;
            $base['answerability'] = ['decision' => 'not_answerable', 'score' => null, 'policy_version' => $artifact['policy_version']];
            return $base + ['reason' => 'RAG_GATE_UNAVAILABLE', 'timing' => ['total_ms' => (microtime(true) - $start) * 1000], 'warnings' => ['No hay evidencia utilizable disponible.']];
        }
    }

    public function chunk(array $row, int $rank): array
    {
        return array_intersect_key($row, array_flip(['document_id', 'document_key', 'chunk_id', 'chunk_key', 'document_type', 'title',
            'source', 'source_version', 'corpus_version', 'section', 'content_hash']))
            + ['excerpt' => mb_substr($row['content'], 0, 1600), 'review_status' => $row['metadata']['review_status'] ?? 'unspecified',
                'similarity' => $row['similarity'], 'ranking_score' => $row['similarity'], 'rank' => $rank];
    }
}
