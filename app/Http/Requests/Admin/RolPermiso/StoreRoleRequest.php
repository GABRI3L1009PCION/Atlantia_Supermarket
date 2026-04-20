<?php

namespace App\Http\Requests\Admin\RolPermiso;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la creacion de roles desde el panel administrativo.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determina si el usuario puede crear roles.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('roles.gestionar') === true;
    }

    /**
     * Prepara datos para validacion.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => str($this->input('name', ''))->lower()->replace(' ', '_')->toString(),
        ]);
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_\\-\\.]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
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
            'name.required' => 'Ingresa el nombre tecnico del rol.',
            'name.regex' => 'El rol solo puede usar letras minusculas, numeros, guion, punto y guion bajo.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'permissions.*.exists' => 'Uno de los permisos seleccionados no existe.',
        ];
    }

    /**
     * Atributos legibles.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'rol',
            'permissions' => 'permisos',
        ];
    }
}
