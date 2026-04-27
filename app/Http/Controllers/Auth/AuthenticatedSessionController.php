<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $intended = $request->session()->get('url.intended');
        $intendedIsSuperadmin = $this->isSuperadminPath($intended);

        if ($user && method_exists($user, 'isSuperadmin')) {
            $user->loadMissing('rol');

            if ($user->isSuperadmin()) {
                if ($intendedIsSuperadmin) {
                    return redirect()->intended('/superadmin');
                }

                $request->session()->forget('url.intended');

                return redirect('/superadmin');
            }
        }

        if ($intendedIsSuperadmin) {
            $request->session()->forget('url.intended');

            return redirect('/')
                ->with('error', 'No tienes permisos para acceder al panel superadmin.');
        }

        return redirect()->intended('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function isSuperadminPath(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return $path === '/superadmin' || str_starts_with((string) $path, '/superadmin/');
    }
}
