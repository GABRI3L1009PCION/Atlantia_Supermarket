<?php

namespace App\Http\Requests\Cliente\Carrito;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la adicion de productos al carrito.
 */
class AddCarritoItemRequest extends FormRequest
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
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
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
            'producto_id.required' => 'Selecciona un producto valido.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'Indica la cantidad que deseas agregar.',
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
            'producto_id' => 'producto',
            'cantidad' => 'cantidad',
        ];
    }
}

