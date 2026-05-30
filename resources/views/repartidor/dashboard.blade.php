@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $overview = $metrics['overview'];
        $rutaActual = $metrics['ruta_actual'];
        $pedidoActual = $rutaActual?->pedido;
        $proximas = $metrics['proximas_entregas']->reject(fn ($ruta) => $rutaActual && $ruta->id === $rutaActual->id);
        $direccion = $pedidoActual?->direccion;
        $telefono = $direccion?->telefono_contacto;
        $telefonoWhatsApp = $telefono ? preg_replace('/\D+/', '', $telefono) : null;
        $items = $pedidoActual?->items ?? collect();
        $entregasHoy = (int) ($overview['entregas_hoy'] ?? 0);
        $gananciaEstimada = $entregasHoy * 25;
        $kmActuales = (float) ($rutaActual?->distancia_km ?? 0);
        $totalPedido = (float) ($pedidoActual?->total ?? 0);
        $saludo = now()->hour < 12 ? 'Buenos dias' : (now()->hour < 18 ? 'Buenas tardes' : 'Buenas noches');
        $initials = collect(preg_split('/\s+/', trim($user->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('') ?: 'R';
        $accepted = $rutaActual?->aceptada_at !== null;
        $readyToPickup = $pedidoActual?->estadoValor() === 'listo_para_entrega';
        $inRoute = $pedidoActual?->estadoValor() === 'en_ruta';
    @endphp

    <section class="repartidor-dashboard -mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-[#fbf7f9] px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-3">
            <article class="overflow-hidden rounded-lg bg-gradient-to-br from-[#941846] via-[#83163f] to-[#651030] p-4 text-white shadow-[0_14px_32px_rgba(86,15,44,0.22)]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white text-atlantia-wine shadow-lg">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3V5M12 19V21M5 12H3M21 12H19M6.3 6.3L4.9 4.9M19.1 19.1L17.7 17.7M17.7 6.3L19.1 4.9M4.9 19.1L6.3 17.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base font-black leading-tight">{{ $saludo }}</p>
                            <h1 class="text-2xl font-black leading-tight">{{ $user->name }}</h1>
                        </div>
                    </div>

                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-white/22 bg-white/10 px-4 py-1.5 text-xs font-black">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        En linea
                    </span>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-3">
                    <div class="rounded-lg border border-white/16 bg-white/10 p-3 backdrop-blur">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white/12">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 7V5C7 4 8 3 9 3H15C16 3 17 4 17 5V7M5 7H19C20.1 7 21 7.9 21 9V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V9C3 7.9 3.9 7 5 7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M9 12H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-white/78">Entregas hoy</p>
                                <p class="text-3xl font-black leading-none">{{ number_format($entregasHoy) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-white/16 bg-white/10 p-3 backdrop-blur">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white/12">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 2V22M17 6.5C16.2 5.6 14.7 5 12.8 5C10.3 5 8.5 6.2 8.5 8.1C8.5 12.2 17.5 10 17.5 15.8C17.5 17.7 15.7 19 12.8 19C10.8 19 9.1 18.4 7.9 17.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-white/78">Ganancia estimada</p>
                                <p class="text-3xl font-black leading-none">Q {{ number_format($gananciaEstimada, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-white/16 bg-white/10 p-3 backdrop-blur">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white/12">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 21S19 14.8 19 8.8C19 4.9 15.9 2 12 2S5 4.9 5 8.8C5 14.8 12 21 12 21Z" stroke="currentColor" stroke-width="1.8"/>
                                    <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-white/78">KM actuales</p>
                                <p class="text-3xl font-black leading-none">{{ number_format($kmActuales, 1) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-lg bg-gradient-to-r from-[#007d51] to-[#009765] p-3 text-white shadow-[0_10px_24px_rgba(0,112,74,0.18)]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 8H20V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M7 8V6C7 4.9 7.9 4 9 4H15C16.1 4 17 4.9 17 6V8" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M15 14H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold text-white/78">Efectivo acumulado</p>
                            <p class="text-3xl font-black leading-none">Q {{ number_format(0, 2) }}</p>
                        </div>
                    </div>

                    <button type="button" class="inline-flex min-h-9 w-fit items-center justify-center gap-2 rounded-full border border-white/45 px-5 text-xs font-black text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 10L12 4L21 10M5 10V20H19V10M9 20V14H15V20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Depositar
                    </button>
                </div>
            </article>

            <div class="grid gap-4 xl:grid-cols-2">
                <article class="min-h-[230px] rounded-lg border border-atlantia-rose/18 bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 7V5C7 4 8 3 9 3H15C16 3 17 4 17 5V7M5 7H19C20.1 7 21 7.9 21 9V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V9C3 7.9 3.9 7 5 7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-black text-atlantia-ink">Entrega actual</h2>
                            <p class="text-xs text-atlantia-ink/60">
                                {{ $pedidoActual ? 'Pedido activo asignado a tu ruta.' : 'No tienes entregas activas por ahora.' }}
                            </p>
                        </div>
                    </div>

                    @if ($pedidoActual)
                        <div class="mt-3 rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/55 p-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-rose">{{ $pedidoActual->numero_pedido }}</p>
                                    <h3 class="mt-1 text-lg font-black text-atlantia-ink">{{ $pedidoActual->cliente?->name ?? 'Cliente no disponible' }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-atlantia-ink/65">
                                        {{ $direccion?->direccion_linea_1 ?? 'Direccion pendiente' }}
                                        @if ($direccion?->municipio)
                                            , {{ $direccion->municipio }}
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-atlantia-wine ring-1 ring-atlantia-rose/25">
                                    {{ str_replace('_', ' ', $pedidoActual->estadoValor()) }}
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div class="rounded-md bg-white p-2.5">
                                    <p class="text-xs text-atlantia-ink/50">Total</p>
                                    <p class="text-base font-black text-atlantia-wine">Q {{ number_format($totalPedido, 2) }}</p>
                                </div>
                                <div class="rounded-md bg-white p-2.5">
                                    <p class="text-xs text-atlantia-ink/50">Distancia</p>
                                    <p class="text-base font-black text-atlantia-ink">{{ number_format($kmActuales, 1) }} km</p>
                                </div>
                                <div class="rounded-md bg-white p-2.5">
                                    <p class="text-xs text-atlantia-ink/50">Telefono</p>
                                    <p class="truncate text-base font-black text-atlantia-ink">{{ $telefono ?: 'Sin dato' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1.5">
                                @foreach ($items->take(3) as $item)
                                    <div class="flex items-center justify-between gap-3 rounded-md bg-white px-3 py-1.5 text-xs">
                                        <span class="font-semibold text-atlantia-ink">{{ $item->cantidad }}x {{ $item->producto?->nombre }}</span>
                                        <span class="font-black text-atlantia-wine">Q {{ number_format((float) $item->subtotal, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @if (! $accepted)
                                    <form method="POST" action="{{ route('repartidor.pedidos.accept', $pedidoActual) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Aceptar entrega</button>
                                    </form>
                                @elseif ($readyToPickup)
                                    <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedidoActual) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Marcar recogido</button>
                                    </form>
                                @elseif ($inRoute)
                                    <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedidoActual) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Marcar entregado</button>
                                    </form>
                                @else
                                    <button type="button" disabled class="w-full rounded-md bg-slate-200 px-4 py-2.5 text-sm font-black text-slate-500">Esperando preparacion</button>
                                @endif

                                <a
                                    href="{{ $direccion?->latitude && $direccion?->longitude ? 'https://www.google.com/maps/dir/?api=1&destination=' . $direccion->latitude . ',' . $direccion->longitude : route('repartidor.pedidos.show', $pedidoActual) }}"
                                    target="{{ $direccion?->latitude && $direccion?->longitude ? '_blank' : '_self' }}"
                                    class="inline-flex min-h-10 items-center justify-center rounded-md border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine"
                                >
                                    Ver ruta
                                </a>
                            </div>

                            @if ($telefonoWhatsApp)
                                <a href="https://wa.me/502{{ $telefonoWhatsApp }}" target="_blank" class="mt-3 inline-flex text-sm font-black text-emerald-700">
                                    Contactar cliente por WhatsApp
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="grid min-h-[165px] place-items-center text-center">
                            <div>
                                <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                    <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 16H16L18 12H21V16H19M6 16A2 2 0 1 0 6 20A2 2 0 0 0 6 16ZM17 16A2 2 0 1 0 17 20A2 2 0 0 0 17 16Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 16V9H13V16M7 12H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        <path d="M14 6L16 8L20 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <h3 class="mt-3 text-lg font-black text-atlantia-ink">Estas libre por ahora.</h3>
                                <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-atlantia-ink/60">
                                    Cuando administracion te asigne una entrega aparecera aqui y recibiras una notificacion interna.
                                </p>
                            </div>
                        </div>
                    @endif
                </article>

                <article class="min-h-[230px] rounded-lg border border-atlantia-rose/18 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4V7M17 4V7M4.5 10H19.5M6 6H18C19.1 6 20 6.9 20 8V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V8C4 6.9 4.9 6 6 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2 class="text-xl font-black text-atlantia-ink">Siguientes entregas</h2>
                        </div>
                        <span class="text-sm font-black text-atlantia-wine">{{ $proximas->count() }} pedidos</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($proximas as $ruta)
                            <a href="{{ route('repartidor.pedidos.show', $ruta->pedido) }}" class="block rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/45 p-3 transition hover:border-atlantia-wine">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-atlantia-ink">{{ $ruta->pedido?->numero_pedido }}</p>
                                        <p class="truncate text-sm text-atlantia-ink/60">{{ $ruta->pedido?->cliente?->name ?? 'Cliente no disponible' }}</p>
                                        <p class="mt-1 truncate text-xs text-atlantia-ink/50">
                                            {{ $ruta->pedido?->direccion?->municipio ?? 'Sin municipio' }} · {{ number_format((float) $ruta->distancia_km, 1) }} km
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-md bg-white px-3 py-2 text-sm font-black text-atlantia-wine">
                                        Q {{ number_format((float) $ruta->pedido?->total, 2) }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="grid min-h-[165px] place-items-center text-center">
                                <div>
                                    <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine/55">
                                        <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 4H15L16 7H8L9 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M6 7H18C19.1 7 20 7.9 20 9V20H4V9C4 7.9 4.9 7 6 7Z" stroke="currentColor" stroke-width="1.8"/>
                                            <path d="M8 12H16M8 16H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <h3 class="mt-3 text-lg font-black text-atlantia-ink">No hay mas entregas pendientes en tu cola.</h3>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
