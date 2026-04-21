<?php

namespace App\Http\Requests\Admin\Pedido;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida actualizacion masiva de pedidos.
 */
class BatchUpdatePedidoRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar la accion.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    /**
     * Reglas de validacion del lote.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pedidos' => ['required', 'array', 'min:1'],
            'pedidos.*' => ['required', 'string', 'distinct', 'exists:pedidos,uuid'],
            'estado' => ['required', 'string', Rule::in(['pendiente', 'confirmado', 'preparando', 'en_ruta', 'entregado', 'cancelado'])],
            'estado_pago' => ['required', 'string', Rule::in(['pendiente', 'validando', 'pagado', 'rechazado', 'reembolsado'])],
            'notas_historial' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
