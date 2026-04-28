<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para registrar direcciones de entrega.
 */
class StoreDireccionRequest extends FormRequest
{
    /**
     * Determina si el cliente puede crear direcciones.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->hasRole('cliente') || $this->user()->can('create delivery addresses'));
    }

    /**
     * Reglas de validacion de direccion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alias' => ['nullable', 'string', 'max:80'],
            'nombre_contacto' => ['required', 'string', 'min:3', 'max:160'],
            'telefono_contacto' => ['required', 'string', 'regex:/^(\+502)?[2-7][0-9]{7}$/'],
            'municipio' => ['required', Rule::in([
                'Puerto Barrios',
                'Santo Tomas',
                'Santo Tomás',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor',
            ])],
            'zona_o_barrio' => ['nullable', 'string', 'max:160'],
            'direccion_linea_1' => ['required', 'string', 'min:8', 'max:500'],
            'direccion_linea_2' => ['nullable', 'string', 'max:500'],
            'referencia' => ['nullable', 'string', 'max:600'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'mapbox_place_id' => ['nullable', 'string', 'max:255'],
            'es_principal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_contacto.required' => 'Ingresa el nombre de quien recibira el pedido.',
            'telefono_contacto.required' => 'Ingresa el telefono de contacto.',
            'telefono_contacto.regex' => 'Ingresa un telefono valido de Guatemala.',
            'municipio.required' => 'Selecciona el municipio de entrega.',
            'municipio.in' => 'El municipio seleccionado no esta dentro de la cobertura configurada.',
            'direccion_linea_1.required' => 'Ingresa la direccion principal.',
            'direccion_linea_1.min' => 'La direccion debe tener al menos :min caracteres.',
            'latitude.required' => 'Usa tu ubicacion actual para guardar la direccion exacta.',
            'longitude.required' => 'Usa tu ubicacion actual para guardar la direccion exacta.',
            'latitude.between' => 'La latitud no es valida.',
            'longitude.between' => 'La longitud no es valida.',
        ];
    }

    /**
     * Atributos legibles.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'alias' => 'alias',
            'nombre_contacto' => 'nombre de contacto',
            'telefono_contacto' => 'telefono de contacto',
            'municipio' => 'municipio',
            'zona_o_barrio' => 'zona o barrio',
            'direccion_linea_1' => 'direccion principal',
            'direccion_linea_2' => 'complemento de direccion',
            'referencia' => 'referencia',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'mapbox_place_id' => 'identificador Mapbox',
            'es_principal' => 'direccion principal',
        ];
    }

    /**
     * Normaliza datos antes de validar.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'alias' => $this->blankToDefault($this->input('alias'), 'Casa'),
            'nombre_contacto' => trim((string) $this->input('nombre_contacto')),
            'telefono_contacto' => preg_replace('/[\s\-]/', '', (string) $this->input('telefono_contacto')),
            'es_principal' => filter_var($this->input('es_principal', false), FILTER_VALIDATE_BOOLEAN),
            'zona_o_barrio' => $this->blankToNull($this->input('zona_o_barrio')),
            'referencia' => $this->blankToNull($this->input('referencia')),
        ]);
    }

    /**
     * Devuelve valor por defecto si esta vacio.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    private function blankToDefault(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }

    /**
     * Convierte cadenas vacias a null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
