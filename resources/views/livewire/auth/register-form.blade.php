@php
    $fieldIcon = function (string $field): string {
        return match ($this->fieldState($field)) {
            'valid' => 'text-emerald-600',
            'invalid' => 'text-rose-600',
            default => 'text-slate-300',
        };
    };
@endphp

<form
    method="POST"
    action="{{ route('register.store') }}"
    class="space-y-4"
    x-data="{ showPassword: false, showPasswordConfirmation: false }"
    x-on:livewire:update.window="
        $nextTick(() => {
            const firstError = $el.querySelector('[data-field-error]');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
    "
>
    @csrf
    <input type="hidden" name="role" value="cliente">

    @if ($errors->any())
        <div data-field-error class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
            <p class="mb-1 font-bold">Corrige los siguientes campos:</p>
            <ul class="list-inside list-disc space-y-0.5 font-normal">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label for="register-name" class="mb-1 block text-sm font-medium text-atlantia-ink">Nombre completo</label>
        <div class="relative">
            <input
                id="register-name"
                name="name"
                type="text"
                wire:model.blur="name"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('name') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('name') }}" aria-hidden="true">
                {!! $this->fieldState('name') === 'valid' ? '&#10003;' : ($this->fieldState('name') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('name') <p data-field-error class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-email" class="mb-1 block text-sm font-medium text-atlantia-ink">Correo electronico</label>
        <div class="relative">
            <input
                id="register-email"
                name="email"
                type="email"
                wire:model.blur="email"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('email') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('email') }}" aria-hidden="true">
                {!! $this->fieldState('email') === 'valid' ? '&#10003;' : ($this->fieldState('email') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('email') <p data-field-error class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-phone" class="mb-1 block text-sm font-medium text-atlantia-ink">Telefono</label>
        <div class="relative">
            <input
                id="register-phone"
                name="phone"
                type="text"
                wire:model.blur="phone"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                placeholder="Ej. 55554669"
                aria-invalid="@error('phone') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('phone') }}" aria-hidden="true">
                {!! $this->fieldState('phone') === 'valid' ? '&#10003;' : ($this->fieldState('phone') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('phone') <p data-field-error class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-password" class="mb-1 block text-sm font-medium text-atlantia-ink">Contrasena</label>
        <div class="relative">
            <input
                id="register-password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                wire:model.blur="password"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-20 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('password') true @else false @enderror"
            >
            <button
                type="button"
                class="absolute inset-y-0 right-9 flex items-center rounded-md px-2 text-atlantia-ink/50 transition hover:text-atlantia-wine"
                aria-label="Mostrar u ocultar contrasena"
                @click="showPassword = !showPassword"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3.5 12C5.5 8.7 8.3 7 12 7C15.7 7 18.5 8.7 20.5 12C18.5 15.3 15.7 17 12 17C8.3 17 5.5 15.3 3.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M12 14.2C13.2 14.2 14.2 13.2 14.2 12C14.2 10.8 13.2 9.8 12 9.8C10.8 9.8 9.8 10.8 9.8 12C9.8 13.2 10.8 14.2 12 14.2Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('password') }}" aria-hidden="true">
                {!! $this->fieldState('password') === 'valid' ? '&#10003;' : ($this->fieldState('password') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        <p class="mt-1 text-xs text-atlantia-ink/60">Usa minimo 12 caracteres, con letras, numeros y simbolos.</p>
        @error('password') <p data-field-error class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-password-confirmation" class="mb-1 block text-sm font-medium text-atlantia-ink">Confirmar contrasena</label>
        <div class="relative">
            <input
                id="register-password-confirmation"
                name="password_confirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                wire:model.blur="password_confirmation"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-20 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('password_confirmation') true @else false @enderror"
            >
            <button
                type="button"
                class="absolute inset-y-0 right-9 flex items-center rounded-md px-2 text-atlantia-ink/50 transition hover:text-atlantia-wine"
                aria-label="Mostrar u ocultar confirmacion de contrasena"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3.5 12C5.5 8.7 8.3 7 12 7C15.7 7 18.5 8.7 20.5 12C18.5 15.3 15.7 17 12 17C8.3 17 5.5 15.3 3.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M12 14.2C13.2 14.2 14.2 13.2 14.2 12C14.2 10.8 13.2 9.8 12 9.8C10.8 9.8 9.8 10.8 9.8 12C9.8 13.2 10.8 14.2 12 14.2Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('password_confirmation') }}" aria-hidden="true">
                {!! $this->fieldState('password_confirmation') === 'valid' ? '&#10003;' : ($this->fieldState('password_confirmation') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('password_confirmation') <p data-field-error class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm">
        <label for="register-terms" class="flex gap-3">
            <input
                id="register-terms"
                name="acepta_terminos"
                type="checkbox"
                value="1"
                wire:model.live="acepta_terminos"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Acepto los terminos y condiciones de Atlantia Supermarket.</span>
        </label>
        @error('acepta_terminos') <p class="text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

        <label for="register-privacy" class="flex gap-3">
            <input
                id="register-privacy"
                name="acepta_privacidad"
                type="checkbox"
                value="1"
                wire:model.live="acepta_privacidad"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Acepto la politica de privacidad y tratamiento de datos.</span>
        </label>
        @error('acepta_privacidad') <p class="text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

        <label for="register-marketing" class="flex gap-3">
            <input
                id="register-marketing"
                name="acepta_marketing"
                type="checkbox"
                value="1"
                wire:model.live="acepta_marketing"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Deseo recibir ofertas, recomendaciones y novedades.</span>
        </label>
    </div>

    <button
        type="submit"
        class="inline-flex w-full items-center justify-center rounded-md bg-atlantia-wine px-4 py-2 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 hover:bg-atlantia-wine-700"
    >
        Crear cuenta
    </button>
</form>
