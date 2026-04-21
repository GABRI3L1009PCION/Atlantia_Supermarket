<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para crear productos del vendedor.
 */
class StoreProductoRequest extends FormRequest
{
    /**
     * Determina si el vendedor puede crear productos.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $vendor = $this->user()?->vendor;

        return $vendor !== null
            && $vendor->is_approved
            && $vendor->status === 'approved'
            && ($this->user()->hasRole('vendedor') || $this->user()->can('create products'));
    }

    /**
     * Reglas de validacion para crear producto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vendorId = $this->user()?->vendor?->id;

        return [
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where('is_active', true)],
            'sku' => ['required', 'string', 'max:80', Rule::unique('productos', 'sku')->where('vendor_id', $vendorId)],
            'nombre' => ['required', 'string', 'min:3', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('productos', 'slug')->where('vendor_id', $vendorId)],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio_base' => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0.01', 'lt:precio_base', 'decimal:0,2'],
            'peso_gramos' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'unidad_medida' => ['required', Rule::in(['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'])],
            'requiere_refrigeracion' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'visible_catalogo' => ['sometimes', 'boolean'],
            'stock_actual' => ['required', 'integer', 'min:0', 'max:999999'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:999999'],
            'stock_maximo' => ['nullable', 'integer', 'gte:stock_minimo', 'max:999999'],
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
            'categoria_id.required' => 'Selecciona una categoria.',
            'categoria_id.exists' => 'La categoria seleccionada no existe o no esta activa.',
            'sku.required' => 'Ingresa el SKU del producto.',
            'sku.unique' => 'Ya existe un producto con este SKU en tu tienda.',
            'nombre.required' => 'Ingresa el nombre del producto.',
            'nombre.min' => 'El nombre debe tener al menos :min caracteres.',
            'slug.unique' => 'Ya existe un producto con esta URL en tu tienda.',
            'precio_base.required' => 'Ingresa el precio base.',
            'precio_base.min' => 'El precio base debe ser mayor a cero.',
            'precio_oferta.lt' => 'El precio de oferta debe ser menor que el precio base.',
            'unidad_medida.in' => 'La unidad de medida seleccionada no es valida.',
            'stock_actual.required' => 'Ingresa el stock inicial disponible.',
            'stock_minimo.required' => 'Ingresa el stock minimo para alertas.',
            'stock_maximo.gte' => 'El stock maximo debe ser mayor o igual al stock minimo.',
            'imagenes.max' => 'Puedes subir como maximo :max imagenes.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen valida.',
            'imagenes.*.mimes' => 'Las imagenes deben ser JPG, PNG o WEBP.',
            'imagenes.*.max' => 'Cada imagen no debe superar 5 MB.',
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
            'stock_actual' => 'stock inicial',
            'stock_minimo' => 'stock minimo',
            'stock_maximo' => 'stock maximo',
            'imagenes' => 'imagenes',
        ];
    }

    /**
     * Normaliza datos del producto.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nombre = trim((string) $this->input('nombre'));

        $this->merge([
            'nombre' => $nombre,
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'slug' => $this->input('slug') ? Str::slug((string) $this->input('slug')) : Str::slug($nombre),
            'precio_base' => $this->normalizarDecimal($this->input('precio_base')),
            'precio_oferta' => $this->blankToNull($this->normalizarDecimal($this->input('precio_oferta'))),
            'unidad_medida' => $this->input('unidad_medida', 'unidad'),
            'requiere_refrigeracion' => filter_var($this->input('requiere_refrigeracion', false), FILTER_VALIDATE_BOOLEAN),
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
            'visible_catalogo' => filter_var($this->input('visible_catalogo', true), FILTER_VALIDATE_BOOLEAN),
            'stock_actual' => $this->input('stock_actual', 0),
            'stock_minimo' => $this->input('stock_minimo', 5),
            'stock_maximo' => $this->blankToNull($this->input('stock_maximo')),
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
