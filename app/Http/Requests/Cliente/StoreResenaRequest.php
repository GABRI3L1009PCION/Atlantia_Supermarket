<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para crear resenas de productos comprados.
 */
class StoreResenaRequest extends FormRequest
{
    /**
     * Determina si el cliente puede crear resenas.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->hasRole('cliente') || $this->user()->can('create reviews'));
    }

    /**
     * Reglas de validacion para resenas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pedidoId = $this->route('pedido')?->id ?? $this->input('pedido_id');

        return [
            'producto_id' => [
                'required',
                Rule::exists('pedido_items', 'producto_id')->where('pedido_id', $pedidoId),
            ],
            'pedido_id' => [
                'nullable',
                Rule::exists('pedidos', 'id')->where('cliente_id', $this->user()?->id),
            ],
            'calificacion' => ['required', 'integer', 'min:1', 'max:5'],
            'titulo' => ['nullable', 'string', 'max:140'],
            'contenido' => ['nullable', 'string', 'min:8', 'max:3000'],
            'imagenes' => ['nullable', 'array', 'max:5'],
            'imagenes.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'producto_id.required' => 'Selecciona el producto que deseas resenar.',
            'producto_id.exists' => 'Solo puedes resenar productos comprados en este pedido.',
            'pedido_id.exists' => 'El pedido seleccionado no pertenece a tu cuenta.',
            'calificacion.required' => 'Selecciona una calificacion.',
            'calificacion.min' => 'La calificacion minima es :min estrella.',
            'calificacion.max' => 'La calificacion maxima es :max estrellas.',
            'contenido.min' => 'El contenido debe tener al menos :min caracteres.',
            'contenido.max' => 'El contenido no debe superar :max caracteres.',
            'imagenes.max' => 'Puedes adjuntar como maximo :max imagenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen valida.',
            'imagenes.*.mimes' => 'Las imagenes deben ser JPG, PNG o WEBP.',
            'imagenes.*.max' => 'Cada imagen no debe superar 4 MB.',
        ];
    }

    /**
     * Atributos legibles.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'producto_id' => 'producto',
            'pedido_id' => 'pedido',
            'calificacion' => 'calificacion',
            'titulo' => 'titulo',
            'contenido' => 'contenido',
            'imagenes' => 'imagenes',
            'imagenes.*' => 'imagen',
        ];
    }

    /**
     * Normaliza textos y pedido recibido por ruta.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'pedido_id' => $this->route('pedido')?->id ?? $this->input('pedido_id'),
            'titulo' => $this->blankToNull($this->input('titulo')),
            'contenido' => $this->blankToNull($this->input('contenido')),
        ]);
    }

    /**
     * Convierte cadenas vacias a null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
