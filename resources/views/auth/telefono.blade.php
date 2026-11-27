@extends('layouts.marketplace')

@section('content')
<x-auth.panel titulo="Entrar con tu teléfono" subtitulo="Te enviaremos un código por WhatsApp. Sin contraseñas.">
        @if (! $numero)
            <form method="POST" action="{{ route('acceso.telefono.enviar') }}" class="space-y-3" data-protect-submit>
                @csrf
                <label for="telefono" class="block text-sm font-black text-atlantia-ink">Número de celular</label>
                <div class="flex overflow-hidden rounded-xl border border-atlantia-rose/30 focus-within:border-atlantia-wine">
                    <span class="grid place-items-center bg-atlantia-blush px-3 text-sm font-black text-atlantia-wine">+502</span>
                    <input id="telefono" name="telefono" type="tel" inputmode="numeric" autocomplete="tel-national" value="{{ old('telefono') }}" placeholder="5555 1234" required class="w-full border-0 px-3 py-3 text-base focus:ring-0">
                </div>
                @error('telefono') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror
                <x-ui.button type="submit" class="w-full py-3">Enviarme el código</x-ui.button>
            </form>
        @else
            <p class="mb-4 text-sm text-atlantia-ink/65">
                Escribe el código que enviamos por WhatsApp al <strong>+{{ substr($numero, 0, 3) }} {{ substr($numero, 3, 4) }} {{ substr($numero, 7) }}</strong>.
            </p>

            <form method="POST" action="{{ route('acceso.telefono.verificar') }}" class="space-y-3" data-protect-submit>
                @csrf
                <label for="codigo" class="block text-sm font-black text-atlantia-ink">Código de 6 dígitos</label>
                <input id="codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus class="w-full rounded-xl border border-atlantia-rose/30 px-3 py-3 text-center text-2xl font-black tracking-[0.5em] focus:border-atlantia-wine focus:ring-atlantia-rose">
                @error('codigo') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror

                <label for="nombre" class="block pt-2 text-sm font-black text-atlantia-ink">¿Cómo te llamas? <span class="font-semibold text-atlantia-ink/50">(solo si es tu primera vez)</span></label>
                <input id="nombre" name="nombre" value="{{ old('nombre') }}" autocomplete="name" class="w-full rounded-xl border border-atlantia-rose/30 px-3 py-3 text-base focus:border-atlantia-wine focus:ring-atlantia-rose">

                <x-ui.button type="submit" class="w-full py-3">Entrar</x-ui.button>
            </form>

            <form method="POST" action="{{ route('acceso.telefono.cambiar') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="text-sm font-bold text-atlantia-wine underline">Usar otro número o pedir otro código</button>
            </form>
        @endif

    <x-slot:pie>
        <p class="mt-4 text-center text-sm">
            <a href="{{ route('login') }}" class="font-black text-atlantia-wine hover:text-atlantia-wine-700">Entrar con correo y contraseña</a>
        </p>
    </x-slot:pie>
</x-auth.panel>
@endsection
