<?php

namespace App\Http\Requests\Admin\Resena;

use Illuminate\Foundation\Http\FormRequest;

class ModerateResenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'empleado']) === true;
    }

    public function rules(): array
    {
        return [
            'aprobada' => ['required', 'boolean'],
            'flagged_ml' => ['sometimes', 'boolean'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

