@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-atlantia-blush text-2xl font-black text-atlantia-wine">
            2FA
        </div>

        <p class="mt-5 text-xs font-bold uppercase tracking-wide text-atlantia-rose">
            Verificacion segura
        </p>
        <h1 class="mt-2 text-3xl font-black text-atlantia-ink">
            Confirma tu identidad
        </h1>
        <p class="mt-3 text-sm leading-6 text-atlantia-ink/65">
            Ingresa el codigo de seis digitos para continuar al panel correspondiente.
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-bold text-atlantia-ink">
                Codigo de seguridad
            </label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                pattern="[0-9]{6}"
                autofocus
                required
                class="mt-2 w-full rounded-lg border border-atlantia-rose/35 bg-white px-4 py-4 text-center text-3xl font-black tracking-[0.35em] text-atlantia-ink shadow-sm outline-none transition focus:border-atlantia-wine focus:ring-4 focus:ring-atlantia-rose/20"
                aria-describedby="code-help"
            >
            <p id="code-help" class="mt-2 text-center text-xs text-atlantia-ink/55">
                El codigo debe tener exactamente seis numeros.
            </p>
        </div>

        <x-ui.button type="submit" class="w-full py-3">
            Verificar y continuar
        </x-ui.button>
    </form>

    <div class="mt-6 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm text-atlantia-ink/75">
        <p class="font-bold text-atlantia-ink">Estas en entorno local?</p>
        <p class="mt-1">
            Mientras activamos 2FA real con app autenticadora, usa el codigo temporal de desarrollo.
        </p>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="text-sm font-bold text-atlantia-wine hover:underline">
            Usar otra cuenta
        </button>
    </form>
@endsection
