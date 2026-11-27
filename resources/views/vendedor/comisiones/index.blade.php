@extends('layouts.app')

@section('content')
    @php
        $vendor = auth()->user()->vendor;
        $planes = app(\App\Services\Monetizacion\PlanesVendedorService::class);
        $claveActual = $planes->planActual($vendor);
        $plan = \App\Services\Monetizacion\PlanesVendedorService::todos()[$claveActual] ?? null;
        $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $estados = [
            'pendiente' => ['Por cobrar', 'bg-amber-50 text-amber-800 ring-amber-200'],
            'facturada' => ['Facturada', 'bg-sky-50 text-sky-700 ring-sky-200'],
            'pagada' => ['Pagada', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'vencida' => ['Vencida', 'bg-rose-50 text-rose-700 ring-rose-200'],
            'anulada' => ['Anulada', 'bg-slate-100 text-slate-600 ring-slate-200'],
        ];
        $pendiente = $comisiones->getCollection()->whereIn('estado', ['pendiente', 'facturada', 'vencida'])->sum(fn ($c) => (float) $c->monto_total);
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Comisiones</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Lo que Atlantia te cobra cada mes según tu plan: la comisión por tus ventas y la mensualidad, si tu plan la tiene.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Tu plan</p>
                <p class="mt-0.5 text-lg font-black text-atlantia-ink">{{ $plan['nombre'] ?? ucfirst($claveActual) }}</p>
                <p class="text-xs text-atlantia-ink/55">
                    {{ ($plan['comision'] ?? 0) > 0 ? $plan['comision'].' % por cada venta' : 'Sin comisión por venta' }}{{ ($plan['precio'] ?? 0) > 0 ? ' · Q'.(fmod((float) $plan['precio'], 1.0) == 0.0 ? number_format((float) $plan['precio'], 0) : number_format((float) $plan['precio'], 2)).' al mes' : '' }}
                </p>
                <a href="{{ route('vendedor.plan.index') }}" class="mt-1 inline-block text-xs font-bold text-atlantia-wine hover:underline">Ver o cambiar plan</a>
            </div>
            <div @class(['rounded-xl border p-4', 'border-amber-200 bg-amber-50' => $pendiente > 0, 'border-atlantia-rose/15 bg-white' => $pendiente <= 0])>
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Pendiente</p>
                <p class="mt-0.5 text-lg font-black {{ $pendiente > 0 ? 'text-amber-800' : 'text-atlantia-ink' }}">Q{{ number_format($pendiente, 2) }}</p>
                <p class="text-xs text-atlantia-ink/55">{{ $pendiente > 0 ? 'meses aún sin pagar' : 'estás al día' }}</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Cómo se cobra</p>
                <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">La comisión de cada venta se descuenta cuando te pagamos (lo ves en <a href="{{ route('vendedor.pagos.index') }}" class="font-bold text-atlantia-wine hover:underline">Ventas y pagos</a>). Aquí está el resumen por mes.</p>
            </div>
        </div>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            @if ($comisiones->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-base font-black text-atlantia-ink">Aún no hay meses cerrados</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Al terminar cada mes verás aquí cuánto vendiste y cuánto fue la comisión.</p>
                </div>
            @else
                <div class="hidden grid-cols-[minmax(0,1fr)_7rem_6rem_7rem_7rem_8rem] gap-3 border-b border-atlantia-rose/10 bg-[#fbf8f7] px-4 py-2 text-xs font-bold text-atlantia-ink/55 md:grid">
                    <span>Mes</span><span class="text-right">Vendiste</span><span class="text-right">Comisión</span><span class="text-right">Mensualidad</span><span class="text-right">Total</span><span class="text-right">Estado</span>
                </div>
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($comisiones as $comision)
                        <li>
                            <a href="{{ route('vendedor.comisiones.show', $comision) }}" class="grid grid-cols-2 items-center gap-x-3 gap-y-1 px-4 py-3 hover:bg-[#fdfafa] md:grid-cols-[minmax(0,1fr)_7rem_6rem_7rem_7rem_8rem]" data-comision>
                                <span class="col-span-2 font-bold text-atlantia-ink md:col-span-1">{{ $meses[$comision->mes] ?? $comision->mes }} {{ $comision->anio }}</span>
                                <span class="text-sm text-atlantia-ink/70 md:text-right"><span class="text-xs text-atlantia-ink/45 md:hidden">Vendiste </span>Q{{ number_format((float) $comision->total_ventas, 2) }}</span>
                                <span class="text-sm text-atlantia-ink/70 md:text-right"><span class="text-xs text-atlantia-ink/45 md:hidden">Comisión </span>Q{{ number_format((float) $comision->monto_comision, 2) }} <span class="text-xs text-atlantia-ink/40">({{ rtrim(rtrim(number_format((float) $comision->commission_percentage, 2), '0'), '.') }} %)</span></span>
                                <span class="text-sm text-atlantia-ink/70 md:text-right"><span class="text-xs text-atlantia-ink/45 md:hidden">Mensualidad </span>Q{{ number_format((float) $comision->renta_fija, 2) }}</span>
                                <span class="text-sm font-black text-atlantia-ink md:text-right"><span class="text-xs font-normal text-atlantia-ink/45 md:hidden">Total </span>Q{{ number_format((float) $comision->monto_total, 2) }}</span>
                                <span class="md:text-right"><span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $estados[$comision->estado][1] ?? '' }}">{{ $estados[$comision->estado][0] ?? $comision->estado }}</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($comisiones->hasPages())
                    <div class="border-t border-atlantia-rose/10 px-4 py-3">{{ $comisiones->links() }}</div>
                @endif
            @endif
        </article>
    </section>
@endsection
