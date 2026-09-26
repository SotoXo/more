<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\CommercialFixtures;
use Tests\TestCase;

class CommercialOperationConcurrencyTest extends TestCase
{
    use DatabaseMigrations, CommercialFixtures;

    public function test_two_postgresql_processes_commit_exactly_one_sale_payment_and_movement(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('testing', config('database.connections.pgsql.database'));
        [$user, $variant] = $this->fixtures();
        $command = [PHP_BINARY, base_path('tests/Support/commercial_operation_worker.php'), (string) Str::uuid(), (string) $user->id, (string) $variant];
        $env = ['APP_ENV' => 'testing', 'DB_DATABASE' => 'testing', 'DB_URL' => '', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'];
        $first = new Process($command, base_path(), $env, timeout: 15);
        $second = new Process($command, base_path(), $env, timeout: 15);
        try {
            $first->start();
            $deadline = microtime(true) + 5;
            while (!str_contains($first->getOutput(), 'CLAIMED') && $first->isRunning() && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertStringContainsString('CLAIMED', $first->getOutput(), $first->getErrorOutput());
            $second->start();
            $first->wait();
            $second->wait();
            $this->assertTrue($first->isSuccessful(), $first->getErrorOutput().$first->getOutput());
            $this->assertTrue($second->isSuccessful(), $second->getErrorOutput().$second->getOutput());
            $a = json_decode(trim(substr($first->getOutput(), strpos($first->getOutput(), "\n") + 1)), true, flags: JSON_THROW_ON_ERROR);
            $b = json_decode(trim($second->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            $this->assertTrue($a['created']);
            $this->assertFalse($b['created']);
            $this->assertSame($a['location'], $b['location']);
            foreach (['commercial_operations', 'ventas', 'detalle_ventas', 'venta_pagos', 'inventario_movimientos'] as $table) {
                $this->assertDatabaseCount($table, 1);
            }
            $this->assertSame(2, (int) DB::table('inventarios')->value('cdi_inv'));
            $this->assertSame(0, (int) DB::table('inventarios')->value('cre_inv'));
        } finally {
            $first->stop();
            $second->stop();
        }
    }
}
