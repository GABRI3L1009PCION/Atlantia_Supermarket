@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl space-y-4">
        <header class="rounded-lg bg-atlantia-wine p-4 text-white">
            <a href="{{ route('admin.entregas-externas.index') }}" class="text-xs font-black text-white/70">Entregas externas</a>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black">{{ $order->external_reference }}</h1>
                    <p class="text-sm text-white/70">{{ $order->store_name }} · {{ $order->status }}</p>
                </div>
                <span class="rounded-md bg-white px-3 py-2 text-sm font-black text-atlantia-wine">Q {{ number_format((float) $order->delivery_fee, 2) }}</span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[1fr_0.8fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Ruta</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-xs font-black uppercase text-atlantia-ink/45">Recogida</p>
                        <p class="mt-1 font-black text-atlantia-ink">{{ $order->store_name }}</p>
                        <p class="text-sm text-atlantia-ink/60">{{ $order->pickup_address }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-xs font-black uppercase text-atlantia-ink/45">Entrega</p>
                        <p class="mt-1 font-black text-atlantia-ink">{{ $order->customer_name }}</p>
                        <p class="text-sm text-atlantia-ink/60">{{ $order->delivery_address }}</p>
                    </div>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-4">
                    <div class="rounded-md bg-atlantia-blush/60 p-3"><p class="text-xs text-atlantia-ink/55">KM</p><p class="font-black text-atlantia-ink">{{ number_format((float) $order->estimated_distance_km, 1) }}</p></div>
                    <div class="rounded-md bg-atlantia-blush/60 p-3"><p class="text-xs text-atlantia-ink/55">Min</p><p class="font-black text-atlantia-ink">{{ $order->estimated_time_min }}</p></div>
                    <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Repartidor</p><p class="font-black text-emerald-700">{{ $order->repartidor?->name ?? 'Sin asignar' }}</p></div>
                    <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Gana</p><p class="font-black text-emerald-700">Q {{ number_format((float) $order->courier_earning, 2) }}</p></div>
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Asignar</h2>
                <form method="POST" action="{{ route('admin.entregas-externas.assign', $order) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-bold text-atlantia-ink">Repartidor
                        <select name="repartidor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required>
                            <option value="">Seleccionar</option>
                            @foreach ($repartidores as $repartidor)
                                <option value="{{ $repartidor->id }}" @selected((int) $order->repartidor_id === (int) $repartidor->id)>{{ $repartidor->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-atlantia-ink">Ganancia ofertada
                        <input name="estimated_gain" type="number" min="0" step="0.01" value="{{ $order->courier_earning }}" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                    </label>
                    <button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Enviar oferta</button>
                </form>
            </article>
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Ofertas</h2>
            <div class="mt-3 divide-y divide-atlantia-rose/10">
                @forelse ($order->offers as $offer)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="font-black text-atlantia-ink">{{ $offer->repartidor?->name ?? 'Repartidor' }}</p>
                            <p class="text-sm text-atlantia-ink/55">{{ $offer->status }} · vence {{ $offer->expires_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="font-black text-emerald-700">Q {{ number_format((float) $offer->estimated_gain, 2) }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin ofertas emitidas.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
