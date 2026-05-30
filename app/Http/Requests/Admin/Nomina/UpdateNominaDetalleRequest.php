<?php

namespace App\Http\Requests\Admin\Nomina;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNominaDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'bonificaciones' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'descuentos' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
