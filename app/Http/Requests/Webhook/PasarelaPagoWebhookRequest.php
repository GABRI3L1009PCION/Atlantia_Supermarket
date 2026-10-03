<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PasarelaPagoWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_uuid' => ['nullable', 'string', 'max:36', 'required_without_all:transaction_id,transaccion_id_pasarela,payment_intent,payload.transaction_id,payload.payment_intent,payload.data.object.id'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
            'transaccion_id_pasarela' => ['nullable', 'string', 'max:120'],
            'payment_intent' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'in:aprobado,rechazado,pendiente,validando,pagado,reversado,anulado,reembolsado'],
            'status' => ['nullable', 'string', 'max:80'],
            'payload' => ['nullable', 'array'],
        ];
    }

    /**
     * Requiere al menos un estado reconocible del proveedor.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payload = $this->input('payload', []);

            if (
                $this->filled('estado')
                || $this->filled('status')
                || data_get($payload, 'status')
                || data_get($payload, 'data.object.status')
            ) {
                return;
            }

            $validator->errors()->add('estado', 'El webhook debe incluir estado o status.');
        });
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
