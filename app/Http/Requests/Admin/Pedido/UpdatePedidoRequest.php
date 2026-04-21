<?php

namespace App\Http\Requests\Admin\Pedido;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,confirmado,preparando,en_ruta,entregado,cancelado'],
            'estado_pago' => ['required', 'in:pendiente,validando,pagado,rechazado,reembolsado'],
            'repartidor_id' => ['nullable', 'integer', 'exists:users,id'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'notas_historial' => ['nullable', 'string', 'max:500'],
        ];
    }
}
