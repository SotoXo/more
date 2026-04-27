<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Prenda;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Inertia\Inertia;

class LogsController extends Controller
{
    public function index()
    {
        $entries = collect();

        User::query()
            ->orderByDesc('fec_usu')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->each(function ($user) use ($entries) {
                $entries->push([
                    'id' => 'USR-' . $user->id,
                    'title' => trim(implode(' ', array_filter([$user->nom_usu, $user->app_usu, $user->apm_usu]))) ?: $user->email,
                    'description' => 'Alta o actualización de usuario.',
                    'date' => $this->formatDate($user->fec_usu),
                    'tone' => 'accent',
                    'sort' => $user->fec_usu?->timestamp ?? 0,
                ]);
            });

        Prenda::query()
            ->orderByDesc('fec_pre')
            ->orderByDesc('id_prenda')
            ->limit(3)
            ->get()
            ->each(function ($prenda) use ($entries) {
                $entries->push([
                    'id' => 'PRE-' . $prenda->id_prenda,
                    'title' => $prenda->nom_pre,
                    'description' => 'Alta o actualización de prenda.',
                    'date' => $this->formatDate($prenda->fec_pre),
                    'tone' => 'success',
                    'sort' => $prenda->fec_pre?->timestamp ?? 0,
                ]);
            });

        Venta::query()
            ->orderByDesc('fec_ven')
            ->orderByDesc('id_venta')
            ->limit(3)
            ->get()
            ->each(function ($venta) use ($entries) {
                $entries->push([
                    'id' => 'VEN-' . $venta->id_venta,
                    'title' => $venta->nve_ven ?: 'Venta #' . $venta->id_venta,
                    'description' => 'Venta registrada.',
                    'date' => $this->formatDate($venta->fec_ven),
                    'tone' => 'warning',
                    'sort' => $venta->fec_ven?->timestamp ?? 0,
                ]);
            });

        Reserva::query()
            ->orderByDesc('fec_res')
            ->orderByDesc('id_reserva')
            ->limit(3)
            ->get()
            ->each(function ($reserva) use ($entries) {
                $entries->push([
                    'id' => 'RES-' . $reserva->id_reserva,
                    'title' => $reserva->nre_res ?: 'Reserva #' . $reserva->id_reserva,
                    'description' => 'Reserva registrada.',
                    'date' => $this->formatDate($reserva->fec_res),
                    'tone' => 'neutral',
                    'sort' => $reserva->fec_res?->timestamp ?? 0,
                ]);
            });

        $entries = $entries
            ->sortByDesc('sort')
            ->take(10)
            ->values()
            ->map(fn ($entry) => collect($entry)->except('sort')->all())
            ->all();

        if (count($entries) === 0) {
            $entries = [
                [
                    'id' => 'LOG-000',
                    'title' => 'Sin movimientos recientes',
                    'description' => 'Todavía no hay registros para mostrar.',
                    'date' => 'Sin fecha',
                    'tone' => 'neutral',
                ],
            ];
        }

        return Inertia::render('Superadmin/Logs', [
            'metrics' => [
                [
                    'label' => 'Eventos recientes',
                    'value' => (string) count($entries),
                    'note' => 'Movimientos reunidos en esta vista.',
                    'tone' => 'accent',
                ],
                [
                    'label' => 'Usuarios',
                    'value' => (string) User::query()->count(),
                    'note' => 'Cuentas registradas.',
                    'tone' => 'accent',
                ],
                [
                    'label' => 'Prendas',
                    'value' => (string) Prenda::query()->count(),
                    'note' => 'Catálogo registrado.',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Operación',
                    'value' => (string) (Venta::query()->count() + Reserva::query()->count()),
                    'note' => 'Ventas y reservas.',
                    'tone' => 'warning',
                ],
            ],
            'entries' => $entries,
        ]);
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        return $value->timezone(config('app.timezone'))->format('d M Y');
    }
}
