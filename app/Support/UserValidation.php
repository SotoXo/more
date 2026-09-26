<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserValidation
{
    public static function normalizeName(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($value)), 'UTF-8');
    }

    public static function registrationRules(): array
    {
        return [
            'nom_usu' => self::upperNameRules(),
            'app_usu' => self::upperNameRules(),
            'apm_usu' => self::upperNameRules(),
            'tel_usu' => self::phoneRules(),
            'email' => self::emailRules(),
            'password' => self::passwordRules(true),
        ];
    }

    public static function adminRules(?User $user = null): array
    {
        return [
            'nom_usu' => self::upperNameRules(),
            'app_usu' => self::upperNameRules(),
            'apm_usu' => self::upperNameRulesNullable(),
            'tel_usu' => self::phoneRules(),
            'email' => self::emailRules($user),
            'cod_roles_usu' => ['required', 'integer', Rule::exists('roles', 'id_rol')],
            'est_usu' => ['nullable', 'boolean'],
            'password' => self::passwordRules($user === null),
        ];
    }

    public static function messages(): array
    {
        return [
            'nom_usu.regex' => 'El nombre debe estar en MAYÚSCULAS y solo puede contener letras y espacios.',
            'app_usu.regex' => 'El apellido paterno debe estar en MAYÚSCULAS y solo puede contener letras y espacios.',
            'apm_usu.regex' => 'El apellido materno debe estar en MAYÚSCULAS y solo puede contener letras y espacios.',
            'tel_usu.regex' => 'El teléfono debe tener 8 dígitos y comenzar con 6 o 7.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.lowercase' => 'El correo debe registrarse en minúsculas.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'cod_roles_usu.required' => 'Selecciona un rol para el usuario.',
            'cod_roles_usu.exists' => 'El rol seleccionado no es válido.',
        ];
    }

    private static function upperNameRules(): array
    {
        return [
            'required',
            'string',
            'max:100',
            'regex:/^[\p{Lu}]+(?:\s+[\p{Lu}]+)*$/u',
        ];
    }

    private static function upperNameRulesNullable(): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            'regex:/^[\p{Lu}]+(?:\s+[\p{Lu}]+)*$/u',
        ];
    }

    private static function phoneRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^[67]\d{7}$/',
        ];
    }

    private static function emailRules(?User $user = null): array
    {
        $unique = Rule::unique('users', 'email');

        if ($user) {
            $unique = $unique->ignore($user->id, 'id');
        }

        return [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            $unique,
        ];
    }

    private static function passwordRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'confirmed',
            Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers(),
        ];
    }
}
