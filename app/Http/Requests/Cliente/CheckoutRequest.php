<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return $this->user() !== null
            && ($this->user()->hasRole('cliente') || $this->user()->can('checkout'));
    }

    /**
     * Reglas de validacion del checkout.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'direccion_id' => [
                'required',
                Rule::exists('direcciones', 'id')->where('user_id', $this->user()?->id)->where('activa', true),
            ],
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
            'envio' => $this->input('envio') === null ? 0 : str_replace(',', '.', (string) $this->input('envio')),
            'notas' => $this->blankToNull($this->input('notas')),
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
