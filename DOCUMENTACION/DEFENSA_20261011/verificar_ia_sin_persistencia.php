<?php

// Ejecutar desde /var/www/html con LOG_CHANNEL=null. No usa AiController,
// no registra ai_runs, no guarda embeddings ni modifica imágenes o inventario.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = $app->make(App\Services\AI\MatiAiClient::class);
$capabilities = $client->request('capabilities');
$report = [
    'checked_at' => date(DATE_ATOM),
    'capabilities' => $capabilities,
    'scope' => 'Laravel-Python; consultas PostgreSQL solo lectura; sin persistencia de resultados',
];

if (in_array('--analyze', $argv, true)) {
    $db = Illuminate\Support\Facades\DB::connection();
    if ($db->getDriverName() !== 'pgsql') {
        throw new RuntimeException('Esta comprobación exige PostgreSQL con transacción READ ONLY.');
    }
    $db->beginTransaction();
    try {
        $db->statement('SET TRANSACTION READ ONLY');
        // Imagen local de la ficha 134, observada durante la revisión de defensa.
        $image = App\Models\ImagenPrenda::findOrFail(136);
        $bytes = $app->make(App\Services\AI\CatalogImageReader::class)->read($image);
        $taxonomies = $app->make(App\Services\AI\CatalogAiService::class)->taxonomies();
        $result = $client->request('analyze', [
            'categories' => $taxonomies['categories'],
            'colors' => $taxonomies['colors'],
            'patterns' => $taxonomies['patterns'],
            'include_embedding' => true,
        ], $bytes);
        $embedding = data_get($result, 'data.embedding', []);
        $values = $embedding['values'] ?? [];
        // Resumen real; no se exportan datos personales ni el vector completo.
        $report['analysis'] = [
            'image_id' => 136,
            'image_sha256' => hash('sha256', $bytes),
            'status' => $result['status'] ?? null,
            'model' => $result['model'] ?? null,
            'classification' => data_get($result, 'data.classification'),
            'inference' => data_get($result, 'data.inference'),
            'embedding_dimension' => $embedding['dimension'] ?? null,
            'embedding_values_count' => count($values),
            'embedding_l2_norm' => $values ? sqrt(array_sum(array_map(fn ($v) => $v * $v, $values))) : null,
            'commercial_color_status' => data_get($result, 'data.colorimetry.commercial_color_status'),
            'automatic_catalog_assignment' => data_get($result, 'data.colorimetry.automatic_catalog_assignment'),
            'human_review_required' => $result['human_review_required'] ?? null,
            'warnings' => $result['warnings'] ?? [],
            'abstentions' => $result['abstentions'] ?? [],
            'error' => $result['error'] ?? null,
            'timing_ms' => $result['timing_ms'] ?? [],
        ];
    } finally {
        $db->rollBack();
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
if (($capabilities['status'] ?? '') === 'degraded' || isset($capabilities['error'])) {
    exit(1);
}
if (isset($report['analysis']) && !$report['analysis']['embedding_values_count']) {
    exit(2);
}
