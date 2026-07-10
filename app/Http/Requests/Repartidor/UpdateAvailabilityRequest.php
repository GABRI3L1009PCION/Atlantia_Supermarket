<?php

namespace App\Http\Requests\Repartidor;

use App\Models\CourierProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('repartidor') === true;
    }

    public function rules(): array
    {
        return [
            'availability_status' => ['required', Rule::in(CourierProfile::AVAILABILITY_STATUSES)],
            'service_scope' => ['nullable', Rule::in(CourierProfile::SERVICE_SCOPES)],
            'vehicle_type' => ['nullable', 'string', 'max:40'],
        ];
    }
}
