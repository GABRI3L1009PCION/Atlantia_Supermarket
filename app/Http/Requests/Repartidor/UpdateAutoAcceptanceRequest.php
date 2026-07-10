<?php

namespace App\Http\Requests\Repartidor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutoAcceptanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'auto_accept_enabled' => ['nullable', 'boolean'],
            'auto_accept_max_distance_km' => ['required', 'numeric', 'min:0.5', 'max:50'],
        ];
    }
}
