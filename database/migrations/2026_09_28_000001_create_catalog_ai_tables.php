<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_id');
            $table->string('model_revision');
            $table->string('pipeline_version');
            $table->timestamps();
            $table->unique(['model_id', 'model_revision', 'pipeline_version'], 'ai_model_identity');
        });
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('prenda_id')->nullable()->index();
            $table->foreign('prenda_id')->references('id_prenda')->on('prendas')->nullOnDelete();
            $table->foreignId('model_version_id')->nullable()->constrained('ai_model_versions')->nullOnDelete();
            $table->string('operation', 50);
            $table->string('status', 30);
            $table->string('input_hash', 64)->nullable()->index();
            $table->string('error_code', 80)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->json('input_metadata')->nullable();
            $table->json('result');
            $table->json('embedding')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['operation', 'created_at']);
        });
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('run_id')->constrained('ai_runs')->cascadeOnDelete();
            $table->string('field', 50);
            $table->json('value');
            $table->string('label');
            $table->string('source', 100);
            $table->decimal('confidence_score', 10, 6)->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_human_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->constrained('ai_suggestions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('human_action', 20);
            $table->json('prediction');
            $table->json('confirmed_value')->nullable();
            $table->unsignedInteger('variant_index')->nullable();
            $table->string('stage', 20)->default('form');
            $table->foreignId('form_review_id')->nullable()->unique()->constrained('ai_human_reviews')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('prendas', fn (Blueprint $table) => $table->string('modelo_pre', 150)->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('prendas', fn (Blueprint $table) => $table->dropColumn('modelo_pre'));
        Schema::dropIfExists('ai_human_reviews');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_runs');
        Schema::dropIfExists('ai_model_versions');
    }
};
