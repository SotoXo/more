<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserValidation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Role;
use Illuminate\Support\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'nom_usu' => mb_strtoupper(trim((string) $request->input('nom_usu')), 'UTF-8'),
            'app_usu' => mb_strtoupper(trim((string) $request->input('app_usu')), 'UTF-8'),
            'apm_usu' => mb_strtoupper(trim((string) $request->input('apm_usu')), 'UTF-8'),
            'tel_usu' => preg_replace('/\D+/', '', (string) $request->input('tel_usu')),
            'email' => mb_strtolower(trim((string) $request->input('email')), 'UTF-8'),
        ]);

        $request->validate(
            UserValidation::registrationRules(),
            UserValidation::messages(),
        );

        $role = Role::query()
            ->whereRaw('LOWER(nom_rol) = ?', ['cliente'])
            ->first();

        if (!$role) {
            throw ValidationException::withMessages([
                'email' => 'El sistema no está configurado correctamente: no se encontró el rol cliente.',
            ]);
        }

        $user = User::create([
            'nom_usu' => mb_strtoupper($request->nom_usu, 'UTF-8'),
            'app_usu' => mb_strtoupper($request->app_usu, 'UTF-8'),
            'apm_usu' => mb_strtoupper($request->apm_usu, 'UTF-8'),
            'tel_usu' => $request->tel_usu,
            'email' => mb_strtolower($request->email, 'UTF-8'),
            'password' => Hash::make($request->password),
            'cod_roles_usu' => $role->id_rol,
            'est_usu' => true,
            'fec_usu' => Carbon::now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect('/');
    }
}
