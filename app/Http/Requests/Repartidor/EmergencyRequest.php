<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;

class EmergencyRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
