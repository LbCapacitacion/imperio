<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRules;

class RegistroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRules::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
            ]
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            // 'name.string' => 'El nombre debe ser una cadena de texto.',
            // 'name.max' => 'El nombre no debe exceder los 255 caracteres.',
             'email.required' => 'El correo electrónico es obligatorio.',
             'email.email' => 'El correo electrónico debe ser una dirección válida.',
            // 'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
             'email.unique' => 'El correo electrónico ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            // 'password.confirmed' => 'La confirmación de la contraseña no coincide.',
             'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe contener al menos una letra.',
             'password.numbers' => 'La contraseña debe contener al menos un número.',
             'password.symbols' => 'La contraseña debe contener al menos un símbolo.'
        ];
    }   
}
