@extends('errors.plantilla')

@section('titulo-pagina', 'Sin permiso')
@section('codigo', '403')
@section('titulo', 'No tienes permiso para entrar aquí.')
@section('mensaje', 'Esta sección es para otro tipo de cuenta o necesita permisos adicionales. Si crees que es un error, avísale al administrador.')

@section('acciones')
    <a href="{{ route('home') }}" class="inline-flex rounded-xl bg-[#4d0006] px-5 py-3 text-sm font-bold text-white hover:bg-[#3a0004]">Volver al inicio</a>
@endsection
