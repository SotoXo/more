<?php

namespace Tests\Feature;

use App\Models\AiHumanReview;
use App\Models\AiRun;
use App\Models\AiSuggestion;
use App\Models\ImagenPrenda;
use App\Models\Prenda;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AiPublicationService;
use App\Services\AI\AiReviewService;
use App\Services\AI\CatalogAiService;
use App\Services\Superadmin\Resources\AdminFormattingService;
use App\Services\Superadmin\Resources\ResourceDefinitionService;
use App\Services\Superadmin\Resources\ResourceWriteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MatiAiWorkflowTest extends TestCase
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
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->mock(CatalogAiService::class, function ($mock) {
            $mock->shouldReceive('taxonomies')->andReturn($this->taxonomies())->byDefault();
            $mock->shouldReceive('candidates')->andReturn([])->byDefault();
            $mock->shouldReceive('exactMatches')->andReturn([])->byDefault();
            $mock->shouldReceive('duplicates')->andReturn([])->byDefault();
        });
    }

    private function taxonomies(): array
    {
        return [
            'categories' => [['id' => 1, 'name' => 'POLERA']], 'brands' => [['id' => 2, 'name' => 'ACME']],
            'colors' => [['id' => 3, 'name' => 'AZUL']], 'sizes' => [], 'patterns' => [],
        ];
    }

    private function user(int $id = 1, string $role = 'superadmin'): User
    {
        $user = (new User())->forceFill(['id' => $id, 'est_usu' => true, 'nom_usu' => 'PRUEBA']);
        $user->exists = true;
        $user->setRelation('rol', (new Role())->forceFill(['id_rol' => 1, 'nom_rol' => $role, 'est_rol' => true]));
        return $user;
    }

    private function envelope(string $id, array $data): array
    {
        return [
            'request_id' => $id, 'contract_version' => '1', 'status' => 'completed',
            'data' => $data, 'warnings' => [], 'human_review_required' => true,
            'model' => ['model_id' => 'fixture', 'model_revision' => 'revision-1', 'pipeline_version' => '1'],
        ];
    }

    private function workflowRun(string $operation, array $data = [], int $actor = 1): AiRun
    {
        $id = (string) Str::uuid();
        return app(AiReviewService::class)->persist($operation, $this->envelope($id, $data), $actor);
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6Gk8AAAAASUVORK5CYII=');
    }

    public function test_ai_routes_require_authentication_and_superadmin_role(): void
    {
        Http::fake();
        $this->getJson('/superadmin/ai/capabilities')->assertUnauthorized();
        $this->actingAs($this->user(role: 'cliente'))->getJson('/superadmin/ai/capabilities')->assertRedirect(route('dashboard'));
        Http::assertNothingSent();
    }

    public function test_saved_catalog_image_can_be_analyzed_without_changing_product(): void
    {
        $product = Prenda::create(['nom_pre' => 'PRENDA MANUAL']);
        Storage::disk('public')->put('productos/fixture.png', $this->png());
        $image = ImagenPrenda::create(['cod_prendas_igp' => $product->id_prenda, 'rut_igp' => 'productos/fixture.png']);
        Http::fake(function (ClientRequest $request) {
            return Http::response($this->envelope($request->header('X-Request-Id')[0], [
                'quality' => [], 'colors' => [], 'classification' => ['abstained' => false, 'top_k' => [['category_id' => 1, 'similarity_score' => 0.72]]],
                'embedding' => ['model_id' => 'fixture', 'model_revision' => 'revision-1', 'values' => [0.2, 0.8]],
            ]));
        });
        $response = $this->actingAs($this->user())->postJson('/superadmin/ai/analyze', ['existing_image_id' => $image->id_imagen_prenda]);
        $response->assertOk()->assertJsonPath('data.internal_checked', true)->assertJsonPath('suggestions.0.field', 'category');
        $this->assertArrayNotHasKey('embedding', $response->json('data'));
        $run = AiRun::findOrFail($response->json('run_id'));
        $this->assertSame([0.2, 0.8], $run->embedding['values']);
        $this->assertArrayNotHasKey('embedding', $run->result['data']);
        $this->assertSame('PRENDA MANUAL', $product->fresh()->nom_pre);
        $this->assertDatabaseCount('ai_suggestions', 1);
    }

    public function test_unavailable_service_returns_manual_fallback_and_records_the_failure(): void
    {
        config()->set('mati_ai.enabled', false);
        Http::fake();
        $product = Prenda::create(['nom_pre' => 'MANUAL']);
        Storage::disk('public')->put('productos/manual.png', $this->png());
        $image = ImagenPrenda::create(['cod_prendas_igp' => $product->id_prenda, 'rut_igp' => 'productos/manual.png']);
        $this->actingAs($this->user())->postJson('/superadmin/ai/analyze', ['existing_image_id' => $image->id_imagen_prenda])
            ->assertOk()->assertJsonPath('status', 'degraded')->assertJsonPath('error.code', 'SERVICE_NOT_CONFIGURED');
        $this->assertSame('MANUAL', $product->fresh()->nom_pre);
        $this->assertDatabaseCount('ai_runs', 1);
        Http::assertNothingSent();
    }

    public function test_brand_and_catalog_context_are_allowed_for_external_search(): void
    {
        Http::fake(fn (ClientRequest $request) => Http::response($this->envelope($request['request_id'], ['candidates' => []])));
        $this->actingAs($this->user())->postJson('/superadmin/ai/external-search', ['brand' => 'ACME', 'category_id' => 1, 'color_id' => 3])
            ->assertOk()->assertJsonPath('data.internal_checked', true);
        Http::assertSent(fn (ClientRequest $request) =>
            $request['brand'] === 'ACME' && $request['query'] === 'ACME' && $request['category'] === 'POLERA'
            && $request['color'] === 'AZUL' && !isset($request['category_id'])
        );
    }

    public function test_exact_internal_identifier_prevents_external_network_search(): void
    {
        $this->mock(CatalogAiService::class, function ($mock) {
            $mock->shouldReceive('taxonomies')->andReturn($this->taxonomies());
            $mock->shouldReceive('candidates')->once()->andReturn([['candidate_id' => '7']]);
            $mock->shouldReceive('exactMatches')->once()->andReturn([['id' => '7', 'exact_identifier' => true]]);
        });
        Http::fake();
        $this->actingAs($this->user())->postJson('/superadmin/ai/external-search', ['gtin' => '7501031311309'])
            ->assertOk()->assertJsonPath('data.external_search_allowed', false)->assertJsonPath('data.candidates', []);
        Http::assertNothingSent();
    }

    public function test_feedback_is_bound_to_owner_and_does_not_save_commercial_data(): void
    {
        $run = $this->workflowRun('analyze', ['classification' => ['abstained' => false]]);
        $suggestion = AiSuggestion::create(['run_id' => $run->id, 'field' => 'category', 'value' => 1, 'label' => 'POLERA', 'source' => 'vision']);
        $this->actingAs($this->user(2))->postJson('/superadmin/ai/feedback', ['suggestion_id' => $suggestion->id, 'action' => 'accepted'])->assertNotFound();
        $this->actingAs($this->user())->postJson('/superadmin/ai/feedback', ['suggestion_id' => $suggestion->id, 'action' => 'corrected', 'confirmed_value' => 8])
            ->assertOk()->assertJsonPath('commercial_data_saved', false);
        $review = AiHumanReview::firstOrFail();
        $this->assertSame(1, $review->prediction);
        $this->assertSame(8, $review->confirmed_value);
        $this->assertDatabaseCount('prendas', 0);
    }

    public function test_feedback_arriving_after_save_records_the_actual_saved_value_once(): void
    {
        $product = Prenda::create(['nom_pre' => 'VALOR GUARDADO']);
        $run = $this->workflowRun('analyze');
        $run->update(['prenda_id' => $product->id_prenda]);
        $suggestion = AiSuggestion::create([
            'run_id' => $run->id, 'field' => 'name', 'value' => 'PREDICCION', 'label' => 'PREDICCION', 'source' => 'external',
        ]);
        $this->actingAs($this->user())->postJson('/superadmin/ai/feedback', [
            'suggestion_id' => $suggestion->id, 'action' => 'accepted', 'confirmed_value' => 'VALOR DEL NAVEGADOR',
        ])->assertOk();
        $form = AiHumanReview::where('stage', 'form')->firstOrFail();
        $saved = AiHumanReview::where('stage', 'saved')->firstOrFail();
        $this->assertSame('VALOR GUARDADO', $saved->confirmed_value);
        $this->assertSame('PREDICCION', $saved->prediction);
        $this->assertSame('corrected', $saved->human_action);
        $this->assertSame($form->id, $saved->form_review_id);
        app(AiReviewService::class)->attachSafelyToProduct($product, ['ai_run_ids' => [$run->id]], 1);
        $this->assertSame(1, AiHumanReview::where('stage', 'saved')->count());
        $this->assertSame('VALOR GUARDADO', $product->fresh()->nom_pre);
    }

    public function test_external_references_use_persisted_urls_and_cannot_be_read_by_another_admin(): void
    {
        $run = $this->workflowRun('external-search', ['candidates' => [[
            'source_id' => 'approved-source', 'canonical_url' => 'https://merchant.test/product',
            'images' => [['original_url' => 'https://merchant.test/photo.png', 'rights_status' => 'unknown']],
        ]]]);
        Http::fake(fn (ClientRequest $request) => Http::response($this->envelope($request['request_id'], [
            'image_base64' => base64_encode($this->png()), 'sha256' => hash('sha256', $this->png()), 'rights_status' => 'unknown',
        ])));
        $url = '/superadmin/ai/external-reference/'.$run->id.'/0/0';
        $this->actingAs($this->user(2))->getJson($url)->assertNotFound();
        Http::assertNothingSent();
        $this->actingAs($this->user())->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        Http::assertSent(fn (ClientRequest $request) => $request['purpose'] === 'reference' && $request['original_url'] === 'https://merchant.test/photo.png');
        $metadata = $run->fresh()->result['data']['candidates'][0]['images'][0];
        $this->assertSame(hash('sha256', $this->png()), $metadata['sha256']);
        $this->assertArrayHasKey('reference_downloaded_at', $metadata);
        $this->assertArrayNotHasKey('image_base64', $metadata);
    }

    public function test_external_result_exposes_only_a_relative_owner_bound_preview_url(): void
    {
        Http::fake(fn (ClientRequest $request) => Http::response($this->envelope($request['request_id'], [
            'candidates' => [['source_id' => 'source', 'title' => 'POLERA', 'canonical_url' => 'https://merchant.test/product',
                'images' => [['original_url' => 'https://merchant.test/photo.png', 'rights_status' => 'unknown']]]],
        ])));
        $response = $this->actingAs($this->user())->postJson('/superadmin/ai/external-search', ['query' => 'POLERA']);
        $response->assertOk();
        $this->assertSame('/superadmin/ai/external-reference/'.$response->json('run_id').'/0/0', $response->json('data.candidates.0.images.0.preview_url'));
    }

    public function test_reference_with_unknown_rights_cannot_be_prepared_for_publication(): void
    {
        $run = $this->workflowRun('external-search', ['candidates' => [[
            'source_id' => 'source', 'canonical_url' => 'https://merchant.test/product', 'rights_status' => 'unknown',
            'images' => [['original_url' => 'https://merchant.test/photo.png', 'rights_status' => 'unknown']],
        ]]]);
        Http::fake();
        $this->actingAs($this->user())->postJson('/superadmin/ai/prepare-publication', [
            'approved' => true, 'external_run_id' => $run->id, 'candidate_index' => 0, 'image_index' => 0,
            'rights_status' => 'owned',
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        Http::assertNothingSent();
    }

    public function test_preparation_keeps_images_private_until_explicit_product_attachment(): void
    {
        if (!function_exists('imagewebp')) $this->markTestSkipped('Se requiere GD con WebP para esta fixture.');
        $canvas = imagecreatetruecolor(4, 4);
        ob_start();
        imagewebp($canvas);
        $bytes = ob_get_clean();
        imagedestroy($canvas);
        $derivatives = array_map(fn ($profile) => [
            'profile' => $profile, 'content_base64' => base64_encode($bytes),
            'sha256' => hash('sha256', $bytes), 'mime_type' => 'image/webp', 'width' => 4, 'height' => 4,
        ], ['thumbnail', 'card', 'detail']);
        $run = $this->workflowRun('prepare-publication');
        $service = app(AiPublicationService::class);
        $prepared = $service->stage($run, $this->envelope($run->id, ['derivatives' => $derivatives]), $this->png(), [
            'rights_status' => 'owned', 'rights_evidence' => 'Carga propia aprobada', 'approved_by' => 1,
        ]);
        $this->assertSame('/superadmin/ai/prepared-images/'.$run->id, $prepared['url']);
        $this->assertDatabaseCount('imagenes_prenda', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Storage::disk('local')->assertExists('mati-ai/prepared/'.$run->id.'/original');
        $this->assertArrayNotHasKey('content_base64', $run->fresh()->result['data']['derivatives'][0]);
        $product = Prenda::create(['nom_pre' => 'ALTA EXPLICITA']);
        $createdPaths = [];
        DB::transaction(function () use ($service, $product, $run, &$createdPaths) {
            $service->attach($product, [$run->id], 1, $createdPaths);
        });
        $this->assertCount(3, $createdPaths);
        $this->assertDatabaseCount('imagenes_prenda', 1);
        $this->assertSame($product->id_prenda, $run->fresh()->prenda_id);
        $this->assertTrue(ImagenPrenda::firstOrFail()->pri_igp);
        foreach ($createdPaths as $path) Storage::disk('public')->assertExists($path);
    }

    public function test_prepared_token_requires_owner_approval_and_is_single_use(): void
    {
        $run = $this->workflowRun('prepare-publication', ['provenance' => [
            'rights_status' => 'owned', 'rights_evidence' => 'Administrador', 'approved_by' => 1,
        ]]);
        $run->update(['expires_at' => now()->addHour()]);
        $service = app(AiPublicationService::class);
        $this->assertSame($run->id, $service->approvedRun($run->id, 1)->id);
        try {
            $service->approvedRun($run->id, 2);
            $this->fail('Otro administrador no debe poder usar el token.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $product = Prenda::create(['nom_pre' => 'MANUAL']);
        $run->update(['prenda_id' => $product->id_prenda]);
        $this->expectException(ValidationException::class);
        $service->approvedRun($run->id, 1);
    }

    public function test_audit_failure_rolls_back_its_savepoint_but_preserves_manual_write(): void
    {
        $product = Prenda::create(['nom_pre' => 'ORIGINAL']);
        $service = new class extends AiReviewService {
            public function attachToProduct(Prenda $product, array $validated, ?int $actor): void
            {
                DB::table('prendas')->where('id_prenda', $product->id_prenda)->update(['nom_pre' => 'AUDITORIA PARCIAL']);
                throw new \RuntimeException('fixture audit failure');
            }
        };
        DB::transaction(function () use ($service, $product) {
            $product->update(['nom_pre' => 'EDICION MANUAL']);
            $service->attachSafelyToProduct($product, ['ai_run_ids' => [(string) Str::uuid()]], 1);
        });
        $this->assertSame('EDICION MANUAL', $product->fresh()->nom_pre);
    }

    public function test_manual_normalization_works_before_ai_migration_is_applied(): void
    {
        Schema::table('prendas', fn (Blueprint $table) => $table->dropColumn('modelo_pre'));
        $writer = new ResourceWriteService(new AdminFormattingService(), new ResourceDefinitionService());
        $payload = $writer->normalizePayload(['sectionKey' => 'catalogo', 'resourceKey' => 'prendas'], [
            'nom_pre' => 'manual', 'modelo_pre' => 'M-1', 'cod_categorias_pre' => 1, 'prb_pre' => 50,
        ]);
        $this->assertSame('MANUAL', $payload['nom_pre']);
        $this->assertArrayNotHasKey('modelo_pre', $payload);
    }
}
