<?php

namespace App\Http\Requests\Admin\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'codigo_empleado' => ['required', 'string', 'max:40', 'unique:empleados,codigo_empleado'],
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

