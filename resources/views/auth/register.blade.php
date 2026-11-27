@extends('layouts.marketplace')

@section('content')
<x-auth.panel titulo="Crear cuenta" subtitulo="Compra en los comercios de Puerto Barrios y Santo Tomás con una cuenta segura." etiqueta="Registro gratis">
    <livewire:auth.register-form />
    <x-slot:pie>
        <p class="mt-4 text-center text-sm text-atlantia-ink/65">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-black text-atlantia-wine hover:text-atlantia-wine-700">Ingresar</a>
        </p>
    </x-slot:pie>
</x-auth.panel>
@endsection
