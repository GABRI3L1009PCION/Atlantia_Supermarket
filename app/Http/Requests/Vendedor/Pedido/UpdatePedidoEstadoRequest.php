<?php

namespace App\Http\Requests\Vendedor\Pedido;

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
            'estado' => ['required', 'in:confirmado,preparando,listo_para_entrega,cancelado'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
