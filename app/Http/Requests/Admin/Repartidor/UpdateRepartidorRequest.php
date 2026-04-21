<?php

namespace App\Http\Requests\Admin\Repartidor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la actualizacion de repartidores.
 */
class UpdateRepartidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        $repartidor = $this->route('repartidor');

        return [
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($repartidor?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => ['nullable', 'string', 'min:12', 'max:128', 'confirmed'],
        ];
    }
}
