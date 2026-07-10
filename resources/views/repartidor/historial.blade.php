@extends('layouts.app')

@section('content')
    @php
        $money = static fn ($value): string => 'Q ' . number_format((float) $value, 2);
        $deliveryDate = static function ($date): string {
            if (! $date) {
                return 'Fecha pendiente';
            }

            if ($date->isToday()) {
                return 'Hoy, ' . $date->format('g:i a') . '.';
            }

            if ($date->isYesterday()) {
                return 'Ayer, ' . $date->format('g:i a') . '.';
            }

            return $date->format('d/m/Y, g:i a') . '.';
        };
        $metricStatus = static fn ($value, $excellent = 90): string => (float) $value >= $excellent ? 'Excelente' : 'Mejorando';
    @endphp

    <section class="-mx-4 -my-6 min-h-screen bg-[#fbf7f9] pb-28 text-atlantia-ink md:hidden">
        <header class="bg-atlantia-wine px-5 pb-5 pt-5 text-white">
            <div class="grid min-h-12 grid-cols-[2.75rem_1fr_2.75rem] items-center gap-3">
                <a href="{{ route('repartidor.dashboard') }}" class="grid h-11 w-11 place-items-center rounded-full text-white active:scale-95" aria-label="Volver">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 5 8 12l7 7M9 12h11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 class="truncate text-center text-xl font-black">Historial y soporte</h1>
                <a href="{{ route('repartidor.soporte.index') }}" class="relative grid h-11 w-11 place-items-center rounded-full text-white active:scale-95" aria-label="Notificaciones">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-2 7-2 9h16c0-2-2-2-2-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="absolute right-2 top-1.5 h-3 w-3 rounded-full bg-red-500 ring-2 ring-atlantia-wine"></span>
                </a>
            </div>
        </header>

        <div class="space-y-5 px-4 pt-5">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-3 gap-3">
                <article class="rounded-2xl bg-white px-3 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <p class="text-xs font-black leading-tight text-atlantia-ink">Tasa de aceptacion</p>
                    <p class="mt-4 text-3xl font-black tracking-normal">{{ number_format((float) $profile->acceptance_rate, 0) }}%</p>
                    <p class="mt-2 flex items-center justify-center gap-1.5 text-xs font-bold text-atlantia-ink/70">
                        <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                        {{ $metricStatus($profile->acceptance_rate) }}
                    </p>
                </article>

                <article class="rounded-2xl bg-white px-3 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <p class="text-xs font-black leading-tight text-atlantia-ink">Tasa de finalizacion</p>
                    <p class="mt-4 text-3xl font-black tracking-normal">{{ number_format((float) $profile->completion_rate, 0) }}%</p>
                    <p class="mt-2 flex items-center justify-center gap-1.5 text-xs font-bold text-atlantia-ink/70">
                        <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                        {{ $metricStatus($profile->completion_rate) }}
                    </p>
                </article>

                <article class="rounded-2xl bg-white px-3 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                    <p class="text-xs font-black leading-tight text-atlantia-ink">Calificacion</p>
                    <p class="mt-4 text-3xl font-black tracking-normal">{{ number_format((float) $profile->rating, 1) }}<span class="text-atlantia-wine">*</span></p>
                    <p class="mt-2 flex items-center justify-center gap-1.5 text-xs font-bold text-atlantia-ink/70">
                        <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                        {{ $metricStatus($profile->rating, 4.5) }}
                    </p>
                </article>
            </div>

            <article>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Entregas completadas</h2>
                    <a href="{{ route('repartidor.historial.index') }}" class="text-sm font-black text-atlantia-wine">Ver todo</a>
                </div>

                <div class="divide-y divide-atlantia-rose/10 rounded-2xl bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                    @forelse ($completedDeliveries as $delivery)
                        @php
                            $initial = strtoupper(substr(trim((string) $delivery['customer']), 0, 1)) ?: 'C';
                        @endphp
                        <a href="{{ $delivery['url'] }}" class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-atlantia-blush text-base font-black text-atlantia-wine">
                                    {{ $initial }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-base font-black text-atlantia-ink">{{ $delivery['title'] }}</span>
                                    <span class="mt-0.5 block text-sm font-bold text-atlantia-ink/55">{{ $deliveryDate($delivery['date']) }}</span>
                                </span>
                            </div>
                            <span class="shrink-0 text-right">
                                <span class="block text-base font-black text-atlantia-ink">{{ $money($delivery['amount']) }}</span>
                                <span class="mt-1 block text-sm font-black text-emerald-700">{{ $delivery['payment'] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Todavia no tienes entregas completadas.</p>
                    @endforelse
                </div>
            </article>

            <article>
                <h2 class="mb-3 text-lg font-black text-atlantia-ink">Necesitas ayuda?</h2>
                <div class="divide-y divide-atlantia-rose/10 rounded-2xl bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                    <a href="{{ route('repartidor.soporte.index') }}" class="flex items-center justify-between gap-3 py-3 first:pt-0">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-atlantia-rose/20 text-atlantia-wine">
                                <span class="text-2xl font-black leading-none">?</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-base font-black text-atlantia-ink">Centro de ayuda</span>
                                <span class="block text-sm font-bold text-atlantia-ink/55">Preguntas frecuentes</span>
                            </span>
                        </span>
                        <svg class="h-6 w-6 shrink-0 text-atlantia-ink" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('repartidor.soporte.index') }}" class="flex items-center justify-between gap-3 py-3">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-atlantia-ink">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 13v-1a8 8 0 0 1 16 0v1M6 13H4v4h2v-4ZM20 13h-2v4h2v-4ZM18 17a4 4 0 0 1-4 4h-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-base font-black text-atlantia-ink">Contactar soporte</span>
                                <span class="block text-sm font-bold text-atlantia-ink/55">Estamos para ayudarte</span>
                            </span>
                        </span>
                        <svg class="h-6 w-6 shrink-0 text-atlantia-ink" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('repartidor.soporte.index') }}" class="flex items-center justify-between gap-3 py-3 last:pb-0">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-atlantia-ink">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 5h14v10H8l-3 3V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M9 10h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-base font-black text-atlantia-ink">Reportar un problema</span>
                                <span class="block text-sm font-bold text-atlantia-ink/55">Cuentanos que paso</span>
                            </span>
                        </span>
                        <svg class="h-6 w-6 shrink-0 text-atlantia-ink" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>

                <form method="POST" action="{{ route('repartidor.emergencia.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="message" value="Emergencia reportada desde Historial y soporte">
                    <button class="flex min-h-16 w-full items-center justify-between gap-3 rounded-2xl bg-red-50 px-5 text-left text-red-800 shadow-[0_10px_26px_rgba(220,38,38,0.12)]">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full">
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3 22 20H2L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 9v5M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-base font-black">Emergencia</span>
                                <span class="block text-sm font-bold text-red-900/70">Linea directa 24/7</span>
                            </span>
                        </span>
                        <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </article>
        </div>

        <nav class="fixed bottom-0 left-1/2 z-50 grid w-full max-w-md -translate-x-1/2 grid-cols-5 rounded-t-2xl border-t border-atlantia-rose/10 bg-white px-2 pb-2 pt-2 shadow-[0_-10px_34px_rgba(42,16,24,0.16)] md:hidden" aria-label="Navegacion del repartidor">
            <a href="{{ route('repartidor.dashboard') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
                <span>Inicio</span>
            </a>
            <a href="{{ route('repartidor.pedidos.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v3h2v13H5V7h2V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Pedidos</span>
            </a>
            <a href="{{ route('repartidor.ganancias.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7.5h15a1 1 0 0 1 1 1v10a1.5 1.5 0 0 1-1.5 1.5H5.5A1.5 1.5 0 0 1 4 18.5v-11Z" stroke="currentColor" stroke-width="1.8"/><path d="M16 13h4v4h-4a2 2 0 0 1 0-4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Ganancias</span>
            </a>
            <a href="{{ route('repartidor.soporte.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h14v10H8l-3 3V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 10h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Mensajes</span>
            </a>
            <a href="{{ route('repartidor.historial.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-wine">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" fill="currentColor"/></svg>
                <span>Perfil</span>
            </a>
        </nav>
    </section>

    <section class="mx-auto hidden max-w-6xl space-y-4 md:block">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Rendimiento</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Historial de entregas</h1>
            </div>
            <a href="{{ route('repartidor.dashboard') }}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Dashboard</a>
        </header>

        <div class="grid gap-3 md:grid-cols-5">
            <div class="rounded-lg bg-atlantia-wine p-4 text-white">
                <p class="text-xs text-white/70">Nivel</p>
                <p class="mt-1 text-2xl font-black">{{ $profile->reward_level }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Completados</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ number_format($completedCount) }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Cancelados</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ number_format($cancelledCount) }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">KM</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ number_format((float) $kmTotal, 1) }}</p>
            </div>
            <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4">
                <p class="text-xs text-atlantia-ink/55">Calificacion</p>
                <p class="mt-1 text-2xl font-black text-atlantia-ink">{{ number_format((float) $profile->rating, 1) }}</p>
            </div>
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Pedidos internos y emprendedores</h2>
            <div class="mt-3 divide-y divide-atlantia-rose/10">
                @forelse ($routes as $route)
                    <a href="{{ $route->pedido ? route('repartidor.pedidos.show', $route->pedido) : '#' }}" class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-black text-atlantia-ink">{{ $route->pedido?->numero_pedido ?? 'Ruta' }}</p>
                            <p class="truncate text-sm text-atlantia-ink/55">{{ $route->pedido?->cliente?->name ?? 'Cliente' }} - {{ $route->pedido?->direccion?->municipio ?? 'Zona' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-black text-atlantia-wine">{{ ucfirst($route->estado) }}</p>
                            <p class="text-xs text-atlantia-ink/45">{{ number_format((float) $route->distancia_km, 1) }} km</p>
                        </div>
                    </a>
                @empty
                    <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin historial interno.</p>
                @endforelse
            </div>
            <div class="mt-3">{{ $routes->links() }}</div>
        </article>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Tiendas online</h2>
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @forelse ($externalOrders as $order)
                    <a href="{{ route('repartidor.externas.show', $order) }}" class="rounded-lg border border-atlantia-rose/15 p-3 hover:border-atlantia-wine">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate font-black text-atlantia-ink">{{ $order->store_name }}</p>
                                <p class="truncate text-sm text-atlantia-ink/55">{{ $order->customer_name }}</p>
                            </div>
                            <span class="rounded-md bg-slate-100 px-3 py-1 text-xs font-black text-atlantia-ink">{{ $order->status }}</span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-lg border border-dashed border-atlantia-rose/25 p-6 text-center text-sm font-bold text-atlantia-ink/55">Sin entregas externas.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
