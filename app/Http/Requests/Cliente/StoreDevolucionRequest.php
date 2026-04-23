<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para solicitar devolucion.
 */
class StoreDevolucionRequest extends FormRequest
{
    /**
     * Autoriza al cliente autenticado.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('cliente') === true;
    }

    /**
     * Reglas de validacion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', Rule::in(['producto_defectuoso', 'no_llego', 'incorrecto', 'otro'])],
            'descripcion' => ['required', 'string', 'min:10', 'max:1200'],
            'foto_evidencia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
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
            'motivo.required' => 'Selecciona el motivo de la devolucion.',
            'motivo.in' => 'El motivo seleccionado no es valido.',
            'descripcion.required' => 'Describe el problema con tu pedido.',
            'descripcion.min' => 'La descripcion debe tener al menos :min caracteres.',
            'foto_evidencia.image' => 'La evidencia debe ser una imagen.',
            'foto_evidencia.max' => 'La imagen no debe superar 4 MB.',
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
            'motivo' => 'motivo de devolucion',
            'descripcion' => 'descripcion del problema',
            'foto_evidencia' => 'foto de evidencia',
        ];
    }
}
