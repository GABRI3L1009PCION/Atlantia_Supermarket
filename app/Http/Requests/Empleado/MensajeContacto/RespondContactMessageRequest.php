<?php

namespace App\Http\Requests\Empleado\MensajeContacto;

use Illuminate\Foundation\Http\FormRequest;

class RespondContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['empleado', 'admin']) === true;
    }

    public function rules(): array
    {
        return ['respuesta' => ['required', 'string', 'max:2000']];
    }
}

