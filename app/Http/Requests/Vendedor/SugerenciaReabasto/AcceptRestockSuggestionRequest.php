<?php

namespace App\Http\Requests\Vendedor\SugerenciaReabasto;

use Illuminate\Foundation\Http\FormRequest;

class AcceptRestockSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('vendedor') === true;
    }

    public function rules(): array
    {
        return ['cantidad_recibida' => ['nullable', 'integer', 'min:1']];
    }
}

