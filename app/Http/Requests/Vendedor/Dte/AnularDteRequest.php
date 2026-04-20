<?php

namespace App\Http\Requests\Vendedor\Dte;

use Illuminate\Foundation\Http\FormRequest;

class AnularDteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('vendedor') === true;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'min:10', 'max:500']];
    }
}

