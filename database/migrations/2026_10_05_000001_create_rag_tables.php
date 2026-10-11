<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql'
            || !DB::selectOne("SELECT 1 FROM pg_extension WHERE extname='vector'")) {
            throw new RuntimeException('RAG_REQUIRES_EXISTING_PGVECTOR');
        }
        Schema::create('rag_model_versions', function (Blueprint $table) {
            $table->id();
            $table->char('space_key', 64)->unique();
            $table->string('vector_space', 20)->default('text');
            $table->string('model_id');
            $table->string('model_revision');
            $table->string('pipeline_version', 100);
            $table->unsignedInteger('dimensions');
            $table->string('normalization', 20);
            $table->string('preprocess_version', 100);
            $table->timestamps();
            $table->unique(['id', 'dimensions'], 'rag_model_dimensions_identity');
        });
        DB::statement("ALTER TABLE rag_model_versions ADD CONSTRAINT rag_text_space CHECK (vector_space='text' AND normalization='l2' AND dimensions BETWEEN 1 AND 16000)");
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_key', 160);
            $table->string('document_type', 50);
            $table->string('title');
            $table->text('source');
            $table->string('source_version', 100);
            $table->string('corpus_version', 100);
            $table->char('content_hash', 64);
            $table->char('snapshot_hash', 64);
            $table->string('status', 20)->default('inactive');
            $table->json('metadata');
            $table->timestamps();
            $table->unique(['document_key', 'corpus_version'], 'rag_document_version_identity');
            $table->index(['status', 'document_type', 'corpus_version']);
        });
        Schema::create('rag_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('rag_documents')->cascadeOnDelete();
            $table->string('chunk_key', 220);
            $table->unsignedInteger('chunk_index');
            $table->string('section', 120);
            $table->text('content');
            $table->char('content_hash', 64);
            $table->text('search_text');
            $table->json('metadata');
            $table->timestamps();
            $table->unique(['document_id', 'chunk_index']);
            $table->unique(['document_id', 'chunk_key']);
        });
        Schema::create('rag_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chunk_id')->constrained('rag_chunks')->cascadeOnDelete();
            $table->foreignId('model_version_id')->constrained('rag_model_versions')->restrictOnDelete();
            $table->unsignedInteger('dimensions');
            $table->char('input_hash', 64);
            $table->double('norm');
            $table->string('status', 20)->default('inactive');
            $table->timestamps();
            $table->unique(['chunk_id', 'model_version_id']);
            $table->foreign(['model_version_id', 'dimensions'])->references(['id', 'dimensions'])->on('rag_model_versions')->restrictOnDelete();
            $table->index(['model_version_id', 'status']);
        });
        DB::statement('ALTER TABLE rag_embeddings ADD COLUMN embedding vector NOT NULL');
        DB::statement('ALTER TABLE rag_embeddings ADD CONSTRAINT rag_vector_dimensions CHECK (dimensions BETWEEN 1 AND 16000 AND vector_dims(embedding)=dimensions)');
        DB::statement('ALTER TABLE rag_embeddings ADD CONSTRAINT rag_vector_normalized CHECK (norm BETWEEN 0.999 AND 1.001 AND vector_norm(embedding) BETWEEN 0.999 AND 1.001)');
        // Small curated corpus: exact search only, no ANN index or extension lifecycle change.
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_embeddings');
        Schema::dropIfExists('rag_chunks');
        Schema::dropIfExists('rag_documents');
        Schema::dropIfExists('rag_model_versions');
    }
};
