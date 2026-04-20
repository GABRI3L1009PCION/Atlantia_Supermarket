<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class CertificadorFelWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dte_uuid' => ['required', 'string', 'max:36'],
            'estado' => ['required', 'string', 'max:40'],
            'uuid_sat' => ['nullable', 'string', 'max:80'],
            'respuesta' => ['nullable', 'array'],
        ];
    }
}

