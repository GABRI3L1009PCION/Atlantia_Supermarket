<?php

namespace App\Http\Requests\Repartidor\Incidencia;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', 'max:80'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'foto_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}

