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
            'estado' => ['sometimes', 'required', 'in:en_ruta,entregado,incidencia'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'foto_entrega' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in' => 'El estado de entrega no es valido.',
            'foto_entrega.image' => 'La evidencia debe ser una imagen.',
            'foto_entrega.max' => 'La foto de entrega no debe pesar mas de 4 MB.',
        ];
    }
}
