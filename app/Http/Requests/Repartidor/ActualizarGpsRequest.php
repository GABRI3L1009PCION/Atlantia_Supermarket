<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para actualizar ubicacion GPS del repartidor.
 */
class ActualizarGpsRequest extends FormRequest
{
    /**
     * Determina si el usuario puede enviar ubicacion GPS.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->hasRole('repartidor') || $this->user()->can('send courier location'));
    }

    /**
     * Reglas de validacion para tracking GPS.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pedido_id' => [
                'nullable',
                Rule::exists('delivery_routes', 'pedido_id')->where('repartidor_id', $this->user()?->id),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timestamp_gps' => ['nullable', 'date', 'before_or_equal:now'],
            'estado' => ['required', Rule::in([
                'disponible',
                'asignado',
                'en_ruta',
                'entregando',
                'fuera_servicio',
            ])],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:5000', 'decimal:0,2'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes personalizados de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pedido_id.exists' => 'El pedido no esta asignado a tu ruta activa.',
            'latitude.required' => 'La latitud es obligatoria.',
            'latitude.between' => 'La latitud no es valida.',
            'longitude.required' => 'La longitud es obligatoria.',
            'longitude.between' => 'La longitud no es valida.',
            'timestamp_gps.date' => 'La fecha GPS no es valida.',
            'timestamp_gps.before_or_equal' => 'La fecha GPS no puede estar en el futuro.',
            'estado.required' => 'Selecciona tu estado de reparto.',
            'estado.in' => 'El estado de reparto seleccionado no es valido.',
            'battery_level.min' => 'El nivel de bateria no puede ser menor que :min.',
            'battery_level.max' => 'El nivel de bateria no puede ser mayor que :max.',
            'accuracy_meters.max' => 'La precision GPS reportada no puede superar :max metros.',
            'notas.max' => 'Las notas no deben superar :max caracteres.',
        ];
    }

    /**
     * Nombres legibles de atributos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pedido_id' => 'pedido asignado',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'timestamp_gps' => 'fecha GPS',
            'estado' => 'estado de reparto',
            'battery_level' => 'nivel de bateria',
            'accuracy_meters' => 'precision GPS',
            'notas' => 'notas',
        ];
    }

    /**
     * Normaliza datos GPS antes de validar.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'latitude' => $this->normalizarDecimal($this->input('latitude')),
            'longitude' => $this->normalizarDecimal($this->input('longitude')),
            'accuracy_meters' => $this->normalizarDecimal($this->input('accuracy_meters')),
            'estado' => $this->input('estado', 'en_ruta'),
            'notas' => $this->blankToNull($this->input('notas')),
        ]);
    }

    /**
     * Normaliza decimales escritos con coma.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizarDecimal(mixed $value): ?string
    {
        return $value === null ? null : str_replace(',', '.', trim((string) $value));
    }

    /**
     * Convierte cadenas vacias en null.
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
