@extends('layouts.guest')

@section('content')
    <x-page-header title="Recuperar acceso" subtitle="Te enviaremos un enlace seguro para restablecer tu contrasena." />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-ui.input name="email" type="email" label="Correo electronico" required />
        <x-ui.button type="submit" class="w-full">Enviar enlace</x-ui.button>
    </form>
@endsection

