<?php

namespace App\Services\AI;

use InvalidArgumentException;
use RuntimeException;

class RagGateV2Model
{
    public const VERSION = 'mati-rag-gate-model-2';
    public function __construct(private RagGateV2Features $features) {}

    public function approved(array $context): array
    {
        $raw = $this->read(config('rag_gate_v2.model_path'));
        $artifact = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        $decision = json_decode($this->read(config('rag_gate_v2.decision_path')), true, 32, JSON_THROW_ON_ERROR);
        if (($decision['schema_version'] ?? null) !== 'mati-rag-gate-decision-2' || ($decision['approved'] ?? null) !== true
            || ($decision['gate_model_sha256'] ?? null) !== hash('sha256', $raw)
            || ($decision['policy_version'] ?? null) !== ($artifact['policy_version'] ?? null)
            || !$this->sameContext($decision['context'] ?? null, $context)) throw new RuntimeException('RAG_GATE_NOT_APPROVED');
        $this->validate($artifact, $context);
        return $artifact;
    }

    public function validate(array $artifact, array $context): void
    {
        if (($artifact['schema_version'] ?? null) !== self::VERSION
            || ($artifact['feature_schema_version'] ?? null) !== RagGateV2Features::VERSION
            || ($artifact['normalization_version'] ?? null) !== RagGateV2Features::NORMALIZATION
            || ($artifact['routing_version'] ?? null) !== RagGateV2Router::VERSION
            || ($artifact['feature_order'] ?? null) !== RagGateV2Features::ORDER
            || !$this->sameContext($artifact['context'] ?? null, $context)
            || !is_string($artifact['policy_version'] ?? null) || !preg_match('/^[a-zA-Z0-9._-]{1,100}$/', $artifact['policy_version'])
            || !in_array($artifact['policy_kind'] ?? null, ['threshold', 'multi_signal', 'logistic_regression'], true)
            || !is_array($artifact['parameters'] ?? null)) throw new InvalidArgumentException('GATE_MODEL_INVALID');
        foreach (['model_id' => 'intfloat/multilingual-e5-small', 'revision' => '614241f622f53c4eeff9890bdc4f31cfecc418b3', 'dimensions' => 384, 'normalization' => 'l2', 'vector_space' => 'text'] as $key => $expected) {
            if (($artifact['text_model'][$key] ?? null) !== $expected) throw new InvalidArgumentException('GATE_TEXT_MODEL_INVALID');
        }
        $p = $artifact['parameters'];
        if ($artifact['policy_kind'] === 'threshold') $this->number($p['threshold'] ?? null, -1, 1);
        if ($artifact['policy_kind'] === 'multi_signal') {
            $this->number($p['vector_min'] ?? null, -1, 1); $this->number($p['overlap_min'] ?? null, 0, 1);
            $this->number($p['lexical_min'] ?? null, 0, 0); $this->number($p['agreement_min'] ?? null, 0, 1);
            if (($p['lexical_operator'] ?? null) !== 'gt' || abs($p['agreement_min'] - 1 / 3) > 1e-12) throw new InvalidArgumentException('GATE_MODEL_INVALID');
        }
        if ($artifact['policy_kind'] === 'logistic_regression') {
            foreach (['coefficients', 'scaler_mean', 'scaler_scale'] as $key) {
                if (!is_array($p[$key] ?? null) || !array_is_list($p[$key]) || count($p[$key]) !== 6) throw new InvalidArgumentException('GATE_MODEL_DIMENSIONS_INVALID');
                foreach ($p[$key] as $value) $this->number($value);
            }
            foreach ($p['scaler_scale'] as $scale) if ($scale <= 0) throw new InvalidArgumentException('GATE_MODEL_SCALE_INVALID');
            $this->number($p['intercept'] ?? null); $this->number($p['threshold'] ?? null, 0, 1);
        }
    }

    public function evaluate(array $artifact, array $values, int $vectorCount, array $context): array
    {
        $this->validate($artifact, $context); $this->features->validate($values);
        if ($vectorCount < 0 || $vectorCount > 3) throw new InvalidArgumentException('GATE_RETRIEVAL_INVALID');
        if ($vectorCount === 0) return ['decision' => 'not_answerable', 'score' => null, 'score_kind' => 'no_evidence', 'policy_version' => $artifact['policy_version']];
        $p = $artifact['parameters'];
        if ($artifact['policy_kind'] === 'threshold') {
            $score = $values['vector_top1']; $accept = $score >= $p['threshold']; $kind = 'cosine_similarity_uncalibrated';
        } elseif ($artifact['policy_kind'] === 'multi_signal') {
            $accept = $values['vector_top1'] >= $p['vector_min'] && ($values['exact_taxonomy_name_match'] == 1
                || ($values['lexical_top1'] > 0 && $values['normalized_keyword_overlap'] >= $p['overlap_min']
                    && $values['vector_lexical_agreement'] >= $p['agreement_min']));
            $score = $accept ? 1.0 : 0.0; $kind = 'deterministic_rule_indicator';
        } else {
            $z = $p['intercept'];
            foreach (array_values($values) as $i => $value) $z += (($value - $p['scaler_mean'][$i]) / $p['scaler_scale'][$i]) * $p['coefficients'][$i];
            if (!is_finite($z)) throw new InvalidArgumentException('GATE_MODEL_NONFINITE');
            $score = $z >= 0 ? 1 / (1 + exp(-$z)) : exp($z) / (1 + exp($z));
            $accept = $score >= $p['threshold']; $kind = 'answerability_score_uncalibrated';
        }
        return ['decision' => $accept ? 'answerable' : 'not_answerable', 'score' => (float) $score,
            'score_kind' => $kind, 'policy_version' => $artifact['policy_version']];
    }

    private function sameContext(mixed $actual, array $expected): bool
    {
        if (!is_array($actual)) return false;
        foreach ($actual as $value) if (!is_string($value) || !preg_match('/^[a-f0-9]{64}$/', $value)) return false;
        ksort($actual); ksort($expected);
        return $actual === $expected;
    }

    private function number(mixed $value, float $minimum = -INF, float $maximum = INF): void
    {
        if ((!is_float($value) && !is_int($value)) || !is_finite((float) $value) || $value < $minimum || $value > $maximum) throw new InvalidArgumentException('GATE_MODEL_NONFINITE');
    }

    private function read(string $path): string
    {
        if (!is_file($path) || filesize($path) > 131072) throw new RuntimeException('RAG_GATE_NOT_APPROVED');
        return file_get_contents($path);
    }
}
