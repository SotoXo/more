<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendedor
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
                ->with('error', 'Tu cuenta esta inactiva. Solicita la reactivacion a un superadmin.');
        }

        if (! method_exists($user, 'hasValidActiveRole') || ! $user->hasValidActiveRole()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Tu usuario no tiene un rol activo o valido. Solicita revision a un superadmin.');
        }

        if (
            (method_exists($user, 'isVendedor') && $user->isVendedor())
            || (method_exists($user, 'isSuperadmin') && $user->isSuperadmin())
        ) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'No tienes permisos para acceder al panel vendedor.');
    }
}
