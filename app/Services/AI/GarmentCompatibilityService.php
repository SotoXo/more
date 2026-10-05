<?php

namespace App\Services\AI;

/**
 * Compare existing data.colorimetry snapshots from garments/analyze.
 * This is a pair-ranking signal; callers must handle null scores and degraded responses.
 */
class GarmentCompatibilityService
{
    public const RULES_VERSION = 'garment-compatibility-1.0.0';

    public function __construct(private readonly MatiAiClient $client) {}

    /**
     * Patterns are optional catalog evidence: solid, printed, striped, checkered or unknown.
     * No image extraction, persistence or outfit generation occurs here.
     */
    public function compare(
        array $colorimetryA,
        array $colorimetryB,
        string $patternA = 'unknown',
        string $patternB = 'unknown',
        ?string $requestId = null,
    ): array {
        return $this->client->request('compatibility', [
            'garment_a' => $colorimetryA,
            'garment_b' => $colorimetryB,
            'rules_version' => self::RULES_VERSION,
            'pattern_a' => $patternA,
            'pattern_b' => $patternB,
        ], requestId: $requestId);
    }
}
