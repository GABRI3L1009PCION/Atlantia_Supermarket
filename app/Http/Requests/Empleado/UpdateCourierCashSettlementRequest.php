<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierCashSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['empleado', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica']) === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'approved_amount' => ['nullable', 'numeric', 'min:0.01'],
            'admin_notes' => ['nullable', 'string', 'max:1500'],
            'settlement_reference' => ['nullable', 'string', 'max:120'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
