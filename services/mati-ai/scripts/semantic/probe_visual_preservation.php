<?php
// Calls the existing pure FastAPI analyzer. No Laravel analysis controller or DB mutation.
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
$phase = in_array('--before', $argv, true) ? 'before' : (in_array('--after', $argv, true) ? 'after' : (in_array('--final', $argv, true) ? 'final' : null));
if (!$phase) throw new RuntimeException('Explicit before/after required');
$out = storage_path('app/ai-semantic/visual-'.$phase.'.json');
if (file_exists($out)) throw new RuntimeException('Preservation evidence exists');
$manifest = json_decode(file_get_contents(base_path('DOCUMENTACION/IA/AUDITORIA_FINAL/evaluation_manifest.json')), true, 64, JSON_THROW_ON_ERROR);
$image = array_values(array_filter($manifest['images'], fn ($row) => $row['image_id'] === 13))[0];
$path = storage_path('app/public/'.$image['relative_path']);
if (hash_file('sha256', $path) !== $image['sha256']) throw new RuntimeException('Original image changed');
$base = rtrim(config('mati_ai.url'), '/');
$request = fn () => Http::baseUrl($base)->withToken(config('mati_ai.token'))->acceptJson()->timeout(90)->withoutRedirecting();
$beforeCaps = $request()->get('/api/v1/capabilities');
$fields = ['include_embedding' => 'true'];
foreach (['categories','colors','patterns'] as $key) $fields[$key] = json_encode($manifest['taxonomies'][$key], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$response = $request()->attach('image', file_get_contents($path), 'image13.jpg')->post('/api/v1/garments/analyze', $fields);
$afterCaps = $request()->get('/api/v1/capabilities');
if (!$response->successful()) throw new RuntimeException('Visual regression failed HTTP '.$response->status());
$body = $response->json();
$data = ['phase' => $phase,'image_id' => 13,'source_image_sha256' => $image['sha256'],'http' => ['capabilities_before' => $beforeCaps->status(),'analyze' => $response->status(),'capabilities_after' => $afterCaps->status()],
 'model' => $body['model'],'classification' => $body['data']['classification'],'embedding' => $body['data']['embedding'],
 'inference' => $body['data']['inference'],'colorimetry' => $body['data']['colorimetry'],
 'capabilities_visual' => $afterCaps->json('data.visual_model'),'capabilities_ocr' => $afterCaps->json('data.ocr'),
 'capabilities_text' => $afterCaps->json('data.text_embeddings'),'capabilities_colorimetry' => $afterCaps->json('data.colorimetry'),'commercial_persistence' => false];
if ($phase !== 'before') {
 $previous = json_decode(file_get_contents(storage_path('app/ai-semantic/visual-before.json')), true, 64, JSON_THROW_ON_ERROR);
 $old = $previous['embedding']['values']; $new = $data['embedding']['values']; $delta = 0.0; $dot = 0.0;
 foreach ($old as $i => $value) { $delta = max($delta, abs($value - $new[$i])); $dot += $value * $new[$i]; }
 $data['checks'] = ['same_model' => $data['model'] === $previous['model'],'same_classification' => $data['classification'] === $previous['classification'],
  'embedding_768_L2_unchanged' => count($new) === 768 && $delta <= 1e-6,'embedding_max_abs_delta' => $delta,'embedding_cosine' => $dot,
  'commercial_color_guard' => $data['capabilities_colorimetry']['automatic_catalog_assignment'] === false && $data['colorimetry']['commercial_color_status'] === 'insufficient_evidence',
  'ocr_enabled_preserved' => $data['capabilities_ocr']['enabled'] === $previous['capabilities_ocr']['enabled'],
  'gpu_visual_preserved' => $data['inference']['device'] === $previous['inference']['device']];
}
file_put_contents($out, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n"); chmod($out,0600);
echo json_encode(['phase' => $phase,'http' => $data['http'],'model' => $data['model'], 'checks' => $data['checks'] ?? null])."\n";
