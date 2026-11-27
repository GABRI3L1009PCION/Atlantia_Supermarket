@extends('layouts.app')

@section('content')
    @php
        $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $estados = [
            'pendiente' => ['Por cobrar', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'facturada' => ['Facturada', 'bg-sky-50 text-sky-700 ring-sky-200'],
            'pagada' => ['Pagada', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'vencida' => ['Vencida', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'anulada' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
        $porcentaje = rtrim(rtrim(number_format((float) $comision->commission_percentage, 2), '0'), '.');
        $factura = $comision->dteComision;
    @endphp

    <section class="mx-auto max-w-[700px] space-y-4 pb-10">
        <a href="{{ route('vendedor.comisiones.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-atlantia-wine hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Comisiones
        </a>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-atlantia-ink">{{ $meses[$comision->mes] ?? $comision->mes }} {{ $comision->anio }}</h1>
                    <p class="text-sm text-atlantia-ink/60">{{ $comision->vendor?->business_name }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$comision->estado][1] ?? '' }}">{{ $estados[$comision->estado][0] ?? $comision->estado }}</span>
            </div>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-atlantia-ink/60">Lo que vendiste ese mes</dt><dd class="font-semibold text-atlantia-ink">Q{{ number_format((float) $comision->total_ventas, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-atlantia-ink/60">Comisión ({{ $porcentaje }} % de tus ventas)</dt><dd class="font-semibold text-atlantia-ink">Q{{ number_format((float) $comision->monto_comision, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-atlantia-ink/60">Mensualidad del plan</dt><dd class="font-semibold text-atlantia-ink">Q{{ number_format((float) $comision->renta_fija, 2) }}</dd></div>
                <div class="flex justify-between border-t border-atlantia-rose/15 pt-2 text-base"><dt class="font-black text-atlantia-ink">Total del mes</dt><dd class="font-black text-atlantia-wine">Q{{ number_format((float) $comision->monto_total, 2) }}</dd></div>
            </dl>

            <dl class="mt-4 grid gap-2 rounded-lg bg-[#fbf8f7] p-3 text-xs sm:grid-cols-3">
                <div><dt class="text-atlantia-ink/50">Emitida</dt><dd class="font-bold text-atlantia-ink">{{ $comision->fecha_emision?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-atlantia-ink/50">Vence</dt><dd class="font-bold text-atlantia-ink">{{ $comision->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-atlantia-ink/50">Pagada</dt><dd class="font-bold text-atlantia-ink">{{ $comision->pagada_at ? \Illuminate\Support\Carbon::parse($comision->pagada_at)->format('d/m/Y') : '—' }}</dd></div>
            </dl>

            @if ($factura && $factura->pdf_path)
                <a href="{{ route('dte.pdf', $factura) }}" target="_blank" rel="noopener" class="mt-4 inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Ver factura de esta comisión</a>
            @endif
        </article>
    </section>
@endsection
