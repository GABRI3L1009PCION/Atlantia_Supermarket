<?php

namespace App\Http\Requests\Admin\ZonaEntrega;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZonaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        $zoneId = $this->route('zona')?->id;

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('delivery_zones', 'slug')->ignore($zoneId)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'municipio' => ['required', 'in:Puerto Barrios,Santo Tomas,Morales,Los Amates,Livingston,El Estor'],
            'costo_base' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'latitude_centro' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_centro' => ['nullable', 'numeric', 'between:-180,180'],
            'poligono_geojson' => ['nullable', 'array'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }
}

