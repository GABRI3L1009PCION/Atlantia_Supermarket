<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Request de validacion para inicio de sesion.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario puede realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->guest();
    }

    /**
     * Reglas de validacion del inicio de sesion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,dns', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'remember' => ['sometimes', 'boolean'],
            'two_factor_code' => ['sometimes', 'nullable', 'string', 'digits:6'],
        ];
    }

    /**
     * Mensajes personalizados de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Ingresa tu correo electronico.',
            'email.email' => 'Ingresa un correo electronico valido.',
            'email.max' => 'El correo electronico no debe superar :max caracteres.',
            'password.required' => 'Ingresa tu contrasena.',
            'password.min' => 'La contrasena debe tener al menos :min caracteres.',
            'password.max' => 'La contrasena no debe superar :max caracteres.',
            'remember.boolean' => 'La opcion de recordar sesion no es valida.',
            'two_factor_code.digits' => 'El codigo de verificacion debe tener 6 digitos.',
        ];
    }

    /**
     * Nombres legibles de atributos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'correo electronico',
            'password' => 'contrasena',
            'remember' => 'recordar sesion',
            'two_factor_code' => 'codigo de verificacion',
        ];
    }

    /**
     * Normaliza datos antes de validar.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'remember' => filter_var($this->input('remember', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
