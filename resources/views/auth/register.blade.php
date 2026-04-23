@extends('layouts.guest')

@section('content')
    <x-page-header title="Crear cuenta" subtitle="Compra a vendedores locales de Izabal con una cuenta segura." />
    <livewire:auth.register-form />
@endsection
