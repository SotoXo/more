<?php

namespace Tests\Feature;

use App\Models\AiHumanReview;
use App\Models\AiRun;
use App\Models\AiSuggestion;
use App\Models\ImagenPrenda;
use App\Models\Prenda;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AiReviewService;
use App\Services\AI\CatalogAiService;
use App\Services\AI\CatalogEmbeddingStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MatiAiP1Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'mati_ai.enabled' => true, 'mati_ai.url' => 'http://mati-ai.test', 'mati_ai.token' => 'fixture-token',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('prendas', function (Blueprint $table) {
            $table->id('id_prenda');
            $table->string('nom_pre');
            $table->timestamps();
        });
        Schema::create('imagenes_prenda', function (Blueprint $table) {
            $table->id('id_imagen_prenda');
            $table->unsignedBigInteger('cod_prendas_igp');
            $table->string('rut_igp');
            $table->string('url_igp')->nullable();
            $table->boolean('pri_igp')->default(false);
            $table->integer('ord_igp')->default(0);
            $table->string('fue_igp')->nullable();
            $table->timestamp('fec_igp')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_28_000001_create_catalog_ai_tables.php'))->up();
        DB::table('users')->insert(['id' => 1]);
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->mock(CatalogAiService::class, function ($mock) {
            $mock->shouldReceive('taxonomies')->andReturn($this->taxonomies())->byDefault();
            $mock->shouldReceive('candidates')->andReturn([])->byDefault();
            $mock->shouldReceive('exactMatches')->andReturn([])->byDefault();
            $mock->shouldReceive('duplicates')->andReturn([])->byDefault();
        });
        $this->mock(CatalogEmbeddingStore::class, function ($mock) {
            $mock->shouldReceive('available')->andReturn(true)->byDefault();
            $mock->shouldReceive('search')->andReturn(['status' => 'completed', 'matches' => [], 'total_candidates' => 0, 'warnings' => []])->byDefault();
        });
    }

    private function taxonomies(): array
    {
        return [
            'categories' => [['id' => 1, 'name' => 'POLERA']], 'brands' => [['id' => 2, 'name' => 'ACME']],
            'colors' => [['id' => 3, 'name' => 'AZUL']], 'sizes' => [], 'patterns' => [['id' => 4, 'name' => 'LISO']],
        ];
    }

    private function admin(): User
    {
        $user = (new User())->forceFill(['id' => 1, 'est_usu' => true, 'nom_usu' => 'PRUEBA']);
        $user->exists = true;
        $user->setRelation('rol', (new Role())->forceFill(['id_rol' => 1, 'nom_rol' => 'superadmin', 'est_rol' => true]));
        return $user;
    }

    private function envelope(string $id, array $data, string $status = 'completed'): array
    {
        return [
            'request_id' => $id, 'contract_version' => '1', 'status' => $status, 'data' => $data,
            'warnings' => [], 'abstentions' => [], 'human_review_required' => true,
            'model' => ['model_id' => 'fixture', 'model_revision' => str_repeat('a', 40), 'pipeline_version' => 'fixture-1'],
        ];
    }

    private function classification(bool $abstained): array
    {
        return ['abstained' => $abstained, 'top_k' => [['category_id' => 1, 'category_name' => 'POLERA', 'similarity_score' => 0.072]]];
    }

    private function image(): ImagenPrenda
    {
        $product = Prenda::create(['nom_pre' => 'MANUAL']);
        $upload = UploadedFile::fake()->image('fixture.png', 32, 32);
        Storage::disk('public')->put('productos/fixture.png', $upload->getContent());
        return ImagenPrenda::create(['cod_prendas_igp' => $product->id_prenda, 'rut_igp' => 'productos/fixture.png']);
    }

    private function fakeImageService(): void
    {
        Http::fake(function (ClientRequest $request) {
            $data = match (true) {
                str_ends_with($request->url(), '/garments/analyze') => ['quality' => [], 'colors' => [], 'classification' => $this->classification(false)],
                str_ends_with($request->url(), '/identification/barcode') => ['codes' => []],
                str_ends_with($request->url(), '/identification/label') => ['raw_text' => '', 'normalized_text' => ''],
                str_ends_with($request->url(), '/garments/embedding') => ['image' => [], 'embedding' => [
                    'values' => [1, 0], 'dimension' => 2, 'model_id' => 'fixture',
                    'model_revision' => str_repeat('a', 40), 'pipeline_version' => 'fixture-1',
                ]],
                default => throw new \RuntimeException('Unexpected HTTP fixture operation'),
            };
            return Http::response($this->envelope($request->header('X-Request-Id')[0], $data));
        });
    }

    public static function imageInputCases(): iterable
    {
        foreach (['analyze', 'barcode', 'label', 'similar'] as $operation) {
            foreach (['upload', 'existing', 'both', 'neither', 'invalid_image', 'missing_id', 'negative_id', 'text_id'] as $case) {
                yield "$operation $case" => [$operation, $case];
            }
        }
    }

    #[DataProvider('imageInputCases')]
    public function test_image_input_contract(string $operation, string $case): void
    {
        $image = $this->image();
        $this->fakeImageService();
        $payload = match ($case) {
            'upload' => ['image' => UploadedFile::fake()->image('upload.png', 32, 32)],
            'existing' => ['existing_image_id' => $image->id_imagen_prenda],
            'both' => ['image' => UploadedFile::fake()->image('upload.png', 32, 32), 'existing_image_id' => $image->id_imagen_prenda],
            'neither' => [],
            'invalid_image' => ['image' => UploadedFile::fake()->createWithContent('invalid.png', 'plain text')->mimeType('text/plain')],
            'missing_id' => ['existing_image_id' => 999999],
            'negative_id' => ['existing_image_id' => -1],
            'text_id' => ['existing_image_id' => 'not-an-id'],
        };
        $response = $this->actingAs($this->admin())->post('/superadmin/ai/'.$operation, $payload, ['Accept' => 'application/json']);
        if (in_array($case, ['upload', 'existing'], true)) {
            $response->assertOk()->assertJsonPath('human_review_required', true)->assertJsonMissingPath('error');
            Http::assertSentCount(1);
            $path = match ($operation) {
                'analyze' => '/garments/analyze', 'similar' => '/garments/embedding',
                'barcode' => '/identification/barcode', 'label' => '/identification/label',
            };
            Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), $path)
                && $request->hasHeader('Authorization', 'Bearer fixture-token'));
            $this->assertDatabaseCount('ai_runs', 1);
        } else {
            $response->assertUnprocessable();
            $errors = match ($case) {
                'both', 'neither' => ['image', 'existing_image_id'], 'invalid_image' => ['image'], default => ['existing_image_id'],
            };
            $response->assertJsonValidationErrors($errors);
            Http::assertNothingSent();
            $this->assertDatabaseCount('ai_runs', 0);
        }
        $this->assertSame('MANUAL', $image->prenda->nom_pre);
        $this->assertDatabaseCount('prendas', 1);
    }

    public function test_publication_rejects_both_sources_or_neither_without_remote_requests(): void
    {
        Http::fake();
        $this->actingAs($this->admin())->post('/superadmin/ai/prepare-publication', [
            'approved' => true, 'image' => UploadedFile::fake()->image('upload.png'),
            'external_run_id' => (string) Str::uuid(), 'candidate_index' => 0, 'image_index' => 0,
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['image', 'external_run_id']);
        $this->postJson('/superadmin/ai/prepare-publication', ['approved' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['image', 'external_run_id']);
        Http::assertNothingSent();
    }

    public static function visualDecisionCases(): iterable
    {
        yield 'completed confident' => ['completed', false, ['id' => 4, 'name' => 'LISO'], ['category', 'pattern']];
        yield 'partial confident category' => ['partial', false, null, ['category']];
        yield 'partial abstained category' => ['partial', true, ['id' => 4, 'name' => 'LISO'], ['pattern']];
        yield 'completed abstained category' => ['completed', true, null, []];
        yield 'pattern explicitly abstained' => ['completed', false, ['id' => 4, 'name' => 'LISO', 'abstained' => true], ['category']];
        yield 'insufficient evidence' => ['insufficient_evidence', false, ['id' => 4, 'name' => 'LISO'], []];
        yield 'degraded service' => ['degraded', false, ['id' => 4, 'name' => 'LISO'], []];
    }

    #[DataProvider('visualDecisionCases')]
    public function test_informational_ranking_survives_but_abstained_fields_are_not_applicable(string $status, bool $abstained, ?array $pattern, array $fields): void
    {
        $service = app(AiReviewService::class);
        $original = $this->envelope((string) Str::uuid(), [
            'classification' => $this->classification($abstained), 'attributes' => ['pattern' => $pattern],
        ], $status);
        $run = $service->persist('analyze', $original, 1);
        $result = $service->suggestions($run, $this->taxonomies());
        $this->assertSame($fields, array_column($result['suggestions'], 'field'));
        $this->assertSame($original['data']['classification'], $result['data']['classification']);
        $this->assertSame($original['data']['attributes'], $result['data']['attributes']);
        $this->assertSame($original, $run->fresh()->result);
        $this->assertTrue($run->result['human_review_required']);
        $this->assertDatabaseCount('ai_suggestions', count($fields));
        $this->assertDatabaseCount('ai_human_reviews', 0);
        $this->assertDatabaseCount('prendas', 0);
    }

    public function test_missing_abstention_flag_does_not_create_an_applicable_category(): void
    {
        $service = app(AiReviewService::class);
        $run = $service->persist('analyze', $this->envelope((string) Str::uuid(), [
            'classification' => ['top_k' => $this->classification(false)['top_k']],
        ]), 1);
        $this->assertSame([], $service->suggestions($run, $this->taxonomies())['suggestions']);
        $this->assertNotEmpty($run->result['data']['classification']['top_k']);
    }

    public function test_legacy_abstained_suggestion_can_be_discarded_but_cannot_be_accepted(): void
    {
        $service = app(AiReviewService::class);
        $run = $service->persist('analyze', $this->envelope((string) Str::uuid(), ['classification' => $this->classification(true)], 'partial'), 1);
        $suggestion = AiSuggestion::create(['run_id' => $run->id, 'field' => 'category', 'value' => 1, 'label' => 'POLERA', 'source' => 'vision']);
        $this->actingAs($this->admin())->postJson('/superadmin/ai/feedback', ['suggestion_id' => $suggestion->id, 'action' => 'accepted'])
            ->assertUnprocessable()->assertJsonValidationErrors('suggestion_id');
        $this->assertDatabaseCount('ai_human_reviews', 0);
        $this->postJson('/superadmin/ai/feedback', ['suggestion_id' => $suggestion->id, 'action' => 'rejected'])->assertOk();
        $this->assertSame('rejected', AiHumanReview::firstOrFail()->human_action);
        $this->assertSame($this->classification(true), $run->fresh()->result['data']['classification']);
        $this->assertDatabaseCount('prendas', 0);
    }

    public function test_color_application_requires_explicit_target_identity_and_commercial_evidence(): void
    {
        $service = app(AiReviewService::class);
        foreach ([[false, 'completed', 0], [true, 'insufficient_evidence', 0], [true, 'completed', 1]] as [$verified, $status, $count]) {
            $run = $service->persist('analyze', $this->envelope((string) Str::uuid(), ['colorimetry' => [
                'version' => 'fixture', 'foreground_confidence' => 0.94, 'warnings' => [],
                'commercial_color_status' => $status, 'evidence' => ['target_garment_identity_verified' => $verified],
                'palette' => [['hex' => '#123456', 'catalog_color_id' => 3, 'confidence_score' => 0.9,
                    'evidence' => ['catalog_match_accepted' => true]]],
            ]]), 1);
            $this->assertCount($count, $service->suggestions($run, $this->taxonomies())['suggestions']);
            $this->assertNotEmpty($run->fresh()->result['data']['colorimetry']['palette']);
        }
        $run = $service->persist('analyze', $this->envelope((string) Str::uuid(), ['colors' => [
            'dominant_color' => ['catalog_id' => 3, 'confidence_score' => 0.94],
        ]]), 1);
        $this->assertSame([], $service->suggestions($run, $this->taxonomies())['suggestions']);
    }
}
