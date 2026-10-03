<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourierWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
