<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierBankVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['empleado', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica']) === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['verify', 'reject'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
