<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Services\InventoryMovementService;
use App\Services\Superadmin\ReservationInventoryService;
use App\Services\Superadmin\Resources\AdminFormattingService;
use App\Services\Superadmin\Resources\ProductCatalogService;
use App\Services\Superadmin\Resources\ProductImageService;
use App\Services\Superadmin\Resources\ResourceDefinitionService;
use App\Services\Superadmin\Resources\ResourceQueryService;
use App\Services\Superadmin\Resources\ResourceValidationService;
use App\Services\Superadmin\Resources\ResourceWriteService;
use App\Services\Superadmin\SaleInventoryService;
use App\Services\SystemLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class InventoryP0RegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->createTestSchema();
    }

    public function test_editing_product_data_preserves_existing_inventory(): void
    {
        [$product, $variant] = $this->createProductWithInventory(5, 2);

        $this->productCatalog()->updateProductRecord(
            Request::create('/superadmin/catalogo/prendas/1', 'PUT'),
            $product,
            [
                'nom_pre' => 'PRENDA EDITADA',
                'des_pre' => 'DESCRIPCION EDITADA',
                'cod_categorias_pre' => 2,
                'cod_marcas_pre' => null,
                'cod_temporadas_pre' => null,
                'gen_pre' => 'mujer',
                'prb_pre' => 100,
                'est_pre' => true,
            ],
            $this->productValidatedPayload([$this->existingVariantRow($variant, 5)]),
        );

        $inventory = $variant->inventario()->firstOrFail();

        $this->assertSame(5, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
        $this->assertSame('PRENDA EDITADA', $product->fresh()->nom_pre);
        $this->assertSame(2, $product->fresh()->cod_categorias_pre);
    }

    public function test_sold_out_product_can_be_validated_and_edited_without_changing_inventory(): void
    {
        [$product, $variant] = $this->createProductWithInventory(0, 2);
        $payload = $this->productValidationPayload($product, [$this->existingVariantRow($variant, 0)]);
        $payload['nom_pre'] = 'PRENDA AGOTADA EDITADA';
        $request = Request::create('/superadmin/catalogo/prendas/' . $product->id_prenda, 'PUT', $payload);

        $validated = $this->productValidator()->validateWrite($request, $this->productDefinition(), $product);
        $this->productCatalog()->updateProductRecord(
            $request,
            $product,
            [...$this->productPayload($product), 'nom_pre' => $validated['nom_pre']],
            $validated,
        );

        $inventory = $variant->inventario()->firstOrFail();
        $this->assertSame('PRENDA AGOTADA EDITADA', $product->fresh()->nom_pre);
        $this->assertSame(0, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
    }

    public function test_new_variant_still_rejects_zero_initial_stock(): void
    {
        [$product, $variant] = $this->createProductWithInventory(0, 2);
        $newRow = $this->existingVariantRow($variant, 0);
        $newRow['id'] = '';
        $newRow['cod_colores_prv'] = 2;
        $newRow['cod_tallas_prv'] = 2;
        $payload = $this->productValidationPayload($product, [$this->existingVariantRow($variant, 0), $newRow]);
        $payload['usa_variantes'] = true;

        try {
            $this->productValidator()->validateWrite(
                Request::create('/superadmin/catalogo/prendas/' . $product->id_prenda, 'PUT', $payload),
                $this->productDefinition(),
                $product,
            );
            $this->fail('La variante nueva sin stock inicial debió ser rechazada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('variant_rows.1.stock_inicial', $exception->errors());
        }

        $payload['variant_rows'][1]['stock_inicial'] = 1;
        $validated = $this->productValidator()->validateWrite(
            Request::create('/superadmin/catalogo/prendas/' . $product->id_prenda, 'PUT', $payload),
            $this->productDefinition(),
            $product,
        );
        $this->assertSame(1, $validated['variant_rows'][1]['stock_inicial']);
        $this->assertSame(1, $product->variantes()->count());
        $this->assertSame(0, $variant->inventario()->firstOrFail()->cdi_inv);
    }

    public function test_foreign_variant_id_cannot_bypass_product_validation(): void
    {
        [$product] = $this->createProductWithInventory(0, 2);
        [, $foreignVariant] = $this->createProductWithInventory(0, 3);
        $payload = $this->productValidationPayload($product, [$this->existingVariantRow($foreignVariant, 0)]);

        try {
            $this->productValidator()->validateWrite(
                Request::create('/superadmin/catalogo/prendas/' . $product->id_prenda, 'PUT', $payload),
                $this->productDefinition(),
                $product,
            );
            $this->fail('El ID de otra prenda debió ser rechazado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('variant_rows', $exception->errors());
            $this->assertArrayHasKey('variant_rows.0.stock_inicial', $exception->errors());
        }

        $inventory = $foreignVariant->inventario()->firstOrFail();
        $this->assertSame(0, $inventory->cdi_inv);
        $this->assertSame(3, $inventory->cre_inv);
    }

    public function test_existing_variant_ignores_stale_stock_payload_and_never_resets_reserved_stock(): void
    {
        [$product, $variant] = $this->createProductWithInventory(5, 2);
        $row = $this->existingVariantRow($variant, 99);
        $row['ubi_inv'] = 'DEPOSITO';

        $this->productCatalog()->updateProductRecord(
            Request::create('/superadmin/catalogo/prendas/1', 'PUT'),
            $product,
            $this->productPayload($product),
            $this->productValidatedPayload([$row]),
        );

        $inventory = $variant->inventario()->firstOrFail();

        $this->assertSame(5, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
        $this->assertSame('DEPOSITO', $inventory->ubi_inv);
    }

    public function test_new_variant_initializes_inventory_with_available_stock_and_zero_reserved(): void
    {
        [$product, $variant] = $this->createProductWithInventory(5, 2);

        $newRow = $this->existingVariantRow($variant, 3);
        $newRow['id'] = '';
        $newRow['cod_colores_prv'] = 2;
        $newRow['cod_tallas_prv'] = 2;
        $newRow['cob_prv'] = 'NUEVA-2';
        $newRow['stock_inicial'] = 3;

        $this->productCatalog()->updateProductRecord(
            Request::create('/superadmin/catalogo/prendas/1', 'PUT'),
            $product,
            $this->productPayload($product),
            $this->productValidatedPayload([
                $this->existingVariantRow($variant, 5),
                $newRow,
            ]),
        );

        $newVariant = PrendaVariante::query()
            ->where('cod_prendas_prv', $product->id_prenda)
            ->where('cod_colores_prv', 2)
            ->where('cod_tallas_prv', 2)
            ->firstOrFail();
        $inventory = $newVariant->inventario()->firstOrFail();

        $this->assertSame(3, $inventory->cdi_inv);
        $this->assertSame(0, $inventory->cre_inv);
    }

    public function test_direct_sale_decreases_available_and_keeps_reserved_unchanged(): void
    {
        [, $variant] = $this->createProductWithInventory(5, 2);
        $service = new SaleInventoryService($this->movementService());

        DB::transaction(fn () => $service->reserveForSale($variant, 2));

        $inventory = $variant->inventario()->firstOrFail();
        $this->assertSame(3, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
    }

    public function test_reservation_decreases_available_and_increases_reserved(): void
    {
        [, $variant] = $this->createProductWithInventory(5, 2);
        $service = new ReservationInventoryService($this->movementService());

        DB::transaction(fn () => $service->reserve($variant, 2));

        $inventory = $variant->inventario()->firstOrFail();
        $this->assertSame(3, $inventory->cdi_inv);
        $this->assertSame(4, $inventory->cre_inv);
    }

    public function test_canceling_reservation_restores_available_and_decreases_reserved(): void
    {
        [, $variant] = $this->createProductWithInventory(3, 4);
        $service = new ReservationInventoryService($this->movementService());

        DB::transaction(fn () => $service->release($variant, 2));

        $inventory = $variant->inventario()->firstOrFail();
        $this->assertSame(5, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
    }

    public function test_collecting_reservation_decreases_reserved_without_decreasing_available_again(): void
    {
        [, $variant] = $this->createProductWithInventory(3, 4);
        $service = new ReservationInventoryService($this->movementService());

        DB::transaction(fn () => $service->complete($variant, 2));

        $inventory = $variant->inventario()->firstOrFail();
        $this->assertSame(3, $inventory->cdi_inv);
        $this->assertSame(2, $inventory->cre_inv);
    }

    public function test_manual_inventory_edit_rejects_a_stale_snapshot(): void
    {
        [, $variant, $inventory] = $this->createProductWithInventory(5, 2);
        $writer = new ResourceWriteService(new AdminFormattingService(), new ResourceDefinitionService());

        Inventario::query()->whereKey($inventory->id_inventario)->update([
            'cdi_inv' => 3,
            'cre_inv' => 4,
        ]);

        try {
            $writer->updateInventoryRecordWithLock(
                $this->inventoryDefinition(),
                $this->inventoryPayload($variant, 7, 5, 2),
                $inventory,
                static fn () => null,
            );
            $this->fail('La actualización obsoleta debió ser rechazada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cdi_inv', $exception->errors());
        }

        $current = $inventory->fresh();
        $this->assertSame(3, $current->cdi_inv);
        $this->assertSame(4, $current->cre_inv);
    }

    public function test_manual_inventory_edit_uses_locked_current_record_and_preserves_reserved(): void
    {
        [, $variant, $inventory] = $this->createProductWithInventory(5, 2);
        $writer = new ResourceWriteService(new AdminFormattingService(), new ResourceDefinitionService());

        $updated = $writer->updateInventoryRecordWithLock(
            $this->inventoryDefinition(),
            $this->inventoryPayload($variant, 7, 5, 2, 0),
            $inventory,
            static fn () => null,
        );

        $this->assertSame(7, $updated->cdi_inv);
        $this->assertSame(2, $updated->cre_inv);
        $this->assertSame($variant->id_prenda_variante, $updated->cod_prenda_variantes_inv);
    }

    private function createTestSchema(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nom_cat');
        });

        Schema::create('prendas', function (Blueprint $table) {
            $table->id('id_prenda');
            $table->string('nom_pre');
            $table->text('des_pre')->nullable();
            $table->unsignedBigInteger('cod_categorias_pre');
            $table->unsignedBigInteger('cod_marcas_pre')->nullable();
            $table->unsignedBigInteger('cod_temporadas_pre')->nullable();
            $table->string('gen_pre', 20)->nullable();
            $table->decimal('prb_pre', 10, 2)->default(0);
            $table->boolean('est_pre')->default(true);
            $table->unsignedBigInteger('cod_usuario_registra_pre')->nullable();
            $table->timestamp('fec_pre')->nullable();
            $table->timestamps();
        });

        Schema::create('prenda_variantes', function (Blueprint $table) {
            $table->id('id_prenda_variante');
            $table->unsignedBigInteger('cod_prendas_prv');
            $table->unsignedBigInteger('cod_colores_prv');
            $table->unsignedBigInteger('cod_tallas_prv');
            $table->string('sku_prv', 50)->unique();
            $table->string('cob_prv', 100)->nullable()->unique();
            $table->decimal('pve_prv', 10, 2)->default(0);
            $table->decimal('cos_prv', 10, 2)->nullable();
            $table->boolean('est_prv')->default(true);
            $table->timestamp('fec_prv')->nullable();
            $table->timestamps();
        });

        Schema::create('inventarios', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->unsignedBigInteger('cod_prenda_variantes_inv')->unique();
            $table->integer('cdi_inv')->default(0);
            $table->integer('cre_inv')->default(0);
            $table->string('ubi_inv', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('imagenes_prenda', function (Blueprint $table) {
            $table->id('id_imagen_prenda');
            $table->unsignedBigInteger('cod_prendas_igp');
            $table->unsignedBigInteger('cod_prenda_variantes_igp')->nullable();
            $table->string('rut_igp')->nullable();
            $table->string('url_igp')->nullable();
            $table->boolean('pri_igp')->default(false);
            $table->integer('ord_igp')->default(0);
            $table->string('fue_igp', 50)->nullable();
            $table->timestamps();
        });

        DB::table('categorias')->insert([
            ['id_categoria' => 1, 'nom_cat' => 'BLUSAS'],
            ['id_categoria' => 2, 'nom_cat' => 'VESTIDOS'],
        ]);
    }

    private function createProductWithInventory(int $available, int $reserved): array
    {
        $product = Prenda::query()->create([
            'nom_pre' => 'PRENDA ORIGINAL',
            'des_pre' => 'DESCRIPCION ORIGINAL',
            'cod_categorias_pre' => 1,
            'cod_marcas_pre' => null,
            'cod_temporadas_pre' => null,
            'gen_pre' => 'mujer',
            'prb_pre' => 100,
            'est_pre' => true,
            'cod_usuario_registra_pre' => null,
        ]);

        $variant = PrendaVariante::query()->create([
            'cod_prendas_prv' => $product->id_prenda,
            'cod_colores_prv' => 1,
            'cod_tallas_prv' => 1,
            'sku_prv' => 'SKU-' . $product->id_prenda,
            'cob_prv' => null,
            'pve_prv' => 100,
            'cos_prv' => 50,
            'est_prv' => true,
        ]);

        $inventory = Inventario::query()->create([
            'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
            'cdi_inv' => $available,
            'cre_inv' => $reserved,
            'ubi_inv' => 'TIENDA',
        ]);

        return [$product, $variant, $inventory];
    }

    private function productCatalog(): ProductCatalogService
    {
        $images = Mockery::mock(ProductImageService::class)->shouldIgnoreMissing();
        $logs = Mockery::mock(SystemLogService::class)->shouldIgnoreMissing();

        return new ProductCatalogService(
            new AdminFormattingService(),
            new ResourceDefinitionService(),
            Mockery::mock(ResourceQueryService::class),
            $images,
            $logs,
            $this->movementService(),
        );
    }

    private function productValidator(): ResourceValidationService
    {
        return new ResourceValidationService(new AdminFormattingService(), new ResourceDefinitionService());
    }

    private function productDefinition(): array
    {
        return ['sectionKey' => 'catalogo', 'resourceKey' => 'prendas'];
    }

    private function productValidationPayload(Prenda $product, array $rows): array
    {
        foreach ([
            ['marcas', 'id_marca'], ['temporadas', 'id_temporada'],
            ['colores', 'id_color'], ['tallas', 'id_talla'],
            ['patrones', 'id_patron'], ['texturas', 'id_textura'], ['materiales', 'id_material'],
        ] as [$tableName, $primaryKey]) {
            Schema::create($tableName, function (Blueprint $table) use ($primaryKey) {
                $table->id($primaryKey);
            });
        }
        foreach ([
            ['prenda_patrones', 'cod_prenda_ppn', 'cod_patron_ppn'],
            ['prenda_texturas', 'cod_prenda_ptx', 'cod_textura_ptx'],
            ['prenda_materiales', 'cod_prenda_pmt', 'cod_material_pmt'],
        ] as [$tableName, $productKey, $attributeKey]) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName, $productKey, $attributeKey) {
                $table->unsignedBigInteger($productKey);
                $table->unsignedBigInteger($attributeKey);
                if ($tableName === 'prenda_materiales') {
                    $table->decimal('porcentaje_pmt', 5, 2)->nullable();
                    $table->integer('ord_pmt')->default(0);
                }
                $table->timestamps();
            });
        }
        DB::table('marcas')->insert(['id_marca' => 1]);
        DB::table('temporadas')->insert(['id_temporada' => 1]);
        DB::table('colores')->insert([['id_color' => 1], ['id_color' => 2]]);
        DB::table('tallas')->insert([['id_talla' => 1], ['id_talla' => 2]]);
        $product->update(['cod_marcas_pre' => 1, 'cod_temporadas_pre' => 1]);
        $imageId = DB::table('imagenes_prenda')->insertGetId([
            'cod_prendas_igp' => $product->id_prenda,
            'pri_igp' => true,
            'ord_igp' => 0,
        ], 'id_imagen_prenda');

        return [
            ...$this->productPayload($product),
            'usa_variantes' => count($rows) > 1,
            'existing_images' => [['id' => $imageId, 'ord_igp' => 0, 'pri_igp' => true, 'remove' => false]],
            'gallery_images' => [],
            'patron_ids' => [],
            'textura_ids' => [],
            'material_rows' => [],
            'variant_rows' => $rows,
        ];
    }

    private function movementService(): InventoryMovementService
    {
        return Mockery::mock(InventoryMovementService::class)->shouldIgnoreMissing();
    }

    private function productPayload(Prenda $product): array
    {
        return [
            'nom_pre' => $product->nom_pre,
            'des_pre' => $product->des_pre,
            'cod_categorias_pre' => $product->cod_categorias_pre,
            'cod_marcas_pre' => $product->cod_marcas_pre,
            'cod_temporadas_pre' => $product->cod_temporadas_pre,
            'gen_pre' => $product->gen_pre,
            'prb_pre' => (float) $product->prb_pre,
            'est_pre' => (bool) $product->est_pre,
        ];
    }

    private function productValidatedPayload(array $rows): array
    {
        return [
            'existing_images' => [],
            'gallery_images' => [],
            'variant_rows' => $rows,
        ];
    }

    private function existingVariantRow(PrendaVariante $variant, int $stock): array
    {
        return [
            'id' => (string) $variant->id_prenda_variante,
            'cod_colores_prv' => $variant->cod_colores_prv,
            'cod_tallas_prv' => $variant->cod_tallas_prv,
            'cob_prv' => $variant->cob_prv,
            'pve_prv' => (float) $variant->pve_prv,
            'cos_prv' => (float) $variant->cos_prv,
            'stock_inicial' => $stock,
            'ubi_inv' => 'TIENDA',
            'est_prv' => true,
            'remove' => false,
        ];
    }

    private function inventoryDefinition(): array
    {
        return [
            'sectionKey' => 'inventario',
            'resourceKey' => 'stock',
        ];
    }

    private function inventoryPayload(
        PrendaVariante $variant,
        int $available,
        int $originalAvailable,
        int $originalReserved,
        int $submittedReserved = 2,
    ): array {
        return [
            'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
            'cdi_inv' => $available,
            'cre_inv' => $submittedReserved,
            'cdi_inv_original' => $originalAvailable,
            'cre_inv_original' => $originalReserved,
            'ubi_inv_original' => 'TIENDA',
            'ubi_inv' => 'TIENDA',
        ];
    }
}
