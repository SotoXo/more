<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommercialOperationTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\CommercialFixtures;

    public static function entryPoints(): array
    {
        return [
            ['superadmin', '/superadmin/comercial/ventas', 'sale'],
            ['superadmin', '/superadmin/comercial/reservas', 'reservation'],
            ['vendedor', '/vendedor/ventas', 'vendorSale'],
            ['vendedor', '/vendedor/reservas', 'vendorReservation'],
            ['cliente', '/compras/online', 'onlineSale'],
            ['cliente', '/bolsa/confirmar', 'bag'],
        ];
    }

    #[DataProvider('entryPoints')]
    public function test_same_intention_creates_only_one_operation_at_every_entry_point(string $role, string $path, string $kind): void
    {
        [$customer, $variant] = $this->fixtures();
        $actor = $role === 'cliente' ? $customer : User::factory()->create([
            'cod_roles_usu' => Role::firstOrCreate(['nom_rol' => $role], ['est_rol' => true])->id_rol,
        ]);
        $payload = $this->payload($kind, $customer->id, $variant);
        if ($kind === 'bag') {
            $this->withSession(['carrito' => [$variant => ['variante_id' => $variant, 'cantidad' => 1, 'precio' => 150]]]);
        }
        $first = $this->actingAs($actor)->post($path, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($path, $payload)->assertStatus(303)->assertRedirect($first->headers->get('Location'));
        $sale = in_array($kind, ['sale', 'vendorSale', 'onlineSale']);
        $this->assertDatabaseCount($sale ? 'ventas' : 'reservas', 1);
        $this->assertDatabaseCount($sale ? 'detalle_ventas' : 'detalle_reservas', 1);
        $this->assertDatabaseCount($sale ? 'venta_pagos' : 'reserva_pagos', 1);
        $this->assertDatabaseCount('commercial_operations', 1);
        $this->assertDatabaseCount('inventario_movimientos', 1);
        $this->assertSame(2, (int) DB::table('inventarios')->value('cdi_inv'));
        $this->assertSame(in_array($kind, ['sale', 'vendorSale']) ? 0 : 1, (int) DB::table('inventarios')->value('cre_inv'));
    }

    public function test_validation_failure_rolls_back_the_intention_and_allows_correction(): void
    {
        [$customer, $variant] = $this->fixtures();
        $staff = User::factory()->create(['cod_roles_usu' => Role::create(['nom_rol' => 'superadmin', 'est_rol' => true])->id_rol]);
        $payload = $this->payload('sale', $customer->id, $variant);
        $this->actingAs($staff)->post('/superadmin/comercial/ventas', [...$payload, 'des_ven' => 999999])->assertSessionHasErrors('des_ven');
        $this->assertDatabaseCount('commercial_operations', 0);
        $this->assertDatabaseCount('ventas', 0);
        $this->post('/superadmin/comercial/ventas', $payload)->assertRedirect();
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('commercial_operations', 1);
        $this->post('/superadmin/comercial/ventas', [...$payload, 'des_ven' => 1])->assertSessionHasErrors('operation_id');
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_missing_intention_is_rejected_before_any_sale_is_created(): void
    {
        [$customer, $variant] = $this->fixtures();
        $payload = $this->payload('onlineSale', $customer->id, $variant);
        unset($payload['operation_id']);
        $this->actingAs($customer)->post('/compras/online', $payload)->assertSessionHasErrors('operation_id');
        $this->assertDatabaseCount('ventas', 0);
    }

    private function payload(string $kind, int $customer, int $variant): array
    {
        $voucher = UploadedFile::fake()->image('QA_TEST_comprobante.jpg');
        return ['operation_id' => (string) Str::uuid()] + match ($kind) {
            'sale' => ['tiv_ven' => 'fisica', 'mpa_ven' => 'efectivo', 'des_ven' => 0, 'sale_items' => [['cod_prenda_variantes_dve' => $variant, 'can_dve' => 1]]],
            'reservation' => ['cod_usuario_cliente_res' => $customer, 'des_res' => 0, 'metodo_pago_rpg' => 'qr', 'monto_declarado_rpg' => 75, 'comprobante_rpg' => $voucher, 'reservation_items' => [['cod_prenda_variantes_dre' => $variant, 'can_dre' => 1]]],
            'vendorSale' => ['customer_id' => $customer, 'payment_method' => 'efectivo', 'amount_paid' => 150, 'sale_items' => [['variant_id' => $variant, 'quantity' => 1]]],
            'vendorReservation' => ['customer_id' => $customer, 'payment_method' => 'efectivo', 'amount_paid' => 75, 'reservation_items' => [['variant_id' => $variant, 'quantity' => 1]]],
            'onlineSale' => ['metodo_pago' => 'qr', 'monto_declarado' => 150, 'comprobante' => $voucher, 'sale_items' => [['variante_id' => $variant, 'cantidad' => 1]]],
            'bag' => ['metodo_pago_rpg' => 'qr', 'monto_declarado_rpg' => 75, 'comprobante_rpg' => $voucher],
        };
    }
}
