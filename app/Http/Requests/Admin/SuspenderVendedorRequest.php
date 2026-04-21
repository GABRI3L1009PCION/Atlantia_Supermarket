<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para suspender vendedores.
 */
class SuspenderVendedorRequest extends FormRequest
{
    /**
     * Determina si el usuario puede suspender vendedores.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true || $this->user()?->can('suspend vendors') === true;
    }

    /**
     * Reglas de validacion para suspension.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo_suspension' => ['required', 'string', 'min:10', 'max:1200'],
            'tipo_suspension' => ['required', Rule::in(['fiscal', 'operativa', 'fraude', 'calidad', 'incumplimiento'])],
            'notificar_vendedor' => ['sometimes', 'boolean'],
            'permitir_reactivacion' => ['sometimes', 'boolean'],
            'fecha_revision' => ['nullable', 'date', 'after_or_equal:today'],
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
            'motivo_suspension.required' => 'Indica el motivo de suspension.',
            'motivo_suspension.min' => 'El motivo debe tener al menos :min caracteres.',
            'motivo_suspension.max' => 'El motivo no debe superar :max caracteres.',
            'tipo_suspension.required' => 'Selecciona el tipo de suspension.',
            'tipo_suspension.in' => 'El tipo de suspension seleccionado no es valido.',
            'fecha_revision.date' => 'La fecha de revision no es valida.',
            'fecha_revision.after_or_equal' => 'La fecha de revision no puede ser anterior a hoy.',
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
            'motivo_suspension' => 'motivo de suspension',
            'tipo_suspension' => 'tipo de suspension',
            'notificar_vendedor' => 'notificar al vendedor',
            'permitir_reactivacion' => 'permitir reactivacion',
            'fecha_revision' => 'fecha de revision',
        ];
    }

    /**
     * Normaliza banderas booleanas.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'motivo_suspension' => trim((string) $this->input('motivo_suspension')),
            'notificar_vendedor' => filter_var($this->input('notificar_vendedor', true), FILTER_VALIDATE_BOOLEAN),
            'permitir_reactivacion' => filter_var($this->input('permitir_reactivacion', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
