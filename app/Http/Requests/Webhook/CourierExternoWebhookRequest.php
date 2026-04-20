<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class CourierExternoWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_uuid' => ['required', 'string', 'max:36'],
            'estado' => ['required', 'string', 'max:40'],
            'ruta_real' => ['nullable', 'array'],
        ];
    }
}

