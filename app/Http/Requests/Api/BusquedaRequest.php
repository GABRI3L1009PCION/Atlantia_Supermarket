<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BusquedaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}

