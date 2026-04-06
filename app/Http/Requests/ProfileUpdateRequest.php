<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'apellido_paterno' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'apellido_materno' => ['required', 'string', 'max:100', 'regex:/^[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]*$/'],
            'telefono' => ['required', 'string', 'regex:/^[67]\d{7}$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre debe iniciar con mayúscula y contener solo letras y espacios.',
            'apellido_paterno.regex' => 'El apellido paterno debe iniciar con mayúscula y contener solo letras y espacios.',
            'apellido_materno.regex' => 'El apellido materno debe iniciar con mayúscula y contener solo letras y espacios.',
            'telefono.regex' => 'El teléfono debe ser de 8 dígitos numéricos, comenzando por 6 o 7.',
        ];
    }
}
