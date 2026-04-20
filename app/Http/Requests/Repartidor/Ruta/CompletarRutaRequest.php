<?php

namespace App\Http\Requests\Repartidor\Ruta;

use Illuminate\Foundation\Http\FormRequest;

class CompletarRutaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'firma_path' => ['required', 'string', 'max:255'],
            'foto_entrega_path' => ['required', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

