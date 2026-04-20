<?php

namespace App\Http\Requests\Admin\Empleado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        $empleadoId = $this->route('empleado')?->id;

        return [
            'codigo_empleado' => ['required', 'string', 'max:40', Rule::unique('empleados', 'codigo_empleado')->ignore($empleadoId)],
            'departamento' => ['required', 'string', 'max:80'],
            'puesto' => ['required', 'string', 'max:120'],
            'telefono_interno' => ['nullable', 'string', 'max:30'],
            'fecha_contratacion' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'supervisor_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'permisos_operativos' => ['nullable', 'array'],
        ];
    }
}

