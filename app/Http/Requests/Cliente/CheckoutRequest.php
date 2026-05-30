<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Request de validacion para finalizar compra.
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Determina si el cliente puede ejecutar checkout.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() === null
            || $this->user()->hasRole('cliente')
            || $this->user()->can('checkout');
    }

    /**
     * Reglas de validacion del checkout.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guest = $this->user() === null;
        $crearCuenta = $this->boolean('crear_cuenta');

        return [
            'direccion_id' => [
                Rule::requiredIf(! $guest),
                'nullable',
                Rule::exists('direcciones', 'id')->where('user_id', $this->user()?->id)->where('activa', true),
            ],
            'guest_nombre' => [Rule::requiredIf($guest), 'nullable', 'string', 'min:3', 'max:160'],
            'guest_email' => [
                Rule::requiredIf($guest),
                'nullable',
                'email',
                'max:190',
                ...($crearCuenta ? [Rule::unique('users', 'email')] : []),
            ],
            'guest_telefono' => [Rule::requiredIf($guest), 'nullable', 'regex:/^(\+502)?[2-7][0-9]{7}$/'],
            'guest_alias' => [Rule::requiredIf($guest), 'nullable', 'string', 'max:80'],
            'guest_municipio' => [Rule::requiredIf($guest), 'nullable', Rule::in(['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'])],
            'guest_zona_barrio' => ['nullable', 'string', 'max:160'],
            'guest_direccion' => [Rule::requiredIf($guest), 'nullable', 'string', 'min:8', 'max:500'],
            'guest_referencia' => ['nullable', 'string', 'max:600'],
            'guest_latitude' => [Rule::requiredIf($guest), 'nullable', 'numeric', 'between:-90,90'],
            'guest_longitude' => [Rule::requiredIf($guest), 'nullable', 'numeric', 'between:-180,180'],
            'crear_cuenta' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf($guest && $crearCuenta), 'nullable', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
            'tipo_entrega' => ['required', Rule::in(['domicilio', 'recoger', 'programado'])],
            'ventana_entrega' => ['required_if:tipo_entrega,domicilio', 'nullable', 'string', 'max:40'],
            'programado_fecha' => ['required_if:tipo_entrega,programado', 'nullable', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(14)->toDateString()],
            'programado_hora' => ['required_if:tipo_entrega,programado', 'nullable', 'date_format:H:i'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'transferencia', 'tarjeta'])],
            'envio' => ['nullable', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'card_token' => ['required_if:metodo_pago,tarjeta', 'nullable', 'string', 'max:180'],
            'referencia_bancaria' => ['required_if:metodo_pago,transferencia', 'nullable', 'string', 'max:120'],
            'comprobante_path' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'acepta_terminos_checkout' => ['accepted'],
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
            'direccion_id.required' => 'Selecciona una direccion de entrega.',
            'direccion_id.exists' => 'La direccion seleccionada no existe o no pertenece a tu cuenta.',
            'guest_nombre.required' => 'Ingresa tu nombre para coordinar la entrega.',
            'guest_email.required' => 'Ingresa un correo para enviarte la confirmacion.',
            'guest_email.email' => 'Ingresa un correo valido.',
            'guest_email.unique' => 'Este correo ya tiene cuenta. Inicia sesion o usa otro correo para crear la cuenta.',
            'guest_telefono.required' => 'Ingresa un telefono de contacto.',
            'guest_telefono.regex' => 'Ingresa un telefono de Guatemala valido (ej. 55551234).',
            'guest_municipio.required' => 'Selecciona el municipio de entrega.',
            'guest_direccion.required' => 'Escribe la direccion completa de entrega.',
            'guest_latitude.required' => 'Captura tu ubicacion exacta para validar cobertura.',
            'password.required' => 'Ingresa una contrasena para crear tu cuenta.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'tipo_entrega.required' => 'Selecciona el tipo de entrega.',
            'tipo_entrega.in' => 'El tipo de entrega seleccionado no esta disponible.',
            'ventana_entrega.required_if' => 'Selecciona una ventana de entrega.',
            'programado_fecha.required_if' => 'Selecciona la fecha del pedido programado.',
            'programado_fecha.after_or_equal' => 'La fecha programada no puede ser anterior a hoy.',
            'programado_fecha.before_or_equal' => 'Solo puedes programar pedidos hasta 14 dias adelante.',
            'programado_hora.required_if' => 'Selecciona la hora del pedido programado.',
            'programado_hora.date_format' => 'Selecciona una hora valida.',
            'metodo_pago.required' => 'Selecciona un metodo de pago.',
            'metodo_pago.in' => 'El metodo de pago seleccionado no esta disponible.',
            'envio.numeric' => 'El costo de envio debe ser numerico.',
            'envio.max' => 'El costo de envio no puede superar Q:max.',
            'notas.max' => 'Las notas no deben superar :max caracteres.',
            'card_token.required_if' => 'No se recibio el token seguro de tarjeta.',
            'referencia_bancaria.required_if' => 'Ingresa la referencia de la transferencia bancaria.',
            'coupon_code.max' => 'El codigo del cupon no debe superar :max caracteres.',
            'acepta_terminos_checkout.accepted' => 'Debes aceptar las condiciones de compra.',
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
            'direccion_id' => 'direccion de entrega',
            'guest_nombre' => 'nombre',
            'guest_email' => 'correo electronico',
            'guest_telefono' => 'telefono',
            'guest_municipio' => 'municipio',
            'guest_direccion' => 'direccion de entrega',
            'guest_latitude' => 'ubicacion exacta',
            'guest_longitude' => 'ubicacion exacta',
            'password' => 'contrasena',
            'tipo_entrega' => 'tipo de entrega',
            'ventana_entrega' => 'ventana de entrega',
            'programado_fecha' => 'fecha programada',
            'programado_hora' => 'hora programada',
            'metodo_pago' => 'metodo de pago',
            'envio' => 'costo de envio',
            'notas' => 'notas del pedido',
            'card_token' => 'token de tarjeta',
            'referencia_bancaria' => 'referencia bancaria',
            'comprobante_path' => 'comprobante de transferencia',
            'coupon_code' => 'codigo de cupon',
            'acepta_terminos_checkout' => 'aceptacion de condiciones de compra',
        ];
    }

    /**
     * Normaliza campos antes de validar.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'metodo_pago' => trim((string) $this->input('metodo_pago')),
            'tipo_entrega' => trim((string) $this->input('tipo_entrega', 'domicilio')),
            'ventana_entrega' => $this->blankToNull($this->input('ventana_entrega')),
            'programado_fecha' => $this->blankToNull($this->input('programado_fecha')),
            'programado_hora' => $this->blankToNull($this->input('programado_hora')),
            'envio' => $this->input('envio') === null ? 0 : str_replace(',', '.', (string) $this->input('envio')),
            'notas' => $this->blankToNull($this->input('notas')),
            'guest_nombre' => $this->blankToNull($this->input('guest_nombre')),
            'guest_email' => $this->blankToNull($this->input('guest_email')),
            'guest_telefono' => $this->blankToNull(preg_replace('/[\s\-]/', '', (string) $this->input('guest_telefono'))),
            'guest_alias' => $this->blankToNull($this->input('guest_alias')) ?? 'Casa',
            'guest_zona_barrio' => $this->blankToNull($this->input('guest_zona_barrio')),
            'guest_direccion' => $this->blankToNull($this->input('guest_direccion')),
            'guest_referencia' => $this->blankToNull($this->input('guest_referencia')),
            'referencia_bancaria' => $this->blankToNull($this->input('referencia_bancaria')),
            'coupon_code' => $this->blankToNull($this->input('coupon_code')),
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
