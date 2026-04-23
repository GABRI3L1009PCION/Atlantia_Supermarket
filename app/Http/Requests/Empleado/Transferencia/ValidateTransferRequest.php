<?php

namespace App\Http\Requests\Empleado\Transferencia;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['empleado', 'admin']) === true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:aprobado,rechazado'],
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
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'array' => 'El campo :attribute debe ser una lista valida.',
            'email' => 'Ingresa un correo electronico valido.',
            'unique' => 'El valor de :attribute ya esta registrado.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'min' => 'El campo :attribute no cumple con el minimo requerido.',
            'max' => 'El campo :attribute supera el maximo permitido.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'accepted' => 'Debes aceptar :attribute.',
            'image' => 'El archivo de :attribute debe ser una imagen valida.',
            'mimes' => 'El archivo de :attribute tiene un formato no permitido.',
        ];
    }
}