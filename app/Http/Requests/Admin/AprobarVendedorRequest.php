<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validacion para aprobar vendedores locales.
 */
class AprobarVendedorRequest extends FormRequest
{
    /**
     * Determina si el usuario puede aprobar vendedores.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true || $this->user()?->can('approve vendors') === true;
    }

    /**
     * Reglas de validacion para aprobacion de vendedor.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:30', 'decimal:0,2'],
            'monthly_rent' => ['required', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'fel_validado' => ['sometimes', 'boolean'],
            'acepta_cash' => ['sometimes', 'boolean'],
            'acepta_transfer' => ['sometimes', 'boolean'],
            'acepta_card' => ['sometimes', 'boolean'],
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
            'commission_percentage.required' => 'Define el porcentaje de comision del vendedor.',
            'commission_percentage.numeric' => 'El porcentaje de comision debe ser numerico.',
            'commission_percentage.max' => 'La comision no puede superar el :max%.',
            'monthly_rent.required' => 'Define la renta mensual del vendedor.',
            'monthly_rent.numeric' => 'La renta mensual debe ser numerica.',
            'monthly_rent.max' => 'La renta mensual no puede superar Q:max.',
            'observaciones.max' => 'Las observaciones no deben superar :max caracteres.',
            'fel_validado.boolean' => 'La validacion FEL debe ser verdadera o falsa.',
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
            'commission_percentage' => 'porcentaje de comision',
            'monthly_rent' => 'renta mensual',
            'observaciones' => 'observaciones',
            'fel_validado' => 'validacion FEL',
            'acepta_cash' => 'acepta efectivo',
            'acepta_transfer' => 'acepta transferencia',
            'acepta_card' => 'acepta tarjeta',
        ];
    }

    /**
     * Normaliza valores monetarios y booleanos.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'commission_percentage' => $this->normalizarDecimal($this->input('commission_percentage')),
            'monthly_rent' => $this->normalizarDecimal($this->input('monthly_rent')),
            'fel_validado' => filter_var($this->input('fel_validado', false), FILTER_VALIDATE_BOOLEAN),
            'acepta_cash' => filter_var($this->input('acepta_cash', true), FILTER_VALIDATE_BOOLEAN),
            'acepta_transfer' => filter_var($this->input('acepta_transfer', true), FILTER_VALIDATE_BOOLEAN),
            'acepta_card' => filter_var($this->input('acepta_card', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Normaliza un valor decimal enviado desde formularios.
     *
     * @param mixed $value
     * @return string
     */
    private function normalizarDecimal(mixed $value): string
    {
        return str_replace(',', '.', trim((string) $value));
    }
}
