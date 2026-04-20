<?php

namespace App\Http\Requests\Empleado\Transferencia;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['empleado', 'admin']) === true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:aprobado,rechazado'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

