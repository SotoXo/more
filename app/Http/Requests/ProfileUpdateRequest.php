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
        $rules = \App\Support\UserValidation::adminRules($this->user());

        return array_intersect_key($rules, array_flip(['nom_usu', 'app_usu', 'apm_usu', 'tel_usu', 'email']));
    }

    public function messages(): array
    {
        return \App\Support\UserValidation::messages();
    }
}
