<?php

namespace App\Http\Requests\Admin\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class StoreNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'periodo_inicio' => ['required', 'date'],
            'periodo_fin' => ['required', 'date', 'after_or_equal:periodo_inicio'],
            'tipo_periodo' => ['required', 'in:mensual,quincenal,extraordinaria'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }
}
