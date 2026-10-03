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
     * Indica si el cliente necesita cambio en efectivo.
     */
    public bool $solicitaCambio = false;

    /**
     * Denominacion del billete para el cual se solicita cambio.
     */
    public ?string $cambioPara = null;

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
                'nullable',
                'string',
                'max:80',
            ],
            'cambioPara' => [
                Rule::requiredIf($this->metodoPago === 'efectivo' && $this->solicitaCambio),
                'nullable',
                'numeric',
                'min:1',
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
            'referenciaTransferencia.max' => 'La referencia no debe superar 80 caracteres.',
            'cambioPara.required' => 'Indica para que billete necesitas cambio.',
            'cambioPara.numeric' => 'El billete para cambio debe ser numerico.',
            'cambioPara.min' => 'El billete para cambio debe ser mayor que cero.',
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
     * Sincroniza la solicitud de cambio.
     */
    public function updatedSolicitaCambio(): void
    {
        if (! $this->solicitaCambio) {
            $this->cambioPara = null;
        }
    }

    /**
     * Valida el billete para cambio.
     */
    public function updatedCambioPara(): void
    {
        $this->validateOnly('cambioPara');
        $this->markFieldAsValidated('cambioPara');
    }

    /**
     * Selecciona un metodo de pago permitido.
     */
    public function seleccionarMetodo(string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;

        $this->validarMetodoPago();
    }

    /**
     * Sincroniza el formulario cuando el cliente cambia el radio.
     */
    public function updatedMetodoPago(string $metodoPago): void
    {
        if (! in_array($metodoPago, $this->metodos, true)) {
            $this->metodoPago = 'efectivo';
        }

        if ($this->metodoPago !== 'transferencia') {
            $this->referenciaTransferencia = null;
        }

        if ($this->metodoPago !== 'efectivo') {
            $this->solicitaCambio = false;
            $this->cambioPara = null;
        }

        $this->validarMetodoPago();
    }

    /**
     * Valida y notifica el metodo seleccionado al formulario padre.
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
     */
    public function render(): View
    {
        return view('livewire.checkout.formulario-pago');
    }
}
