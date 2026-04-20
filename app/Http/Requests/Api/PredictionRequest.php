<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['vendedor', 'admin']) === true;
    }

    public function rules(): array
    {
        return [
            'horizonte_dias' => ['nullable', 'integer', 'in:7,14,30'],
        ];
    }
}

