<?php

namespace App\Http\Requests\Admin\HeroBanner;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeroBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:140'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'inicia_en' => ['nullable', 'date'],
            'termina_en' => ['nullable', 'date', 'after_or_equal:inicia_en'],
            'desktop_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'termina_en.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha de inicio.',
            'desktop_image.required' => 'Debes subir una imagen desktop para el banner.',
        ];
    }
}
