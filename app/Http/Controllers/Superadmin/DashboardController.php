<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Favorito;
use App\Models\ImagenPrenda;
use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $usersCount = User::query()->count();
        $rolesCount = Role::query()->count();
        $prendasCount = Prenda::query()->count();
        $variantesCount = PrendaVariante::query()->count();
        $imagenesCount = ImagenPrenda::query()->count();
        $ventasCount = Venta::query()->count();
        $reservasCount = Reserva::query()->count();
        $favoritosCount = Favorito::query()->count();
        $lowStockCount = Inventario::query()
            ->whereRaw('(COALESCE(cdi_inv, 0) - COALESCE(cre_inv, 0)) <= 2')
            ->count();
        $withoutImageCount = Prenda::query()->doesntHave('imagenes')->count();

        $latestPrenda = Prenda::query()->orderByDesc('fec_pre')->orderByDesc('id_prenda')->first();
        $latestVenta = Venta::query()->orderByDesc('fec_ven')->orderByDesc('id_venta')->first();
        $latestReserva = Reserva::query()->orderByDesc('fec_res')->orderByDesc('id_reserva')->first();

        return Inertia::render('Superadmin/Dashboard', [
            'metrics' => [
                [
                    'label' => 'Usuarios registrados',
                    'value' => (string) $usersCount,
                    'note' => $rolesCount . ' roles activos.',
                    'tone' => 'accent',
                    'href' => '/superadmin/seguridad/usuarios',
                ],
                [
                    'label' => 'Prendas',
                    'value' => (string) $prendasCount,
                    'note' => $variantesCount . ' variantes registradas.',
                    'tone' => 'success',
                    'href' => '/superadmin/catalogo/prendas',
                ],
                [
                    'label' => 'Ventas',
                    'value' => (string) $ventasCount,
                    'note' => 'Consulta comercial. ' . $reservasCount . ' reservas activas.',
                    'tone' => 'warning',
                    'href' => '/superadmin/comercial/ventas',
                ],
                [
                    'label' => 'Favoritos',
                    'value' => (string) $favoritosCount,
                    'note' => 'Registros guardados por clientes.',
                    'tone' => 'accent',
                    'href' => '/superadmin/comercial/favoritos',
                ],
            ],
            'moduleCards' => [
                [
                    'eyebrow' => 'Seguridad',
                    'title' => 'Roles y usuarios',
                    'description' => 'Control de cuentas internas y perfiles de acceso.',
                    'tone' => 'accent',
                    'href' => '/superadmin/seguridad/usuarios',
                ],
                [
                    'eyebrow' => 'Catálogo',
                    'title' => 'Prendas y variantes',
                    'description' => $prendasCount . ' prendas, ' . $variantesCount . ' variantes y ' . $imagenesCount . ' imágenes.',
                    'tone' => 'success',
                    'href' => '/superadmin/catalogo/prendas',
                ],
                [
                    'eyebrow' => 'Inventario',
                    'title' => 'Consulta de existencias',
                    'description' => 'Disponible, reservado y alertas por código interno, sin movimientos de inventario en esta fase.',
                    'tone' => 'warning',
                    'href' => '/superadmin/inventario/stock',
                ],
                [
                    'eyebrow' => 'Comercial',
                    'title' => 'Consulta comercial',
                    'description' => 'Lectura de ventas, apartados y favoritos registrados.',
                    'tone' => 'accent',
                    'href' => '/superadmin/comercial/ventas',
                ],
            ],
            'alerts' => [
                [
                    'eyebrow' => 'Existencias críticas',
                    'title' => $lowStockCount . ' registros por revisar',
                    'description' => 'Disponibles con margen de 2 unidades o menos.',
                    'tone' => $lowStockCount > 0 ? 'warning' : 'success',
                ],
                [
                    'eyebrow' => 'Prendas sin imagen',
                    'title' => $withoutImageCount . ' prendas pendientes',
                    'description' => 'Prendas sin recurso visual principal.',
                    'tone' => $withoutImageCount > 0 ? 'accent' : 'success',
                ],
                [
                    'eyebrow' => 'Reservas activas',
                    'title' => $reservasCount . ' registros',
                    'description' => 'Reservas abiertas dentro del módulo comercial.',
                    'tone' => 'neutral',
                ],
            ],
            'activity' => [
                [
                    'title' => $latestPrenda?->nom_pre ?? 'Sin prendas recientes',
                    'description' => $latestPrenda
                        ? 'Última prenda registrada el ' . $this->formatDate($latestPrenda->fec_pre)
                        : 'No hay prendas registradas.',
                    'date' => $this->formatDate($latestPrenda?->fec_pre),
                    'tone' => 'accent',
                ],
                [
                    'title' => $latestVenta?->nve_ven ?: 'Sin ventas recientes',
                    'description' => $latestVenta
                        ? 'Última venta por ' . $this->money((float) $latestVenta->tot_ven)
                        : 'No hay ventas registradas.',
                    'date' => $this->formatDate($latestVenta?->fec_ven),
                    'tone' => 'success',
                ],
                [
                    'title' => $latestReserva?->nre_res ?: 'Sin reservas recientes',
                    'description' => $latestReserva
                        ? 'Última reserva registrada el ' . $this->formatDate($latestReserva->fec_res)
                        : 'No hay reservas registradas.',
                    'date' => $this->formatDate($latestReserva?->fec_res),
                    'tone' => 'warning',
                ],
            ],
        ]);
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return $value->timezone(config('app.timezone'))->format('d M Y');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }
}
