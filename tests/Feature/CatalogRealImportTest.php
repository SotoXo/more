<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Prenda;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\Talla;
use App\Models\Temporada;
use App\Models\User;
use App\Services\AI\AiPublicationService;
use App\Services\AI\AiReviewService;
use App\Services\AuditLogService;
use App\Services\InventoryMovementService;
use App\Services\SystemLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CatalogRealImportTest extends TestCase
{
    private string $testStorage;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->testStorage = sys_get_temp_dir().'/mati-import-test-'.bin2hex(random_bytes(6));
        $this->app->useStoragePath($this->testStorage);
        mkdir(storage_path('app/catalog-import/images'), 0755, true);
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->app->instance(AuditLogService::class, Mockery::mock(AuditLogService::class)->shouldIgnoreMissing());
        $this->app->instance(InventoryMovementService::class, Mockery::mock(InventoryMovementService::class)->shouldIgnoreMissing());
        $aiReview = Mockery::mock(AiReviewService::class);
        $aiReview->shouldNotReceive('attachSafelyToProduct');
        $this->app->instance(AiReviewService::class, $aiReview);
        $aiPublication = Mockery::mock(AiPublicationService::class);
        $aiPublication->shouldNotReceive('attach');
        $aiPublication->shouldNotReceive('approvedRun');
        $this->app->instance(AiPublicationService::class, $aiPublication);
        $tables = [
            'roles' => ['id_rol', ['nom_rol', 'est_rol']],
            'users' => ['id', ['nom_usu', 'cod_roles_usu', 'est_usu']],
            'marcas' => ['id_marca', ['nom_mar', 'est_mar']],
            'categorias' => ['id_categoria', ['nom_cat', 'est_cat', 'tip_tal_cat', 'gru_tal_cat']],
            'temporadas' => ['id_temporada', ['nom_tem', 'est_tem']],
            'colores' => ['id_color', ['nom_col', 'est_col']],
            'tallas' => ['id_talla', ['nom_tal', 'est_tal', 'seg_tal', 'sis_tal', 'gru_tal']],
            'prendas' => ['id_prenda', ['nom_pre', 'des_pre', 'modelo_pre', 'cod_categorias_pre', 'cod_marcas_pre', 'cod_temporadas_pre', 'gen_pre', 'prb_pre', 'est_pre', 'cod_usuario_registra_pre', 'fec_pre']],
            'prenda_variantes' => ['id_prenda_variante', ['cod_prendas_prv', 'cod_colores_prv', 'cod_tallas_prv', 'sku_prv', 'cob_prv', 'pve_prv', 'cos_prv', 'est_prv', 'fec_prv']],
            'inventarios' => ['id_inventario', ['cod_prenda_variantes_inv', 'cdi_inv', 'cre_inv', 'ubi_inv']],
            'imagenes_prenda' => ['id_imagen_prenda', ['cod_prendas_igp', 'cod_prenda_variantes_igp', 'rut_igp', 'url_igp', 'pri_igp', 'ord_igp', 'fue_igp', 'fec_igp']],
            'system_logs' => ['id', ['cod_usuario_slg', 'rol_slg', 'modulo_slg', 'accion_slg', 'entidad_tipo_slg', 'entidad_id_slg', 'descripcion_slg', 'nivel_slg', 'ip_slg', 'user_agent_slg', 'metodo_http_slg', 'ruta_slg', 'metadata_slg']],
        ];
        foreach ($tables as $name => [$key, $fields]) {
            Schema::create($name, function (Blueprint $table) use ($key, $fields) {
                $table->id($key);
                foreach ($fields as $field) {
                    $field === 'metadata_slg' ? $table->json($field)->nullable() : $table->string($field)->nullable();
                }
                $table->timestamps();
            });
        }
        $role = Role::query()->create(['nom_rol' => 'SUPERADMIN', 'est_rol' => true]);
        $this->actor = User::query()->create(['nom_usu' => 'TEST', 'cod_roles_usu' => $role->getKey(), 'est_usu' => true]);
        Marca::query()->create(['nom_mar' => 'ZARA', 'est_mar' => true]);
        Categoria::query()->create(['nom_cat' => 'CAMISAS', 'est_cat' => true, 'tip_tal_cat' => 'ALFA', 'gru_tal_cat' => 'SUPERIOR']);
        Temporada::query()->create(['nom_tem' => 'PERMANENTE', 'est_tem' => true]);
        Color::query()->create(['nom_col' => 'AZUL MARINO', 'est_col' => true]);
        Talla::query()->create(['nom_tal' => 'M', 'est_tal' => true, 'seg_tal' => 'ADULTO', 'sis_tal' => 'ALFA', 'gru_tal' => 'SUPERIOR']);
        $image = UploadedFile::fake()->image('fixture.jpg', 300, 400);
        copy($image->getPathname(), storage_path('app/catalog-import/images/fixture.jpg'));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->testStorage);
        parent::tearDown();
    }

    public function test_dry_run_writes_no_products_inventory_images_or_receipts(): void
    {
        $manifest = $this->manifest([$this->entry()]);
        $this->artisan('catalog:import-real', ['manifest' => $manifest, '--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Prenda::query()->count());
        $this->assertSame(0, Inventario::query()->count());
        $this->assertDatabaseCount('system_logs', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $report = $this->report('dry_run_report.json');
        $this->assertSame(1, $report['counts']['valid']);
        $this->assertSame(0, $report['counts']['imported']);
        $this->assertSame('unknown', $report['valid'][0]['rights_status']);
    }

    public function test_import_uses_domain_storage_zero_reservations_and_is_idempotent(): void
    {
        $manifest = $this->manifest([$this->entry()]);
        $this->artisan('catalog:import-real', ['manifest' => $manifest])->assertSuccessful();
        $product = Prenda::query()->with(['variantes.inventario', 'imagenes'])->sole();
        $this->assertSame('REF-100', $product->modelo_pre);
        $variant = $product->variantes->sole();
        $this->assertSame('CAM-00001', $variant->sku_prv);
        $this->assertNull($variant->cob_prv);
        $this->assertSame(1, $variant->inventario->cdi_inv);
        $this->assertSame(0, $variant->inventario->cre_inv);
        $image = $product->imagenes->sole();
        $this->assertStringStartsWith('productos/1/', $image->rut_igp);
        Storage::disk('public')->assertExists($image->rut_igp);
        $this->artisan('catalog:import-real', ['manifest' => $manifest])->assertSuccessful();
        $this->assertSame(1, Prenda::query()->count());
        $this->assertSame(1, $this->report('import_report.json')['counts']['skipped_duplicates']);
    }

    public function test_failed_product_rolls_back_and_next_product_can_succeed(): void
    {
        $realLogs = new SystemLogService;
        $logs = Mockery::mock(SystemLogService::class)->shouldIgnoreMissing();
        $logs->shouldReceive('info')->andReturnUsing(function (...$args) use ($realLogs) {
            if ($args[1] === 'catalog_real_imported' && $args[3]->modelo_pre === 'FAIL-100') {
                return null;
            }

            return $realLogs->info(...$args);
        });
        $this->app->instance(SystemLogService::class, $logs);
        $bad = $this->entry('FAIL-100', 'CAMISA FALLIDA');
        $good = $this->entry('OK-101', 'CAMISA VÁLIDA');
        $this->artisan('catalog:import-real', ['manifest' => $this->manifest([$bad, $good])])->assertFailed();
        $this->assertSame('OK-101', Prenda::query()->sole()->modelo_pre);
        $this->assertSame(1, Inventario::query()->count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $report = $this->report('import_report.json');
        $this->assertSame(1, $report['counts']['failed']);
        $this->assertSame(1, $report['counts']['imported']);
    }

    public function test_preflight_rejects_inactive_colors_and_path_escape_before_any_write(): void
    {
        Color::query()->create(['nom_col' => 'ROJO VINO', 'est_col' => false]);
        $inactive = $this->entry();
        $inactive['variants'][0]['color'] = 'ROJO VINO';
        $escaped = $this->entry('REF-101');
        $escaped['images'][0]['local_path'] = 'composer.json';
        $this->artisan('catalog:import-real', ['manifest' => $this->manifest([$inactive, $escaped])])->assertFailed();
        $this->assertSame(0, Prenda::query()->count());
        $this->assertSame(2, $this->report('import_report.json')['counts']['invalid']);
    }

    public function test_preflight_rejects_unconfirmed_size_and_prohibited_rights(): void
    {
        $unconfirmed = $this->entry();
        $unconfirmed['metadata']['source_sizes'] = ['XS', 'S'];
        $prohibited = $this->entry('REF-102');
        $prohibited['images'][0]['rights_status'] = 'prohibited';
        $this->artisan('catalog:import-real', ['manifest' => $this->manifest([$unconfirmed, $prohibited]), '--dry-run' => true])->assertFailed();
        $this->assertSame(2, $this->report('dry_run_report.json')['counts']['invalid']);
        $this->assertSame(0, Prenda::query()->count());
    }

    public function test_idempotency_survives_missing_model_column_and_deleted_operational_report(): void
    {
        Schema::table('prendas', fn (Blueprint $table) => $table->dropColumn('modelo_pre'));
        $manifest = $this->manifest([$this->entry()]);
        $this->artisan('catalog:import-real', ['manifest' => $manifest])->assertSuccessful();
        unlink(storage_path('app/catalog-import/import_report.json'));
        $sameReference = $this->entry();
        $sameReference['source']['url'] = 'https://example.com/new-public-path';
        $this->artisan('catalog:import-real', ['manifest' => $this->manifest([$sameReference])])->assertSuccessful();
        $this->assertSame(1, Prenda::query()->count());
        $this->assertSame(1, $this->report('import_report.json')['counts']['skipped_duplicates']);
        $receipt = SystemLog::query()->where('accion_slg', 'catalog_real_imported')->sole();
        $this->assertSame('REF-100', $receipt->metadata_slg['provenance']['model_reference']);
        $this->assertSame('https://example.com/products/REF-100', $receipt->metadata_slg['provenance']['page']);
    }

    public function test_report_cannot_overwrite_manifest_image_or_lock(): void
    {
        $manifest = $this->manifest([$this->entry()]);
        $protectedManifest = storage_path('app/catalog-import/input_report.json');
        copy($manifest, $protectedManifest);
        $image = storage_path('app/catalog-import/images/fixture.jpg');
        $lock = storage_path('app/catalog-import/.import.lock');
        file_put_contents($lock, 'LOCK-FIXTURE');
        foreach ([$protectedManifest, $image, $lock] as $target) {
            $before = hash_file('sha256', $target);
            $this->artisan('catalog:import-real', ['manifest' => $protectedManifest, '--dry-run' => true, '--report' => $target])->assertFailed();
            $this->assertSame($before, hash_file('sha256', $target));
        }
        $this->assertSame(0, Prenda::query()->count());
    }

    private function entry(string $reference = 'REF-100', string $name = 'CAMISA DE PRUEBA'): array
    {
        return [
            'source' => ['url' => 'https://example.com/products/'.$reference, 'consulted_at' => '2026-10-03T20:00:00-04:00', 'rights_status' => 'unknown'],
            'product' => ['name' => $name, 'description' => 'Fixture aislado de prueba.', 'brand' => 'ZARA', 'category' => 'CAMISAS', 'gender' => 'hombre', 'season' => 'PERMANENTE', 'model_reference' => $reference, 'price_origin' => 'demo_bob', 'demo_price_bob' => 180],
            'variants' => [['color' => 'AZUL MARINO', 'size' => 'M', 'initial_stock' => 1, 'location' => 'TIENDA']],
            'images' => [['local_path' => storage_path('app/catalog-import/images/fixture.jpg'), 'source_url' => 'https://example.com/fixture.jpg', 'rights_status' => 'unknown', 'is_primary' => true]],
            'metadata' => ['source_sizes' => ['S', 'M', 'L']],
        ];
    }

    private function manifest(array $entries): string
    {
        $path = storage_path('app/catalog-import/test.json');
        file_put_contents($path, json_encode($entries, JSON_THROW_ON_ERROR));

        return $path;
    }

    private function report(string $name): array
    {
        return json_decode(file_get_contents(storage_path('app/catalog-import/'.$name)), true, 512, JSON_THROW_ON_ERROR);
    }
}
