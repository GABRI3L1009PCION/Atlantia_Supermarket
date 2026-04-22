<?php

namespace App\Http\Requests\Admin\ZonaEntrega;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateZonaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
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
            'tiempo_estimado_min' => ['nullable', 'integer', 'min:10', 'max:240'],
            'capacidad_diaria' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'envio_gratis_desde' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'hora_apertura' => ['nullable', 'date_format:H:i'],
            'hora_cierre' => ['nullable', 'date_format:H:i'],
            'barrios' => ['nullable', 'string', 'max:700'],
            'dias_operacion' => ['nullable', 'array'],
            'dias_operacion.*' => ['string', 'in:lun,mar,mie,jue,vie,sab,dom'],
            'acepta_programados' => ['sometimes', 'boolean'],
            'cobro_peso_volumen' => ['sometimes', 'boolean'],
            'latitude_centro' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_centro' => ['nullable', 'numeric', 'between:-180,180'],
            'poligono_geojson' => ['nullable', 'array'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nombre = trim((string) $this->input('nombre'));

        $this->merge([
            'nombre' => $nombre,
            'slug' => $this->filled('slug') ? Str::slug((string) $this->input('slug')) : Str::slug($nombre),
            'descripcion' => $this->blankToNull($this->input('descripcion')),
            'barrios' => $this->blankToNull($this->input('barrios')),
            'activa' => filter_var($this->input('activa', false), FILTER_VALIDATE_BOOLEAN),
            'acepta_programados' => filter_var($this->input('acepta_programados', false), FILTER_VALIDATE_BOOLEAN),
            'cobro_peso_volumen' => filter_var($this->input('cobro_peso_volumen', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
