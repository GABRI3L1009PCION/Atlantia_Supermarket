<?php

namespace App\Http\Requests\Admin\Empleado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        $empleadoId = $this->route('empleado')?->id;
        $userId = $this->route('empleado')?->user_id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'codigo_empleado' => ['required', 'string', 'max:40', Rule::unique('empleados', 'codigo_empleado')->ignore($empleadoId)],
            'departamento' => ['required', 'string', 'max:80'],
            'puesto' => ['required', 'string', 'max:120'],
            'telefono_interno' => ['nullable', 'string', 'max:30'],
            'fecha_contratacion' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'supervisor_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'permisos_operativos' => ['nullable', 'array'],
            'permisos_operativos.*' => ['string', Rule::in([
                'contacto',
                'transferencias',
                'moderacion',
                'reportes',
                'soporte',
            ])],
        ];
    }
}
