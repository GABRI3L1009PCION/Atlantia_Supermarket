<?php

namespace App\Http\Requests\Admin\ExternalDelivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    public function rules(): array
    {
        return [
            'source_channel' => ['nullable', 'string', 'max:80'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'store_name' => ['required', 'string', 'max:160'],
            'store_contact_name' => ['nullable', 'string', 'max:160'],
            'store_phone' => ['nullable', 'string', 'max:40'],
            'store_email' => ['nullable', 'email', 'max:160'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_notes' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['cash', 'digital', 'card', 'transfer'])],
            'amount_to_collect' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'amount_to_pay_store' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'change_required' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'courier_earning' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'tip_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }
}
