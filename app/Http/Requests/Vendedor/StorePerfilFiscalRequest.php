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
    /** Secciones que se guardan por separado. */
    public const SECCIONES = ['banco', 'fiscal', 'fel', 'modo'];

    public function rules(): array
    {
        $vendorId = $this->user()?->vendor?->id;
        $seccion = $this->input('seccion');

        $banco = [
            'banco_nombre' => ['required', 'string', 'max:120'],
            'cuenta_bancaria' => ['required', 'string', 'regex:/^[0-9\-\s]{5,40}$/'],
            'cuenta_bancaria_tipo' => ['required', Rule::in(['monetaria', 'ahorro'])],
            'cuenta_bancaria_titular' => ['required', 'string', 'min:3', 'max:180'],
        ];
        $fiscal = [
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
        ];
        $fel = [
            'certificador_fel' => ['required', Rule::in(['infile'])],
            'fel_usuario' => ['nullable', 'string', 'max:190'],
            'fel_llave_firma' => ['nullable', 'string', 'max:5000'],
            'fel_llave_certificador' => ['nullable', 'string', 'max:5000'],
        ];

        return ['seccion' => ['nullable', Rule::in(self::SECCIONES)]] + match ($seccion) {
            'banco' => $banco,
            'fiscal' => $fiscal,
            'fel' => $fel,
            'modo' => ['modo_facturacion' => ['required', Rule::in(['manual_sat', 'certificador'])]],
            /* Sin sección: el formulario completo de antes (todo lo fiscal obligatorio, banco opcional). */
            default => $fiscal + $fel + array_map(
                fn (array $reglas): array => ['nullable', ...array_values(array_diff($reglas, ['required']))],
                $banco,
            ),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'banco_nombre.required' => 'Elige tu banco.',
            'cuenta_bancaria.required' => 'Escribe tu número de cuenta.',
            'cuenta_bancaria.regex' => 'El número de cuenta solo puede tener números y guiones.',
            'cuenta_bancaria_tipo.required' => 'Elige el tipo de cuenta.',
            'cuenta_bancaria_titular.required' => 'Escribe a nombre de quién está la cuenta.',
            'nit.required' => 'Escribe tu NIT.',
            'nit.regex' => 'El NIT solo puede tener números, guion y la letra K.',
            'nit.unique' => 'Ese NIT ya está registrado en otra tienda.',
            'razon_social.required' => 'Escribe tu nombre o razón social como aparece en SAT.',
            'direccion_fiscal.required' => 'Escribe tu dirección fiscal.',
            'codigo_establecimiento.required' => 'Escribe el número de establecimiento que te dio SAT.',
            'regimen_sat.required' => 'Elige tu régimen en SAT.',
            'regimen_sat.in' => 'El régimen elegido no es válido.',
            'afiliacion_iva.required' => 'Escribe tu afiliación de IVA.',
            'certificador_fel.in' => 'Ese certificador no está disponible.',
            'cuenta_bancaria_tipo.in' => 'Elige monetaria o de ahorro.',
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
