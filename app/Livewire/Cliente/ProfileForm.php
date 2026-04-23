<?php

namespace App\Livewire\Cliente;

use App\Services\Clientes\PerfilClienteService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Formulario de perfil con validacion inmediata para clientes.
 */
class ProfileForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $dpi = '';
    public string $telefono = '';
    public string $fecha_nacimiento = '';

    /**
     * Campos validados correctamente.
     *
     * @var array<int, string>
     */
    public array $validatedFields = [];

    /**
     * Carga datos actuales del cliente.
     */
    public function mount(): void
    {
        $user = auth()->user()?->load('clienteDetalle');

        $this->name = (string) $user?->name;
        $this->email = (string) $user?->email;
        $this->phone = (string) ($user?->phone ?? '');
        $this->dpi = (string) ($user?->clienteDetalle?->dpi ?? '');
        $this->telefono = (string) ($user?->clienteDetalle?->telefono ?? '');
        $this->fecha_nacimiento = optional($user?->clienteDetalle?->fecha_nacimiento)?->format('Y-m-d') ?? '';
    }

    /**
     * Reglas del formulario.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dpi' => ['nullable', 'digits:13'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-12 years'],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Ingresa tu nombre completo.',
            'dpi.digits' => 'El DPI debe contener exactamente 13 digitos.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento no es valida.',
        ];
    }

    /**
     * Valida solamente el campo actualizado.
     */
    public function updated($property): void
    {
        $this->validateOnly($property);

        if (! in_array($property, $this->validatedFields, true)) {
            $this->validatedFields[] = $property;
        }
    }

    /**
     * Guarda el perfil del cliente.
     */
    public function save(PerfilClienteService $perfilClienteService): void
    {
        $data = $this->validate();
        $perfilClienteService->update(auth()->user(), $data);
        session()->flash('success', 'Perfil actualizado correctamente.');
        $this->dispatch('toast', type: 'success', message: 'Tu perfil fue actualizado.');
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
     * Renderiza el formulario.
     */
    public function render(): View
    {
        return view('livewire.cliente.profile-form');
    }
}
