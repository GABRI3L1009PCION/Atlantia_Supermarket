<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RoutePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'repartidor']) === true;
    }

    public function rules(): array
    {
        return [
            'origen_latitude' => ['required', 'numeric', 'between:-90,90'],
            'origen_longitude' => ['required', 'numeric', 'between:-180,180'],
            'destino_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destino_longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}

