@extends('layouts.app')

@section('content')
    @php
        $estados = [
            'certificado' => ['Válida', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'anulado' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200'],
            'rechazado' => ['Rechazada por SAT', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'borrador' => ['En proceso', 'bg-amber-50 text-amber-800 ring-amber-200'],
        ];
        $felActivo = (bool) ($perfil?->fel_activo ?? false);
        $hayFiltros = array_filter($filtros) !== [];
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Facturas</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Tu historial de facturas (FEL) por cada venta: las que se emiten solas y las que registras tú desde la SAT. Descarga el resumen de cada mes para tu declaración.</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white px-4 py-2 text-sm">
                <span class="text-atlantia-ink/55">Este mes:</span>
                <strong class="text-atlantia-ink">{{ (int) ($delMes->n ?? 0) }} {{ (int) ($delMes->n ?? 0) === 1 ? 'factura' : 'facturas' }}</strong>
                <span class="text-atlantia-ink/55">· Q{{ number_format((float) ($delMes->total ?? 0), 2) }}</span>
            </div>
        </div>

        @php
            $modoTienda = $perfil?->modoFacturacion() ?? 'manual_sat';
            $meses = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        @endphp

        {{-- Resumen del mes para la declaración --}}
        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4" data-resumen-mes>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-black text-atlantia-ink">Resumen del mes</h2>
                    <p class="text-xs text-atlantia-ink/55">Tus facturas válidas del mes, listas para tu declaración ante la SAT o para tu contador.</p>
                </div>
                <form method="GET" action="{{ route('vendedor.dte.index') }}" class="flex items-end gap-2" data-storefront-filters>
                    <label class="text-xs font-bold text-atlantia-ink/60">Mes
                        <select name="mes" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm text-atlantia-ink">
                            @foreach ($meses as $opcion)
                                <option value="{{ $opcion->format('Y-m') }}" @selected($opcion->format('Y-m') === $mes->format('Y-m'))>{{ ucfirst($opcion->translatedFormat('F Y')) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <noscript><button type="submit" class="h-10 rounded-lg border border-atlantia-rose/25 px-3 text-sm font-bold text-atlantia-wine">Ver</button></noscript>
                    <a href="{{ route('vendedor.dte.exportar', ['mes' => $mes->format('Y-m')]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-exportar-mes>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                        Descargar (Excel)
                    </a>
                </form>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-[#fbf8f7] px-3 py-2"><dt class="text-[11px] font-bold text-atlantia-ink/55">Facturas</dt><dd class="text-xl font-black text-atlantia-ink">{{ (int) ($resumenMes->n ?? 0) }}</dd></div>
                <div class="rounded-lg bg-[#fbf8f7] px-3 py-2"><dt class="text-[11px] font-bold text-atlantia-ink/55">Sin IVA</dt><dd class="text-xl font-black text-atlantia-ink">Q{{ number_format((float) ($resumenMes->neto ?? 0), 2) }}</dd></div>
                <div class="rounded-lg bg-[#fbf8f7] px-3 py-2"><dt class="text-[11px] font-bold text-atlantia-ink/55">IVA</dt><dd class="text-xl font-black text-atlantia-ink">Q{{ number_format((float) ($resumenMes->iva ?? 0), 2) }}</dd></div>
                <div class="rounded-lg bg-atlantia-blush/60 px-3 py-2"><dt class="text-[11px] font-bold text-atlantia-wine/70">Total facturado</dt><dd class="text-xl font-black text-atlantia-wine">Q{{ number_format((float) ($resumenMes->total ?? 0), 2) }}</dd></div>
            </dl>
        </article>

        @if ($sinFactura->isNotEmpty())
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4" data-ventas-sin-factura>
                <h2 class="text-sm font-black text-amber-900">Ventas entregadas sin factura ({{ $sinFactura->count() }}{{ $sinFactura->count() === 8 ? '+' : '' }})</h2>
                <p class="text-xs text-amber-900/80">Emite la factura en la SAT y regístrala en el pedido para que tu cliente la reciba.</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($sinFactura as $venta)
                        <li><a href="{{ route('vendedor.pedidos.show', $venta) }}" class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-200 hover:bg-amber-100">#{{ $venta->numero_pedido }} · Q{{ number_format(max(0, (float) $venta->subtotal - (float) $venta->descuento), 2) }}</a></li>
                    @endforeach
                </ul>
            </article>
        @endif

        @if ($modoTienda === 'manual_sat')
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" data-aviso-manual>
                <strong>Facturas en la SAT.</strong> En cada pedido tienes los datos del cliente para copiarlos y el formulario para registrar tu factura.
                <a href="{{ route('vendedor.perfil-fiscal.edit', ['seccion' => 'fel']) }}" class="font-bold underline">Cambiar forma de facturar</a>
            </div>
        @elseif (! $felActivo)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" data-aviso-fel>
                <strong>Tu factura electrónica automática todavía no está activa.</strong> Mientras tanto puedes emitir en la SAT y registrar la factura en cada pedido.
                Revisa tus datos en <a href="{{ route('vendedor.perfil-fiscal.edit', ['seccion' => 'fel']) }}" class="font-bold underline">Datos de facturación</a>.
            </div>
        @endif

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <form method="GET" action="{{ route('vendedor.dte.index') }}" class="flex flex-wrap items-end gap-2 border-b border-atlantia-rose/10 p-3">
                <label class="text-xs font-bold text-atlantia-ink/60">Estado
                    <select name="estado" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 bg-white px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                        <option value="">Todas</option>
                        @foreach ($estados as $valor => [$texto])
                            <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold text-atlantia-ink/60">Del
                    <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                </label>
                <label class="text-xs font-bold text-atlantia-ink/60">al
                    <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="mt-0.5 block h-10 rounded-lg border border-atlantia-rose/25 px-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine">
                </label>
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Ver</button>
                @if ($hayFiltros)
                    <a href="{{ route('vendedor.dte.index') }}" class="h-10 px-1 text-sm font-bold leading-10 text-atlantia-wine hover:underline">Quitar filtros</a>
                @endif
            </form>

            @if ($dtes->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-base font-black text-atlantia-ink">{{ $hayFiltros ? 'No hay facturas con esos filtros' : 'Aún no tienes facturas' }}</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">{{ $modoTienda === 'manual_sat' ? 'Cuando registres la factura de un pedido aparecerá aquí.' : ($felActivo ? 'Cada venta pagada genera su factura y aparecerá aquí.' : 'Aparecerán aquí cuando emitas tus facturas.') }}</p>
                </div>
            @else
                <div class="hidden grid-cols-[minmax(0,1fr)_8rem_7rem_7rem_9rem] gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2 text-xs font-bold text-atlantia-ink/55 md:grid">
                    <span>Factura</span><span>Fecha</span><span class="text-right">Total</span><span>Estado</span><span class="text-right">Acciones</span>
                </div>
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($dtes as $dte)
                        <li class="grid grid-cols-2 items-center gap-x-3 gap-y-1 px-4 py-3 md:grid-cols-[minmax(0,1fr)_8rem_7rem_7rem_9rem]" data-dte>
                            <div class="col-span-2 min-w-0 md:col-span-1">
                                <a href="{{ route('vendedor.dte.show', $dte) }}" class="font-bold text-atlantia-ink hover:text-atlantia-wine">{{ $dte->serie ? $dte->serie.'-' : '' }}{{ $dte->numero ?? $dte->numero_dte }}</a>
                                <p class="truncate text-xs text-atlantia-ink/50">{{ $dte->tipo_dte ?? 'FACT' }} · pedido #{{ $dte->pedido?->pedidoPadre?->numero_pedido ?? $dte->pedido?->numero_pedido ?? '—' }} · {{ $dte->origen === 'manual' ? 'registrada por ti' : 'automática' }}</p>
                            </div>
                            <p class="text-sm text-atlantia-ink/70">{{ ($dte->fecha_certificacion ?? $dte->created_at)?->format('d/m/Y') }}</p>
                            <p class="text-sm font-bold text-atlantia-ink md:text-right">Q{{ number_format((float) $dte->monto_total, 2) }}</p>
                            <span><span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$dte->estado][1] ?? '' }}">{{ $estados[$dte->estado][0] ?? $dte->estado }}</span></span>
                            <div class="flex justify-end gap-1.5">
                                @if ($dte->pdf_path)
                                    <a href="{{ route('dte.pdf', $dte) }}" target="_blank" rel="noopener" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">PDF</a>
                                @endif
                                <a href="{{ route('vendedor.dte.show', $dte) }}" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold leading-9 text-atlantia-wine hover:bg-atlantia-blush">Ver</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($dtes->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $dtes->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
