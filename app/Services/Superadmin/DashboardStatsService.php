<?php

namespace App\Services\Superadmin;

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Inventario;
use App\Models\Prenda;
use App\Models\PrendaVariante;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const LOW_STOCK_LIMIT = 3;

    public function payload(): array
    {
        $stockDisponible = (int) Inventario::query()->sum('cdi_inv');
        $stockReservado = (int) Inventario::query()->sum('cre_inv');
        $stockTotalOperativo = $stockDisponible + $stockReservado;
        $lowStockCount = $this->lowStockQuery()->count();
        $withoutStockCount = $this->withoutStockQuery()->count();
        $activeReservations = $this->activeReservationsQuery()->count();

        return [
            'metrics' => [
                [
                    'key' => 'stock_disponible',
                    'label' => 'Stock disponible',
                    'value' => $this->number($stockDisponible),
                    'note' => $stockReservado > 0
                        ? $this->number($stockReservado) . ' unidades comprometidas. Total operativo: ' . $this->number($stockTotalOperativo) . '.'
                        : 'Sin unidades comprometidas.',
                    'tone' => 'success',
                    'priority' => 'high',
                    'href' => '/superadmin/inventario/stock',
                    'icon' => 'warehouse',
                ],
                [
                    'key' => 'prendas_activas',
                    'label' => 'Prendas activas',
                    'value' => $this->number((int) Prenda::query()->where('est_pre', true)->count()),
                    'note' => $this->number((int) Prenda::query()->count()) . ' prendas registradas.',
                    'tone' => 'accent',
                    'priority' => 'high',
                    'href' => '/superadmin/catalogo/prendas',
                    'icon' => 'shirt',
                ],
                [
                    'key' => 'ventas',
                    'label' => 'Ventas registradas',
                    'value' => $this->number((int) Venta::query()->count()),
                    'note' => $this->money((float) Venta::query()->sum('tot_ven')) . ' acumulado.',
                    'tone' => 'warning',
                    'priority' => 'high',
                    'href' => '/superadmin/comercial/ventas',
                    'icon' => 'receipt',
                ],
                [
                    'key' => 'reservas_activas',
                    'label' => 'Reservas activas',
                    'value' => $this->number((int) $activeReservations),
                    'note' => $this->number((int) Reserva::query()->count()) . ' reservas registradas.',
                    'tone' => 'info',
                    'priority' => 'high',
                    'href' => '/superadmin/comercial/reservas',
                    'icon' => 'calendar',
                ],
                [
                    'key' => 'bajo_stock',
                    'label' => 'Productos con bajo stock',
                    'value' => $this->number((int) $lowStockCount),
                    'note' => 'Disponible menor o igual a ' . self::LOW_STOCK_LIMIT . '.',
                    'tone' => $lowStockCount > 0 ? 'danger' : 'success',
                    'priority' => 'high',
                    'href' => '/superadmin/inventario/alertas',
                    'icon' => 'alert',
                ],
                [
                    'key' => 'variantes',
                    'label' => 'Variantes registradas',
                    'value' => $this->number((int) PrendaVariante::query()->count()),
                    'note' => 'Combinaciones de color, talla y precio.',
                    'tone' => 'neutral',
                    'priority' => 'normal',
                    'href' => '/superadmin/catalogo/prendas',
                    'icon' => 'boxes',
                ],
                [
                    'key' => 'clientes',
                    'label' => 'Clientes registrados',
                    'value' => $this->number($this->usersByRole('cliente')),
                    'note' => $this->number($this->usersByRole('vendedor')) . ' vendedores activos.',
                    'tone' => 'accent',
                    'priority' => 'normal',
                    'href' => '/superadmin/seguridad/usuarios?role_slug=cliente',
                    'icon' => 'users',
                ],
                [
                    'key' => 'admins',
                    'label' => 'Administradores activos',
                    'value' => $this->number($this->usersByRole('superadmin')),
                    'note' => 'Cuentas con acceso administrativo.',
                    'tone' => 'neutral',
                    'priority' => 'normal',
                    'href' => '/superadmin/seguridad/usuarios?role_slug=superadmin',
                    'icon' => 'shield',
                ],
                [
                    'key' => 'favoritos',
                    'label' => 'Favoritos registrados',
                    'value' => $this->number((int) Favorito::query()->count()),
                    'note' => 'Prendas guardadas por clientes.',
                    'tone' => 'accent',
                    'priority' => 'normal',
                    'href' => '/superadmin/comercial/favoritos',
                    'icon' => 'heart',
                ],
                [
                    'key' => 'stock_reservado',
                    'label' => 'Stock reservado',
                    'value' => $this->number($stockReservado),
                    'note' => 'Unidades apartadas en inventario.',
                    'tone' => 'warning',
                    'priority' => 'normal',
                    'href' => '/superadmin/inventario/stock',
                    'icon' => 'clipboard',
                ],
            ],
            'charts' => [
                'stockByCategory' => $this->stockByCategory(),
                'productsByCategory' => $this->productsByCategory(),
                'stockByState' => [
                    ['label' => 'Disponible', 'value' => $stockDisponible, 'tone' => 'success'],
                    ['label' => 'Reservado', 'value' => $stockReservado, 'tone' => 'warning'],
                    ['label' => 'Bajo stock', 'value' => (int) $lowStockCount, 'tone' => 'danger'],
                    ['label' => 'Sin stock', 'value' => (int) $withoutStockCount, 'tone' => 'neutral'],
                ],
                'salesByPeriod' => $this->salesByPeriod(),
                'reservationsByState' => $this->reservationsByState(),
                'topCategories' => $this->topCategories(),
                'topBrands' => $this->topBrands(),
                'topFavorites' => $this->topFavorites(),
            ],
            'lists' => [
                'lowStock' => $this->lowStockItems(),
                'recentProducts' => $this->recentProducts(),
                'recentSales' => $this->recentSales(),
                'recentReservations' => $this->recentReservations(),
                'alerts' => $this->adminAlerts($lowStockCount, $withoutStockCount, $activeReservations),
            ],
        ];
    }

    private function usersByRole(string $role): int
    {
        return User::query()
            ->join('roles', 'roles.id_rol', '=', 'users.cod_roles_usu')
            ->whereRaw('LOWER(roles.nom_rol) = ?', [$role])
            ->where('users.est_usu', true)
            ->count();
    }

    private function lowStockQuery()
    {
        return Inventario::query()
            ->where('cdi_inv', '<=', self::LOW_STOCK_LIMIT);
    }

    private function withoutStockQuery()
    {
        return Inventario::query()
            ->where('cdi_inv', '<=', 0);
    }

    private function activeReservationsQuery()
    {
        return Reserva::query()
            ->where(function ($query) {
                $query->whereNull('est_res')
                    ->orWhereRaw("LOWER(COALESCE(est_res, '')) NOT IN (?, ?, ?, ?)", [
                        'cancelada',
                        'completada',
                        'entregada',
                        'cerrada',
                    ]);
            });
    }

    private function expiredReservationsQuery()
    {
        return $this->activeReservationsQuery()
            ->whereNotNull('ven_res')
            ->where('ven_res', '<', now());
    }

    private function stockByCategory(): array
    {
        return Inventario::query()
            ->join('prenda_variantes', 'prenda_variantes.id_prenda_variante', '=', 'inventarios.cod_prenda_variantes_inv')
            ->join('prendas', 'prendas.id_prenda', '=', 'prenda_variantes.cod_prendas_prv')
            ->leftJoin('categorias', 'categorias.id_categoria', '=', 'prendas.cod_categorias_pre')
            ->selectRaw("COALESCE(categorias.nom_cat, 'Sin categoría') as label")
            ->selectRaw('SUM(CASE WHEN COALESCE(inventarios.cdi_inv, 0) > 0 THEN COALESCE(inventarios.cdi_inv, 0) ELSE 0 END) as value')
            ->groupBy('categorias.nom_cat')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function productsByCategory(): array
    {
        return Categoria::query()
            ->leftJoin('prendas', 'prendas.cod_categorias_pre', '=', 'categorias.id_categoria')
            ->selectRaw("COALESCE(categorias.nom_cat, 'Sin categoría') as label")
            ->selectRaw('COUNT(prendas.id_prenda) as value')
            ->groupBy('categorias.id_categoria', 'categorias.nom_cat')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function salesByPeriod(): array
    {
        $from = now()->subDays(29)->startOfDay();

        return Venta::query()
            ->whereNotNull('fec_ven')
            ->where('fec_ven', '>=', $from)
            ->selectRaw('DATE(fec_ven) as period')
            ->selectRaw('COUNT(*) as value')
            ->selectRaw('SUM(COALESCE(tot_ven, 0)) as amount')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'label' => Carbon::parse($row->period)->format('d M'),
                'value' => (int) $row->value,
                'amount' => (float) $row->amount,
            ])
            ->all();
    }

    private function reservationsByState(): array
    {
        $rows = Reserva::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(est_res), ''), 'Sin estado') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => ucfirst(mb_strtolower($row->label, 'UTF-8')),
                'value' => (int) $row->value,
            ])
            ->all();

        return $rows;
    }

    private function topCategories(): array
    {
        return Categoria::query()
            ->leftJoin('prendas', 'prendas.cod_categorias_pre', '=', 'categorias.id_categoria')
            ->selectRaw('categorias.nom_cat as label')
            ->selectRaw('COUNT(prendas.id_prenda) as value')
            ->groupBy('categorias.id_categoria', 'categorias.nom_cat')
            ->havingRaw('COUNT(prendas.id_prenda) > 0')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function topBrands(): array
    {
        return DB::table('marcas')
            ->leftJoin('prendas', 'prendas.cod_marcas_pre', '=', 'marcas.id_marca')
            ->selectRaw('marcas.nom_mar as label')
            ->selectRaw('COUNT(prendas.id_prenda) as value')
            ->groupBy('marcas.id_marca', 'marcas.nom_mar')
            ->havingRaw('COUNT(prendas.id_prenda) > 0')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function topFavorites(): array
    {
        return Favorito::query()
            ->join('prendas', 'prendas.id_prenda', '=', 'favoritos.cod_prendas_fav')
            ->selectRaw('prendas.nom_pre as label')
            ->selectRaw('COUNT(favoritos.id_favorito) as value')
            ->selectRaw('MAX(prendas.id_prenda) as product_id')
            ->groupBy('prendas.nom_pre')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
                'href' => '/superadmin/catalogo/prendas/' . $row->product_id,
            ])
            ->all();
    }

    private function lowStockItems(): array
    {
        return Inventario::query()
            ->with(['variante.prenda.categoria', 'variante.prenda.marca', 'variante.color', 'variante.talla'])
            ->where('cdi_inv', '<=', self::LOW_STOCK_LIMIT)
            ->orderBy('cdi_inv')
            ->limit(6)
            ->get()
            ->map(function (Inventario $inventory) {
                $variant = $inventory->variante;
                $product = $variant?->prenda;
                $available = max(0, (int) $inventory->cdi_inv);

                return [
                    'title' => $product?->nom_pre ?? 'Prenda sin nombre',
                    'meta' => trim(implode(' · ', array_filter([
                        $variant?->color?->nom_col,
                        $variant?->talla?->nom_tal,
                        $product?->categoria?->nom_cat,
                        $product?->marca?->nom_mar,
                    ]))),
                    'value' => $this->number($available),
                    'label' => 'disponible',
                    'tone' => $available <= 0 ? 'danger' : 'warning',
                    'href' => $variant ? '/superadmin/catalogo/variantes/' . $variant->id_prenda_variante : null,
                ];
            })
            ->all();
    }

    private function recentProducts(): array
    {
        return Prenda::query()
            ->with(['categoria', 'marca'])
            ->orderByDesc('fec_pre')
            ->orderByDesc('id_prenda')
            ->limit(5)
            ->get()
            ->map(fn (Prenda $product) => [
                'title' => $product->nom_pre,
                'meta' => trim(implode(' · ', array_filter([$product->categoria?->nom_cat, $product->marca?->nom_mar]))),
                'value' => $product->est_pre ? 'Activa' : 'Inactiva',
                'label' => $this->formatDate($product->fec_pre),
                'tone' => $product->est_pre ? 'success' : 'neutral',
                'href' => '/superadmin/catalogo/prendas/' . $product->id_prenda,
            ])
            ->all();
    }

    private function recentSales(): array
    {
        return Venta::query()
            ->orderByDesc('fec_ven')
            ->orderByDesc('id_venta')
            ->limit(5)
            ->get()
            ->map(fn (Venta $sale) => [
                'title' => $sale->nve_ven ?: 'Venta #' . $sale->id_venta,
                'meta' => $this->formatDate($sale->fec_ven),
                'value' => $this->money((float) $sale->tot_ven),
                'label' => $sale->est_ven ?: 'Registrada',
                'tone' => $this->statusTone($sale->est_ven),
                'href' => '/superadmin/comercial/ventas/' . $sale->id_venta,
            ])
            ->all();
    }

    private function recentReservations(): array
    {
        return Reserva::query()
            ->with(['cliente'])
            ->orderByDesc('fec_res')
            ->orderByDesc('id_reserva')
            ->limit(5)
            ->get()
            ->map(fn (Reserva $reservation) => [
                'title' => $reservation->nre_res ?: 'Reserva #' . $reservation->id_reserva,
                'meta' => $reservation->cliente?->nombreCompleto() ?: 'Sin cliente',
                'value' => $reservation->est_res ?: 'Seguimiento',
                'label' => 'Vence ' . $this->formatDate($reservation->ven_res),
                'tone' => $this->statusTone($reservation->est_res),
                'href' => '/superadmin/comercial/reservas/' . $reservation->id_reserva,
            ])
            ->all();
    }

    private function adminAlerts(int $lowStockCount, int $withoutStockCount, int $activeReservations): array
    {
        $withoutImage = Prenda::query()->doesntHave('imagenes')->count();
        $expiredReservations = $this->expiredReservationsQuery()->count();
        $variantsWithoutInventory = PrendaVariante::query()->doesntHave('inventario')->count();

        return [
            [
                'title' => 'Prendas sin imagen',
                'value' => $this->number((int) $withoutImage),
                'description' => $withoutImage > 0 ? 'Completa la ficha visual del catálogo.' : 'No hay prendas sin imagen.',
                'tone' => $withoutImage > 0 ? 'warning' : 'success',
                'href' => '/superadmin/catalogo/prendas',
            ],
            [
                'title' => 'Productos sin stock',
                'value' => $this->number($withoutStockCount),
                'description' => $withoutStockCount > 0 ? 'Revisa variantes sin unidades disponibles.' : 'No hay productos sin stock.',
                'tone' => $withoutStockCount > 0 ? 'danger' : 'success',
                'href' => '/superadmin/inventario/alertas',
            ],
            [
                'title' => 'Productos con bajo stock',
                'value' => $this->number($lowStockCount),
                'description' => $lowStockCount > 0 ? 'Hay productos cerca de agotarse.' : 'No hay productos con bajo stock.',
                'tone' => $lowStockCount > 0 ? 'warning' : 'success',
                'href' => '/superadmin/inventario/alertas',
            ],
            [
                'title' => 'Reservas vencidas',
                'value' => $this->number((int) $expiredReservations),
                'description' => $expiredReservations > 0 ? 'Revisa reservas con fecha vencida.' : 'No existen reservas vencidas.',
                'tone' => $expiredReservations > 0 ? 'danger' : 'success',
                'href' => '/superadmin/comercial/reservas',
            ],
            [
                'title' => 'Reservas activas',
                'value' => $this->number($activeReservations),
                'description' => $activeReservations > 0 ? 'Reservas abiertas para seguimiento.' : 'No existen reservas activas en este momento.',
                'tone' => $activeReservations > 0 ? 'info' : 'neutral',
                'href' => '/superadmin/comercial/reservas',
            ],
            [
                'title' => 'Variantes sin inventario',
                'value' => $this->number((int) $variantsWithoutInventory),
                'description' => $variantsWithoutInventory > 0 ? 'Completa la línea de inventario.' : 'Todas las variantes tienen inventario.',
                'tone' => $variantsWithoutInventory > 0 ? 'warning' : 'success',
                'href' => '/superadmin/catalogo/prendas',
            ],
        ];
    }

    private function statusTone(?string $value): string
    {
        return match (mb_strtolower((string) $value, 'UTF-8')) {
            'cancelada', 'inactivo', 'vencida' => 'danger',
            'pendiente', 'seguimiento' => 'warning',
            'activa', 'entregada', 'completada', 'registrada' => 'success',
            default => 'neutral',
        };
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return ($value instanceof Carbon ? $value : Carbon::parse($value))
            ->timezone(config('app.timezone'))
            ->format('d M Y');
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    private function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }
}
