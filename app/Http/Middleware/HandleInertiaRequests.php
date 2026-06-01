<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $reservationBagItems = array_values($request->session()->get('carrito', []));
        $reservationBagCount = collect($reservationBagItems)->sum(fn (array $item) => (int) ($item['cantidad'] ?? 0));

        if ($user instanceof User) {
            $user->loadMissing('rol');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nom_usu' => $user->nom_usu,
                    'app_usu' => $user->app_usu,
                    'apm_usu' => $user->apm_usu,
                    'nombre' => $user->nombreCompleto(),
                    'email' => $user->email,
                    'tel_usu' => $user->tel_usu,
                ] : null,
                'rol' => $user?->rol ? [
                    'id' => $user->rol->id_rol,
                    'nombre' => $user->rol->nom_rol,
                ] : null,
                'is_superadmin' => $user?->isSuperadmin() ?? false,
                'is_vendedor' => $user?->isVendedor() ?? false,
                'can_manage_commercial' => $user?->canManageCommercialOperations() ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'reservation_bag_count' => $reservationBagCount,
        ];
    }
}
