<?php

namespace Tests\Unit;

use App\Services\AI\MatiAiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatiAiClientTest extends TestCase
{
    private const REQUEST_ID = 'd12c4730-fbf6-4c4f-8e45-2de5778ac768';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set(['mati_ai.enabled' => true, 'mati_ai.url' => 'http://mati-ai.test', 'mati_ai.token' => 'only-a-test-token']);
        Http::preventStrayRequests();
    }

    private function envelope(array $data, string $status = 'completed'): array
    {
        return [
            'request_id' => self::REQUEST_ID, 'contract_version' => '1', 'status' => $status,
            'data' => $data, 'warnings' => [], 'human_review_required' => true,
            'model' => ['model_id' => 'test-model', 'model_revision' => 'test-revision', 'pipeline_version' => '1'],
        ];
    }

    public function test_disabled_service_returns_manual_fallback_without_network(): void
    {
        config()->set('mati_ai.enabled', false);
        Http::fake();
        $result = app(MatiAiClient::class)->request('barcode', [], 'image', self::REQUEST_ID);
        $this->assertSame('SERVICE_NOT_CONFIGURED', $result['error']['code']);
        $this->assertSame('degraded', $result['status']);
        Http::assertNothingSent();
    }

    public function test_missing_token_never_sends_an_unauthenticated_request(): void
    {
        config()->set('mati_ai.token', '');
        Http::fake();
        $result = app(MatiAiClient::class)->request('capabilities', requestId: self::REQUEST_ID);
        $this->assertSame('SERVICE_NOT_CONFIGURED', $result['error']['code']);
        Http::assertNothingSent();
    }

    public function test_timeout_returns_retryable_manual_fallback(): void
    {
        Http::fake(fn () => throw new ConnectionException('fixture timeout'));
        $result = app(MatiAiClient::class)->request('barcode', [], 'image', self::REQUEST_ID);
        $this->assertSame('CONNECTION_TIMEOUT', $result['error']['code']);
        $this->assertTrue($result['error']['retryable']);
    }

    public function test_response_with_another_request_id_is_rejected(): void
    {
        Http::fake(['*' => Http::response([...$this->envelope(['codes' => []]), 'request_id' => 'unexpected'], 200)]);
        $result = app(MatiAiClient::class)->request('barcode', [], 'image', self::REQUEST_ID);
        $this->assertSame('INVALID_RESPONSE', $result['error']['code']);
    }

    public function test_incomplete_operation_payload_is_rejected(): void
    {
        Http::fake(['*' => Http::response($this->envelope(['codes' => []]), 200)]);
        $result = app(MatiAiClient::class)->request('analyze', [], 'image', self::REQUEST_ID);
        $this->assertSame('INVALID_RESPONSE', $result['error']['code']);
    }

    public function test_valid_partial_result_preserves_evidence_and_sends_bearer_and_contract(): void
    {
        $expected = $this->envelope(['candidates' => []], 'partial');
        $expected['warnings'] = ['SOURCE_TIMEOUT'];
        Http::fake(['*' => Http::response($expected)]);
        $result = app(MatiAiClient::class)->request('external-search', ['query' => 'POLERA'], requestId: self::REQUEST_ID);
        $this->assertSame($expected, $result);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'http://mati-ai.test/api/v1/products/external-search'
            && $request->hasHeader('Authorization', 'Bearer only-a-test-token')
            && $request->hasHeader('X-Contract-Version', '1')
            && $request->hasHeader('X-Request-Id', self::REQUEST_ID)
            && $request['request_id'] === self::REQUEST_ID
            && $request['query'] === 'POLERA'
        );
    }

    public function test_upstream_html_error_is_never_exposed_as_an_ai_result(): void
    {
        Http::fake(['*' => Http::response('<html>upstream problem</html>', 502)]);
        $result = app(MatiAiClient::class)->request('barcode', [], 'image', self::REQUEST_ID);
        $this->assertSame('SERVICE_UNAVAILABLE', $result['error']['code']);
        $this->assertSame([], $result['data']);
    }
}
