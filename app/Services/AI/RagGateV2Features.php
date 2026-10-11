<?php

namespace App\Services\AI;

use InvalidArgumentException;
use RuntimeException;

class RagGateV2Features
{
    public const VERSION = 'mati-rag-gate-features-2';
    public const NORMALIZATION = 'mati-rag-query-normalization-2';
    public const ORDER = ['vector_top1', 'margin_1_2', 'lexical_top1', 'vector_lexical_agreement', 'exact_taxonomy_name_match', 'normalized_keyword_overlap'];

    public function __construct(private RagRetrievalPolicy $normalizer) {}

    public function schema(): array
    {
        if (!is_file(config('rag_gate_v2.feature_schema_path'))) throw new RuntimeException('GATE_FEATURE_SCHEMA_UNAVAILABLE');
        $schema = json_decode(file_get_contents(config('rag_gate_v2.feature_schema_path')), true, 32, JSON_THROW_ON_ERROR);
        if (($schema['schema_version'] ?? null) !== self::VERSION || ($schema['normalization_version'] ?? null) !== self::NORMALIZATION
            || ($schema['feature_order'] ?? null) !== self::ORDER || !is_array($schema['stopwords'] ?? null)) throw new RuntimeException('GATE_FEATURE_SCHEMA_INVALID');
        return $schema;
    }

    public function query(string $query): string
    {
        if (trim($query) === '' || !mb_check_encoding($query, 'UTF-8') || mb_strlen($query) > 1000) throw new InvalidArgumentException('RAG_QUERY_INVALID');
        return $this->normalizer->normalized($query);
    }

    public function usefulTokens(string $text): array
    {
        $tokens = preg_split('/\s+/u', $this->normalizer->normalized($text), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_diff($tokens, $this->schema()['stopwords'])));
    }

    public function compute(string $query, array $vector, array $lexical, array $taxonomy): array
    {
        $normalized = $this->query($query);
        $this->validateRows($vector, false);
        $this->validateRows($lexical, true);
        $scores = array_pad(array_column($vector, 'similarity'), 3, 0.0);
        $lexicalScore = $lexical[0]['lexical_score'] ?? 0.0;
        $agreement = count(array_intersect(array_column($vector, 'chunk_id'), array_column($lexical, 'chunk_id'))) / 3;
        $exact = 0;
        foreach ($taxonomy as $document) {
            if (($document['document_type'] ?? null) !== 'taxonomy_category') continue;
            foreach ([$document['title'], ...($document['metadata']['aliases'] ?? [])] as $alias) {
                $normalizedAlias = $this->normalizer->normalized($alias);
                if ($normalizedAlias !== '' && str_contains(' '.$normalized.' ', ' '.$normalizedAlias.' ')) $exact = 1;
            }
        }
        $queryTokens = $this->usefulTokens($query); $documentTokens = [];
        foreach ($vector as $row) array_push($documentTokens, ...$this->usefulTokens($row['search_text']));
        $overlap = $queryTokens ? count(array_intersect($queryTokens, array_unique($documentTokens))) / count($queryTokens) : 0.0;
        $features = array_combine(self::ORDER, [(float) $scores[0], (float) max(0, $scores[0] - $scores[1]),
            (float) $lexicalScore, $agreement, $exact, $overlap]);
        $this->validate($features);
        return $features;
    }

    public function validate(array $features): void
    {
        if (array_keys($features) !== self::ORDER) throw new InvalidArgumentException('GATE_FEATURE_ORDER_INVALID');
        foreach ($features as $name => $value) {
            if ((!is_float($value) && !is_int($value)) || !is_finite((float) $value)) throw new InvalidArgumentException('GATE_FEATURE_NONFINITE');
            $valid = match ($name) {
                'vector_top1' => $value >= -1.000001 && $value <= 1.000001,
                'margin_1_2' => $value >= 0 && $value <= 2.000002,
                'lexical_top1' => $value >= 0,
                'exact_taxonomy_name_match' => (float) $value === 0.0 || (float) $value === 1.0,
                default => $value >= 0 && $value <= 1,
            };
            if (!$valid) throw new InvalidArgumentException('GATE_FEATURE_RANGE_INVALID');
        }
    }

    private function validateRows(array $rows, bool $lexical): void
    {
        if (!array_is_list($rows) || count($rows) > 3) throw new InvalidArgumentException('GATE_RETRIEVAL_INVALID');
        $ids = []; $previous = null;
        foreach ($rows as $row) {
            $score = $row[$lexical ? 'lexical_score' : 'similarity'] ?? null;
            if (!is_int($row['chunk_id'] ?? null) || $row['chunk_id'] < 1 || isset($ids[$row['chunk_id']])
                || !is_string($row['search_text'] ?? null) || !mb_check_encoding($row['search_text'], 'UTF-8')
                || (!is_float($score) && !is_int($score)) || !is_finite((float) $score)
                || ($lexical ? $score <= 0 : ($score < -1.000001 || $score > 1.000001))) throw new InvalidArgumentException('GATE_RETRIEVAL_INVALID');
            if ($previous !== null && ($score > $previous['score'] || ((float) $score === $previous['score'] && $row['chunk_id'] < $previous['id']))) throw new InvalidArgumentException('GATE_RETRIEVAL_ORDER_INVALID');
            $ids[$row['chunk_id']] = true; $previous = ['score' => (float) $score, 'id' => $row['chunk_id']];
        }
    }
}
