@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Tiendas online</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Entregas externas</h1>
            </div>
            <a href="{{ route('repartidor.dashboard') }}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Dashboard</a>
        </header>

        <div class="grid gap-3">
            @forelse ($orders as $order)
                <a href="{{ route('repartidor.externas.show', $order) }}" class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm transition hover:border-atlantia-wine">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-atlantia-blush px-2 py-1 text-xs font-black text-atlantia-wine">{{ $order->status }}</span>
                                <span class="text-xs text-atlantia-ink/45">{{ $order->external_reference }}</span>
                            </div>
                            <h2 class="mt-2 text-lg font-black text-atlantia-ink">{{ $order->store_name }}</h2>
                            <p class="truncate text-sm text-atlantia-ink/60">{{ $order->pickup_address }}</p>
                            <p class="truncate text-sm text-atlantia-ink/60">{{ $order->delivery_address }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center md:min-w-80">
                            <div class="rounded-md bg-slate-50 p-2">
                                <p class="text-xs text-atlantia-ink/50">KM</p>
                                <p class="font-black text-atlantia-ink">{{ number_format((float) $order->estimated_distance_km, 1) }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-2">
                                <p class="text-xs text-atlantia-ink/50">Cobrar</p>
                                <p class="font-black text-atlantia-ink">Q {{ number_format((float) $order->amount_to_collect, 2) }}</p>
                            </div>
                            <div class="rounded-md bg-emerald-50 p-2">
                                <p class="text-xs text-emerald-700/70">Ganas</p>
                                <p class="font-black text-emerald-700">Q {{ number_format((float) $order->courier_earning, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-atlantia-rose/25 bg-white p-10 text-center">
                    <p class="text-lg font-black text-atlantia-ink">Sin entregas externas asignadas</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">Las solicitudes de tiendas online apareceran aqui.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
