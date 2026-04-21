<?php

namespace App\Http\Requests\Admin\Antifraude;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida resolucion de alertas antifraude.
 */
class ResolveFraudAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin', 'empleado']) === true;
    }

    public function rules(): array
    {
        return [
            'resuelta' => ['required', 'boolean'],
            'accion' => ['required', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
