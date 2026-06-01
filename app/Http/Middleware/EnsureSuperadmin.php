<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $user->loadMissing('rol');

        if (method_exists($user, 'isActive') && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Tu cuenta está inactiva. Solicita la reactivación a un superadmin.');
        }

        if (! method_exists($user, 'hasValidActiveRole') || ! $user->hasValidActiveRole()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Tu usuario no tiene un rol activo o válido. Solicita revisión a un superadmin.');
        }

        if (method_exists($user, 'isSuperadmin') && $user->isSuperadmin()) {
            return $next($request);
        }

        if (method_exists($user, 'isVendedor') && $user->isVendedor() && $this->isSellerAllowedRoute($request)) {
            return $next($request);
        }

        if (! method_exists($user, 'canManageCommercialOperations') || ! $user->canManageCommercialOperations()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'No tienes permisos para acceder al panel administrativo.');
        }

        return redirect()
            ->route('superadmin.dashboard')
            ->with('error', 'Tu rol vendedor solo puede gestionar el flujo comercial.');
    }

    private function isSellerAllowedRoute(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        if (in_array($routeName, [
            'superadmin.dashboard',
            'superadmin.profile.edit',
            'superadmin.profile.update',
            'superadmin.profile.password.update',
        ], true)) {
            return true;
        }

        if (! str_starts_with($routeName, 'superadmin.resources.')) {
            return str_starts_with($routeName, 'superadmin.receipts.')
                || str_starts_with($routeName, 'superadmin.sales.');
        }

        return (string) $request->route('section') === 'comercial';
    }
}
