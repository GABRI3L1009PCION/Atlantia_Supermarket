<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class PasarelaPagoWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_uuid' => ['required', 'string', 'max:36'],
            'estado' => ['required', 'in:aprobado,rechazado,pendiente,reversado'],
            'transaccion_id_pasarela' => ['nullable', 'string', 'max:120'],
            'payload' => ['nullable', 'array'],
        ];
    }
}

