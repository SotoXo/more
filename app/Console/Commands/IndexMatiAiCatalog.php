<?php

namespace App\Console\Commands;

use App\Models\AiRun;
use App\Models\ImagenPrenda;
use App\Services\AI\AiReviewService;
use App\Services\AI\CatalogEmbeddingStore;
use App\Services\AI\CatalogImageReader;
use App\Services\AI\MatiAiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IndexMatiAiCatalog extends Command
{
    protected $signature = 'mati-ai:index-catalog
        {--limit=100 : Máximo de imágenes o ejecuciones a procesar}
        {--batch-size=25 : Cantidad de registros leídos en cada lote}
        {--after-id=0 : Procesar imágenes posteriores a este ID}
        {--from-runs : Reutilizar embeddings reales de ai_runs sin ejecutar inferencia}
        {--after-run-id= : Reanudar después de este UUID de ai_runs}
        {--force : Actualizar la fila canónica existente de la misma imagen e identidad}';
    protected $description = 'Indexa embeddings reales del catálogo en pgvector sin aplicar sugerencias ni cambiar productos.';

    public function handle(MatiAiClient $client, CatalogImageReader $reader, AiReviewService $reviews, CatalogEmbeddingStore $store): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        $batchSize = filter_var($this->option('batch-size'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        $after = filter_var($this->option('after-id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($limit === false || $batchSize === false || $after === false) {
            $this->error('--limit debe estar entre 1 y 10000; --batch-size entre 1 y 500; --after-id debe ser un entero no negativo.');
            return self::INVALID;
        }
        $fromRuns = (bool) $this->option('from-runs');
        $afterRunId = $this->option('after-run-id');
        if (($afterRunId && !$fromRuns) || ($fromRuns && $after > 0)) {
            $this->error('Usa --after-id con imágenes y --after-run-id con --from-runs.');
            return self::INVALID;
        }
        if (!$store->available() || !Schema::hasTable('ai_runs')) {
            $this->error('El almacenamiento vectorial no está disponible. Requiere PostgreSQL con pgvector, las migraciones IA y MATI_AI_VECTOR_ENABLED.');
            return self::FAILURE;
        }
        if (!$fromRuns && (!config('mati_ai.enabled') || blank(config('mati_ai.token')))) {
            $this->error('Configura MATI_AI_ENABLED y MATI_AI_TOKEN antes de generar embeddings de imágenes.');
            return self::FAILURE;
        }

        $runCursor = null;
        if ($afterRunId) {
            if (!is_string($afterRunId) || !Str::isUuid($afterRunId) || !($runCursor = AiRun::find($afterRunId))) {
                $this->error('--after-run-id debe identificar una ejecución existente.');
                return self::INVALID;
            }
        }
        $indexed = $skipped = $failed = $processed = 0;
        while ($processed < $limit) {
            if ($fromRuns) {
                $query = AiRun::whereNotNull('prenda_id')->whereNotNull('embedding');
                if ($runCursor) {
                    $query->where(function ($query) use ($runCursor) {
                        $query->where('created_at', '>', $runCursor->created_at)
                            ->orWhere(fn ($sameTime) => $sameTime->where('created_at', $runCursor->created_at)->where('id', '>', $runCursor->id));
                    });
                }
                $entries = $query->orderBy('created_at')->orderBy('id')->limit(min($batchSize, $limit - $processed))->get();
            } else {
                $entries = ImagenPrenda::where('pri_igp', true)
                    ->whereHas('prenda', fn ($query) => $query->where('est_pre', true))
                    ->where('rut_igp', 'like', 'productos/%')->where('id_imagen_prenda', '>', $after)
                    ->orderBy('id_imagen_prenda')->limit(min($batchSize, $limit - $processed))->get();
            }
            if ($entries->isEmpty()) break;
            foreach ($entries as $entry) {
                $processed++;
                // Advance for every inspected record, including failures, and print the cursor.
                if ($fromRuns) $runCursor = $entry;
                else $after = (int) $entry->id_imagen_prenda;
                try {
                    if ($fromRuns) {
                        // An old run must never be relinked to a replaced image at the same ID.
                        $imageId = data_get($entry->input_metadata, 'image_id');
                        if ($imageId !== null) {
                            $image = ImagenPrenda::whereKey($imageId)->where('cod_prendas_igp', $entry->prenda_id)->first();
                            if (!$image || !is_string($entry->input_hash)
                                || !hash_equals(strtolower($entry->input_hash), hash('sha256', $reader->read($image)))) {
                                throw new \InvalidArgumentException('EMBEDDING_IMAGE_CONTENT_CHANGED');
                            }
                        }
                        // storeRun enforces recorded hash, pinned identity and actual dimensions.
                        $stored = $store->storeRun($entry, null, ['indexed_from_existing_run' => true], (bool) $this->option('force'));
                        if (!$stored) throw new \RuntimeException('EMBEDDING_NOT_STORED');
                    } else {
                        $bytes = $reader->read($entry);
                        $hash = hash('sha256', $bytes);
                        $result = $client->request('embedding', [], $bytes);
                        $embedding = data_get($result, 'data.embedding');
                        if (isset($result['error']) || !is_array($embedding) || !is_array($result['model'] ?? null)) {
                            throw new \RuntimeException('EMBEDDING_NOT_AVAILABLE');
                        }
                        // Validate real output before checking the complete canonical identity.
                        $store->prepare($embedding, $result['model']);
                        if (!$this->option('force') && $store->existingForProduct((int) $entry->cod_prendas_igp, $hash, $embedding, $result['model'])) {
                            $skipped++;
                            continue;
                        }
                        $stored = DB::transaction(function () use ($reviews, $store, $result, $hash, $entry) {
                            $run = $reviews->persist('index-catalog', $result, null, [
                                'sha256' => $hash, 'image_id' => (int) $entry->id_imagen_prenda,
                                'original_preserved' => true,
                            ]);
                            $run->update(['prenda_id' => $entry->cod_prendas_igp]);
                            $stored = $store->storeRun($run, (int) $entry->id_imagen_prenda, [], (bool) $this->option('force'));
                            if (!$stored) throw new \RuntimeException('EMBEDDING_NOT_STORED');
                            return $stored;
                        });
                    }
                    if ($stored->wasRecentlyCreated || $this->option('force')) $indexed++;
                    else $skipped++;
                } catch (\Throwable $exception) {
                    $failed++;
                    $context = $fromRuns ? ['run_id' => $entry->id] : ['image_id' => $entry->id_imagen_prenda];
                    Log::warning('mati_ai.catalog_index_failed', $context);
                    $label = $fromRuns ? 'Ejecución '.$entry->id : 'Imagen '.$entry->id_imagen_prenda;
                    $reason = $exception instanceof \InvalidArgumentException ? ' ('.$exception->getMessage().')' : '';
                    $this->warn($label.': no se pudo indexar'.$reason.'.');
                }
            }
            $this->line('Procesadas: '.$processed.'. Cursor: '.($fromRuns ? '--after-run-id='.$runCursor->id : '--after-id='.$after).'.');
        }
        $this->info('Indexadas o actualizadas: '.$indexed.'. Ya indexadas: '.$skipped.'. No disponibles: '.$failed.'.');
        $this->line('Las coincidencias visuales requieren revisión humana; este comando no modifica el catálogo comercial.');
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
