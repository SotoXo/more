<?php

namespace App\Console\Commands;

use App\Models\AiRun;
use App\Models\ImagenPrenda;
use App\Services\AI\AiReviewService;
use App\Services\AI\CatalogAiService;
use App\Services\AI\CatalogImageReader;
use App\Services\AI\MatiAiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IndexMatiAiCatalog extends Command
{
    protected $signature = 'mati-ai:index-catalog
        {--limit=100 : Máximo de portadas locales a procesar}
        {--after-id=0 : Procesar imágenes posteriores a este ID}
        {--force : Registrar también entradas con el mismo hash e identidad de embedding}';
    protected $description = 'Indexa portadas locales para similitud visual sin cambiar productos ni aplicar sugerencias.';

    public function handle(MatiAiClient $client, CatalogAiService $catalog, CatalogImageReader $reader, AiReviewService $reviews): int
    {
        if (!config('mati_ai.enabled') || blank(config('mati_ai.token'))) {
            $this->error('Configura MATI_AI_ENABLED y MATI_AI_TOKEN antes de indexar.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('ai_runs')) {
            $this->error('Aplica primero la migración IA.');
            return self::FAILURE;
        }
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        $after = filter_var($this->option('after-id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($limit === false || $after === false) {
            $this->error('--limit debe estar entre 1 y 10000 y --after-id debe ser un entero no negativo.');
            return self::INVALID;
        }
        $taxonomies = $catalog->taxonomies();
        $images = ImagenPrenda::where('pri_igp', true)->whereHas('prenda')->where('rut_igp', 'like', 'productos/%')
            ->where('id_imagen_prenda', '>', $after)->orderBy('id_imagen_prenda')->limit($limit)->get();
        $indexed = $skipped = $failed = 0;
        foreach ($images as $image) {
            try {
                $bytes = $reader->read($image);
                $hash = hash('sha256', $bytes);
                $result = $client->request('analyze', [...$taxonomies, 'include_embedding' => true], $bytes);
                $embedding = data_get($result, 'data.embedding');
                if (isset($result['error']) || !is_array($embedding) || empty($embedding['values'])
                    || empty($embedding['model_id']) || empty($embedding['model_revision'])) {
                    $failed++;
                    $this->warn('Imagen '.$image->id_imagen_prenda.': embedding no disponible.');
                    continue;
                }
                // Compare the real returned embedding identity; never substitute a fabricated vector.
                $existing = AiRun::where('prenda_id', $image->cod_prendas_igp)->where('input_hash', $hash)
                    ->whereNotNull('embedding')->get()->contains(fn ($run) =>
                        data_get($run->embedding, 'model_id') === $embedding['model_id']
                        && data_get($run->embedding, 'model_revision') === $embedding['model_revision']);
                if ($existing && !$this->option('force')) {
                    $skipped++;
                    continue;
                }
                DB::transaction(function () use ($reviews, $result, $hash, $image) {
                    $run = $reviews->persist('index-catalog', $result, null, ['sha256' => $hash, 'image_id' => $image->id_imagen_prenda]);
                    $run->update(['prenda_id' => $image->cod_prendas_igp]);
                });
                $indexed++;
            } catch (\Throwable) {
                $failed++;
                Log::warning('mati_ai.catalog_index_failed', ['image_id' => $image->id_imagen_prenda]);
                $this->warn('Imagen '.$image->id_imagen_prenda.': no se pudo indexar.');
            }
        }
        $this->info('Indexadas: '.$indexed.'. Ya indexadas: '.$skipped.'. No disponibles: '.$failed.'.');
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
