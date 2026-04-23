@php
    $fieldIcon = function (string $field): string {
        return match ($this->fieldState($field)) {
            'valid' => 'text-emerald-600',
            'invalid' => 'text-rose-600',
            default => 'text-slate-300',
        };
    };
@endphp

<form wire:submit="register" class="space-y-4">
    <input type="hidden" name="role" value="cliente">

    <div>
        <label for="register-name" class="mb-1 block text-sm font-medium text-atlantia-ink">Nombre completo</label>
        <div class="relative">
            <input
                id="register-name"
                type="text"
                wire:model.blur="name"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('name') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('name') }}" aria-hidden="true">
                {!! $this->fieldState('name') === 'valid' ? '&#10003;' : ($this->fieldState('name') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-email" class="mb-1 block text-sm font-medium text-atlantia-ink">Correo electronico</label>
        <div class="relative">
            <input
                id="register-email"
                type="email"
                wire:model.live.debounce.400ms="email"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('email') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('email') }}" aria-hidden="true">
                {!! $this->fieldState('email') === 'valid' ? '&#10003;' : ($this->fieldState('email') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('email') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-phone" class="mb-1 block text-sm font-medium text-atlantia-ink">Telefono</label>
        <div class="relative">
            <input
                id="register-phone"
                type="text"
                wire:model.live.debounce.300ms="phone"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                placeholder="Ej. 55554669"
                aria-invalid="@error('phone') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('phone') }}" aria-hidden="true">
                {!! $this->fieldState('phone') === 'valid' ? '&#10003;' : ($this->fieldState('phone') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('phone') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-password" class="mb-1 block text-sm font-medium text-atlantia-ink">Contrasena</label>
        <div class="relative">
            <input
                id="register-password"
                type="password"
                wire:model.blur="password"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('password') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('password') }}" aria-hidden="true">
                {!! $this->fieldState('password') === 'valid' ? '&#10003;' : ($this->fieldState('password') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        <p class="mt-1 text-xs text-atlantia-ink/60">Usa minimo 12 caracteres, con letras, numeros y simbolos.</p>
        @error('password') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="register-password-confirmation" class="mb-1 block text-sm font-medium text-atlantia-ink">Confirmar contrasena</label>
        <div class="relative">
            <input
                id="register-password-confirmation"
                type="password"
                wire:model.blur="password_confirmation"
                class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                aria-invalid="@error('password_confirmation') true @else false @enderror"
            >
            <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('password_confirmation') }}" aria-hidden="true">
                {!! $this->fieldState('password_confirmation') === 'valid' ? '&#10003;' : ($this->fieldState('password_confirmation') === 'invalid' ? '&#10005;' : '&bull;') !!}
            </span>
        </div>
        @error('password_confirmation') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm">
        <label for="register-terms" class="flex gap-3">
            <input
                id="register-terms"
                type="checkbox"
                wire:model.live="acepta_terminos"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Acepto los terminos y condiciones de Atlantia Supermarket.</span>
        </label>
        @error('acepta_terminos') <p class="text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

        <label for="register-privacy" class="flex gap-3">
            <input
                id="register-privacy"
                type="checkbox"
                wire:model.live="acepta_privacidad"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Acepto la politica de privacidad y tratamiento de datos.</span>
        </label>
        @error('acepta_privacidad') <p class="text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

        <label for="register-marketing" class="flex gap-3">
            <input
                id="register-marketing"
                type="checkbox"
                wire:model.live="acepta_marketing"
                class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
            >
            <span>Deseo recibir ofertas, recomendaciones y novedades.</span>
        </label>
    </div>

    <button
        type="submit"
        class="inline-flex w-full items-center justify-center rounded-md bg-atlantia-wine px-4 py-2 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 hover:bg-atlantia-wine-700"
        wire:loading.attr="disabled"
        wire:target="register"
    >
        <span wire:loading.remove wire:target="register">Crear cuenta</span>
        <span wire:loading wire:target="register">Creando cuenta...</span>
    </button>
</form>
