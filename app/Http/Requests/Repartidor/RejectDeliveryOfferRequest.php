<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;

class RejectDeliveryOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
