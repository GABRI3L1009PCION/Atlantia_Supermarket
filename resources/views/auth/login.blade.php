@extends('layouts.guest')

@section('content')
    <x-page-header title="Ingresar" subtitle="Accede a tu cuenta de Atlantia Supermarket." />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-ui.input name="email" type="email" label="Correo electronico" required autofocus />
        <x-ui.input name="password" type="password" label="Contrasena" required />
        <x-ui.button type="submit" class="w-full">Ingresar</x-ui.button>
    </form>

    <a href="{{ route('password.request') }}" class="mt-4 block text-sm font-medium text-atlantia-wine">
        Olvide mi contrasena
    </a>
@endsection

