<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatiAiClient
{
    private const PATHS = [
        'capabilities' => '/api/v1/capabilities',
        'analyze' => '/api/v1/garments/analyze',
        'compatibility' => '/api/v1/garments/compatibility',
        'embedding' => '/api/v1/garments/embedding',
        'barcode' => '/api/v1/identification/barcode',
        'label' => '/api/v1/identification/label',
        'match' => '/api/v1/products/match',
        'external-search' => '/api/v1/products/external-search',
        'external-image' => '/api/v1/products/external-image',
        'prepare-publication' => '/api/v1/images/prepare-publication',
    ];

    public function request(string $operation, array $payload = [], ?string $image = null, ?string $requestId = null): array
    {
        $requestId ??= (string) Str::uuid();
        $colorCatalog = is_array($payload['colors'] ?? null) ? $payload['colors'] : [];
        $start = microtime(true);
        $result = $this->fallback($requestId, 'SERVICE_UNAVAILABLE');
        try {
            if (!config('mati_ai.enabled') || blank(config('mati_ai.token'))) {
                return $result = $this->fallback($requestId, 'SERVICE_NOT_CONFIGURED');
            }
            if (!isset(self::PATHS[$operation])) {
                return $result = $this->fallback($requestId, 'INVALID_OPERATION');
            }
            $client = Http::baseUrl(rtrim(config('mati_ai.url'), '/'))
                ->withToken(config('mati_ai.token'))
                ->withHeaders(['X-Request-Id' => $requestId, 'X-Contract-Version' => '1'])
                ->acceptJson()
                ->connectTimeout(max(1, config('mati_ai.connect_timeout')))
                ->timeout(max(1, config('mati_ai.timeout')))
                ->withoutRedirecting();
            $payload['request_id'] = $requestId;
            if ($image !== null) {
                $client = $client->attach('image', $image, 'capture.img');
                $payload = array_map(fn ($value) => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value), $payload);
            }
            $response = $operation === 'capabilities'
                ? $client->get(self::PATHS[$operation])
                : $client->post(self::PATHS[$operation], $payload);
            $body = $response->json();
            if (!$response->successful()) {
                $code = is_array($body) ? data_get($body, 'error.code', 'SERVICE_UNAVAILABLE') : 'SERVICE_UNAVAILABLE';
                $result = $this->fallback($requestId, preg_match('/^[A-Z_]{3,80}$/', (string) $code) ? $code : 'SERVICE_UNAVAILABLE');
            } elseif (!$this->validResponse($body, $requestId, $operation)) {
                $result = $this->fallback($requestId, 'INVALID_RESPONSE');
            } else {
                $result = $operation === 'analyze'
                    ? app(ColorimetryCatalogGuard::class)->normalize($body, $colorCatalog)
                    : $body;
            }
        } catch (ConnectionException) {
            $result = $this->fallback($requestId, 'CONNECTION_TIMEOUT');
        } catch (\Throwable) {
            // Never include payload, OCR text, credentials or exception messages in technical logs.
            $result = $this->fallback($requestId, 'SERVICE_UNAVAILABLE');
        } finally {
            Log::info('mati_ai.request', [
                'request_id' => $requestId, 'operation' => $operation,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                'model' => data_get($result, 'model.model_id'),
                'status' => $result['status'], 'error_code' => data_get($result, 'error.code'),
            ]);
        }

        return $result;
    }

    private function validResponse(mixed $body, string $requestId, string $operation): bool
    {
        return is_array($body)
            && ($body['request_id'] ?? null) === $requestId
            && ($body['contract_version'] ?? null) === '1'
            && in_array($body['status'] ?? '', ['completed', 'partial', 'insufficient_evidence', 'degraded'], true)
            && is_array($body['data'] ?? null)
            && is_array($body['warnings'] ?? null)
            && is_array($body['model'] ?? null)
            && is_string(data_get($body, 'model.model_id'))
            && is_string(data_get($body, 'model.model_revision'))
            && is_string(data_get($body, 'model.pipeline_version'))
            && match ($operation) {
                'capabilities' => is_array(data_get($body, 'data.operations')),
                'analyze' => is_array(data_get($body, 'data.quality')) && is_array(data_get($body, 'data.colors')) && is_array(data_get($body, 'data.classification.top_k')),
                'compatibility' => data_get($body, 'data.status') === $body['status']
                    && $this->validCompatibilityResponse($body['data']),
                'embedding' => is_array(data_get($body, 'data.image'))
                    && is_array(data_get($body, 'data.embedding.values'))
                    && is_int(data_get($body, 'data.embedding.dimension'))
                    && is_string(data_get($body, 'data.embedding.model_id'))
                    && is_string(data_get($body, 'data.embedding.model_revision'))
                    && is_string(data_get($body, 'data.embedding.pipeline_version')),
                'barcode' => is_array(data_get($body, 'data.codes')),
                'label' => is_string(data_get($body, 'data.raw_text')) && is_string(data_get($body, 'data.normalized_text')),
                'match' => is_array(data_get($body, 'data.matches')),
                'external-search' => is_array(data_get($body, 'data.candidates')),
                'external-image' => is_string(data_get($body, 'data.image_base64')) && is_string(data_get($body, 'data.sha256')),
                'prepare-publication' => is_array(data_get($body, 'data.derivatives')) && count($body['data']['derivatives']) > 0,
                default => false,
            };
    }

    private function validCompatibilityResponse(array $data): bool
    {
        if (($data['version'] ?? null) !== GarmentCompatibilityService::RULES_VERSION
            || ($data['rules_version'] ?? null) !== GarmentCompatibilityService::RULES_VERSION
            || ($data['score_kind'] ?? null) !== 'heuristic_ranking_signal'
            || !in_array($data['status'] ?? null, ['completed', 'partial', 'insufficient_evidence'], true)
            || !array_key_exists('compatibility_score', $data)
            || !is_array($data['components'] ?? null)
            || !is_array($data['evidence'] ?? null)) {
            return false;
        }

        $score = $data['compatibility_score'];
        if ($data['status'] === 'insufficient_evidence') {
            if ($score !== null) return false;
        } elseif (!$this->validCompatibilityScore($score)) {
            return false;
        }
        foreach (['temperature', 'lightness', 'hue', 'contrast'] as $component) {
            if (!array_key_exists($component, $data['components'])
                || ($data['components'][$component] !== null
                    && !$this->validCompatibilityScore($data['components'][$component]))) {
                return false;
            }
        }
        foreach (['harmony_types', 'explanations', 'warnings'] as $field) {
            if (!is_array($data[$field] ?? null) || !array_is_list($data[$field])) return false;
            foreach ($data[$field] as $value) {
                if (!is_string($value)) return false;
            }
        }

        return !array_diff($data['harmony_types'], [
            'monochromatic', 'analogous', 'complementary', 'split_complementary',
            'triadic', 'neutrals', 'neutral_accent', 'light_dark_contrast',
        ]);
    }

    private function validCompatibilityScore(mixed $value): bool
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value) && $value >= 0 && $value <= 100;
    }

    public function fallback(string $requestId, string $code): array
    {
        return [
            'request_id' => $requestId, 'contract_version' => '1', 'status' => 'degraded', 'data' => [],
            'warnings' => ['No fue posible obtener sugerencias automáticas. Puedes continuar con el registro manual.'],
            'error' => ['code' => $code, 'message' => 'No fue posible obtener sugerencias automáticas.', 'retryable' => true],
            'model' => ['model_id' => 'unavailable', 'model_revision' => 'unknown', 'pipeline_version' => '1'],
            'human_review_required' => true,
        ];
    }
}
