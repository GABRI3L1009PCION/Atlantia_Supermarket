<?php

namespace App\Http\Requests\Vendedor\ZonaEntrega;

use Illuminate\Foundation\Http\FormRequest;

class SyncVendorZonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('vendedor') === true;
    }

    public function rules(): array
    {
        return [
            'zonas' => ['present', 'array'],
            'zonas.*.delivery_zone_id' => ['required', 'integer', 'exists:delivery_zones,id'],
            'zonas.*.costo_override' => ['nullable', 'numeric', 'min:0'],
            'zonas.*.tiempo_estimado_min' => ['nullable', 'integer', 'min:5', 'max:240'],
            'zonas.*.activa' => ['sometimes', 'boolean'],
        ];
    }
}

