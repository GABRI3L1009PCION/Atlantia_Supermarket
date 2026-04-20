<?php

namespace App\Http\Requests\Repartidor\Pedido;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntregaEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:en_camino,entregado,incidencia'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

