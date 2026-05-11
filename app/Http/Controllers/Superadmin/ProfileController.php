<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing('rol');

        return Inertia::render('Superadmin/Profile/Edit', [
            'profile' => [
                'nom_usu' => $user->nom_usu,
                'app_usu' => $user->app_usu,
                'apm_usu' => $user->apm_usu,
                'tel_usu' => $user->tel_usu,
                'email' => $user->email,
                'rol' => $user->rol?->nom_rol ?? 'Sin rol',
                'estado' => $user->est_usu ? 'Activo' : 'Inactivo',
                'fec_usu' => optional($user->fec_usu)->toIso8601String(),
                'nombre_completo' => $user->nombreCompleto(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom_usu' => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'app_usu' => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'apm_usu' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'tel_usu' => ['nullable', 'string', 'max:20', 'regex:/^[67]\d{7}$/'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id, 'id'),
            ],
        ], [
            'regex' => 'Formato inválido.',
            'tel_usu.regex' => 'Ingresa un teléfono celular válido.',
        ]);

        $payload = [
            'nom_usu' => $this->normalizeUpper($validated['nom_usu']),
            'app_usu' => $this->normalizeUpper($validated['app_usu']),
            'apm_usu' => $this->normalizeUpper($validated['apm_usu'] ?? null),
            'tel_usu' => $this->normalizeNullableText($validated['tel_usu'] ?? null),
            'email' => mb_strtolower(trim($validated['email']), 'UTF-8'),
        ];

        $emailChanged = $user->email !== $payload['email'];

        $user->fill($payload);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('superadmin.profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return redirect()
            ->route('superadmin.profile.edit')
            ->with('success', 'Contraseña actualizada correctamente.');
    }

    private function normalizeUpper(?string $value): ?string
    {
        $normalized = $this->normalizeNullableText($value);

        return $normalized ? mb_strtoupper($normalized, 'UTF-8') : null;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === '' ? null : $normalized;
    }
}
