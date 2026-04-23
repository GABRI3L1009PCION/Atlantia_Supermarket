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
            'phone' => ['required', 'string', 'min:8', 'max:15', 'regex:/^\+?[1-9][0-9]{7,14}$/'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
            'role' => ['required', Rule::in(['cliente', 'vendedor'])],
            'acepta_terminos' => ['accepted'],
            'acepta_privacidad' => ['accepted'],
            'dpi' => ['nullable', 'string', 'digits:13'],
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
            'nit' => [
                Rule::requiredIf($isVendedor),
                'nullable',
                'string',
                'regex:/^[0-9Kk\-]{4,30}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->nitValidoGuatemala((string) $value)) {
                        $fail('Ingresa un NIT valido para Guatemala.');
                    }
                },
            ],
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
            'email.dns' => 'El dominio del correo electronico no existe o no recibe correo.',
            'phone.required' => 'Ingresa un telefono de contacto.',
            'phone.min' => 'El telefono debe tener al menos 8 digitos.',
            'phone.max' => 'El telefono no debe superar 15 digitos.',
            'phone.regex' => 'Ingresa un telefono valido en formato nacional o E.164.',
            'password.required' => 'Ingresa una contrasena.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'role.required' => 'Selecciona el tipo de cuenta.',
            'role.in' => 'El tipo de cuenta seleccionado no es valido.',
            'acepta_terminos.accepted' => 'Debes aceptar los terminos y condiciones.',
            'acepta_privacidad.accepted' => 'Debes aceptar la politica de privacidad.',
            'dpi.digits' => 'El DPI debe contener exactamente 13 digitos.',
            'fecha_nacimiento.before_or_equal' => 'Debes ser mayor de edad para registrarte.',
            'business_name.required' => 'Ingresa el nombre comercial del vendedor.',
            'municipio.required' => 'Selecciona el municipio donde opera el vendedor.',
            'direccion_comercial.required' => 'Ingresa la direccion comercial del vendedor.',
            'nit.required' => 'Ingresa el NIT del vendedor.',
            'nit.regex' => 'El NIT solo puede contener numeros, guion y la letra K.',
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
            'dpi' => preg_replace('/\D+/', '', (string) $this->input('dpi')) ?: null,
            'nit' => Str::upper(str_replace([' ', '.'], '', (string) $this->input('nit'))),
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

    /**
     * Valida el digito verificador del NIT guatemalteco.
     *
     * @param string $nit
     * @return bool
     */
    private function nitValidoGuatemala(string $nit): bool
    {
        $nit = Str::upper(str_replace('-', '', trim($nit)));

        if (! preg_match('/^[0-9]+[0-9K]$/', $nit) || strlen($nit) < 2) {
            return false;
        }

        $verificador = substr($nit, -1);
        $base = substr($nit, 0, -1);
        $factor = strlen($base) + 1;
        $suma = 0;

        foreach (str_split($base) as $digito) {
            $suma += ((int) $digito) * $factor;
            $factor--;
        }

        $resultado = 11 - ($suma % 11);
        $esperado = match ($resultado) {
            11 => '0',
            10 => 'K',
            default => (string) $resultado,
        };

        return $verificador === $esperado;
    }
}
