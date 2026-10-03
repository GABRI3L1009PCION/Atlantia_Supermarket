<?php

namespace App\Http\Requests\Admin\ExternalDelivery;

use Illuminate\Foundation\Http\FormRequest;

class AssignExternalDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    public function rules(): array
    {
        return [
            'repartidor_id' => ['required', 'integer', 'exists:users,id'],
            'estimated_gain' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'ttl_seconds' => ['nullable', 'integer', 'min:30', 'max:900'],
        ];
    }
}
