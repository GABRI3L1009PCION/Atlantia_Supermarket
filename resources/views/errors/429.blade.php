@extends('errors.plantilla')

@php
    /* Espera real que indicó el límite (cabecera Retry-After), no un número fijo. */
    $espera = (int) (($exception ?? null)?->getHeaders()['Retry-After'] ?? 10);
    $espera = max(1, min($espera, 3600));

    /*
     * Al terminar: si se intentaba abrir una página (GET), se vuelve a abrir esa misma
     * (por ejemplo el enlace del correo). Si no, la página anterior del sitio.
     */
    $anterior = url()->previous();
    $volverA = request()->isMethod('GET')
        ? request()->fullUrl()
        : (str_starts_with($anterior, url('/')) && $anterior !== url()->current() ? $anterior : route('home'));
@endphp

@section('titulo-pagina', 'Espera un momento')
@section('codigo', '429')
@section('titulo', 'Hiciste muchos intentos seguidos.')
@section('mensaje', 'Por seguridad hay que esperar un momento antes de volver a intentarlo. No perdiste nada: cuando termine el tiempo, la página se vuelve a abrir sola.')

@section('acciones')
    <div class="rounded-2xl border border-[#e8e2df] bg-white p-5">
        <p class="text-sm text-[#5b5052]">Podrás continuar en</p>
        <p class="mt-1 text-3xl font-bold text-[#4d0006]">
            <span
                data-retry-countdown="{{ $espera }}"
                data-retry-key="atlantia-retry-{{ md5($volverA) }}"
                data-retry-redirect="{{ $volverA }}"
            >{{ $espera }} segundos</span>
        </p>
    </div>
    <a href="{{ $volverA }}" class="mt-4 inline-flex rounded-xl border border-[#d8d1ce] bg-white px-5 py-3 text-sm font-bold text-[#4d0006] hover:bg-[#4d0006]/[0.05]">Intentar de nuevo</a>
@endsection
