<?php

namespace App\Providers;

use App\Models\DetalleReserva;
use App\Models\DetalleVenta;
use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\ReservaPago;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Observers\CriticalAuditObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inventario::observe(CriticalAuditObserver::class);
        Venta::observe(CriticalAuditObserver::class);
        DetalleVenta::observe(CriticalAuditObserver::class);
        Reserva::observe(CriticalAuditObserver::class);
        DetalleReserva::observe(CriticalAuditObserver::class);
        VentaPago::observe(CriticalAuditObserver::class);
        ReservaPago::observe(CriticalAuditObserver::class);
        Prenda::observe(CriticalAuditObserver::class);
        PrendaVariante::observe(CriticalAuditObserver::class);
        User::observe(CriticalAuditObserver::class);
        Role::observe(CriticalAuditObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
