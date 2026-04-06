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
            'nombre' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'apellido_paterno' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'apellido_materno' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'telefono' => ['required', 'string', 'regex:/^[67]\d{7}$/'],
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
            'nombre.regex' => 'El nombre debe iniciar con mayúscula y contener solo letras y espacios.',
            'apellido_paterno.regex' => 'El apellido paterno debe iniciar con mayúscula y contener solo letras y espacios.',
            'apellido_materno.regex' => 'El apellido materno debe iniciar con mayúscula y contener solo letras y espacios.',
            'telefono.regex' => 'El teléfono debe ser de 8 dígitos numéricos válidos en la región, comenzando por 6 o 7.',
        ]);

        $role_id = \Illuminate\Support\Facades\DB::table('roles')->where('nombre_rol', 'cliente')->value('id_rol');

        $user = User::create([
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_rol' => $role_id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect('/');
    }
}
