@extends('errors.plantilla')

@section('titulo-pagina', 'Página no encontrada')
@section('codigo', '404')
@section('titulo', 'No encontramos la página que buscas.')
@section('mensaje', 'Puede que el enlace haya cambiado o que la página ya no exista. Busca lo que necesitas o vuelve al inicio.')

@section('acciones')
    <form method="GET" action="{{ route('catalogo.index') }}" class="flex gap-2">
        <label for="buscar-producto" class="sr-only">Buscar en Atlantia</label>
        <input id="buscar-producto" type="search" name="q" placeholder="Buscar productos o tiendas..." class="min-w-0 flex-1 rounded-xl border border-[#d8d1ce] bg-white px-4 py-3 text-sm focus:border-[#4d0006] focus:outline-none focus:ring-2 focus:ring-[#4d0006]/15">
        <button type="submit" class="rounded-xl bg-[#4d0006] px-5 py-3 text-sm font-bold text-white hover:bg-[#3a0004]">Buscar</button>
    </form>
    <a href="{{ route('home') }}" class="mt-4 inline-flex text-sm font-bold text-[#4d0006] hover:underline">← Volver al inicio</a>
@endsection
