<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\Role;
use App\Models\User;
use App\Services\CommercialNotificationService;
use App\Services\InventoryMovementService;
use App\Services\Superadmin\PickupLogisticsService;
use App\Services\Superadmin\ReservationInventoryService;
use App\Services\Superadmin\ReservationNumberService;
use App\Services\Superadmin\ReservationPaymentService;
use App\Services\Superadmin\ReservationService;
use App\Services\Superadmin\ReservationToSaleService;
use App\Services\Superadmin\Resources\AdminFormattingService;
use App\Services\SystemLogService;
use App\Services\Vendedor\VendedorReservationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CriticalReservationCheckoutP1Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.timezone', 'UTC');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_variant_and_product_with_stock_can_be_added_to_reservation_cart(): void
    {
        [, $variant] = $this->createProductWithInventory(true, true, 5, 0);

        $this->postJson('/api/carrito/agregar', [
            'variante_id' => $variant->id_prenda_variante,
            'cantidad' => 2,
        ])->assertOk();

        $this->assertSame(2, session('carrito.'.$variant->id_prenda_variante.'.cantidad'));
    }

    public function test_inactive_variant_cannot_be_added_to_reservation_cart(): void
    {
        [, $variant] = $this->createProductWithInventory(true, false, 5, 0);

        $this->postJson('/api/carrito/agregar', [
            'variante_id' => $variant->id_prenda_variante,
            'cantidad' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('variante_id');

        $this->assertNull(session('carrito'));
    }

    public function test_inactive_product_cannot_be_added_to_reservation_cart(): void
    {
        [, $variant] = $this->createProductWithInventory(false, true, 5, 0);

        $this->postJson('/api/carrito/agregar', [
            'variante_id' => $variant->id_prenda_variante,
            'cantidad' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('variante_id');

        $this->assertNull(session('carrito'));
    }

    public function test_inactive_cart_variant_cannot_have_its_quantity_updated(): void
    {
        [, $variant] = $this->createProductWithInventory(true, true, 5, 0);

        $this->postJson('/api/carrito/agregar', [
            'variante_id' => $variant->id_prenda_variante,
            'cantidad' => 1,
        ])->assertOk();

        $variant->update(['est_prv' => false]);

        $this->patchJson('/api/carrito/'.$variant->id_prenda_variante, [
            'cantidad' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('variante_id');

        $this->assertSame(1, session('carrito.'.$variant->id_prenda_variante.'.cantidad'));
    }

    public function test_product_deactivated_after_cart_add_is_rejected_at_confirmation_without_inventory_changes(): void
    {
        [$product, $variant, $inventory] = $this->createProductWithInventory(true, true, 5, 0);
        $customer = $this->createUser('cliente');

        $this->postJson('/api/carrito/agregar', [
            'variante_id' => $variant->id_prenda_variante,
            'cantidad' => 2,
        ])->assertOk();

        $product->update(['est_pre' => false]);

        $this->actingAs($customer)->post('/bolsa/confirmar', [
            'monto_declarado_rpg' => 100,
            'metodo_pago_rpg' => 'qr',
            'comprobante_rpg' => UploadedFile::fake()->image('reserva.jpg'),
        ])->assertSessionHasErrors('reservation_items');

        $currentInventory = $inventory->fresh();
        $this->assertSame(5, $currentInventory->cdi_inv);
        $this->assertSame(0, $currentInventory->cre_inv);
        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('detalle_reservas', 0);
        $this->assertDatabaseCount('reserva_pagos', 0);
    }

    public function test_final_reservation_transaction_rechecks_product_activity_before_creating_or_moving_stock(): void
    {
        [$product, $variant, $inventory] = $this->createProductWithInventory(true, true, 5, 0);
        $customer = $this->createUser('cliente');
        $request = Request::create('/bolsa/confirmar', 'POST');
        $request->setUserResolver(fn () => $customer);
        $product->update(['est_pre' => false]);

        $this->assertValidationFailure(
            fn () => $this->reservationService()->createReservation($request, [
                'cod_usuario_cliente_res' => $customer->id,
                'des_res' => 0,
                'obs_res' => null,
                'monto_declarado_rpg' => 100,
                'metodo_pago_rpg' => 'qr',
                'reservation_items' => [[
                    'cod_prenda_variantes_dre' => $variant->id_prenda_variante,
                    'can_dre' => 1,
                ]],
            ]),
            'reservation_items',
        );

        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
        $this->assertDatabaseCount('reservas', 0);
        $this->assertDatabaseCount('detalle_reservas', 0);
        $this->assertDatabaseCount('reserva_pagos', 0);
    }

    public function test_active_customer_can_open_checkout(): void
    {
        $customer = $this->createUser('cliente');

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk();

        $this->assertTrue(true);
    }

    public function test_inactive_customer_is_rejected_from_checkout_without_side_effects(): void
    {
        $this->assertCheckoutRejectedWithoutEffects($this->createUser('cliente', false));
    }

    public function test_customer_with_inactive_role_is_rejected_from_checkout_without_side_effects(): void
    {
        $this->assertCheckoutRejectedWithoutEffects($this->createUser('cliente', true, false));
    }

    public function test_vendor_is_rejected_from_customer_checkout_without_side_effects(): void
    {
        $this->assertCheckoutRejectedWithoutEffects($this->createUser('vendedor'));
    }

    public function test_superadmin_is_rejected_from_customer_checkout_without_side_effects(): void
    {
        $this->assertCheckoutRejectedWithoutEffects($this->createUser('superadmin'));
    }

    public function test_reservation_without_confirmed_payments_can_be_cancelled_and_releases_inventory(): void
    {
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory('confirmada');

        $this->reservationService()->cancel($reservation, 'Cliente desistió.');

        $this->assertSame('cancelada', $reservation->fresh()->est_res);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
    }

    public function test_pending_payment_does_not_count_as_confirmed_and_cancellation_is_allowed(): void
    {
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory('por_procesar');
        $payment = $this->createReservationPayment($reservation, 'pendiente_revision', 0, 50);

        $this->reservationService()->cancel($reservation, 'Comprobante no confirmado.');

        $this->assertSame('cancelada', $reservation->fresh()->est_res);
        $this->assertSame('pendiente_revision', $payment->fresh()->est_rpg);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
    }

    public function test_confirmed_payment_blocks_superadmin_cancellation_without_partial_changes(): void
    {
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory('confirmada');
        $payment = $this->createReservationPayment($reservation, 'confirmado', 50, 50);

        $this->assertValidationFailure(
            fn () => $this->reservationService()->cancel($reservation, 'Intento inválido.'),
            'reservation_action',
        );

        $this->assertSame('confirmada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
        $this->assertSame('confirmado', $payment->fresh()->est_rpg);
        $this->assertSame('50.00', $payment->fresh()->monto_confirmado_rpg);
    }

    public function test_confirmed_payment_blocks_vendor_cancellation_without_partial_changes(): void
    {
        $vendor = $this->createUser('vendedor');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'confirmada',
            null,
            $vendor->id,
        );
        $payment = $this->createReservationPayment($reservation, 'confirmado', 50, 50);
        $request = Request::create('/vendedor/reservas/'.$reservation->id_reserva.'/cancelar', 'POST', [
            'cancellation_reason' => 'Intento inválido.',
        ]);
        $request->setUserResolver(fn () => $vendor);

        $this->assertValidationFailure(
            fn () => $this->vendorReservationService()->cancel($request, $reservation),
            'reservation_action',
        );

        $this->assertSame('confirmada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
        $this->assertSame('confirmado', $payment->fresh()->est_rpg);
    }

    public function test_second_cancellation_is_rejected_and_does_not_release_inventory_twice(): void
    {
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory('confirmada');
        $service = $this->reservationService();

        $service->cancel($reservation, 'Primera cancelación.');
        $afterFirstCancellation = $inventory->fresh();

        $this->assertValidationFailure(
            fn () => $service->cancel($reservation, 'Segunda cancelación.'),
            'reservation_action',
        );

        $this->assertSame('cancelada', $reservation->fresh()->est_res);
        $this->assertSame(5, $afterFirstCancellation->cdi_inv);
        $this->assertSame(0, $afterFirstCancellation->cre_inv);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
    }

    public function test_future_deadline_cannot_expire_reservation_and_changes_nothing(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'confirmada',
            Carbon::now()->addMinute(),
        );
        $payment = $this->createReservationPayment($reservation, 'pendiente_revision', 0, 50);

        $this->assertValidationFailure(
            fn () => $this->reservationService()->markExpired($reservation),
            'reservation_action',
        );

        $this->assertSame('confirmada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
        $this->assertSame('pendiente_revision', $payment->fresh()->est_rpg);
        $this->assertFalse($this->reservationService()->isVisuallyExpired($reservation->fresh()));
    }

    public function test_manual_http_request_cannot_expire_reservation_before_deadline(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        $superadmin = $this->createUser('superadmin');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'confirmada',
            Carbon::now()->addMinute(),
            $superadmin->id,
        );
        $payment = $this->createReservationPayment($reservation, 'pendiente_revision', 0, 50);

        $this->actingAs($superadmin)
            ->put('/superadmin/comercial/reservas/'.$reservation->id_reserva, [
                'reservation_action' => 'expire',
            ])
            ->assertSessionHasErrors('reservation_action');

        $this->assertSame('confirmada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
        $this->assertSame('pendiente_revision', $payment->fresh()->est_rpg);
    }

    public function test_deadline_exactly_reached_can_expire_reservation(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'confirmada',
            Carbon::now(),
        );
        $service = $this->reservationService();

        $this->assertTrue($service->isVisuallyExpired($reservation));
        $service->markExpired($reservation, 'Fecha límite alcanzada.');

        $this->assertSame('vencida', $reservation->fresh()->est_res);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
    }

    public function test_past_deadline_can_expire_reservation(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'por_procesar',
            Carbon::now()->subSecond(),
        );

        $this->reservationService()->markExpired($reservation);

        $this->assertSame('vencida', $reservation->fresh()->est_res);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(0, $inventory->fresh()->cre_inv);
    }

    public function test_cancelled_reservation_cannot_be_marked_expired(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'cancelada',
            Carbon::now()->subDay(),
        );

        $this->assertValidationFailure(
            fn () => $this->reservationService()->markExpired($reservation),
            'reservation_action',
        );

        $this->assertSame('cancelada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
    }

    public function test_completed_reservation_cannot_be_marked_expired(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        [$reservation, , $inventory] = $this->createReservationWithCommittedInventory(
            'completada',
            Carbon::now()->subDay(),
        );

        $this->assertValidationFailure(
            fn () => $this->reservationService()->markExpired($reservation),
            'reservation_action',
        );

        $this->assertSame('completada', $reservation->fresh()->est_res);
        $this->assertSame(3, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
    }

    private function assertCheckoutRejectedWithoutEffects(User $user): void
    {
        [, , $inventory] = $this->createProductWithInventory(true, true, 5, 2);

        $this->actingAs($user)
            ->post('/compras/online')
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('detalle_ventas', 0);
        $this->assertDatabaseCount('venta_pagos', 0);
        $this->assertDatabaseCount('inventario_movimientos', 0);
        $this->assertSame(5, $inventory->fresh()->cdi_inv);
        $this->assertSame(2, $inventory->fresh()->cre_inv);
    }

    private function assertValidationFailure(callable $callback, string $errorKey): ValidationException
    {
        try {
            $callback();
            $this->fail('La operación debió ser rechazada por una regla de negocio.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($errorKey, $exception->errors());

            return $exception;
        }
    }

    private function reservationService(): ReservationService
    {
        $logs = Mockery::mock(SystemLogService::class)->shouldIgnoreMissing();

        return new ReservationService(
            new ReservationNumberService,
            $this->reservationInventoryService(),
            new ReservationPaymentService($logs),
            new PickupLogisticsService,
            Mockery::mock(ReservationToSaleService::class)->shouldIgnoreMissing(),
            new AdminFormattingService,
            Mockery::mock(CommercialNotificationService::class)->shouldIgnoreMissing(),
            $logs,
        );
    }

    private function vendorReservationService(): VendedorReservationService
    {
        $logs = Mockery::mock(SystemLogService::class)->shouldIgnoreMissing();

        return new VendedorReservationService(
            new ReservationNumberService,
            $this->reservationInventoryService(),
            new ReservationPaymentService($logs),
            new PickupLogisticsService,
            $logs,
        );
    }

    private function reservationInventoryService(): ReservationInventoryService
    {
        return new ReservationInventoryService(
            Mockery::mock(InventoryMovementService::class)->shouldIgnoreMissing(),
        );
    }

    private function createUser(string $roleName, bool $active = true, bool $roleActive = true): User
    {
        $role = Role::query()->create([
            'nom_rol' => $roleName,
            'des_rol' => null,
            'est_rol' => $roleActive,
            'fec_rol' => Carbon::now(),
        ]);

        return User::query()->create([
            'nom_usu' => strtoupper($roleName),
            'app_usu' => 'PRUEBA',
            'apm_usu' => null,
            'tel_usu' => '70000000',
            'email' => $roleName.'-'.$role->id_rol.'@example.test',
            'password' => 'password',
            'cod_roles_usu' => $role->id_rol,
            'est_usu' => $active,
            'fec_usu' => Carbon::now(),
        ]);
    }

    private function createProductWithInventory(
        bool $productActive,
        bool $variantActive,
        int $available,
        int $reserved,
    ): array {
        $product = Prenda::query()->create([
            'nom_pre' => 'PRENDA DE PRUEBA',
            'des_pre' => null,
            'cod_categorias_pre' => 1,
            'cod_marcas_pre' => 1,
            'cod_temporadas_pre' => null,
            'gen_pre' => 'mujer',
            'prb_pre' => 100,
            'est_pre' => $productActive,
            'cod_usuario_registra_pre' => null,
            'fec_pre' => Carbon::now(),
        ]);
        $variant = PrendaVariante::query()->create([
            'cod_prendas_prv' => $product->id_prenda,
            'cod_colores_prv' => 1,
            'cod_tallas_prv' => 1,
            'sku_prv' => 'SKU-'.$product->id_prenda,
            'cob_prv' => null,
            'pve_prv' => 100,
            'cos_prv' => 50,
            'est_prv' => $variantActive,
            'fec_prv' => Carbon::now(),
        ]);
        $inventory = Inventario::query()->create([
            'cod_prenda_variantes_inv' => $variant->id_prenda_variante,
            'cdi_inv' => $available,
            'cre_inv' => $reserved,
            'ubi_inv' => 'TIENDA',
        ]);

        return [$product, $variant, $inventory];
    }

    private function createReservationWithCommittedInventory(
        string $status,
        ?Carbon $limit = null,
        ?int $responsibleId = null,
    ): array {
        [, $variant, $inventory] = $this->createProductWithInventory(true, true, 3, 2);
        $reservation = Reserva::query()->create([
            'nre_res' => 'RES-TEST-'.$variant->id_prenda_variante,
            'cod_usuario_cliente_res' => null,
            'cod_usuario_responsable_res' => $responsibleId,
            'est_res' => $status,
            'sub_res' => 200,
            'des_res' => 0,
            'tot_res' => 200,
            'monto_pagado_res' => 0,
            'saldo_res' => 200,
            'est_pago_res' => 'pendiente_revision',
            'est_logistico_res' => 'lista_recojo',
            'ven_res' => $limit,
            'fec_disponible_recojo_res' => Carbon::now()->subDay(),
            'fec_limite_recojo_res' => $limit,
            'fec_recogida_res' => null,
            'punto_recojo_res' => 'Mati Boutique',
            'origen_stock_res' => 'TIENDA',
            'cod_usuario_entrega_res' => null,
            'cod_usuario_verifica_pago_res' => null,
            'fec_verificacion_pago_res' => null,
            'obs_pago_res' => null,
            'obs_res' => null,
            'fec_res' => Carbon::now()->subDays(2),
        ]);

        DB::table('detalle_reservas')->insert([
            'cod_reservas_dre' => $reservation->id_reserva,
            'cod_prenda_variantes_dre' => $variant->id_prenda_variante,
            'can_dre' => 2,
            'pun_dre' => 100,
            'sub_dre' => 200,
            'fec_dre' => Carbon::now()->subDays(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [$reservation, $variant, $inventory];
    }

    private function createReservationPayment(
        Reserva $reservation,
        string $status,
        float $confirmed,
        float $declared,
    ): ReservaPago {
        return ReservaPago::query()->create([
            'cod_reservas_rpg' => $reservation->id_reserva,
            'monto_declarado_rpg' => $declared,
            'monto_confirmado_rpg' => $confirmed,
            'porcentaje_rpg' => $confirmed > 0 ? 25 : 0,
            'metodo_pago_rpg' => 'qr',
            'comprobante_ruta_rpg' => null,
            'comprobante_url_rpg' => null,
            'est_rpg' => $status,
            'cod_usuario_verifica_rpg' => null,
            'fec_verificacion_rpg' => $status === 'confirmado' ? Carbon::now() : null,
            'obs_rpg' => null,
            'fec_rpg' => Carbon::now(),
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nom_rol');
            $table->string('des_rol')->nullable();
            $table->boolean('est_rol')->default(true);
            $table->timestamp('fec_rol')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom_usu');
            $table->string('app_usu');
            $table->string('apm_usu')->nullable();
            $table->string('tel_usu')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('cod_roles_usu')->nullable();
            $table->boolean('est_usu')->default(true);
            $table->timestamp('fec_usu')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('marcas', function (Blueprint $table) {
            $table->id('id_marca');
            $table->string('nom_mar');
            $table->timestamps();
        });
        Schema::create('colores', function (Blueprint $table) {
            $table->id('id_color');
            $table->string('nom_col');
            $table->string('hex_col')->nullable();
            $table->timestamps();
        });
        Schema::create('tallas', function (Blueprint $table) {
            $table->id('id_talla');
            $table->string('nom_tal');
            $table->timestamps();
        });

        DB::table('marcas')->insert(['id_marca' => 1, 'nom_mar' => 'MARCA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('colores')->insert(['id_color' => 1, 'nom_col' => 'NEGRO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('tallas')->insert(['id_talla' => 1, 'nom_tal' => 'M', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

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
            $table->string('cob_prv', 100)->nullable();
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
            $table->string('ubi_inv')->nullable();
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
            $table->string('fue_igp')->nullable();
            $table->timestamps();
        });

        Schema::create('reservas', function (Blueprint $table) {
            $table->id('id_reserva');
            $table->string('nre_res')->unique();
            $table->unsignedBigInteger('cod_usuario_cliente_res')->nullable();
            $table->unsignedBigInteger('cod_usuario_responsable_res')->nullable();
            $table->string('est_res');
            $table->decimal('sub_res', 10, 2)->default(0);
            $table->decimal('des_res', 10, 2)->default(0);
            $table->decimal('tot_res', 10, 2)->default(0);
            $table->decimal('monto_pagado_res', 10, 2)->default(0);
            $table->decimal('saldo_res', 10, 2)->default(0);
            $table->string('est_pago_res')->nullable();
            $table->string('est_logistico_res')->nullable();
            $table->timestamp('ven_res')->nullable();
            $table->timestamp('fec_disponible_recojo_res')->nullable();
            $table->timestamp('fec_limite_recojo_res')->nullable();
            $table->timestamp('fec_recogida_res')->nullable();
            $table->string('punto_recojo_res')->nullable();
            $table->string('origen_stock_res')->nullable();
            $table->unsignedBigInteger('cod_usuario_entrega_res')->nullable();
            $table->unsignedBigInteger('cod_usuario_verifica_pago_res')->nullable();
            $table->timestamp('fec_verificacion_pago_res')->nullable();
            $table->text('obs_pago_res')->nullable();
            $table->text('obs_res')->nullable();
            $table->timestamp('fec_res')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_reservas', function (Blueprint $table) {
            $table->id('id_detalle_reserva');
            $table->unsignedBigInteger('cod_reservas_dre');
            $table->unsignedBigInteger('cod_prenda_variantes_dre');
            $table->integer('can_dre');
            $table->decimal('pun_dre', 10, 2);
            $table->decimal('sub_dre', 10, 2);
            $table->timestamp('fec_dre')->nullable();
            $table->timestamps();
        });

        Schema::create('reserva_pagos', function (Blueprint $table) {
            $table->id('id_reserva_pago');
            $table->unsignedBigInteger('cod_reservas_rpg');
            $table->decimal('monto_declarado_rpg', 10, 2)->default(0);
            $table->decimal('monto_confirmado_rpg', 10, 2)->default(0);
            $table->decimal('porcentaje_rpg', 5, 2)->default(0);
            $table->string('metodo_pago_rpg')->nullable();
            $table->string('comprobante_ruta_rpg')->nullable();
            $table->string('comprobante_url_rpg')->nullable();
            $table->string('est_rpg');
            $table->unsignedBigInteger('cod_usuario_verifica_rpg')->nullable();
            $table->timestamp('fec_verificacion_rpg')->nullable();
            $table->text('obs_rpg')->nullable();
            $table->timestamp('fec_rpg')->nullable();
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            $table->string('nve_ven')->nullable();
            $table->timestamps();
        });
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('id_detalle_venta');
            $table->timestamps();
        });
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id('id_venta_pago');
            $table->timestamps();
        });
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id('id_inventario_movimiento');
            $table->timestamps();
        });
    }
}
