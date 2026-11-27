@extends('layouts.marketplace')

@section('content')
<x-auth.panel titulo="Recuperar acceso" subtitulo="Te enviaremos un enlace seguro para restablecer tu contraseña.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" data-protect-submit>
        @csrf
        <x-ui.input name="email" type="email" label="Correo electrónico" required />
        <x-ui.button type="submit" class="w-full">Enviar enlace</x-ui.button>
    </form>
    <x-slot:pie>
        <p class="mt-4 text-center text-sm text-atlantia-ink/65">
            <a href="{{ route('login') }}" class="font-black text-atlantia-wine hover:text-atlantia-wine-700">← Volver a ingresar</a>
        </p>
    </x-slot:pie>
</x-auth.panel>
@endsection
