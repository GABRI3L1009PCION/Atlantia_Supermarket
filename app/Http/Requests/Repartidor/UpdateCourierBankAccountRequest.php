<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_account_type' => ['required', Rule::in(['monetaria', 'ahorro'])],
            'bank_account_number' => ['required', 'string', 'max:40'],
            'bank_account_holder' => ['required', 'string', 'max:120'],
            'payout_method' => ['required', Rule::in(['transfer'])],
            'bank_document_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
