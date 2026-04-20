<?php

namespace App\Http\Requests\Cliente\Perfil;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('cliente') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dpi' => ['nullable', 'string', 'size:13'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-12 years'],
            'preferencias' => ['nullable', 'array'],
        ];
    }
}

