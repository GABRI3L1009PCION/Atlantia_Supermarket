<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para perfil fiscal FEL del vendedor.
 */
class StorePerfilFiscalRequest extends FormRequest
{
    /**
     * Determina si el vendedor puede gestionar su perfil fiscal.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->vendor !== null
            && ($this->user()->hasRole('vendedor') || $this->user()->can('manage fiscal profile'));
    }

    /**
     * Reglas de validacion del perfil fiscal.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vendorId = $this->user()?->vendor?->id;

        return [
            'nit' => [
                'required',
                'string',
                'regex:/^[0-9K\-]{4,30}$/',
                Rule::unique('vendor_fiscal_profiles', 'nit')->ignore($vendorId, 'vendor_id'),
            ],
            'razon_social' => ['required', 'string', 'min:3', 'max:220'],
            'nombre_comercial_sat' => ['nullable', 'string', 'max:220'],
            'direccion_fiscal' => ['required', 'string', 'min:8', 'max:600'],
            'regimen_sat' => ['required', Rule::in(['pequeno_contribuyente', 'general', 'exento'])],
            'codigo_establecimiento' => ['required', 'string', 'max:50'],
            'afiliacion_iva' => ['required', 'string', 'max:80'],
            'certificador_fel' => ['required', Rule::in(['infile'])],
            'fel_usuario' => ['nullable', 'string', 'max:190'],
            'fel_llave_firma' => ['nullable', 'string', 'max:5000'],
            'fel_llave_certificador' => ['nullable', 'string', 'max:5000'],
            'banco_nombre' => ['nullable', 'string', 'max:120'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:120'],
            'cuenta_bancaria_tipo' => ['nullable', Rule::in(['monetaria', 'ahorro'])],
            'cuenta_bancaria_titular' => ['nullable', 'string', 'max:180'],
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
            'nit.required' => 'Ingresa el NIT registrado ante SAT.',
            'nit.regex' => 'Ingresa un NIT valido para Guatemala.',
            'nit.unique' => 'Este NIT ya esta registrado en otro perfil fiscal.',
            'razon_social.required' => 'Ingresa la razon social.',
            'direccion_fiscal.required' => 'Ingresa la direccion fiscal.',
            'regimen_sat.required' => 'Selecciona el regimen SAT.',
            'regimen_sat.in' => 'El regimen SAT seleccionado no es valido.',
            'codigo_establecimiento.required' => 'Ingresa el codigo de establecimiento.',
            'afiliacion_iva.required' => 'Ingresa la afiliacion IVA.',
            'certificador_fel.in' => 'El certificador FEL seleccionado no esta disponible.',
            'cuenta_bancaria_tipo.in' => 'El tipo de cuenta bancaria no es valido.',
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
            'nit' => 'NIT',
            'razon_social' => 'razon social',
            'nombre_comercial_sat' => 'nombre comercial SAT',
            'direccion_fiscal' => 'direccion fiscal',
            'regimen_sat' => 'regimen SAT',
            'codigo_establecimiento' => 'codigo de establecimiento',
            'afiliacion_iva' => 'afiliacion IVA',
            'certificador_fel' => 'certificador FEL',
            'fel_usuario' => 'usuario FEL',
            'fel_llave_firma' => 'llave de firma FEL',
            'fel_llave_certificador' => 'llave del certificador',
            'banco_nombre' => 'banco',
            'cuenta_bancaria' => 'cuenta bancaria',
            'cuenta_bancaria_tipo' => 'tipo de cuenta bancaria',
            'cuenta_bancaria_titular' => 'titular de la cuenta',
        ];
    }

    /**
     * Normaliza datos fiscales.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nit' => Str::upper(str_replace(' ', '', (string) $this->input('nit'))),
            'razon_social' => trim((string) $this->input('razon_social')),
            'certificador_fel' => $this->input('certificador_fel', 'infile'),
            'afiliacion_iva' => Str::upper(trim((string) $this->input('afiliacion_iva', 'GEN'))),
            'cuenta_bancaria' => $this->input('cuenta_bancaria') === null
                ? null
                : preg_replace('/[\s\-]/', '', (string) $this->input('cuenta_bancaria')),
        ]);
    }
}
