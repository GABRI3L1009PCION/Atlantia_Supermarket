<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Request de validacion para registro de clientes y vendedores.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determina si el usuario puede realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * Reglas de validacion del registro.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isVendedor = $this->input('role') === 'vendedor';

        return [
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^(\+502)?[2-7][0-9]{7}$/'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
            'role' => ['required', Rule::in(['cliente', 'vendedor'])],
            'acepta_terminos' => ['accepted'],
            'acepta_privacidad' => ['accepted'],
            'dpi' => ['nullable', 'string', 'regex:/^[0-9]{13}$/'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:-18 years'],
            'genero' => ['nullable', Rule::in(['femenino', 'masculino', 'otro', 'prefiero_no_decir'])],
            'preferencias' => ['nullable', 'array'],
            'acepta_marketing' => ['sometimes', 'boolean'],
            'business_name' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'min:3', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:1200'],
            'municipio' => [Rule::requiredIf($isVendedor), 'nullable', $this->municipioRule()],
            'direccion_comercial' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'min:8', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'nit' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'regex:/^[0-9Kk\-]{4,30}$/'],
            'razon_social' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'min:3', 'max:220'],
            'direccion_fiscal' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'min:8', 'max:600'],
            'regimen_sat' => [Rule::requiredIf($isVendedor), 'nullable', Rule::in([
                'pequeno_contribuyente',
                'general',
                'exento',
            ])],
            'codigo_establecimiento' => [Rule::requiredIf($isVendedor), 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Mensajes personalizados de validacion.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa tu nombre completo.',
            'name.min' => 'El nombre debe tener al menos :min caracteres.',
            'email.required' => 'Ingresa tu correo electronico.',
            'email.email' => 'Ingresa un correo electronico valido.',
            'email.unique' => 'Este correo electronico ya esta registrado.',
            'phone.required' => 'Ingresa un telefono de contacto.',
            'phone.regex' => 'Ingresa un telefono valido de Guatemala.',
            'password.required' => 'Ingresa una contrasena.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'role.required' => 'Selecciona el tipo de cuenta.',
            'role.in' => 'El tipo de cuenta seleccionado no es valido.',
            'acepta_terminos.accepted' => 'Debes aceptar los terminos y condiciones.',
            'acepta_privacidad.accepted' => 'Debes aceptar la politica de privacidad.',
            'dpi.regex' => 'El DPI debe contener 13 digitos.',
            'fecha_nacimiento.before_or_equal' => 'Debes ser mayor de edad para registrarte.',
            'business_name.required' => 'Ingresa el nombre comercial del vendedor.',
            'municipio.required' => 'Selecciona el municipio donde opera el vendedor.',
            'direccion_comercial.required' => 'Ingresa la direccion comercial del vendedor.',
            'nit.required' => 'Ingresa el NIT del vendedor.',
            'nit.regex' => 'Ingresa un NIT valido para Guatemala.',
            'razon_social.required' => 'Ingresa la razon social registrada ante SAT.',
            'direccion_fiscal.required' => 'Ingresa la direccion fiscal registrada ante SAT.',
            'regimen_sat.required' => 'Selecciona el regimen SAT.',
            'codigo_establecimiento.required' => 'Ingresa el codigo de establecimiento SAT.',
        ];
    }

    /**
     * Nombres legibles de atributos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'email' => 'correo electronico',
            'phone' => 'telefono',
            'password' => 'contrasena',
            'role' => 'tipo de cuenta',
            'dpi' => 'DPI',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'genero' => 'genero',
            'preferencias' => 'preferencias',
            'acepta_marketing' => 'aceptacion de marketing',
            'business_name' => 'nombre comercial',
            'descripcion' => 'descripcion del negocio',
            'municipio' => 'municipio',
            'direccion_comercial' => 'direccion comercial',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'nit' => 'NIT',
            'razon_social' => 'razon social',
            'direccion_fiscal' => 'direccion fiscal',
            'regimen_sat' => 'regimen SAT',
            'codigo_establecimiento' => 'codigo de establecimiento',
        ];
    }

    /**
     * Normaliza datos antes de validar.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->normalizarTelefono($this->input('phone')),
            'role' => $this->input('role', 'cliente'),
            'acepta_marketing' => filter_var($this->input('acepta_marketing', false), FILTER_VALIDATE_BOOLEAN),
            'business_name' => $this->blankToNull($this->input('business_name')),
            'nit' => Str::upper(str_replace(' ', '', (string) $this->input('nit'))),
        ]);
    }

    /**
     * Regla de municipios atendidos por Atlantia.
     *
     * @return \Illuminate\Validation\Rules\In
     */
    private function municipioRule()
    {
        return Rule::in([
            'Puerto Barrios',
            'Santo Tomas',
            'Santo Tomás',
            'Morales',
            'Los Amates',
            'Livingston',
            'El Estor',
        ]);
    }

    /**
     * Normaliza telefono guatemalteco.
     *
     * @param mixed $telefono
     * @return string
     */
    private function normalizarTelefono(mixed $telefono): string
    {
        return preg_replace('/[\s\-]/', '', (string) $telefono) ?? '';
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
