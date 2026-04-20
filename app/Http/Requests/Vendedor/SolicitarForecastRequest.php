<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para solicitar forecast de demanda.
 */
class SolicitarForecastRequest extends FormRequest
{
    /**
     * Determina si el vendedor puede solicitar predicciones.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->vendor !== null
            && ($this->user()->hasRole('vendedor') || $this->user()->can('view demand predictions'));
    }

    /**
     * Reglas de validacion para forecast.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where('vendor_id', $this->user()?->vendor?->id),
            ],
            'horizonte_dias' => ['required', Rule::in([7, 14, 30])],
            'recalcular' => ['sometimes', 'boolean'],
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
            'producto_id.required' => 'Selecciona el producto a predecir.',
            'producto_id.exists' => 'El producto seleccionado no pertenece a tu tienda.',
            'horizonte_dias.required' => 'Selecciona el horizonte de prediccion.',
            'horizonte_dias.in' => 'El horizonte debe ser 7, 14 o 30 dias.',
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
            'producto_id' => 'producto',
            'horizonte_dias' => 'horizonte de prediccion',
            'recalcular' => 'recalcular prediccion',
        ];
    }

    /**
     * Normaliza bandera de recalculo.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recalcular' => filter_var($this->input('recalcular', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
