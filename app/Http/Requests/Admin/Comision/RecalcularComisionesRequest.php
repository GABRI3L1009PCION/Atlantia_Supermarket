<?php

namespace App\Http\Requests\Admin\Comision;

use Illuminate\Foundation\Http\FormRequest;

class RecalcularComisionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'min:2024', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
