<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Superadmin\DamagedProductService;
use App\Services\Vendedor\VendedorDamagedProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DamagedProductNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_damaged_product_name_is_normalized_without_uppercasing_description(): void
    {
        $this->actingAs(User::factory()->create());
        $report = app(DamagedProductService::class)->createReport($this->request('   Polo   Niño  100% algodón - L   '));
        $this->assertSame('Polo Niño 100% algodón - L', $report->nombre_prenda_pdr);
        $this->assertSame('Descripción libre con Mayúsculas.', $report->descripcion_prenda_pdr);
        $this->assertDatabaseCount('inventario_movimientos', 0);
    }

    public function test_both_entry_points_reject_garbage_names_before_creating_records(): void
    {
        foreach (['@@@', '   ', '123', '<script>alert(1)</script>'] as $name) {
            foreach ([DamagedProductService::class, VendedorDamagedProductService::class] as $class) {
                try {
                    $service = app($class);
                    $class === DamagedProductService::class ? $service->createReport($this->request($name)) : $service->store($this->request($name));
                    $this->fail('El nombre inválido debe rechazarse.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('nombre_prenda_pdr', $exception->errors());
                }
            }
        }
        $this->assertDatabaseCount('prenda_danio_reportes', 0);
    }

    private function request(string $name): Request
    {
        return Request::create('/', 'POST', [
            'nombre_prenda_pdr' => $name,
            'descripcion_prenda_pdr' => 'Descripción libre con Mayúsculas.',
            'causa_pdr' => array_key_first(DamagedProductService::CAUSAS),
            'tipo_danio_pdr' => array_key_first(DamagedProductService::TIPOS_DANIO),
            'severidad_pdr' => array_key_first(DamagedProductService::SEVERIDADES),
            'cantidad_pdr' => 1,
            'observacion_pdr' => 'QA_TEST_revision',
        ]);
    }
}
