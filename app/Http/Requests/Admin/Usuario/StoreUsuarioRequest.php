<?php

namespace App\Http\Requests\Admin\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Valida el alta administrativa de usuarios.
 */
class StoreUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario puede crear cuentas.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'role' => ['required', 'string', 'exists:roles,name', ...$this->allowedRoleRules()],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa el nombre completo.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'Ingresa el correo electronico.',
            'email.email' => 'Ingresa un correo electronico valido.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'Ingresa una contrasena temporal para el usuario.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'password.min' => 'La contrasena debe tener al menos 12 caracteres.',
            'password.letters' => 'La contrasena debe incluir letras.',
            'password.numbers' => 'La contrasena debe incluir numeros.',
            'password.symbols' => 'La contrasena debe incluir al menos un simbolo.',
            'status.required' => 'Selecciona el estado inicial del usuario.',
            'status.in' => 'El estado seleccionado no es valido.',
            'role.required' => 'Selecciona el rol del usuario.',
            'role.exists' => 'El rol seleccionado no existe.',
            'role.not_in' => 'No tienes permiso para crear usuarios con ese rol.',
        ];
    }

    /**
     * Nombres legibles para mensajes de validacion.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'email' => 'correo electronico',
            'phone' => 'telefono',
            'password' => 'contrasena',
            'password_confirmation' => 'confirmacion de contrasena',
            'status' => 'estado',
            'role' => 'rol',
        ];
    }

    /**
     * Restringe roles protegidos para administradores operativos.
     *
     * @return array<int, mixed>
     */
    private function allowedRoleRules(): array
    {
        if ($this->user()?->isSuperAdmin()) {
            return [];
        }

        return [Rule::notIn(['admin', 'super_admin'])];
    }
}
