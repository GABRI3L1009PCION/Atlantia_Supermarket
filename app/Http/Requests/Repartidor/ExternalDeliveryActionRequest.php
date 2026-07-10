<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;

class ExternalDeliveryActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'issue_reason' => ['nullable', 'string', 'max:255'],
            'cash_notes' => ['nullable', 'string', 'max:1000'],
            'confirmation_code' => ['nullable', 'digits:4'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_code.digits' => 'El codigo de entrega debe tener 4 digitos.',
        ];
    }
}
