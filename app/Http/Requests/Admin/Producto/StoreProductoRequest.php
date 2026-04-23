<?php

namespace App\Http\Requests\Admin\Producto;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Valida la creacion administrativa de productos.
 */
class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) === true;
    }

    public function rules(): array
    {
        return [
            'owner_type' => ['required', Rule::in(['atlantia', 'vendor'])],
            'vendor_id' => ['nullable', 'required_if:owner_type,vendor', 'integer', 'exists:vendors,id'],
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('is_active', true)],
            'sku' => ['required', 'string', 'max:80', Rule::unique('productos', 'sku')->where('vendor_id', $this->resolvedVendorId())],
            'nombre' => ['required', 'string', 'min:3', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('productos', 'slug')->where('vendor_id', $this->resolvedVendorId())],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio_base' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0.01', 'lt:precio_base', 'decimal:0,2'],
            'peso_gramos' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'unidad_medida' => ['required', Rule::in(['unidad', 'libra', 'kilogramo', 'gramo', 'litro', 'mililitro', 'paquete'])],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'stock_maximo' => ['nullable', 'integer', 'min:0'],
            'requiere_refrigeracion' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'visible_catalogo' => ['sometimes', 'boolean'],
            'imagenes' => ['nullable', 'array', 'max:8'],
            'imagenes.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nombre = trim((string) $this->input('nombre'));
        $ownerType = (string) $this->input('owner_type', $this->filled('vendor_id') ? 'vendor' : 'atlantia');

        $this->merge([
            'owner_type' => $ownerType,
            'nombre' => $nombre,
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'slug' => $this->input('slug') ? Str::slug((string) $this->input('slug')) : Str::slug($nombre),
            'precio_base' => $this->normalizeDecimal($this->input('precio_base')),
            'precio_oferta' => $this->blankToNull($this->normalizeDecimal($this->input('precio_oferta'))),
            'requiere_refrigeracion' => filter_var($this->input('requiere_refrigeracion', false), FILTER_VALIDATE_BOOLEAN),
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
            'visible_catalogo' => filter_var($this->input('visible_catalogo', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Devuelve el vendedor usado para validar unicidad.
     */
    private function resolvedVendorId(): int
    {
        if ($this->input('owner_type') === 'vendor') {
            return (int) $this->input('vendor_id');
        }

        return (int) (Vendor::query()->where('slug', 'atlantia-supermarket')->value('id') ?? 0);
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        return $value === null ? null : str_replace(',', '.', trim((string) $value));
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
    /**
     * Mensajes personalizados de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'array' => 'El campo :attribute debe ser una lista valida.',
            'email' => 'Ingresa un correo electronico valido.',
            'unique' => 'El valor de :attribute ya esta registrado.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'min' => 'El campo :attribute no cumple con el minimo requerido.',
            'max' => 'El campo :attribute supera el maximo permitido.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'accepted' => 'Debes aceptar :attribute.',
            'image' => 'El archivo de :attribute debe ser una imagen valida.',
            'mimes' => 'El archivo de :attribute tiene un formato no permitido.',
        ];
    }
}
