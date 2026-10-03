<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourierSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'pedido_id' => ['nullable', 'integer', 'exists:pedidos,id'],
            'external_delivery_order_id' => ['nullable', 'integer', 'exists:external_delivery_orders,id'],
            'type' => ['required', Rule::in([
                'support_chat',
                'closed_business',
                'customer_problem',
                'store_problem',
                'damaged_order',
                'incomplete_order',
                'payment_problem',
                'forgotten_item',
                'accident',
                'insurance',
            ])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'message' => ['required', 'string', 'max:1500'],
        ];
    }
}
