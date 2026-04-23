<?php

namespace App\Http\Requests\Admin\Cupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para crear cupones.
 */
class StoreCuponRequest extends FormRequest
{
    /**
     * Autoriza administracion.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60', 'unique:cupones,codigo'],
            'tipo' => ['required', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'minimo_compra' => ['nullable', 'numeric', 'min:0'],
            'maximo_descuento' => ['nullable', 'numeric', 'min:0.01'],
            'usos_maximos' => ['nullable', 'integer', 'min:1'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'activo' => ['sometimes', 'boolean'],
            'solo_primera_compra' => ['sometimes', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
