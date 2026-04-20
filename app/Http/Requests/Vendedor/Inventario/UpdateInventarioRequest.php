<?php

namespace App\Http\Requests\Vendedor\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('vendedor') === true;
    }

    public function rules(): array
    {
        return [
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'stock_maximo' => ['nullable', 'integer', 'gte:stock_minimo'],
        ];
    }
}

