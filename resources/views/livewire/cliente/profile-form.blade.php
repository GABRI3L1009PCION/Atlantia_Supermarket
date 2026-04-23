@php
    $fieldIcon = function (string $field): string {
        return match ($this->fieldState($field)) {
            'valid' => 'text-emerald-600',
            'invalid' => 'text-rose-600',
            default => 'text-slate-300',
        };
    };
@endphp

<form wire:submit="save" class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="profile-name" class="mb-1 block text-sm font-medium text-atlantia-ink">Nombre completo</label>
            <div class="relative">
                <input
                    id="profile-name"
                    type="text"
                    wire:model.blur="name"
                    class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('name') }}" aria-hidden="true">
                    {!! $this->fieldState('name') === 'valid' ? '&#10003;' : ($this->fieldState('name') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="profile-email" class="mb-1 block text-sm font-medium text-atlantia-ink">Correo electronico</label>
            <input
                id="profile-email"
                type="email"
                value="{{ $email }}"
                disabled
                class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
            >
        </div>

        <div>
            <label for="profile-phone" class="mb-1 block text-sm font-medium text-atlantia-ink">Telefono de cuenta</label>
            <div class="relative">
                <input
                    id="profile-phone"
                    type="text"
                    wire:model.live.debounce.300ms="phone"
                    class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('phone') }}" aria-hidden="true">
                    {!! $this->fieldState('phone') === 'valid' ? '&#10003;' : ($this->fieldState('phone') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('phone') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="profile-dpi" class="mb-1 block text-sm font-medium text-atlantia-ink">DPI</label>
            <div class="relative">
                <input
                    id="profile-dpi"
                    type="text"
                    wire:model.blur="dpi"
                    class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('dpi') }}" aria-hidden="true">
                    {!! $this->fieldState('dpi') === 'valid' ? '&#10003;' : ($this->fieldState('dpi') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('dpi') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="profile-contact-phone" class="mb-1 block text-sm font-medium text-atlantia-ink">Telefono alterno</label>
            <div class="relative">
                <input
                    id="profile-contact-phone"
                    type="text"
                    wire:model.blur="telefono"
                    class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('telefono') }}" aria-hidden="true">
                    {!! $this->fieldState('telefono') === 'valid' ? '&#10003;' : ($this->fieldState('telefono') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('telefono') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="profile-birthdate" class="mb-1 block text-sm font-medium text-atlantia-ink">Fecha de nacimiento</label>
            <div class="relative">
                <input
                    id="profile-birthdate"
                    type="date"
                    wire:model.blur="fecha_nacimiento"
                    class="w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 pr-11 text-sm text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-rose"
                >
                <span class="absolute inset-y-0 right-3 flex items-center {{ $fieldIcon('fecha_nacimiento') }}" aria-hidden="true">
                    {!! $this->fieldState('fecha_nacimiento') === 'valid' ? '&#10003;' : ($this->fieldState('fecha_nacimiento') === 'invalid' ? '&#10005;' : '&bull;') !!}
                </span>
            </div>
            @error('fecha_nacimiento') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-atlantia-wine px-5 py-3 text-sm font-semibold text-white transition hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">Actualizar perfil</span>
            <span wire:loading wire:target="save">Guardando cambios...</span>
        </button>
    </div>
</form>
