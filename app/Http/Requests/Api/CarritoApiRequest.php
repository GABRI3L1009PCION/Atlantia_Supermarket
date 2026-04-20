<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida sincronizacion de carrito por API.
 */
class CarritoApiRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],
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
            'items.required' => 'Envia al menos un producto para sincronizar.',
            'items.*.producto_id.distinct' => 'No repitas productos dentro del carrito.',
            'items.*.producto_id.exists' => 'Uno de los productos no existe.',
            'items.*.cantidad.min' => 'Cada cantidad debe ser al menos 1.',
        ];
    }
}

