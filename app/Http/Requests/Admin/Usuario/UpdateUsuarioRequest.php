<?php

namespace App\Http\Requests\Admin\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Password;

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
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'roles.required' => 'Selecciona al menos un rol.',
            'roles.*.not_in' => 'No tienes permiso para asignar uno de los roles seleccionados.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contrasena debe incluir al menos una mayuscula y una minuscula.',
            'password.symbols' => 'La contrasena debe incluir al menos un simbolo.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $usuario = $this->route('usuario');

            if (! $usuario || (int) $usuario->id !== (int) $this->user()?->id) {
                return;
            }

            if ($this->filled('password')) {
                $validator->errors()->add(
                    'password',
                    'No puedes cambiar tu propia contrasena desde el panel administrativo.'
                );
            }

            if ($this->input('email') !== $usuario->email) {
                $validator->errors()->add(
                    'email',
                    'No puedes cambiar tu propio correo desde el panel administrativo.'
                );
            }

            $currentRoles = $usuario->roles()
                ->pluck('name')
                ->sort()
                ->values()
                ->all();
            $submittedRoles = collect($this->input('roles', []))
                ->filter()
                ->sort()
                ->values()
                ->all();

            if ($submittedRoles !== $currentRoles) {
                $validator->errors()->add(
                    'roles',
                    'No puedes cambiar tus propios roles desde el panel administrativo.'
                );
            }
        });
    }

    private function allowedRoleRules(): array
    {
        $usuario = $this->route('usuario');

        if ($this->user()?->isSuperAdmin() || (int) $usuario?->id === (int) $this->user()?->id) {
            return [];
        }

        return [Rule::notIn(['admin', 'super_admin'])];
    }
}
