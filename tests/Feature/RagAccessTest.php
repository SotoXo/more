<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AI\RagGateV2Retriever as RagRetriever;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RagAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
        DB::purge('sqlite');
    }

    private function user(string $role): User
    {
        $user = (new User())->forceFill(['id' => 1, 'est_usu' => true]);
        $user->exists = true;
        $user->setRelation('rol', (new Role())->forceFill(['id_rol' => 1, 'nom_rol' => $role, 'est_rol' => true]));
        return $user;
    }

    public function test_guest_and_non_admin_cannot_retrieve(): void
    {
        $this->mock(RagRetriever::class, fn ($mock) => $mock->shouldNotReceive('search'));
        $this->postJson('/superadmin/ai/rag/search', ['query' => 'blusa'])->assertUnauthorized();
        $this->actingAs($this->user('vendedor'))->postJson('/superadmin/ai/rag/search', ['query' => 'blusa'])->assertRedirect(route('vendedor.dashboard'));
    }

    public function test_invalid_filters_and_limits_are_rejected_before_inference(): void
    {
        $this->mock(RagRetriever::class, fn ($mock) => $mock->shouldNotReceive('search'));
        $this->actingAs($this->user('superadmin'))->postJson('/superadmin/ai/rag/search', [
            'query' => 'blusa', 'limit' => 1000, 'mode' => 'sql', 'document_type' => "'; DROP TABLE prendas; --",
        ])->assertUnprocessable()->assertJsonValidationErrors(['limit', 'mode', 'document_type']);
    }

    public function test_unapproved_retriever_fails_closed_even_if_request_attempts_evaluation_bypass(): void
    {
        config(['rag.profile' => ['approved' => true, 'retrieval_approved' => false, 'vector_space' => 'text',
            'model_id' => 'fixture/text', 'revision' => str_repeat('a', 40), 'dimensions' => 3, 'normalization' => 'l2',
            'pipeline_version' => 'fixture-1', 'preprocess_version' => 'text-fixture-1', 'minimum_cosine' => .74],
            'rag.evaluation_mode' => true]);
        (new \ReflectionProperty(\Illuminate\Foundation\Application::class, 'isRunningInConsole'))->setValue(app(), false);
        Http::preventStrayRequests();
        $this->withSession(['_token' => 'csrf-fixture'])->actingAs($this->user('superadmin'))->postJson('/superadmin/ai/rag/search', [
            '_token' => 'csrf-fixture', 'query' => 'diferencia camisa y blusa', 'evaluation_mode' => true,
        ])->assertOk()->assertJsonPath('status', 'insufficient_evidence')->assertJsonPath('reason', 'RAG_GATE_NOT_APPROVED')->assertJsonPath('chunks', []);
        Http::assertNothingSent();
    }

    public function test_admin_gets_retrieval_provenance_without_vectors_or_generated_answer(): void
    {
        $result = ['query_id' => 'fixture-query', 'status' => 'completed', 'generated_answer' => false,
            'chunks' => [['document_id' => 1, 'chunk_id' => 1, 'title' => 'BLUSAS', 'excerpt' => 'Definición.',
                'source' => 'mati-taxonomy', 'source_version' => '1.0.0', 'similarity' => .8, 'rank' => 1]]];
        $this->mock(RagRetriever::class, fn ($mock) => $mock->shouldReceive('search')->once()
            ->with('¿Qué es una blusa?', 3, 'hybrid', [])->andReturn($result));
        $this->actingAs($this->user('superadmin'))->postJson('/superadmin/ai/rag/search', ['query' => '¿Qué es una blusa?'])
            ->assertOk()->assertJsonPath('generated_answer', false)->assertJsonPath('chunks.0.source_version', '1.0.0')
            ->assertJsonMissingPath('chunks.0.embedding');
    }
}
