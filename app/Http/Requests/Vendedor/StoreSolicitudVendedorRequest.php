<?php

namespace App\Http\Requests\Vendedor;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreSolicitudVendedorRequest extends FormRequest
{
    /**
     * Determina si la solicitud puede ejecutarse.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validacion para el registro publico de vendedor.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $adultLimit = CarbonImmutable::now()->subYears(18)->format('Y-m-d');
        $oldestLimit = CarbonImmutable::now()->subYears(120)->format('Y-m-d');

        return [
            'name' => ['required', 'string', 'min:5', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+502\s?\d{4}\s?\d{4}$/'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . $adultLimit, 'after_or_equal:' . $oldestLimit],
            'gender' => ['nullable', Rule::in(['masculino', 'femenino', 'prefiero_no_decir'])],
            'address_street' => ['required', 'string', 'max:160'],
            'address_number' => ['required', 'string', 'max:40'],
            'address_suite' => ['nullable', 'string', 'max:80'],
            'address_municipio' => ['required', Rule::in($this->municipios())],
            'address_departamento' => ['required', Rule::in($this->departamentos())],
            'address_zip' => ['nullable', 'string', 'max:12'],

            'document_type' => ['required', Rule::in(['dpi', 'nit', 'pasaporte'])],
            'document_number' => ['required', 'string', 'max:80', 'unique:vendors,document_number'],
            'document_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'business_name' => ['required', 'string', 'min:3', 'max:100'],
            'business_description' => ['required', 'string', 'max:500'],
            'business_category' => ['required', Rule::in($this->businessCategories())],
            'business_category_other' => ['nullable', 'required_if:business_category,otro', 'string', 'max:120'],
            'business_logo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'has_nit' => ['required', 'boolean'],
            'seller_plan' => ['required', Rule::in(['starter', 'plus', 'crecimiento', 'profesional'])],

            'nit_number' => ['nullable', 'required_if:has_nit,1', 'string', 'max:30', 'unique:vendor_fiscal_profiles,nit'],
            'razon_social' => ['nullable', 'required_if:has_nit,1', 'string', 'min:5', 'max:220'],
            'regimen_sat' => ['nullable', 'required_if:has_nit,1', Rule::in(['ordinario', 'simplificado', 'otro'])],
            'business_street' => ['nullable', 'string', 'max:160'],
            'business_number' => ['nullable', 'string', 'max:40'],
            'business_municipio' => ['nullable', Rule::in($this->municipios())],
            'nit_file' => ['nullable', 'required_if:has_nit,1', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'bank' => ['required', Rule::in($this->banks())],
            'account_type' => ['required', Rule::in(['ahorros', 'corriente'])],
            'account_number' => ['required', 'string', 'min:6', 'max:40'],
            'account_holder' => ['required', 'string', 'min:5', 'max:180'],
            'bank_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'payment_frequency' => ['required', Rule::in(['semanal', 'quincenal', 'mensual'])],
            'preferred_payment_method' => ['required', Rule::in(['transferencia', 'deposito'])],

            'terms' => ['accepted'],
            'truth' => ['accepted'],
            'data_consent' => ['accepted'],
        ];
    }

    /**
     * Mensajes personalizados para que el usuario entienda que corregir.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este correo ya esta registrado.',
            'document_number.unique' => 'Este documento ya tiene una cuenta o solicitud.',
            'nit_number.unique' => 'Este NIT ya esta registrado.',
            'birthdate.before_or_equal' => 'Debes tener al menos 18 anos para vender.',
            'birthdate.after_or_equal' => 'La fecha de nacimiento no puede superar 120 anos.',
            'phone.regex' => 'Usa el formato de Guatemala: +502 XXXX XXXX.',
            '*.mimes' => 'Solo aceptamos JPG, PNG o PDF.',
            '*.max' => 'El archivo no debe superar 5MB.',
            'terms.accepted' => 'Debes aceptar los terminos y condiciones.',
            'truth.accepted' => 'Debes certificar que la informacion es verdadera.',
            'data_consent.accepted' => 'Debes autorizar el procesamiento de tus datos.',
        ];
    }

    /**
     * Validaciones de formato que dependen del tipo de documento.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('document_type');
            $number = preg_replace('/\s+/', '', (string) $this->input('document_number'));

            if ($type === 'dpi' && $number !== '' && ! preg_match('/^\d{8,13}-?\d{0,5}$/', $number)) {
                $validator->errors()->add('document_number', 'Ingresa un DPI valido, por ejemplo 12345678-1234.');
            }

            if ($type === 'nit' && $number !== '' && ! preg_match('/^\d{5,12}-?[\dkK]$/', $number)) {
                $validator->errors()->add('document_number', 'Ingresa un NIT valido, por ejemplo 1234567-8.');
            }
        });
    }

    /**
     * Municipios disponibles en el flujo publico.
     *
     * @return array<int, string>
     */
    public function municipios(): array
    {
        return ['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'];
    }

    /**
     * Departamentos de Guatemala.
     *
     * @return array<int, string>
     */
    public function departamentos(): array
    {
        return [
            'Alta Verapaz',
            'Baja Verapaz',
            'Chimaltenango',
            'Chiquimula',
            'El Progreso',
            'Escuintla',
            'Guatemala',
            'Huehuetenango',
            'Izabal',
            'Jalapa',
            'Jutiapa',
            'Peten',
            'Quetzaltenango',
            'Quiche',
            'Retalhuleu',
            'Sacatepequez',
            'San Marcos',
            'Santa Rosa',
            'Solola',
            'Suchitepequez',
            'Totonicapan',
            'Zacapa',
        ];
    }

    /**
     * Categorias comerciales aceptadas.
     *
     * @return array<int, string>
     */
    public function businessCategories(): array
    {
        return ['alimentos_frescos', 'panaderia_reposteria', 'bebidas_licores', 'artesania', 'ropa_accesorios', 'cosmeticos', 'electronica', 'servicios', 'otro'];
    }

    /**
     * Bancos disponibles.
     *
     * @return array<int, string>
     */
    public function banks(): array
    {
        return ['Banco Azteca', 'Banrural', 'Banco Industrial', 'BAM', 'BAC', 'G&T Continental', 'Banco Promerica', 'Otros'];
    }
}
