@extends('layouts.app')

@section('content')
    @php
        $estados = [
            'certificado' => ['Válida', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'anulado' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200'],
            'rechazado' => ['Rechazada por SAT', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'borrador' => ['En proceso', 'bg-amber-50 text-amber-800 ring-amber-200'],
        ];
        $puedeAnular = auth()->user()->can('anular', $dte);
        $receptor = $dte->pedido?->facturacion_nombre ?: ($dte->pedido?->cliente?->name ?? 'Consumidor final');
        $nit = $dte->pedido?->facturacion_nit ?: 'CF';
    @endphp

    <section class="mx-auto max-w-[800px] space-y-4 pb-10">
        <a href="{{ route('vendedor.dte.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-atlantia-wine hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Facturas
        </a>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-atlantia-ink">Factura {{ $dte->serie ? $dte->serie.'-' : '' }}{{ $dte->numero ?? $dte->numero_dte }}</h1>
                    <p class="text-sm text-atlantia-ink/60">{{ ($dte->fecha_certificacion ?? $dte->created_at)?->format('d/m/Y h:i a') }} · pedido #{{ $dte->pedido?->numero_pedido ?? '—' }}</p>
                    @if ($dte->uuid_sat)
                        <p class="mt-0.5 font-mono text-[11px] text-atlantia-ink/45">Autorización SAT: {{ $dte->uuid_sat }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$dte->estado][1] ?? '' }}">{{ $estados[$dte->estado][0] ?? $dte->estado }}</span>
                    @if ($dte->pdf_path)
                        <a href="{{ route('dte.pdf', $dte) }}" target="_blank" rel="noopener" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">Ver PDF</a>
                        <a href="{{ route('dte.pdf', ['dte' => $dte, 'download' => 1]) }}" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">Descargar</a>
                    @endif
                </div>
            </div>

            <p class="mt-3 text-sm text-atlantia-ink/70">Para: <strong class="text-atlantia-ink">{{ $receptor }}</strong> · NIT {{ $nit }}</p>

            <ul class="mt-3 divide-y divide-atlantia-rose/10 rounded-lg border border-atlantia-rose/10">
                @forelse ($dte->items as $item)
                    <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                        <span class="min-w-0 truncate text-atlantia-ink">{{ (float) ($item->cantidad ?? 1) }} × {{ $item->descripcion ?? $item->producto?->nombre ?? 'Producto' }}</span>
                        <span class="shrink-0 font-semibold text-atlantia-ink">Q{{ number_format((float) ($item->monto_total ?? 0), 2) }}</span>
                    </li>
                @empty
                    <li class="px-3 py-2 text-sm text-atlantia-ink/55">Sin detalle de productos.</li>
                @endforelse
            </ul>

            <dl class="mt-3 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-atlantia-ink/60">Sin IVA</dt><dd class="text-atlantia-ink">Q{{ number_format((float) $dte->monto_neto, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-atlantia-ink/60">IVA</dt><dd class="text-atlantia-ink">Q{{ number_format((float) $dte->monto_iva, 2) }}</dd></div>
                <div class="flex justify-between border-t border-atlantia-rose/15 pt-1 text-base"><dt class="font-black text-atlantia-ink">Total</dt><dd class="font-black text-atlantia-wine">Q{{ number_format((float) $dte->monto_total, 2) }}</dd></div>
            </dl>
        </article>

        @if ($dte->anulacion)
            <article class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-black">Anulación {{ $dte->anulacion->estado === 'certificado' || $dte->estado === 'anulado' ? 'registrada' : 'en proceso' }}</p>
                <p class="mt-0.5">{{ $dte->anulacion->motivo }}</p>
                <p class="mt-0.5 text-xs">{{ $dte->anulacion->fecha_anulacion ? \Illuminate\Support\Carbon::parse($dte->anulacion->fecha_anulacion)->format('d/m/Y h:i a') : '' }}{{ $dte->anulacion->usuario ? ' · '.$dte->anulacion->usuario->name : '' }}</p>
            </article>
        @elseif ($puedeAnular)
            <details class="rounded-xl border border-rose-200 bg-white" @if ($errors->has('motivo')) open @endif>
                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-bold text-rose-700 [&::-webkit-details-marker]:hidden">Anular esta factura</summary>
                <form method="POST" action="{{ route('vendedor.dte.anular', $dte) }}" class="space-y-2 border-t border-rose-100 px-4 py-3" data-confirm="¿Anular la factura {{ $dte->numero ?? $dte->numero_dte }}? Esto se informa a SAT y no se puede deshacer.">
                    @csrf
                    <p class="text-xs text-atlantia-ink/60">Úsalo solo si la factura tiene un error (monto, datos del cliente). La anulación se informa a SAT y no se puede deshacer.</p>
                    <textarea name="motivo" rows="2" minlength="10" maxlength="500" required placeholder="Motivo, ej. El NIT del cliente estaba equivocado" class="w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('motivo') }}</textarea>
                    @error('motivo') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    <div class="flex justify-end"><button type="submit" class="h-9 rounded-lg bg-rose-700 px-4 text-xs font-bold text-white hover:bg-rose-800">Anular factura</button></div>
                </form>
            </details>
        @endif
    </section>
@endsection
