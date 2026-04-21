<?php

namespace App\Http\Requests\Admin\Antifraude;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida resolucion masiva de alertas antifraude.
 */
class BatchResolveFraudAlertRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar la accion.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin', 'empleado']) === true;
    }

    /**
     * Reglas de validacion del lote.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alertas' => ['required', 'array', 'min:1'],
            'alertas.*' => ['required', 'string', 'distinct', 'exists:fraud_alerts,uuid'],
            'accion' => ['required', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
