@extends('layouts.marketplace')

@section('content')
<x-auth.panel titulo="Nueva contraseña" subtitulo="Usa una contraseña fuerte para proteger tu cuenta.">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4" data-protect-submit>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-ui.input name="email" type="email" label="Correo electrónico" :value="$email" required />
        <x-ui.input name="password" type="password" label="Nueva contraseña" required />
        <x-ui.input name="password_confirmation" type="password" label="Confirmar contraseña" required />
        <x-ui.button type="submit" class="w-full">Actualizar contraseña</x-ui.button>
    </form>
    <x-slot:pie>
        <p class="mt-4 text-center text-sm text-atlantia-ink/65">
            <a href="{{ route('login') }}" class="font-black text-atlantia-wine hover:text-atlantia-wine-700">← Volver a ingresar</a>
        </p>
    </x-slot:pie>
</x-auth.panel>
@endsection
