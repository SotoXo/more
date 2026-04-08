<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
        $request->validate([
            'nom_usu' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'app_usu' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'apm_usu' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'tel_usu' => ['required', 'string', 'regex:/^[67]\d{7}$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => [
                'required', 
                'confirmed', 
                Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
        ], [
            'nom_usu.regex' => 'El nombre debe iniciar con mayúscula y contener solo letras y espacios.',
            'app_usu.regex' => 'El apellido paterno debe iniciar con mayúscula y contener solo letras y espacios.',
            'apm_usu.regex' => 'El apellido materno debe iniciar con mayúscula y contener solo letras y espacios.',
            'tel_usu.regex' => 'El teléfono debe ser de 8 dígitos numéricos válidos en la región, comenzando por 6 o 7.',
        ]);

        // Buscar el rol CLIENTE dinámicamente
        $role = Role::where('nom_rol', 'CLIENTE')
            ->orWhere('nom_rol', 'cliente')
            ->first();

        if (!$role) {
            throw ValidationException::withMessages([
                'email' => 'El sistema no está configurado correctamente (rol CLIENTE no encontrado).',
            ]);
        }

        $user = User::create([
            'nom_usu' => mb_strtoupper($request->nom_usu, 'UTF-8'),
            'app_usu' => mb_strtoupper($request->app_usu, 'UTF-8'),
            'apm_usu' => mb_strtoupper($request->apm_usu, 'UTF-8'),
            'tel_usu' => $request->tel_usu,
            'email' => $request->email,
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
