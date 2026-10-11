<?php

namespace App\Services\AI;

use RuntimeException;

class RagGateV2Router
{
    public const VERSION = 'mati-rag-routing-2';
    public function __construct(private RagGateV2Features $features) {}

    public function route(string $query): array
    {
        $text = $this->features->query($query);
        if (!is_file(config('rag_gate_v2.routing_rules_path'))) throw new RuntimeException('GATE_ROUTING_UNAVAILABLE');
        $rules = json_decode(file_get_contents(config('rag_gate_v2.routing_rules_path')), true, 32, JSON_THROW_ON_ERROR);
        if (($rules['schema_version'] ?? null) !== 'mati-rag-routing-rules-2' || ($rules['policy_version'] ?? null) !== self::VERSION
            || ($rules['normalization_version'] ?? null) !== RagGateV2Features::NORMALIZATION) throw new RuntimeException('GATE_ROUTING_INVALID');
        if (in_array($text, $rules['ambiguous_exact'], true)) return ['route' => 'required_information', 'reason' => 'AMBIGUOUS_QUERY', 'required_tool' => null];
        $matches = function (string $pattern) use ($text): bool {
            $result = preg_match('~'.$pattern.'~u', $text);
            if ($result === false) throw new RuntimeException('GATE_ROUTING_INVALID');
            return $result === 1;
        };
        if ($matches($rules['meta_concept_pattern']) && $matches($rules['meta_question_pattern']) && !$matches($rules['direct_value_pattern'])) {
            return ['route' => 'candidate_rag', 'reason' => 'CONCEPTUAL_META_QUERY', 'required_tool' => null];
        }
        $garment = $matches($rules['garment_pattern']);
        $refs = [];
        foreach ($rules['reference_patterns'] as $key => $pattern) $refs[$key] = $matches($pattern);
        foreach ($rules['rules'] as $rule) {
            if (!$matches($rule['signal'])) continue;
            $reference = match ($rule['reference']) {
                'garment' => $garment,
                'garment_or_inventory' => $garment || $refs['inventory'],
                'current_or_garment', 'garment_or_current' => $garment || $refs['current'],
                'garment_or_explicit_size' => $garment || $refs['explicit_size'],
                'garment_or_ownership' => $garment || $refs['ownership'],
                'ownership_or_state' => $refs['ownership'] || $refs['state'],
                'garment_or_order_ownership' => $garment || ($refs['ownership'] && $refs['order']),
                default => throw new RuntimeException('GATE_ROUTING_INVALID'),
            };
            if ($reference) return ['route' => 'tool', 'reason' => 'DYNAMIC_'.$rule['id'], 'required_tool' => $rule['required_tool']];
        }
        return ['route' => 'candidate_rag', 'reason' => null, 'required_tool' => null];
    }
}
