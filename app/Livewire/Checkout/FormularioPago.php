<?php

namespace App\Livewire\Checkout;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Selector seguro de metodo de pago para checkout.
 */
class FormularioPago extends Component
{
    /**
     * Metodo de pago seleccionado por el cliente.
     */
    public string $metodoPago = 'efectivo';

    /**
     * Numero parcial de referencia para transferencia.
     */
    public ?string $referenciaTransferencia = null;

    /**
     * Campos de tarjeta para validacion visual.
     */
    public string $cardNumberPreview = '';
    public string $cardExpPreview = '';
    public string $cardCvvPreview = '';
    public string $cardNamePreview = '';

    /**
     * Indica si el cliente acepta terminos de compra.
     */
    public bool $aceptaTerminos = false;

    /**
     * Campos validados correctamente.
     *
     * @var array<int, string>
     */
    public array $validatedFields = [];

    /**
     * Metodos de pago permitidos.
     *
     * @var array<int, string>
     */
    public array $metodos = ['efectivo', 'transferencia', 'tarjeta'];

    /**
     * Reglas de validacion del componente.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'metodoPago' => ['required', 'string', Rule::in($this->metodos)],
            'referenciaTransferencia' => [
                Rule::requiredIf($this->metodoPago === 'transferencia'),
                'nullable',
                'string',
                'max:80',
            ],
            'cardNumberPreview' => [
                Rule::requiredIf($this->metodoPago === 'tarjeta'),
                'nullable',
                'string',
                'min:19',
                'max:19',
            ],
            'cardExpPreview' => [
                Rule::requiredIf($this->metodoPago === 'tarjeta'),
                'nullable',
                'regex:/^(0[1-9]|1[0-2])\s?\/\s?[0-9]{2}$/',
            ],
            'cardCvvPreview' => [
                Rule::requiredIf($this->metodoPago === 'tarjeta'),
                'nullable',
                'digits_between:3,4',
            ],
            'cardNamePreview' => [
                Rule::requiredIf($this->metodoPago === 'tarjeta'),
                'nullable',
                'string',
                'min:4',
                'max:120',
            ],
            'aceptaTerminos' => ['accepted'],
        ];
    }

    /**
     * Mensajes de validacion en espanol.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'metodoPago.required' => 'Selecciona un metodo de pago.',
            'metodoPago.in' => 'El metodo de pago seleccionado no esta disponible.',
            'referenciaTransferencia.required' => 'Ingresa la referencia de la transferencia.',
            'referenciaTransferencia.max' => 'La referencia no debe superar 80 caracteres.',
            'cardNumberPreview.required' => 'Ingresa el numero de tarjeta.',
            'cardExpPreview.required' => 'Ingresa la fecha de vencimiento.',
            'cardExpPreview.regex' => 'Usa el formato MM / AA.',
            'cardCvvPreview.required' => 'Ingresa el codigo de seguridad.',
            'cardCvvPreview.digits_between' => 'El codigo de seguridad debe tener 3 o 4 digitos.',
            'cardNamePreview.required' => 'Ingresa el nombre de la tarjeta.',
            'aceptaTerminos.accepted' => 'Debes aceptar las condiciones de compra.',
        ];
    }

    /**
     * Valida unicamente el campo de referencia.
     */
    public function updatedReferenciaTransferencia(): void
    {
        $this->validateOnly('referenciaTransferencia');
        $this->markFieldAsValidated('referenciaTransferencia');
    }

    /**
     * Valida numero de tarjeta visible.
     */
    public function updatedCardNumberPreview(): void
    {
        $digits = preg_replace('/\D+/', '', $this->cardNumberPreview) ?? '';
        $this->cardNumberPreview = trim(chunk_split(substr($digits, 0, 16), 4, ' '));
        $this->validateOnly('cardNumberPreview');
        $this->markFieldAsValidated('cardNumberPreview');
    }

    /**
     * Valida vencimiento de tarjeta.
     */
    public function updatedCardExpPreview(): void
    {
        $this->validateOnly('cardExpPreview');
        $this->markFieldAsValidated('cardExpPreview');
    }

    /**
     * Valida CVV visible.
     */
    public function updatedCardCvvPreview(): void
    {
        $this->cardCvvPreview = substr(preg_replace('/\D+/', '', $this->cardCvvPreview) ?? '', 0, 4);
        $this->validateOnly('cardCvvPreview');
        $this->markFieldAsValidated('cardCvvPreview');
    }

    /**
     * Valida nombre de tarjeta.
     */
    public function updatedCardNamePreview(): void
    {
        $this->validateOnly('cardNamePreview');
        $this->markFieldAsValidated('cardNamePreview');
    }

    /**
     * Selecciona un metodo de pago permitido.
     *
     * @param string $metodoPago
     * @return void
     */
    public function seleccionarMetodo(string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;

        if ($metodoPago !== 'transferencia') {
            $this->referenciaTransferencia = null;
        }

        $this->validarMetodoPago();
    }

    /**
     * Valida y notifica el metodo seleccionado al formulario padre.
     *
     * @return void
     */
    public function validarMetodoPago(): void
    {
        $this->validateOnly('metodoPago');

        $this->dispatch('checkout.metodo-pago-actualizado', metodoPago: $this->metodoPago);
    }

    /**
     * Estado visual de un campo.
     */
    public function fieldState(string $field): string
    {
        if (! in_array($field, $this->validatedFields, true)) {
            return 'idle';
        }

        return $this->getErrorBag()->has($field) ? 'invalid' : 'valid';
    }

    /**
     * Marca un campo como revisado para feedback visual.
     */
    private function markFieldAsValidated(string $field): void
    {
        if (! in_array($field, $this->validatedFields, true)) {
            $this->validatedFields[] = $field;
        }
    }

    /**
     * Renderiza el selector de pago.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.checkout.formulario-pago');
    }
}
