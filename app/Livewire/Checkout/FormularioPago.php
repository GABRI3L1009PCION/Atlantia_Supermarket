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
     * Indica si el cliente acepta terminos de compra.
     */
    public bool $aceptaTerminos = false;

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
            'aceptaTerminos.accepted' => 'Debes aceptar las condiciones de compra.',
        ];
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
     * Renderiza el selector de pago.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.checkout.formulario-pago');
    }
}
