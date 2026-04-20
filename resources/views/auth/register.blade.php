@extends('layouts.guest')

@section('content')
    <x-page-header title="Crear cuenta" subtitle="Compra a vendedores locales de Izabal con una cuenta segura." />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <x-ui.input name="name" label="Nombre completo" required />
        <x-ui.input name="email" type="email" label="Correo electronico" required />
        <x-ui.input name="phone" label="Telefono" />
        <x-ui.input name="password" type="password" label="Contrasena" required />
        <x-ui.input name="password_confirmation" type="password" label="Confirmar contrasena" required />
        <x-ui.button type="submit" class="w-full">Crear cuenta</x-ui.button>
    </form>
@endsection

