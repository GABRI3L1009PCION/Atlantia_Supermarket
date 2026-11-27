@extends('errors.plantilla')

@section('titulo-pagina', 'Problema interno')
@section('codigo', '500')
@section('titulo', 'Tuvimos un problema de nuestro lado.')
@section('mensaje', 'No es culpa tuya. Ya quedó registrado para revisarlo. Intenta de nuevo en unos minutos.')

@section('acciones')
    <a href="{{ url('/') }}" class="inline-flex rounded-xl bg-[#4d0006] px-5 py-3 text-sm font-bold text-white hover:bg-[#3a0004]">Volver al inicio</a>
@endsection
