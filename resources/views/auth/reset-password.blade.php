@extends('layouts.guest')

@section('content')
    <x-page-header title="Nueva contrasena" subtitle="Usa una contrasena fuerte para proteger tu cuenta." />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-ui.input name="email" type="email" label="Correo electronico" :value="$email" required />
        <x-ui.input name="password" type="password" label="Nueva contrasena" required />
        <x-ui.input name="password_confirmation" type="password" label="Confirmar contrasena" required />
        <x-ui.button type="submit" class="w-full">Actualizar contrasena</x-ui.button>
    </form>
@endsection

