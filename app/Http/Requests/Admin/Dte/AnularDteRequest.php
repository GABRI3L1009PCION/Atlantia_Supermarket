<?php

namespace App\Http\Requests\Admin\Dte;

use Illuminate\Foundation\Http\FormRequest;

class AnularDteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }
}
