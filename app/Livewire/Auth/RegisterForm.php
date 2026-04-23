<?php

namespace App\Livewire\Auth;

use App\Services\Auth\RegistroService;
use App\Services\Carrito\CarritoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Formulario de registro con validacion en tiempo real para clientes.
 */
class RegisterForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $acepta_terminos = false;
    public bool $acepta_privacidad = false;
    public bool $acepta_marketing = false;

    /**
     * Campos validados correctamente.
     *
     * @var array<int, string>
     */
    public array $validatedFields = [];

    /**
     * Reglas del formulario.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:8', 'max:15', 'regex:/^\+?[1-9][0-9]{7,14}$/'],
            'password' => ['required', Password::min(12)->letters()->numbers()->symbols()],
            'password_confirmation' => ['required', 'same:password'],
            'acepta_terminos' => ['accepted'],
            'acepta_privacidad' => ['accepted'],
            'acepta_marketing' => ['boolean'],
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
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'Ingresa tu correo electronico.',
            'email.email' => 'Ingresa un correo electronico valido.',
            'email.unique' => 'Este correo electronico ya esta registrado.',
            'phone.required' => 'Ingresa tu telefono.',
            'phone.regex' => 'Ingresa un telefono valido en formato nacional o internacional.',
            'password.required' => 'Ingresa una contrasena segura.',
            'password_confirmation.same' => 'La confirmacion no coincide con la contrasena.',
            'acepta_terminos.accepted' => 'Debes aceptar los terminos y condiciones.',
            'acepta_privacidad.accepted' => 'Debes aceptar la politica de privacidad.',
        ];
    }

    /**
     * Valida solo el campo actualizado.
     */
    public function updated($property): void
    {
        if (in_array($property, ['email', 'phone'], true)) {
            $this->{$property} = trim((string) $this->{$property});
        }

        if ($property === 'email') {
            $this->email = Str::lower($this->email);
        }

        $this->validateOnly($property);

        if (! in_array($property, $this->validatedFields, true)) {
            $this->validatedFields[] = $property;
        }
    }

    /**
     * Procesa el registro del cliente.
     */
    public function register(RegistroService $registroService, CarritoService $carritoService)
    {
        $data = $this->validate();
        $guestSessionId = session()->getId();
        $user = $registroService->register([
            ...$data,
            'role' => 'cliente',
        ]);

        Auth::login($user);
        session()->regenerate();
        $carritoService->mergeGuestCartIntoUser($guestSessionId, $user);
        session()->flash('success', 'Cuenta creada correctamente. Revisa tu correo para verificarla.');
        $this->dispatch('toast', type: 'success', message: 'Tu cuenta fue creada correctamente.');

        return redirect()->route('cliente.carrito.index');
    }

    /**
     * Devuelve el estado visual de un campo.
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
        return view('livewire.auth.register-form');
    }
}
