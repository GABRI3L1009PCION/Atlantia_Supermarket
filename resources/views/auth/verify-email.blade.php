@extends('layouts.guest')

@section('content')
    <x-page-header title="Verifica tu correo" subtitle="Antes de continuar, confirma tu direccion de correo electronico." />

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-ui.button type="submit" class="w-full">Reenviar verificacion</x-ui.button>
    </form>
@endsection

