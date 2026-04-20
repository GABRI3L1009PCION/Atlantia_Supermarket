<?php

namespace App\Http\Requests\Admin\Comision;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,facturada,pagada,vencida,anulada'],
            'fecha_vencimiento' => ['nullable', 'date'],
        ];
    }
}

