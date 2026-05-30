@extends('layouts.app')

@section('content')
    <section class="mx-auto w-full max-w-full pb-24 sm:max-w-lg md:max-w-2xl xl:max-w-4xl">
        <div class="overflow-hidden rounded-none bg-[#fbf7f9] shadow-sm sm:rounded-2xl sm:border sm:border-atlantia-rose/20">

            <header class="bg-atlantia-wine px-4 pb-5 pt-5 text-white sm:px-6 md:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/70">Mis pedidos</p>
                        <h1 class="text-lg font-black leading-tight">Entregas asignadas</h1>
                    </div>
                    <a
                        href="{{ route('repartidor.dashboard') }}"
                        class="rounded-full border border-white/25 bg-white/10 px-4 py-2 text-xs font-black"
                    >
                        Dashboard
                    </a>
                </div>
            </header>

            <div class="px-4 py-5 sm:px-6 md:px-8">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @forelse ($pedidos as $pedido)
                    @php
                        $estado = $pedido->estadoValor();
                        $estadoLabel = match ($estado) {
                            'confirmado'       => ['label' => 'Confirmado',   'class' => 'bg-sky-50 text-sky-700'],
                            'preparando'       => ['label' => 'Preparando',   'class' => 'bg-amber-50 text-amber-700'],
                            'en_ruta'          => ['label' => 'En ruta',      'class' => 'bg-atlantia-wine text-white'],
                            'entregado'        => ['label' => 'Entregado',    'class' => 'bg-emerald-50 text-emerald-700'],
                            'cancelado'        => ['label' => 'Cancelado',    'class' => 'bg-red-50 text-red-700'],
                            default            => ['label' => ucfirst(str_replace('_', ' ', $estado)), 'class' => 'bg-slate-100 text-slate-600'],
                        };
                        $direccion = $pedido->direccion;
                        $ruta      = $pedido->deliveryRoute;
                    @endphp

                    <a
                        href="{{ route('repartidor.pedidos.show', $pedido->uuid) }}"
                        class="mb-3 flex items-center justify-between gap-4 rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm transition hover:border-atlantia-wine hover:shadow-md"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="rounded px-2 py-0.5 text-[11px] font-black {{ $estadoLabel['class'] }}">
                                    {{ $estadoLabel['label'] }}
                                </span>
                                <span class="text-xs text-atlantia-ink/50">{{ $pedido->numero_pedido }}</span>
                            </div>
                            <p class="mt-2 font-black text-atlantia-ink">
                                {{ $direccion?->nombre_contacto ?: $pedido->cliente?->name }}
                            </p>
                            <p class="mt-0.5 truncate text-sm text-atlantia-ink/60">
                                {{ $direccion?->direccion_linea_1 }}
                                @if ($direccion?->municipio)
                                    · {{ $direccion->municipio }}
                                @endif
                            </p>
                            @if ($ruta)
                                <p class="mt-1 text-xs text-atlantia-ink/45">
                                    {{ number_format((float) $ruta->distancia_km, 1) }} km
                                    · {{ $ruta->tiempo_estimado_min ?? '—' }} min est.
                                    @if ($ruta->asignada_at)
                                        · Asignado {{ $ruta->asignada_at->diffForHumans() }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-lg font-black text-atlantia-wine">Q {{ number_format((float) $pedido->total, 2) }}</p>
                            <p class="mt-1 rounded bg-atlantia-blush px-2 py-1 text-[10px] font-black uppercase text-atlantia-ink/60">
                                {{ ucfirst($pedido->metodoPagoValor()) }}
                            </p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-atlantia-rose/30 p-10 text-center">
                        <p class="text-lg font-black text-atlantia-ink">Sin pedidos asignados</p>
                        <p class="mt-2 text-sm text-atlantia-ink/55">
                            Cuando administracion te asigne una entrega aparecera aqui.
                        </p>
                        <a href="{{ route('repartidor.dashboard') }}" class="mt-4 inline-block rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white">
                            Ir al dashboard
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Bottom nav --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-atlantia-rose/15 bg-white px-4 py-2 pb-safe shadow-[0_-8px_24px_rgba(42,16,24,0.08)] xl:hidden">
            <div class="mx-auto grid max-w-2xl grid-cols-4 text-center text-xs font-black sm:text-sm">
                <a href="{{ route('repartidor.dashboard') }}" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Entregas</a>
                <a href="{{ route('repartidor.rutas.index') }}" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Ruta</a>
                <a href="{{ route('repartidor.pedidos.index') }}" class="rounded-lg px-2 py-2.5 text-atlantia-wine">Pedidos</a>
                <a href="#" class="rounded-lg px-2 py-2.5 text-atlantia-ink/55">Perfil</a>
            </div>
        </nav>
    </section>
@endsection
