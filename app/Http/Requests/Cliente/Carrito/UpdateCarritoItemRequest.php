<?php

namespace App\Http\Requests\Cliente\Carrito;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida cambios de cantidad en el carrito.
 */
class UpdateCarritoItemRequest extends FormRequest
{
    /**
     * Determina si el usuario puede realizar la solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('cliente') === true;
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cantidad.required' => 'Indica la nueva cantidad.',
            'cantidad.min' => 'La cantidad minima es 1.',
            'cantidad.max' => 'La cantidad maxima por producto es 99.',
        ];
    }

    /**
     * Atributos legibles.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cantidad' => 'cantidad',
        ];
    }
}

