<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para disparar reentrenamientos ML.
 */
class DispararReentrenamientoRequest extends FormRequest
{
    /**
     * Determina si el usuario puede disparar entrenamientos ML.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true || $this->user()?->can('train ml') === true;
    }

    /**
     * Reglas de validacion para reentrenamiento.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'modelo_nombre' => ['required', Rule::in([
                'demand_forecast',
                'product_recommendation',
                'restock_suggestion',
                'fraud_detection',
                'review_nlp',
            ])],
            'fecha_inicio_dataset' => ['nullable', 'date', 'before_or_equal:today'],
            'fecha_fin_dataset' => ['nullable', 'date', 'after_or_equal:fecha_inicio_dataset', 'before_or_equal:today'],
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
            'forzar_reentrenamiento' => ['sometimes', 'boolean'],
            'usar_staging' => ['sometimes', 'boolean'],
            'parametros' => ['nullable', 'array'],
            'parametros.horizonte_dias' => ['nullable', Rule::in([7, 14, 30])],
            'parametros.max_trials' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'modelo_nombre.required' => 'Selecciona el modelo a reentrenar.',
            'modelo_nombre.in' => 'El modelo seleccionado no esta habilitado para reentrenamiento.',
            'fecha_inicio_dataset.before_or_equal' => 'La fecha inicial del dataset no puede ser futura.',
            'fecha_fin_dataset.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'fecha_fin_dataset.before_or_equal' => 'La fecha final del dataset no puede ser futura.',
            'motivo.required' => 'Indica el motivo del reentrenamiento.',
            'motivo.min' => 'El motivo debe tener al menos :min caracteres.',
            'parametros.array' => 'Los parametros deben enviarse como un objeto valido.',
            'parametros.horizonte_dias.in' => 'El horizonte debe ser 7, 14 o 30 dias.',
            'parametros.max_trials.max' => 'El numero de pruebas no puede superar :max.',
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
            'modelo_nombre' => 'modelo ML',
            'fecha_inicio_dataset' => 'fecha inicial del dataset',
            'fecha_fin_dataset' => 'fecha final del dataset',
            'motivo' => 'motivo',
            'forzar_reentrenamiento' => 'forzar reentrenamiento',
            'usar_staging' => 'usar staging',
            'parametros' => 'parametros',
            'parametros.horizonte_dias' => 'horizonte de prediccion',
            'parametros.max_trials' => 'numero maximo de pruebas',
        ];
    }

    /**
     * Normaliza banderas y nombre del modelo.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'modelo_nombre' => trim((string) $this->input('modelo_nombre')),
            'motivo' => trim((string) $this->input('motivo')),
            'forzar_reentrenamiento' => filter_var(
                $this->input('forzar_reentrenamiento', false),
                FILTER_VALIDATE_BOOLEAN
            ),
            'usar_staging' => filter_var($this->input('usar_staging', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
