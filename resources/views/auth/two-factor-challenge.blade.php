@extends('layouts.guest')

@section('content')
    <x-page-header title="Verificacion 2FA" subtitle="Ingresa el codigo de seis digitos de tu segundo factor." />

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf
        <x-ui.input name="code" label="Codigo de seguridad" inputmode="numeric" required />
        <x-ui.button type="submit" class="w-full">Verificar</x-ui.button>
    </form>
@endsection

