<?php

namespace App\Http\Requests\Admin\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name', ...$this->allowedRoleRules()],
            'password' => ['nullable', 'confirmed', 'string', 'min:12', 'max:128'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'roles.required' => 'Selecciona al menos un rol.',
            'roles.*.not_in' => 'No tienes permiso para asignar uno de los roles seleccionados.',
        ];
    }

    private function allowedRoleRules(): array
    {
        if ($this->user()?->isSuperAdmin()) {
            return [];
        }

        return [Rule::notIn(['admin', 'super_admin'])];
    }
}
