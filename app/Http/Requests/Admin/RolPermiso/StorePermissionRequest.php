<?php

namespace App\Http\Requests\Admin\RolPermiso;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la creacion de permisos operativos personalizados.
 */
class StorePermissionRequest extends FormRequest
{
    /**
     * Determina si el usuario puede crear permisos.
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
            'name' => str($this->input('name', ''))->lower()->replace(' ', '.')->toString(),
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
                'max:120',
                'regex:/^[a-z0-9_\\-\\.]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
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
            'name.required' => 'Ingresa el nombre tecnico del permiso.',
            'name.regex' => 'El permiso solo puede usar letras minusculas, numeros, guion, punto y guion bajo.',
            'name.unique' => 'Ya existe un permiso con ese nombre.',
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
            'name' => 'permiso',
        ];
    }
}
