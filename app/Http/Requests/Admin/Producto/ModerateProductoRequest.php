<?php

namespace App\Http\Requests\Admin\Producto;

use Illuminate\Foundation\Http\FormRequest;

class ModerateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'empleado']) === true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'visible_catalogo' => ['required', 'boolean'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

