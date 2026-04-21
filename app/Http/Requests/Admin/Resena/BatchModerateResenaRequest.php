<?php

namespace App\Http\Requests\Admin\Resena;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida moderacion masiva de resenas.
 */
class BatchModerateResenaRequest extends FormRequest
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
            'resenas' => ['required', 'array', 'min:1'],
            'resenas.*' => ['required', 'string', 'distinct', 'exists:resenas,uuid'],
            'accion' => ['required', 'string', Rule::in(['aprobar', 'rechazar', 'marcar_ml'])],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
