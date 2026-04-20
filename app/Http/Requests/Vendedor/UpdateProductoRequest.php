<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para actualizar productos del vendedor.
 */
class UpdateProductoRequest extends FormRequest
{
    /**
     * Determina si el vendedor puede actualizar el producto.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $producto = $this->route('producto');
        $vendor = $this->user()?->vendor;

        return $vendor !== null
            && $producto !== null
            && (int) $producto->vendor_id === (int) $vendor->id
            && ($this->user()->hasRole('vendedor') || $this->user()->can('update products'));
    }

    /**
     * Reglas de validacion para actualizar producto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $producto = $this->route('producto');
        $vendorId = $this->user()?->vendor?->id;

        return [
            'categoria_id' => ['sometimes', 'required', Rule::exists('categorias', 'id')->where('is_active', true)],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('productos', 'sku')->where('vendor_id', $vendorId)->ignore($producto?->id),
            ],
            'nombre' => ['sometimes', 'required', 'string', 'min:3', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('productos', 'slug')->where('vendor_id', $vendorId)->ignore($producto?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio_base' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0.01', 'lt:precio_base', 'decimal:0,2'],
            'peso_gramos' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'unidad_medida' => ['sometimes', 'required', Rule::in([
                'unidad',
                'libra',
                'kilogramo',
                'gramo',
                'litro',
                'mililitro',
                'paquete',
            ])],
            'requiere_refrigeracion' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'visible_catalogo' => ['sometimes', 'boolean'],
            'imagenes' => ['nullable', 'array', 'max:8'],
            'imagenes.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
            'categoria_id.exists' => 'La categoria seleccionada no existe o no esta activa.',
            'sku.unique' => 'Ya existe otro producto con este SKU en tu tienda.',
            'nombre.min' => 'El nombre debe tener al menos :min caracteres.',
            'slug.unique' => 'Ya existe otro producto con esta URL en tu tienda.',
            'precio_base.min' => 'El precio base debe ser mayor a cero.',
            'precio_oferta.lt' => 'El precio de oferta debe ser menor que el precio base.',
            'unidad_medida.in' => 'La unidad de medida seleccionada no es valida.',
            'imagenes.max' => 'Puedes subir como maximo :max imagenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen valida.',
            'imagenes.*.mimes' => 'Las imagenes deben ser JPG, PNG o WEBP.',
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
            'categoria_id' => 'categoria',
            'sku' => 'SKU',
            'nombre' => 'nombre',
            'slug' => 'URL del producto',
            'descripcion' => 'descripcion',
            'precio_base' => 'precio base',
            'precio_oferta' => 'precio de oferta',
            'peso_gramos' => 'peso en gramos',
            'unidad_medida' => 'unidad de medida',
            'requiere_refrigeracion' => 'requiere refrigeracion',
            'is_active' => 'producto activo',
            'visible_catalogo' => 'visible en catalogo',
            'imagenes' => 'imagenes',
        ];
    }

    /**
     * Normaliza datos enviados por formulario.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nombre = $this->input('nombre') === null ? null : trim((string) $this->input('nombre'));

        $this->merge([
            'nombre' => $nombre,
            'sku' => $this->input('sku') === null ? null : Str::upper(trim((string) $this->input('sku'))),
            'slug' => $this->input('slug') ? Str::slug((string) $this->input('slug')) : null,
            'precio_base' => $this->normalizarDecimal($this->input('precio_base')),
            'precio_oferta' => $this->blankToNull($this->normalizarDecimal($this->input('precio_oferta'))),
            'requiere_refrigeracion' => $this->has('requiere_refrigeracion')
                ? filter_var($this->input('requiere_refrigeracion'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'is_active' => $this->has('is_active') ? filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN) : null,
            'visible_catalogo' => $this->has('visible_catalogo')
                ? filter_var($this->input('visible_catalogo'), FILTER_VALIDATE_BOOLEAN)
                : null,
        ]);
    }

    /**
     * Normaliza decimales escritos con coma.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizarDecimal(mixed $value): ?string
    {
        return $value === null ? null : str_replace(',', '.', trim((string) $value));
    }

    /**
     * Convierte cadenas vacias en null.
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
