<?php

namespace App\Http\Requests\Vendedor\Pedido;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('vendedor') === true;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'in:confirmado,preparando,listo_para_entrega,entregado,cancelado',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== 'entregado') {
                        return;
                    }
                    /* Solo los pedidos para recoger en tienda los entrega el comercio, y cuando ya estan listos. */
                    $pedido = $this->route('pedido');
                    if (! $pedido instanceof Pedido || $pedido->tipo_entrega !== 'recoger') {
                        $fail('Solo puedes marcar como entregados los pedidos que el cliente recoge en tienda.');
                    } elseif ($pedido->estadoValor() !== 'listo_para_entrega') {
                        $fail('Marca el pedido como listo antes de entregarlo al cliente.');
                    }
                },
            ],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes personalizados de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'array' => 'El campo :attribute debe ser una lista valida.',
            'email' => 'Ingresa un correo electronico valido.',
            'unique' => 'El valor de :attribute ya esta registrado.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'min' => 'El campo :attribute no cumple con el minimo requerido.',
            'max' => 'El campo :attribute supera el maximo permitido.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'accepted' => 'Debes aceptar :attribute.',
            'image' => 'El archivo de :attribute debe ser una imagen valida.',
            'mimes' => 'El archivo de :attribute tiene un formato no permitido.',
        ];
    }
}
