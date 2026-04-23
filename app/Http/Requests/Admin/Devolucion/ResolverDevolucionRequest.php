<?php

namespace App\Http\Requests\Admin\Devolucion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para resolver devoluciones desde administracion.
 */
class ResolverDevolucionRequest extends FormRequest
{
    /**
     * Autoriza administradores.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['aprobada', 'rechazada'])],
            'monto_reembolso' => ['required_if:decision,aprobada', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'notas_admin' => ['nullable', 'string', 'max:1200'],
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
            'decision.required' => 'Selecciona si aprobaras o rechazaras la devolucion.',
            'decision.in' => 'La decision seleccionada no es valida.',
            'monto_reembolso.required_if' => 'Ingresa el monto a reembolsar.',
            'monto_reembolso.numeric' => 'El monto de reembolso debe ser numerico.',
            'notas_admin.max' => 'Las notas administrativas no deben superar :max caracteres.',
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
            'decision' => 'decision',
            'monto_reembolso' => 'monto de reembolso',
            'notas_admin' => 'notas administrativas',
        ];
    }
}
