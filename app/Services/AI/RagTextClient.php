<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class RagTextClient
{
    public function profile(): array
    {
        $profile = config('rag.profile');
        if (!is_array($profile)) {
            $path = config('rag.profile_path');
            if (!is_string($path) || !is_file($path)) throw new RuntimeException('TEXT_MODEL_NOT_APPROVED');
            $profile = json_decode(file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        }
        if (($profile['approved'] ?? null) !== true
            || ($profile['vector_space'] ?? null) !== 'text'
            || ($profile['normalization'] ?? null) !== 'l2'
            || !is_int($profile['dimensions'] ?? null)
            || $profile['dimensions'] < 1 || $profile['dimensions'] > 16000
            || !is_numeric($profile['minimum_cosine'] ?? null)
            || !is_finite((float) $profile['minimum_cosine'])
            || $profile['minimum_cosine'] < -1 || $profile['minimum_cosine'] > 1) {
            throw new RuntimeException('TEXT_MODEL_PROFILE_INVALID');
        }
        foreach (['model_id', 'revision', 'pipeline_version', 'preprocess_version'] as $field) {
            if (!is_string($profile[$field] ?? null) || $profile[$field] === '' || strlen($profile[$field]) > 255) {
                throw new RuntimeException('TEXT_MODEL_IDENTITY_INVALID');
            }
        }
        if (!preg_match('/^[a-f0-9]{40,64}$/', $profile['revision'])) throw new RuntimeException('TEXT_MODEL_REVISION_NOT_PINNED');
        return $profile;
    }

    public function identity(array $profile): array
    {
        return [
            'vector_space' => 'text', 'model_id' => $profile['model_id'], 'revision' => $profile['revision'],
            'dimensions' => $profile['dimensions'], 'normalization' => 'l2',
            'preprocess_version' => $profile['preprocess_version'], 'pipeline_version' => $profile['pipeline_version'],
        ];
    }

    public function spaceKey(array $profile): string
    {
        return hash('sha256', json_encode($this->identity($profile), JSON_THROW_ON_ERROR));
    }

    public function embed(array $texts, string $inputType, ?array $profile = null): array
    {
        $profile ??= $this->profile();
        if (!array_is_list($texts) || count($texts) < 1 || count($texts) > 16
            || !in_array($inputType, ['query', 'document'], true)) throw new InvalidArgumentException('TEXT_INPUT_INVALID');
        foreach ($texts as $text) {
            if (!is_string($text) || trim($text) === '' || !mb_check_encoding($text, 'UTF-8')
                || mb_strlen($text) > 8192) throw new InvalidArgumentException('TEXT_INPUT_INVALID');
        }
        if (strlen(implode('', $texts)) > 65536) throw new InvalidArgumentException('TEXT_BATCH_TOO_LARGE');
        if (!config('mati_ai.enabled') || blank(config('mati_ai.token'))) throw new RuntimeException('TEXT_SERVICE_NOT_CONFIGURED');
        $requestId = (string) Str::uuid();
        $response = Http::baseUrl(rtrim(config('mati_ai.url'), '/'))->withToken(config('mati_ai.token'))
            ->acceptJson()->withHeaders(['X-Request-Id' => $requestId, 'X-Contract-Version' => '1'])
            ->connectTimeout(max(1, (int) config('mati_ai.connect_timeout')))
            ->timeout(max(1, (int) config('mati_ai.timeout')))->withoutRedirecting()
            ->post('/api/v1/rag/embed', [
                'schema_version' => 'mati-text-embed-request-1', 'request_id' => $requestId,
                'texts' => $texts, 'input_type' => $inputType,
            ]);
        if (!$response->successful()) throw new RuntimeException('TEXT_EMBEDDING_UNAVAILABLE');
        $body = $response->json();
        if (!is_array($body) || ($body['request_id'] ?? null) !== $requestId
            || ($body['contract_version'] ?? null) !== '1' || ($body['status'] ?? null) !== 'completed'
            || ($body['data']['input_type'] ?? null) !== $inputType
            || !is_array($body['data']['embeddings'] ?? null)
            || !array_is_list($body['data']['embeddings'])
            || count($body['data']['embeddings']) !== count($texts)) throw new RuntimeException('TEXT_RESPONSE_INVALID');
        $expected = $this->identity($profile);
        foreach (['data.model', 'model'] as $path) {
            $model = data_get($body, $path);
            if (!is_array($model)) throw new RuntimeException('TEXT_SPACE_MISMATCH');
            foreach ($expected as $key => $value) {
                // The standard envelope uses model_revision; textual data uses revision.
                $actual = $model[$key] ?? ($key === 'revision' ? ($model['model_revision'] ?? null) : null);
                if ($actual !== $value) throw new RuntimeException('TEXT_SPACE_MISMATCH');
            }
        }
        foreach ($body['data']['embeddings'] as $index => $entry) {
            if (($entry['index'] ?? null) !== $index || ($entry['dimensions'] ?? null) !== $profile['dimensions']
                || ($entry['text_sha256'] ?? null) !== hash('sha256', $texts[$index])
                || ($entry['truncated'] ?? null) !== false
                || (!is_int($entry['norm'] ?? null) && !is_float($entry['norm'] ?? null))) throw new RuntimeException('TEXT_ENTRY_INVALID');
            $this->validateVector($entry['vector'] ?? null, $profile['dimensions'], $entry['norm'] ?? null);
        }
        return $body['data']['embeddings'];
    }

    public function validateVector(mixed $vector, int $dimensions, mixed $reportedNorm = null): float
    {
        if (!is_array($vector) || !array_is_list($vector) || count($vector) !== $dimensions) {
            throw new InvalidArgumentException('TEXT_VECTOR_DIMENSIONS_INVALID');
        }
        $sum = 0.0;
        foreach ($vector as $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
                throw new InvalidArgumentException('TEXT_VECTOR_NONFINITE');
            }
            $sum += $value * $value;
        }
        $norm = sqrt($sum);
        if (!is_finite($norm) || abs($norm - 1.0) > 0.001
            || ($reportedNorm !== null && ((!is_int($reportedNorm) && !is_float($reportedNorm))
                || !is_finite((float) $reportedNorm) || abs($reportedNorm - $norm) > 0.001))) {
            throw new InvalidArgumentException('TEXT_VECTOR_NOT_NORMALIZED');
        }
        return $norm;
    }
}
