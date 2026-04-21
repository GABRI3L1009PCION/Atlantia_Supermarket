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
            'email.required' => 'Ingresa el correo electronico.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'role.exists' => 'El rol seleccionado no existe.',
            'role.not_in' => 'No tienes permiso para crear usuarios con ese rol.',
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
