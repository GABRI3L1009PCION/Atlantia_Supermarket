@extends('errors.plantilla')

@php
    $anterior = url()->previous();
    $volverA = str_starts_with($anterior, url('/')) && $anterior !== url()->current() ? $anterior : route('home');
@endphp

@section('titulo-pagina', 'Página vencida')
@section('codigo', '419')
@section('titulo', 'La página estuvo abierta mucho tiempo.')
@section('mensaje', 'Por seguridad la vencimos. Vuelve a abrirla e inténtalo de nuevo; si ya habías iniciado sesión, quizá tengas que entrar otra vez.')

@section('acciones')
    <div class="flex flex-wrap gap-3">
        <a href="{{ $volverA }}" class="inline-flex rounded-xl bg-[#4d0006] px-5 py-3 text-sm font-bold text-white hover:bg-[#3a0004]">Volver a intentarlo</a>
        <a href="{{ route('login') }}" class="inline-flex rounded-xl border border-[#d8d1ce] bg-white px-5 py-3 text-sm font-bold text-[#4d0006] hover:bg-[#4d0006]/[0.05]">Iniciar sesión</a>
    </div>
@endsection
