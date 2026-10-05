<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('El catálogo vectorial requiere PostgreSQL con pgvector instalado.');
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        Schema::create('ai_product_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_id');
            $table->unsignedBigInteger('image_id')->nullable();
            $table->foreignId('ai_model_version_id')->constrained('ai_model_versions')->restrictOnDelete();
            $table->foreignUuid('source_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->char('content_hash', 64);
            $table->unsignedInteger('dimensions');
            $table->char('geometry_key', 64);
            $table->string('embedding_version', 100);
            $table->string('preprocess_version', 100);
            $table->string('normalization', 20)->default('l2');
            $table->double('norm');
            $table->double('input_norm');
            $table->json('metadata');
            $table->timestamps();

            $table->foreign('prenda_id')->references('id_prenda')->on('prendas')->cascadeOnDelete();
            $table->foreign('image_id')->references('id_imagen_prenda')->on('imagenes_prenda')->nullOnDelete();
            $table->unique(
                ['prenda_id', 'content_hash', 'ai_model_version_id', 'dimensions', 'geometry_key'],
                'ai_product_embeddings_identity',
            );
            $table->index(['ai_model_version_id', 'dimensions', 'geometry_key'], 'ai_product_embeddings_compatibility');
            $table->index('image_id');
            $table->index('content_hash');
        });

        // Actual output length is discovered at inference, never guessed from a model name.
        // Different dimensions coexist, but exact search materializes a compatible subset first.
        DB::statement('ALTER TABLE ai_product_embeddings ADD COLUMN embedding vector NOT NULL');
        DB::statement('ALTER TABLE ai_product_embeddings ADD CONSTRAINT ai_product_embeddings_dimensions CHECK (dimensions BETWEEN 1 AND 16000 AND vector_dims(embedding) = dimensions)');
        DB::statement("ALTER TABLE ai_product_embeddings ADD CONSTRAINT ai_product_embeddings_normalized CHECK (normalization = 'l2' AND norm BETWEEN 0.999 AND 1.001 AND vector_norm(embedding) BETWEEN 0.999 AND 1.001)");
        // No HNSW/IVFFlat: the current catalog uses exact cosine search.
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_product_embeddings');
        // The shared extension may serve other tables; rollback never removes it.
    }
};
