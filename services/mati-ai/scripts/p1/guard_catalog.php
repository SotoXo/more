<?php
// Capture schema, AI identities and commercial table fingerprints with SELECT only.
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use App\Models\ImagenPrenda;
use App\Services\AI\CatalogImageReader;
use Illuminate\Support\Facades\DB;

$options = getopt('', ['name:', 'compare:', 'expect-idempotence']);
$name = $options['name'] ?? 'catalog-guard';
$connection = p1Connection();
$data = p1ReadOnly(function () use ($connection, $options): array {
    $tables = ['ai_model_versions', 'ai_runs', 'ai_suggestions', 'ai_human_reviews', 'ai_product_embeddings'];
    $counts = [];
    foreach ($tables as $table) $counts[$table] = DB::table($table)->count();
    $columns = DB::select("SELECT table_name,column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND (table_name LIKE 'ai_%' OR (table_name='prendas' AND column_name='modelo_pre')) ORDER BY table_name,ordinal_position");
    $vectorColumn = DB::selectOne("SELECT format_type(a.atttypid,a.atttypmod) AS type FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid WHERE c.relname='ai_product_embeddings' AND a.attname='embedding' AND NOT a.attisdropped");
    $constraints = DB::select("SELECT c.conrelid::regclass::text AS table_name,c.conname,c.contype,pg_get_constraintdef(c.oid) AS definition,c.convalidated FROM pg_constraint c WHERE c.conrelid IN ('ai_model_versions'::regclass,'ai_runs'::regclass,'ai_suggestions'::regclass,'ai_human_reviews'::regclass,'ai_product_embeddings'::regclass) ORDER BY table_name,c.conname");
    $foreignKeys = array_filter($constraints, fn ($constraint) => $constraint->contype === 'f');
    $modelo = DB::selectOne("SELECT is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='prendas' AND column_name='modelo_pre'");
    $invalid = DB::selectOne("SELECT count(*) FILTER (WHERE dimensions<>vector_dims(embedding) OR dimensions<>768) AS dimensions,count(*) FILTER (WHERE normalization<>'l2' OR norm NOT BETWEEN .999 AND 1.001 OR vector_norm(embedding) NOT BETWEEN .999 AND 1.001) AS norm FROM ai_product_embeddings");
    $orphan = DB::selectOne("SELECT count(*) FROM ai_product_embeddings e LEFT JOIN prendas p ON p.id_prenda=e.prenda_id LEFT JOIN ai_model_versions m ON m.id=e.ai_model_version_id LEFT JOIN ai_runs r ON r.id=e.source_run_id LEFT JOIN imagenes_prenda i ON i.id_imagen_prenda=e.image_id WHERE p.id_prenda IS NULL OR m.id IS NULL OR (e.source_run_id IS NOT NULL AND r.id IS NULL) OR (e.image_id IS NOT NULL AND (i.id_imagen_prenda IS NULL OR i.cod_prendas_igp<>e.prenda_id))");
    $eligible = DB::selectOne("SELECT count(*) AS image_count,count(DISTINCT i.cod_prendas_igp) AS product_count FROM imagenes_prenda i JOIN prendas p ON p.id_prenda=i.cod_prendas_igp WHERE i.pri_igp=true AND p.est_pre=true AND i.rut_igp LIKE 'productos/%'");
    $covered = DB::selectOne("SELECT count(DISTINCT e.prenda_id) AS product_count,count(DISTINCT e.image_id) AS image_count FROM ai_product_embeddings e JOIN prendas p ON p.id_prenda=e.prenda_id JOIN imagenes_prenda i ON i.id_imagen_prenda=e.image_id AND i.cod_prendas_igp=e.prenda_id WHERE p.est_pre=true AND i.pri_igp=true AND i.rut_igp LIKE 'productos/%' AND (e.metadata->>'image_path' IS NULL OR e.metadata->>'image_path'=i.rut_igp)");
    $canonical = DB::select("SELECT id,prenda_id,image_id,ai_model_version_id,source_run_id,content_hash,dimensions,geometry_key,embedding_version,preprocess_version,normalization,norm,input_norm,md5(embedding::text) AS vector_text_md5,md5(metadata::text) AS metadata_md5,created_at,updated_at FROM ai_product_embeddings ORDER BY id");
    $baseline = p1Json(file_get_contents('/var/www/html/storage/app/ai-p1/commercial-before.json'));
    $fingerprints = [];
    foreach ($baseline as $table => $evidence) {
        if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_ ,]+$/', $evidence['columns'])) {
            throw new RuntimeException('P1_BASELINE_SQL_IDENTIFIERS_INVALID');
        }
        // Keep baseline columns: the newly added nullable modelo_pre changes schema only.
        $row = DB::selectOne("SELECT count(*) AS count,COALESCE(string_agg(row_to_json(t)::text,E'\\n' ORDER BY row_to_json(t)::text),'') AS payload FROM (SELECT {$evidence['columns']} FROM {$table}) t");
        $fingerprints[$table] = ['columns' => $evidence['columns'], 'count' => (int) $row->count, 'sha256' => hash('sha256', $row->payload)];
    }
    $imageReader = app(CatalogImageReader::class);
    $imageHashes = [];
    foreach (ImagenPrenda::where('rut_igp', 'like', 'productos/%')->orderBy('id_imagen_prenda')->get() as $image) {
        $imageHashes[] = ['image_id' => (int) $image->id_imagen_prenda, 'product_id' => (int) $image->cod_prendas_igp, 'path' => $image->rut_igp, 'sha256' => hash('sha256', $imageReader->read($image))];
    }
    $checks = [
        'five_ai_tables' => count($counts) === 5,
        'modelo_pre_nullable' => $modelo && $modelo->is_nullable === 'YES',
        'vector_column_unbounded' => $vectorColumn && $vectorColumn->type === 'vector',
        'foreign_keys_11_validated' => count($foreignKeys) === 11 && count(array_filter($foreignKeys, fn ($fk) => !in_array($fk->convalidated, [true, 1, '1', 't'], true))) === 0,
        'dimensions_768_and_l2' => (int) $invalid->dimensions === 0 && (int) $invalid->norm === 0,
        'embedding_references_consistent' => (int) $orphan->count === 0,
        'commercial_baseline_fingerprints_identical' => $baseline === $fingerprints,
    ];
    $comparison = null;
    if (isset($options['compare'])) {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,80}$/', $options['compare'])) throw new RuntimeException('P1_COMPARISON_NAME_INVALID');
        $previous = p1Json(file_get_contents('/var/www/html/storage/app/ai-p1/'.$options['compare'].'.json'));
        $comparison = [
            'previous_report' => $options['compare'],
            'commercial_fingerprints_identical' => $previous['commercial_fingerprints'] === $fingerprints,
            'image_sha256_identical' => $previous['image_hashes'] === $imageHashes,
            'canonical_identities_and_values_identical' => $previous['canonical_rows'] === json_decode(json_encode($canonical, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
            'ai_counts_identical' => $previous['ai_counts'] === $counts,
        ];
    }
    if (isset($options['expect-idempotence'])) {
        $checks['idempotence_identical_canonical_rows_and_counts'] = $comparison !== null && $comparison['canonical_identities_and_values_identical'] && $comparison['ai_counts_identical'];
    }
    return [
        'utc' => gmdate('c'), 'read_only' => true, 'connection' => $connection, 'checks' => $checks,
        'ai_counts' => $counts, 'columns' => $columns, 'constraints' => $constraints,
        'eligible_images' => (int) $eligible->image_count, 'eligible_products' => (int) $eligible->product_count,
        'covered_images' => (int) $covered->image_count, 'covered_products' => (int) $covered->product_count,
        'coverage_fraction' => $eligible->product_count ? (int) $covered->product_count / (int) $eligible->product_count : null,
        'canonical_rows' => $canonical, 'commercial_fingerprints' => $fingerprints,
        'fingerprint_algorithm' => 'SHA256 UTF8 newline-joined PostgreSQL row_to_json baseline columns ordered by row_to_json(row)::text; no final newline; matches commercial-before.json',
        'image_hashes' => $imageHashes, 'comparison' => $comparison,
    ];
});
$path = p1Output($name, $data);
echo json_encode(['report' => $path, 'checks' => $data['checks'], 'ai_counts' => $data['ai_counts'], 'eligible_products' => $data['eligible_products'], 'covered_products' => $data['covered_products'], 'comparison' => $data['comparison']], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
exit(in_array(false, $data['checks'], true) ? 1 : 0);
