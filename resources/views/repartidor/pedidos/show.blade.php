@extends('layouts.app')

@section('content')
    @php
        $estado = $pedido->estadoValor();
        $ruta = $pedido->deliveryRoute;
        $direccion = $pedido->direccion;
        $accepted = $ruta?->aceptada_at !== null;
        $ready = $estado === 'listo_para_entrega';
        $enRuta = $estado === 'en_ruta';
        $entregado = $estado === 'entregado';
        $cancelado = $estado === 'cancelado';
        $pickupMaps = $ruta?->pickup_latitude && $ruta?->pickup_longitude
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $ruta->pickup_latitude . ',' . $ruta->pickup_longitude
            : null;
        $deliveryMaps = $direccion?->latitude && $direccion?->longitude
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $direccion->latitude . ',' . $direccion->longitude
            : null;
        $pickupWaze = $ruta?->pickup_latitude && $ruta?->pickup_longitude
            ? 'https://waze.com/ul?ll=' . $ruta->pickup_latitude . ',' . $ruta->pickup_longitude . '&navigate=yes'
            : null;
        $deliveryWaze = $direccion?->latitude && $direccion?->longitude
            ? 'https://waze.com/ul?ll=' . $direccion->latitude . ',' . $direccion->longitude . '&navigate=yes'
            : null;
        $telefono = $direccion?->telefono_contacto;
        $whatsapp = $telefono ? preg_replace('/\D+/', '', $telefono) : null;
        $pickupName = $ruta?->pickup_name ?? $pedido->vendor?->business_name ?? 'Atlantia Supermarket';
        $pickupAddress = $ruta?->pickup_address ?? $pedido->vendor?->direccion_comercial ?? $pedido->vendor?->municipio ?? 'Centro operativo Atlantia';
        $pickupZone = $pedido->vendor?->municipio ?? $ruta?->pickup_notes ?? 'Punto de recogida';
        $pickupLogo = strtoupper(substr(trim((string) $pickupName), 0, 1)) ?: 'A';
        $orderDigits = preg_replace('/\D+/', '', (string) $pedido->numero_pedido);
        $shortOrder = $orderDigits !== '' ? substr($orderDigits, -4) : (string) $pedido->id;
        $canArrivePickup = $accepted && ! $ruta?->arrived_pickup_at && ! $ruta?->picked_up_at;
        $canPickup = $accepted && $ready && ! $ruta?->picked_up_at;
        $canReportNotReady = $accepted && ! $ruta?->picked_up_at;
        $mobileTitle = $ruta?->picked_up_at ? 'Pedido recogido' : ($ruta?->arrived_pickup_at ? 'En el establecimiento' : 'En camino al establecimiento');
        $customerName = $direccion?->nombre_contacto ?: $pedido->cliente?->name ?? 'Cliente';
        $customerAddress = collect([$direccion?->direccion_linea_1, $direccion?->zona_o_barrio, $direccion?->municipio])->filter()->join(', ');
        $customerInitial = strtoupper(substr(trim((string) $customerName), 0, 1)) ?: 'C';
        $deliveryCode = (string) ($ruta?->confirmation_code ?? '');
        $codeDigits = array_pad(str_split($deliveryCode), 4, '');
    @endphp

    @if ($enRuta)
        <section class="-mx-4 -my-6 min-h-screen bg-[#fbf7f9] pb-6 text-atlantia-ink md:hidden">
            <header class="bg-atlantia-wine px-5 pb-5 pt-5 text-white">
                <div class="flex min-h-12 items-center justify-between gap-3">
                    <a href="{{ route('repartidor.dashboard') }}" class="grid h-11 w-11 place-items-center rounded-full text-white active:scale-95" aria-label="Volver">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 5 8 12l7 7M9 12h11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <h1 class="truncate text-xl font-black">En camino al cliente</h1>
                    <a href="{{ route('repartidor.soporte.index') }}" class="grid h-11 w-11 place-items-center rounded-full border-2 border-white/85 text-white active:scale-95" aria-label="Ayuda">
                        <span class="text-2xl font-black leading-none">?</span>
                    </a>
                </div>
            </header>

            <div class="relative h-[21rem] overflow-hidden rounded-b-[1.75rem] bg-[#f3eee8]" style="background-image: linear-gradient(31deg, rgba(148, 163, 184, .35) 1px, transparent 1px), linear-gradient(121deg, rgba(148, 163, 184, .28) 1px, transparent 1px), linear-gradient(0deg, rgba(255,255,255,.62), rgba(255,255,255,.62)); background-size: 42px 42px, 56px 56px, 100% 100%;">
                <div class="absolute left-0 top-4 h-36 w-24 rotate-12 rounded-full bg-emerald-100/85"></div>
                <div class="absolute right-3 top-16 h-20 w-28 rotate-12 rounded-full bg-emerald-100/85"></div>
                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 390 340" fill="none" aria-hidden="true" preserveAspectRatio="none">
                    <path d="M74 166 L132 205 L170 130 L236 170 L280 67" stroke="#8b0832" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="280" cy="67" r="9" fill="#8b0832"/>
                </svg>
                <span class="absolute left-[20%] top-[37%] grid h-14 w-14 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z" fill="currentColor"/>
                        <circle cx="12" cy="9" r="2.8" fill="white"/>
                    </svg>
                </span>
                <span class="absolute right-[18%] top-[9%] grid h-16 w-16 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/>
                    </svg>
                </span>
                <a
                    href="{{ $deliveryMaps ?? route('repartidor.pedidos.show', $pedido) }}"
                    @if ($deliveryMaps) target="_blank" @endif
                    class="absolute bottom-9 right-5 grid h-16 w-16 place-items-center rounded-full bg-atlantia-wine text-white shadow-[0_14px_34px_rgba(139,8,50,0.28)]"
                    aria-label="Navegar al cliente"
                >
                    <svg class="h-8 w-8 -rotate-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20.7 3.3 3.9 10.4c-.9.4-.8 1.7.2 1.9l7.2 1.4 1.4 7.2c.2 1 1.5 1.1 1.9.2l7.1-16.8c.3-.7-.3-1.3-1-1Z"/>
                    </svg>
                </a>
            </div>

            <div class="relative z-10 mx-4 -mt-8 space-y-3">
                @if (session('success'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
                @endif

                <article class="flex items-center justify-between gap-4 rounded-2xl bg-white p-4 shadow-[0_16px_38px_rgba(42,16,24,0.10)]">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-atlantia-wine text-3xl font-black text-white">
                            {{ $customerInitial }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-xl font-black text-atlantia-ink">{{ $customerName }}</h2>
                            <p class="mt-1 line-clamp-2 text-base font-bold leading-5 text-atlantia-ink/65">{{ $customerAddress ?: 'Direccion de entrega' }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        @if ($telefono)
                            <a href="tel:{{ $telefono }}" class="grid h-12 w-12 place-items-center rounded-full bg-white text-atlantia-wine shadow-[0_10px_24px_rgba(42,16,24,0.13)]" aria-label="Llamar al cliente">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6.5 4.5 9 4l2 5-1.5 1.1a10 10 0 0 0 4.4 4.4L15 13l5 2-.5 2.5c-.2 1-1 1.7-2 1.7A13.7 13.7 0 0 1 4.8 6.5c0-1 .7-1.8 1.7-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @endif
                        <a href="{{ $whatsapp ? 'https://wa.me/502' . $whatsapp : route('repartidor.soporte.index') }}" @if ($whatsapp) target="_blank" @endif class="grid h-12 w-12 place-items-center rounded-full bg-white text-atlantia-wine shadow-[0_10px_24px_rgba(42,16,24,0.13)]" aria-label="Enviar mensaje">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 5h14v10H8l-3 3V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M9 10h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </a>
                    </div>
                </article>

                <article class="rounded-2xl bg-white p-5 shadow-[0_16px_38px_rgba(42,16,24,0.10)]">
                    @if (! $ruta?->arrived_customer_at)
                        <div class="flex items-start gap-4">
                            <span class="mt-1 grid h-10 w-10 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z" stroke="currentColor" stroke-width="1.8"/>
                                    <circle cx="12" cy="9" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-xl font-black text-atlantia-ink">Confirma llegada</h2>
                                <p class="mt-1 text-base leading-6 text-atlantia-ink/65">Cuando estes frente al cliente, marca llegada para solicitar el codigo de entrega.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('repartidor.pedidos.arrived-customer', $pedido) }}" class="mt-5">
                            @csrf
                            @method('PATCH')
                            <button class="min-h-14 w-full rounded-2xl bg-atlantia-wine px-5 text-xl font-black text-white shadow-[0_12px_28px_rgba(139,8,50,0.22)]">
                                Llegue al cliente
                            </button>
                        </form>
                    @else
                        <div class="flex items-start gap-4">
                            <span class="mt-1 grid h-10 w-10 place-items-center rounded-xl bg-white text-atlantia-wine">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 14v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-xl font-black text-atlantia-ink">Codigo de entrega</h2>
                                <p class="mt-1 text-base leading-6 text-atlantia-ink/65">Pide al cliente el codigo de 4 digitos para completar la entrega de forma segura.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedido) }}" enctype="multipart/form-data" class="mt-5 space-y-4" data-delivery-code-form>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="confirmation_code" data-delivery-code-hidden>

                            <div class="grid grid-cols-4 gap-3">
                                @for ($i = 0; $i < 4; $i++)
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="1"
                                        required
                                        value=""
                                        data-delivery-code-digit
                                        aria-label="Digito {{ $i + 1 }} del codigo de entrega"
                                        class="h-24 min-w-0 rounded-xl border border-atlantia-rose/30 bg-white text-center text-5xl font-black text-atlantia-ink shadow-inner outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"
                                    >
                                @endfor
                            </div>

                            <div class="flex items-start gap-3 rounded-xl bg-atlantia-blush/70 p-4">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <p class="text-sm font-bold leading-6 text-atlantia-ink/70">Esta orden solo se marca como entregada despues de verificar correctamente el codigo.</p>
                            </div>

                            <button class="inline-flex min-h-14 w-full items-center justify-center gap-3 rounded-2xl bg-atlantia-wine px-5 text-xl font-black text-white shadow-[0_12px_28px_rgba(139,8,50,0.22)]">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3 19 6v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Verificar codigo
                            </button>
                        </form>
                    @endif
                </article>
            </div>
        </section>
    @endif

    <section class="{{ $enRuta ? 'hidden' : '' }} -mx-4 -my-6 min-h-screen bg-white text-atlantia-ink md:hidden">
        <header class="bg-atlantia-wine px-5 pb-5 pt-5 text-white">
            <div class="flex min-h-12 items-center justify-between gap-3">
                <a href="{{ route('repartidor.dashboard') }}" class="grid h-11 w-11 place-items-center rounded-full text-white active:scale-95" aria-label="Volver">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 5 8 12l7 7M9 12h11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 class="truncate text-xl font-black">{{ $mobileTitle }}</h1>
                <a href="{{ route('repartidor.soporte.index') }}" class="grid h-11 w-11 place-items-center rounded-full border-2 border-white/85 text-white active:scale-95" aria-label="Ayuda">
                    <span class="text-2xl font-black leading-none">?</span>
                </a>
            </div>
        </header>

        <div class="relative h-[22rem] overflow-hidden bg-[#f3eee8]" style="background-image: linear-gradient(31deg, rgba(148, 163, 184, .35) 1px, transparent 1px), linear-gradient(121deg, rgba(148, 163, 184, .28) 1px, transparent 1px), linear-gradient(0deg, rgba(255,255,255,.62), rgba(255,255,255,.62)); background-size: 42px 42px, 56px 56px, 100% 100%;">
            <div class="absolute left-8 top-24 h-16 w-24 rotate-12 rounded-full bg-emerald-100/85"></div>
            <div class="absolute right-6 top-8 h-20 w-24 rotate-12 rounded-full bg-emerald-100/85"></div>
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 390 360" fill="none" aria-hidden="true" preserveAspectRatio="none">
                <path d="M94 118 L170 198 L228 158 L304 119 L336 148" stroke="#8b0832" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="absolute left-[22%] top-[29%] grid h-14 w-14 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z" fill="currentColor"/>
                    <circle cx="12" cy="9" r="2.8" fill="white"/>
                </svg>
            </span>
            <span class="absolute right-[15%] top-[34%] grid h-16 w-16 place-items-center rounded-full bg-atlantia-wine text-white shadow-lg">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/>
                </svg>
            </span>
            <a
                href="{{ $pickupMaps ?? route('repartidor.pedidos.show', $pedido) }}"
                @if ($pickupMaps) target="_blank" @endif
                class="absolute bottom-9 right-5 grid h-16 w-16 place-items-center rounded-full bg-atlantia-wine text-white shadow-[0_14px_34px_rgba(139,8,50,0.28)]"
                aria-label="Navegar al establecimiento"
            >
                <svg class="h-8 w-8 -rotate-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20.7 3.3 3.9 10.4c-.9.4-.8 1.7.2 1.9l7.2 1.4 1.4 7.2c.2 1 1.5 1.1 1.9.2l7.1-16.8c.3-.7-.3-1.3-1-1Z"/>
                </svg>
            </a>
        </div>

        <article class="relative z-10 mx-4 -mt-10 rounded-[2rem] bg-white p-5 shadow-[0_22px_54px_rgba(42,16,24,0.14)]">
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-atlantia-wine text-4xl font-black text-white shadow-[0_14px_34px_rgba(139,8,50,0.22)]">
                        {{ $pickupLogo }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-2xl font-black text-atlantia-ink">{{ $pickupName }}</h2>
                        <p class="mt-1 truncate text-lg font-bold text-atlantia-ink/70">{{ $pickupAddress }}</p>
                        <p class="mt-1 truncate text-base font-bold text-atlantia-ink/55">{{ $pickupZone }}</p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-base font-bold text-atlantia-ink/65">Pedido</p>
                    <p class="text-2xl font-black text-atlantia-ink">#{{ $shortOrder }}</p>
                </div>
            </div>

            <div class="mt-5 divide-y divide-atlantia-rose/12 border-y border-atlantia-rose/12 py-2">
                @forelse ($pedido->items->take(4) as $item)
                    <div class="grid grid-cols-[3.25rem_1fr] items-center gap-2 py-3">
                        <span class="text-xl font-black text-atlantia-ink">{{ number_format((float) $item->cantidad, 0) }}x</span>
                        <span class="truncate text-xl font-black text-atlantia-ink">{{ $item->producto_nombre_snapshot ?: $item->producto?->nombre ?? 'Producto' }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm font-bold text-atlantia-ink/55">Sin items registrados.</p>
                @endforelse
            </div>

            <div class="mt-5 grid gap-3">
                @if ($canArrivePickup)
                    <form method="POST" action="{{ route('repartidor.pedidos.arrived-pickup', $pedido) }}">
                        @csrf
                        @method('PATCH')
                        <button class="min-h-14 w-full rounded-2xl bg-atlantia-wine px-5 text-xl font-black text-white shadow-[0_12px_28px_rgba(139,8,50,0.22)]">
                            Llegue al establecimiento
                        </button>
                    </form>
                @else
                    <button disabled class="min-h-14 w-full rounded-2xl bg-atlantia-wine/80 px-5 text-xl font-black text-white">
                        {{ $ruta?->arrived_pickup_at ? 'Llegada registrada' : 'Llegue al establecimiento' }}
                    </button>
                @endif

                @if ($canPickup)
                    <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedido) }}">
                        @csrf
                        @method('PATCH')
                        <button class="inline-flex min-h-14 w-full items-center justify-center gap-3 rounded-2xl border-2 border-atlantia-wine bg-white px-5 text-xl font-black text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                                <path d="m8 12 2.4 2.5L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Pedido recogido
                        </button>
                    </form>
                @else
                    <button disabled class="inline-flex min-h-14 w-full items-center justify-center gap-3 rounded-2xl border-2 border-atlantia-wine/45 bg-white px-5 text-xl font-black text-atlantia-wine/55">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m8 12 2.4 2.5L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $ready ? 'Pedido recogido' : 'Esperando pedido listo' }}
                    </button>
                @endif

                @if ($canReportNotReady)
                    <form method="POST" action="{{ route('repartidor.pedidos.pickup-not-ready', $pedido) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="pickup_issue_reason" value="El pedido no esta listo">
                        <button class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-xl bg-white px-5 text-lg font-black text-atlantia-wine">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 4 21 20H3L12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M12 10v4M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            </svg>
                            El pedido no esta listo
                        </button>
                    </form>
                @endif
            </div>
        </article>
    </section>

    <section class="hidden mx-auto max-w-4xl space-y-4 pb-16 md:block">
        <header class="rounded-lg bg-atlantia-wine p-4 text-white">
            <a href="{{ route('repartidor.pedidos.index') }}" class="text-xs font-black text-white/70">Mis pedidos</a>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black">{{ $pedido->numero_pedido }}</h1>
                    <p class="text-sm text-white/70">{{ ucfirst(str_replace('_', ' ', $estado)) }}</p>
                </div>
                <span class="rounded-md bg-white px-3 py-2 text-sm font-black text-atlantia-wine">
                    Q {{ number_format((float) ($ruta?->estimated_earning ?? 0), 2) }}
                </span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Recogida</p>
                <h2 class="mt-1 text-xl font-black text-atlantia-ink">{{ $ruta?->pickup_name ?? 'Atlantia Supermarket' }}</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">{{ $ruta?->pickup_address ?? 'Direccion de comercio pendiente' }}</p>
                @if ($ruta?->pickup_notes)
                    <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ $ruta->pickup_notes }}</p>
                @endif
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @if ($pickupMaps)<a href="{{ $pickupMaps }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Google Maps</a>@endif
                    @if ($pickupWaze)<a href="{{ $pickupWaze }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Waze</a>@endif
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Entrega</p>
                <h2 class="mt-1 text-xl font-black text-atlantia-ink">{{ $direccion?->nombre_contacto ?: $pedido->cliente?->name }}</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">
                    {{ $direccion?->direccion_linea_1 }}
                    @if ($direccion?->zona_o_barrio) · {{ $direccion->zona_o_barrio }} @endif
                    @if ($direccion?->municipio) · {{ $direccion->municipio }} @endif
                </p>
                @if ($direccion?->referencia)
                    <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ $direccion->referencia }}</p>
                @endif
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @if ($deliveryMaps)<a href="{{ $deliveryMaps }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Google Maps</a>@endif
                    @if ($deliveryWaze)<a href="{{ $deliveryWaze }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Waze</a>@endif
                </div>
                @if ($telefono)
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <a href="tel:{{ $telefono }}" class="rounded-md bg-emerald-50 px-4 py-2 text-center text-sm font-black text-emerald-700">Llamar</a>
                        @if ($whatsapp)
                            <a href="https://wa.me/502{{ $whatsapp }}" target="_blank" class="rounded-md bg-emerald-50 px-4 py-2 text-center text-sm font-black text-emerald-700">WhatsApp</a>
                        @endif
                    </div>
                @endif
            </article>
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Cobro y pedido</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-4">
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Metodo</p><p class="font-black text-atlantia-ink">{{ ucfirst($pedido->metodoPagoValor()) }}</p></div>
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Cobrar</p><p class="font-black text-atlantia-ink">Q {{ number_format((float) ($ruta?->cash_to_collect ?? 0), 2) }}</p></div>
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Pagar comercio</p><p class="font-black text-atlantia-ink">Q {{ number_format((float) ($ruta?->cash_to_pay_pickup ?? 0), 2) }}</p></div>
                <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Ganar</p><p class="font-black text-emerald-700">Q {{ number_format((float) ($ruta?->estimated_earning ?? 0) + (float) ($ruta?->tip_amount ?? 0) + (float) ($ruta?->bonus_amount ?? 0), 2) }}</p></div>
            </div>
            <div class="mt-3 divide-y divide-atlantia-rose/10">
                @forelse ($pedido->items as $item)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <p class="font-bold text-atlantia-ink">{{ $item->producto_nombre_snapshot ?: $item->producto?->nombre ?? 'Producto' }}</p>
                        <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ $item->cantidad }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-atlantia-ink/55">Sin items registrados.</p>
                @endforelse
            </div>
        </article>

        @if (! $entregado && ! $cancelado)
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Acciones</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @if (! $accepted)
                        <form method="POST" action="{{ route('repartidor.pedidos.accept', $pedido) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Aceptar entrega</button></form>
                        <form method="POST" action="{{ route('repartidor.pedidos.reject', $pedido) }}">@csrf @method('PATCH')<input type="hidden" name="reason" value="No disponible"><button class="w-full rounded-md border border-red-200 px-4 py-2.5 text-sm font-black text-red-700">Rechazar</button></form>
                    @elseif (! $ruta?->arrived_pickup_at)
                        <form method="POST" action="{{ route('repartidor.pedidos.arrived-pickup', $pedido) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue al establecimiento</button></form>
                    @elseif ($ready && ! $ruta?->picked_up_at)
                        <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedido) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido recogido</button></form>
                        <form method="POST" action="{{ route('repartidor.pedidos.pickup-not-ready', $pedido) }}">@csrf @method('PATCH')<input name="pickup_issue_reason" class="mb-2 w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="Motivo"><button class="w-full rounded-md border border-amber-200 px-4 py-2.5 text-sm font-black text-amber-700">Pedido no listo</button></form>
                    @elseif ($enRuta && ! $ruta?->arrived_customer_at)
                        <form method="POST" action="{{ route('repartidor.pedidos.arrived-customer', $pedido) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue al cliente</button></form>
                    @elseif ($enRuta)
                        <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedido) }}" enctype="multipart/form-data" class="space-y-2 sm:col-span-2">
                            @csrf
                            @method('PATCH')
                            @if ($ruta?->confirmation_code)
                                <input name="confirmation_code" class="w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="Codigo de entrega">
                            @endif
                            <input type="file" name="foto_entrega" accept="image/*" class="w-full rounded-md border border-atlantia-rose/25 p-2 text-sm">
                            <textarea name="notas" rows="2" class="w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="Nota de entrega"></textarea>
                            <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido entregado</button>
                        </form>
                    @else
                        <p class="rounded-lg border border-dashed border-atlantia-rose/25 p-5 text-center text-sm font-bold text-atlantia-ink/55 sm:col-span-2">Esperando que el comercio marque listo para entrega.</p>
                    @endif
                </div>
            </article>
        @endif

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Soporte</h2>
            <form method="POST" action="{{ route('repartidor.incidencias.store', $pedido) }}" class="mt-3 grid gap-2 sm:grid-cols-[180px_1fr_auto]">
                @csrf
                <select name="tipo" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm">
                    <option value="cliente">Cliente</option>
                    <option value="comercio">Comercio</option>
                    <option value="cobro">Cobro</option>
                    <option value="pedido_danado">Pedido dañado</option>
                    <option value="cancelado">Cancelacion</option>
                </select>
                <input name="descripcion" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="Describe el problema" required>
                <button class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-sm font-black text-atlantia-wine">Reportar</button>
            </form>
        </article>
    </section>
@endsection
