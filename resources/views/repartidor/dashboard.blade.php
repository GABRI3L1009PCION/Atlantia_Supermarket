@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $overview = $metrics['overview'];
        $profile = $metrics['profile'];
        $reward = $metrics['reward'];
        $wallet = $metrics['wallet']['wallet'];
        $offers = $metrics['offers'];
        $rutaActual = $metrics['ruta_actual'];
        $pedidoActual = $rutaActual?->pedido;
        $externalActive = $metrics['external_active'];
        $zonas = $metrics['demand_zones'];
        $promotions = $metrics['promotions'];
        $statusLabels = [
            'offline' => 'Desconectado',
            'available' => 'En linea',
            'busy' => 'Ocupado',
            'paused' => 'Pausado',
            'emergency' => 'Emergencia',
        ];
        $scopeLabels = [
            'internal' => 'Empresa',
            'entrepreneurs' => 'Emprendedores',
            'external' => 'Tiendas online',
            'both' => 'Todos',
        ];
        $entregasHoy = (int) ($overview['entregas_hoy'] ?? 0) + (int) ($overview['externas_hoy'] ?? 0);
        $nameParts = array_values(array_filter(preg_split('/\s+/', trim((string) $user->name))));
        $firstName = $nameParts[0] ?? 'Repartidor';
        $initials = strtoupper(substr($nameParts[0] ?? 'R', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
        $isOnline = $profile->availability_status === 'available';
        $mobileStatusLabel = $statusLabels[$profile->availability_status] ?? $profile->availability_status;
        $todayEarnings = (float) ($metrics['wallet']['today_earnings'] ?? 0);
        $onlineHours = $isOnline && $profile->last_online_at ? round($profile->last_online_at->diffInMinutes(now()) / 60, 1) : 0;
        $bonusTarget = 6;
        $bonusProgress = min($entregasHoy, $bonusTarget);
        $bonusPercent = min(100, (int) round(($bonusProgress / $bonusTarget) * 100));
        $primaryPromotion = $promotions[0] ?? ['title' => 'Bono activo', 'description' => 'Completa entregas para sumar incentivos.'];
        $incomingOffer = $offers->first();
        $incomingInternal = $incomingOffer?->pedido;
        $incomingExternal = $incomingOffer?->externalOrder;
        $incomingMeta = (array) ($incomingOffer?->metadata ?? []);
        $incomingTitle = $incomingInternal?->vendor?->business_name ?? $incomingExternal?->store_name ?? $incomingMeta['business_name'] ?? 'Atlantia Supermarket';
        $incomingCategory = $incomingInternal?->vendor?->business_category
            ?? ($incomingOffer?->source_type === 'external' ? 'Tienda online' : ($incomingOffer?->source_type === 'entrepreneurs' ? 'Emprendedor local' : 'Pedido interno'));
        $incomingPickup = $incomingMeta['pickup_address']
            ?? $incomingExternal?->pickup_address
            ?? $incomingInternal?->vendor?->municipio
            ?? $incomingInternal?->vendor?->direccion_comercial
            ?? 'Punto de recogida';
        $incomingDelivery = $incomingMeta['delivery_zone']
            ?? $incomingExternal?->delivery_address
            ?? $incomingInternal?->direccion?->municipio
            ?? 'Zona de entrega';
        $incomingPaymentRaw = strtolower((string) ($incomingOffer?->payment_method ?? 'digital'));
        $incomingPaymentLabel = match ($incomingPaymentRaw) {
            'cash', 'efectivo' => 'Efectivo',
            'card', 'tarjeta' => 'Tarjeta',
            'transfer', 'transferencia' => 'Transferencia',
            default => 'Digital',
        };
        $incomingPaymentIsCash = in_array($incomingPaymentRaw, ['cash', 'efectivo'], true);
        $incomingSeconds = $incomingOffer?->expires_at ? max(0, (int) now()->diffInSeconds($incomingOffer->expires_at, false)) : 0;
        $incomingTimerBase = max(1, $incomingSeconds);
        $incomingRingPercent = min(100, max(0, (int) round(($incomingSeconds / $incomingTimerBase) * 100)));
        $incomingLogo = strtoupper(substr(trim((string) $incomingTitle), 0, 1)) ?: 'A';
    @endphp

    <section class="-mx-4 -my-6 min-h-screen bg-[#8b0832] pb-24 text-atlantia-ink md:hidden">
        <div class="mx-auto min-h-screen max-w-md overflow-hidden bg-[#8b0832]">
            <div class="px-5 pb-5 pt-4 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border-4 border-white/15 bg-white text-xl font-black text-atlantia-wine shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <h1 class="truncate text-2xl font-black leading-tight">Hola, {{ $firstName }}!</h1>
                            <p class="mt-0.5 truncate text-sm font-bold text-white/82">Buen dia para repartir</p>
                        </div>
                    </div>
                    <a href="{{ route('notificaciones.index') }}" class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white transition active:scale-95" aria-label="Notificaciones">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 18.5a3 3 0 0 1-6 0M18.5 9.8V13l1.6 3.4H3.9L5.5 13V9.8a6.5 6.5 0 0 1 13 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="absolute right-2.5 top-2.5 h-3 w-3 rounded-full border-2 border-[#8b0832] bg-red-500"></span>
                    </a>
                </div>

                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-white/20 bg-white/12 px-3 py-2 text-sm font-bold text-white">
                        {{ session('success') }}
                    </div>
                @endif
            </div>

            <div class="space-y-3 px-4">
                <form method="POST" action="{{ route('repartidor.estado.disponibilidad') }}" class="flex min-h-20 items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-[0_16px_40px_rgba(42,16,24,0.12)]">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="availability_status" value="{{ $isOnline ? 'offline' : 'available' }}">
                    <input type="hidden" name="service_scope" value="{{ $profile->service_scope }}">
                    <input type="hidden" name="vehicle_type" value="{{ $profile->vehicle_type }}">

                    <div class="flex min-w-0 items-center gap-3">
                        <span class="text-base font-black">Estado</span>
                        <span class="h-3.5 w-3.5 shrink-0 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        <span class="truncate text-base font-black">{{ $mobileStatusLabel }}</span>
                    </div>
                    <button type="submit" class="relative h-11 w-[4.6rem] shrink-0 rounded-full {{ $isOnline ? 'bg-atlantia-wine' : 'bg-slate-300' }} shadow-inner transition active:scale-95" aria-label="Cambiar estado">
                        <span class="absolute top-1 h-9 w-9 rounded-full bg-white shadow-md transition {{ $isOnline ? 'right-1' : 'left-1' }}"></span>
                    </button>
                </form>

                <a href="{{ route('repartidor.ganancias.index') }}" class="flex min-h-32 items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-[0_16px_40px_rgba(42,16,24,0.12)]">
                    <div>
                        <p class="text-base font-black">Ganancias de hoy</p>
                        <p class="mt-2 text-4xl font-black tracking-normal text-atlantia-ink">Q {{ number_format($todayEarnings, 2) }}</p>
                        <p class="mt-1 text-base font-bold text-atlantia-ink/65">{{ number_format($entregasHoy) }} pedidos completados</p>
                    </div>
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white text-atlantia-wine shadow-[0_10px_25px_rgba(42,16,24,0.16)]">
                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7.5h13.8a2.2 2.2 0 0 1 2.2 2.2v7.6a2.2 2.2 0 0 1-2.2 2.2H5.8A2.8 2.8 0 0 1 3 16.7V6.5A2.5 2.5 0 0 0 5.5 9H20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 13.5h3.5v3H17a1.5 1.5 0 0 1 0-3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </a>

                <article class="relative h-48 overflow-hidden rounded-2xl border border-white/70 bg-[#f3eee8] shadow-[0_14px_34px_rgba(42,16,24,0.12)]" style="background-image: linear-gradient(31deg, rgba(148, 163, 184, .35) 1px, transparent 1px), linear-gradient(121deg, rgba(148, 163, 184, .28) 1px, transparent 1px), linear-gradient(0deg, rgba(255,255,255,.65), rgba(255,255,255,.65)); background-size: 42px 42px, 56px 56px, 100% 100%;">
                    <div class="absolute -left-8 top-20 h-12 w-28 rotate-12 rounded-full bg-emerald-100/80"></div>
                    <div class="absolute right-4 top-12 h-16 w-20 rotate-12 rounded-full bg-emerald-100/80"></div>
                    <span class="absolute left-[31%] top-[34%] h-12 w-12 rounded-full bg-red-500/25 blur-sm"></span>
                    <span class="absolute left-[34%] top-[39%] h-4 w-4 rounded-full bg-red-500 shadow-[0_0_0_10px_rgba(239,68,68,0.12)]"></span>
                    <span class="absolute right-[14%] top-[22%] h-12 w-12 rounded-full bg-red-500/25 blur-sm"></span>
                    <span class="absolute right-[17%] top-[27%] h-4 w-4 rounded-full bg-red-500 shadow-[0_0_0_10px_rgba(239,68,68,0.12)]"></span>
                    <span class="absolute bottom-[12%] left-[22%] h-10 w-10 rounded-full bg-red-500/20 blur-sm"></span>
                    <span class="absolute bottom-[19%] left-[25%] h-3.5 w-3.5 rounded-full bg-red-500 shadow-[0_0_0_9px_rgba(239,68,68,0.12)]"></span>
                    <span class="absolute left-1/2 top-[48%] h-5 w-5 rounded-full border-2 border-white bg-blue-500 shadow-[0_0_0_6px_rgba(59,130,246,0.18)]"></span>
                    <a href="{{ route('repartidor.rutas.index') }}" class="absolute bottom-4 right-4 inline-flex min-h-11 items-center gap-2 rounded-full bg-atlantia-wine px-4 text-sm font-black text-white shadow-lg">
                        Ver mapa completo
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </article>

                <div class="grid grid-cols-2 gap-3">
                    <article class="rounded-2xl bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.10)]">
                            <h2 class="text-sm font-black text-atlantia-wine">Zonas con alta demanda</h2>
                        <div class="mt-3 space-y-3">
                            @forelse ($zonas->take(3) as $zona)
                                @php
                                    $demandLabel = $loop->index < 2 ? 'Alta' : 'Media';
                                @endphp
                                <div class="flex items-center justify-between gap-2 text-sm">
                                    <span class="min-w-0 truncate font-bold text-atlantia-ink">{{ $zona->nombre }}</span>
                                    <span class="shrink-0 font-black {{ $demandLabel === 'Alta' ? 'text-red-600' : 'text-amber-600' }}">{{ $demandLabel }}</span>
                                </div>
                            @empty
                                @foreach (['Centro', 'Zona comercial', 'Residencial'] as $index => $zoneName)
                                    <div class="flex items-center justify-between gap-2 text-sm">
                                        <span class="min-w-0 truncate font-bold text-atlantia-ink">{{ $zoneName }}</span>
                                        <span class="shrink-0 font-black {{ $index < 2 ? 'text-red-600' : 'text-amber-600' }}">{{ $index < 2 ? 'Alta' : 'Media' }}</span>
                                    </div>
                                @endforeach
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-2xl bg-atlantia-wine p-4 text-white shadow-[0_14px_34px_rgba(42,16,24,0.18)]">
                        <h2 class="text-base font-black">{{ $primaryPromotion['title'] }}</h2>
                        <p class="mt-3 text-xl font-black">Q 10.00 extra</p>
                        <p class="mt-1 line-clamp-2 text-xs font-bold text-white/82">{{ $primaryPromotion['description'] }}</p>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/18">
                            <div class="h-full rounded-full bg-pink-300" style="width: {{ $bonusPercent }}%"></div>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-2">
                            <span class="text-base font-black">{{ $bonusProgress }} / {{ $bonusTarget }} pedidos</span>
                            <svg class="h-10 w-10 text-pink-200" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 10h16v10H4V10ZM3 7h18v3H3V7Z" fill="currentColor" opacity=".85"/>
                                <path d="M12 7v13M8 7C6.7 5.2 7 3.5 8.5 3.2 10 2.9 11.2 4.7 12 7c.8-2.3 2-4.1 3.5-3.8C17 3.5 17.3 5.2 16 7" stroke="#fff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </article>
                </div>

                <div class="grid grid-cols-4 gap-2.5">
                    <article class="rounded-2xl bg-white px-2 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                        <p class="text-xs font-black">Pedidos</p>
                        <p class="mt-2 text-3xl font-black">{{ number_format($entregasHoy) }}</p>
                        <p class="text-[11px] font-bold text-atlantia-ink/60">completados</p>
                    </article>
                    <article class="rounded-2xl bg-white px-2 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                        <p class="text-xs font-black">Aceptacion</p>
                        <p class="mt-2 text-3xl font-black">{{ number_format((float) $profile->acceptance_rate, 0) }}%</p>
                        <p class="text-[11px] font-bold text-atlantia-ink/60">activa</p>
                    </article>
                    <article class="rounded-2xl bg-white px-2 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                        <p class="text-xs font-black">Calificacion</p>
                        <p class="mt-2 text-3xl font-black">{{ number_format((float) $profile->rating, 1) }}<span class="text-atlantia-wine">*</span></p>
                        <p class="text-[11px] font-bold text-atlantia-ink/60">Excelente</p>
                    </article>
                    <article class="rounded-2xl bg-white px-2 py-4 text-center shadow-[0_12px_28px_rgba(42,16,24,0.09)]">
                        <p class="text-xs font-black">Horas</p>
                        <p class="mt-2 text-3xl font-black">{{ number_format($onlineHours, 1) }}</p>
                        <p class="text-[11px] font-bold text-atlantia-ink/60">hoy</p>
                    </article>
                </div>
            </div>
        </div>

        @if ($incomingOffer)
            <div data-offer-modal class="fixed inset-0 z-[60] flex items-center justify-center bg-[#201119]/88 px-4 py-6 md:hidden">
                <article class="max-h-[calc(100vh-2rem)] w-full max-w-sm overflow-y-auto rounded-[1.75rem] bg-white p-5 shadow-[0_28px_70px_rgba(0,0,0,0.32)]">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="pt-2 text-2xl font-black leading-tight text-atlantia-ink">Tienes un nuevo pedido!</h2>
                        <div
                            data-offer-ring
                            class="grid h-[4.65rem] w-[4.65rem] shrink-0 place-items-center rounded-full p-1"
                            style="background: conic-gradient(#8b0832 {{ $incomingRingPercent }}%, rgba(139, 8, 50, .16) 0);"
                        >
                            <div class="grid h-full w-full place-items-center rounded-full bg-white text-center text-atlantia-wine">
                                <div class="leading-none">
                                    <span
                                        data-offer-countdown
                                        data-offer-expires-at="{{ $incomingOffer->expires_at->toIso8601String() }}"
                                        data-offer-total-seconds="{{ $incomingTimerBase }}"
                                        class="block text-2xl font-black"
                                    >{{ $incomingSeconds }}</span>
                                    <span class="text-xs font-black">seg</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-atlantia-wine text-4xl font-black text-white shadow-[0_14px_34px_rgba(139,8,50,0.24)]">
                            {{ $incomingLogo }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-2xl font-black text-atlantia-ink">{{ $incomingTitle }}</p>
                            <p class="mt-0.5 truncate text-lg font-bold text-atlantia-ink/70">{{ $incomingCategory }}</p>
                            <p class="mt-2 flex items-center gap-2 text-base font-bold text-atlantia-ink/75">
                                <span class="text-amber-400">★</span>
                                <span>Comercio verificado</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-atlantia-rose/12 pt-4">
                        <div class="grid gap-4">
                            <div class="grid grid-cols-[2.75rem_1fr] gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg border border-atlantia-rose/12 text-atlantia-wine">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z" stroke="currentColor" stroke-width="1.8"/>
                                        <circle cx="12" cy="9" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-base font-bold text-atlantia-ink/65">Recoger en</p>
                                    <p class="truncate text-2xl font-black text-atlantia-ink">{{ $incomingPickup }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-[2.75rem_1fr] gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg border border-atlantia-rose/12 text-atlantia-wine">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z" stroke="currentColor" stroke-width="1.8"/>
                                        <circle cx="12" cy="9" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-base font-bold text-atlantia-ink/65">Entregar en</p>
                                    <p class="truncate text-2xl font-black text-atlantia-ink">{{ $incomingDelivery }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-atlantia-rose/12 pt-4">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 12h16M12 4l8 8-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span class="text-base font-bold text-atlantia-ink/75">Distancia al restaurante</span>
                                </div>
                                <span class="text-base font-black text-atlantia-ink">{{ $incomingOffer->pickup_distance_km !== null ? number_format((float) $incomingOffer->pickup_distance_km, 1) . ' km' : '--' }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 17a4 4 0 0 1 0-8h1a4 4 0 0 1 4 4v0a4 4 0 0 0 4 4h1a3 3 0 0 0 0-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                            <circle cx="6" cy="17" r="2" stroke="currentColor" stroke-width="1.8"/>
                                            <circle cx="18" cy="7" r="2" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </span>
                                    <span class="text-base font-bold text-atlantia-ink/75">Distancia total</span>
                                </div>
                                <span class="text-base font-black text-atlantia-ink">{{ $incomingOffer->total_distance_km !== null ? number_format((float) $incomingOffer->total_distance_km, 1) . ' km' : '--' }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M5 7h14v12H5V7ZM8 5h8v2H8V5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                            <path d="M9 13h6M12 10v6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <span class="text-base font-bold text-atlantia-ink/75">Ganancia estimada</span>
                                </div>
                                <span class="text-xl font-black text-atlantia-wine">Q {{ number_format((float) $incomingOffer->estimated_gain, 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 7h16v12H4V7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                            <circle cx="12" cy="13" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                                            <path d="M7 10h.01M17 16h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <span class="text-base font-bold text-atlantia-ink/75">Metodo de pago</span>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-base font-black {{ $incomingPaymentIsCash ? 'text-emerald-700' : 'text-sky-700' }}">
                                    {{ $incomingPaymentLabel }}
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3">
                        <form method="POST" action="{{ route('repartidor.ofertas.accept', $incomingOffer) }}">
                            @csrf
                            @method('PATCH')
                            <button class="min-h-14 w-full rounded-2xl bg-atlantia-wine px-5 text-xl font-black text-white shadow-[0_12px_28px_rgba(139,8,50,0.22)]">
                                Aceptar
                            </button>
                        </form>
                        <form method="POST" action="{{ route('repartidor.ofertas.reject', $incomingOffer) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="reason" value="No disponible">
                            <button class="min-h-14 w-full rounded-2xl border-2 border-atlantia-wine bg-white px-5 text-xl font-black text-atlantia-wine">
                                Rechazar
                            </button>
                        </form>
                    </div>
                </article>
            </div>
        @endif

        <nav class="fixed bottom-0 left-1/2 z-50 grid w-full max-w-md -translate-x-1/2 grid-cols-5 rounded-t-2xl border-t border-atlantia-rose/10 bg-white px-2 pb-2 pt-2 shadow-[0_-10px_34px_rgba(42,16,24,0.16)] md:hidden" aria-label="Navegacion del repartidor">
            <a href="{{ route('repartidor.dashboard') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-wine">
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
            <a href="{{ route('repartidor.historial.index') }}" class="flex min-w-0 flex-col items-center gap-1 rounded-xl px-1.5 py-2 text-[11px] font-black text-atlantia-ink/55">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Perfil</span>
            </a>
        </nav>
    </section>

    <section class="hidden -mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-[#f7f8fb] px-4 py-4 sm:-mx-6 sm:px-6 md:block lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-4">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <header class="grid gap-3 lg:grid-cols-[1.3fr_0.7fr]">
                <article class="rounded-lg bg-atlantia-wine p-4 text-white shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/65">Operacion de reparto</p>
                            <h1 class="mt-1 text-2xl font-black">{{ $user->name }}</h1>
                            <p class="mt-1 text-sm text-white/70">
                                {{ $statusLabels[$profile->availability_status] ?? $profile->availability_status }}
                                · {{ $scopeLabels[$profile->service_scope] ?? $profile->service_scope }}
                                · Nivel {{ $reward['current'] }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('repartidor.emergencia.store') }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 items-center rounded-md bg-red-600 px-4 text-sm font-black text-white shadow-sm">
                                Emergencia
                            </button>
                        </form>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-white/65">Entregas hoy</p>
                            <p class="mt-1 text-3xl font-black">{{ number_format($entregasHoy) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-white/65">Ofertas pendientes</p>
                            <p class="mt-1 text-3xl font-black">{{ number_format((int) $overview['ofertas_pendientes']) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-white/65">Saldo disponible</p>
                            <p class="mt-1 text-3xl font-black">Q {{ number_format((float) $wallet->available_balance, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-white/65">Efectivo</p>
                            <p class="mt-1 text-3xl font-black">Q {{ number_format((float) $wallet->cash_balance, 2) }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <div class="grid gap-3">
                        <form method="POST" action="{{ route('repartidor.estado.disponibilidad') }}" class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs font-black text-atlantia-ink/60">
                                Estado
                                <select name="availability_status" class="mt-1 w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm text-atlantia-ink">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($profile->availability_status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-black text-atlantia-ink/60">
                                Alcance
                                <select name="service_scope" class="mt-1 w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm text-atlantia-ink">
                                    @foreach ($scopeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($profile->service_scope === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-black text-atlantia-ink/60">
                                Vehiculo
                                <input name="vehicle_type" value="{{ $profile->vehicle_type }}" class="mt-1 w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm text-atlantia-ink" placeholder="Moto">
                            </label>
                            <button class="rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white sm:col-span-3 lg:col-span-1">Actualizar</button>
                        </form>

                        <form method="POST" action="{{ route('repartidor.estado.auto-aceptacion') }}" class="rounded-lg bg-atlantia-blush/55 p-3">
                            @csrf
                            @method('PATCH')
                            <label class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                                <input type="checkbox" name="auto_accept_enabled" value="1" @checked($profile->auto_accept_enabled) class="rounded border-atlantia-rose/35">
                                Aceptacion automatica
                            </label>
                            <div class="mt-2 flex items-end gap-2">
                                <label class="flex-1 text-xs font-black text-atlantia-ink/60">
                                    Max km
                                    <input name="auto_accept_max_distance_km" type="number" min="0.5" max="50" step="0.1" value="{{ $profile->auto_accept_max_distance_km }}" class="mt-1 w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm">
                                </label>
                                <button class="rounded-md border border-atlantia-rose/30 bg-white px-3 py-2 text-xs font-black text-atlantia-wine">Guardar</button>
                            </div>
                        </form>
                    </div>
                </article>
            </header>

            <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Ofertas</p>
                            <h2 class="text-xl font-black text-atlantia-ink">Pedidos disponibles</h2>
                        </div>
                        <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ $offers->count() }}</span>
                    </div>

                    <div class="mt-3 space-y-3">
                        @forelse ($offers as $offer)
                            @php
                                $internal = $offer->pedido;
                                $external = $offer->externalOrder;
                                $title = $internal?->vendor?->business_name ?? $external?->store_name ?? 'Atlantia Supermarket';
                                $deliveryZone = $internal?->direccion?->municipio ?? $external?->delivery_address ?? 'Zona pendiente';
                            @endphp
                            <div class="rounded-lg border border-atlantia-rose/15 bg-[#fffdfd] p-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black uppercase text-atlantia-rose">{{ $offer->source_type === 'external' ? 'Tienda online' : ($offer->source_type === 'entrepreneurs' ? 'Emprendedor' : 'Empresa') }}</p>
                                        <h3 class="mt-1 text-lg font-black text-atlantia-ink">{{ $title }}</h3>
                                        <p class="mt-1 text-sm text-atlantia-ink/60">{{ $deliveryZone }}</p>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center text-xs md:min-w-72">
                                        <div class="rounded-md bg-atlantia-blush/70 p-2">
                                            <p class="text-atlantia-ink/55">Total km</p>
                                            <p class="font-black text-atlantia-ink">{{ number_format((float) $offer->total_distance_km, 1) }}</p>
                                        </div>
                                        <div class="rounded-md bg-atlantia-blush/70 p-2">
                                            <p class="text-atlantia-ink/55">Pago</p>
                                            <p class="font-black text-atlantia-ink">{{ ucfirst((string) $offer->payment_method) }}</p>
                                        </div>
                                        <div class="rounded-md bg-emerald-50 p-2">
                                            <p class="text-emerald-700/70">Ganas</p>
                                            <p class="font-black text-emerald-700">Q {{ number_format((float) $offer->estimated_gain, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <form method="POST" action="{{ route('repartidor.ofertas.accept', $offer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Aceptar</button>
                                    </form>
                                    <form method="POST" action="{{ route('repartidor.ofertas.reject', $offer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="reason" value="No disponible">
                                        <button class="w-full rounded-md border border-red-200 px-4 py-2.5 text-sm font-black text-red-700">Rechazar</button>
                                    </form>
                                </div>
                                <p class="mt-2 text-xs text-atlantia-ink/45">Vence {{ $offer->expires_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-atlantia-rose/25 p-8 text-center">
                                <p class="text-base font-black text-atlantia-ink">Sin ofertas pendientes</p>
                                <p class="mt-1 text-sm text-atlantia-ink/55">Mantente disponible para recibir pedidos internos, emprendedores y tiendas online.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Programa</p>
                    <div class="mt-2 flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-atlantia-ink">{{ $reward['current'] }}</h2>
                            <p class="text-sm text-atlantia-ink/60">{{ number_format($reward['points']) }} puntos</p>
                        </div>
                        <p class="text-sm font-black text-atlantia-wine">{{ $reward['progress'] }}%</p>
                    </div>
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-atlantia-blush">
                        <div class="h-full rounded-full bg-atlantia-wine" style="width: {{ $reward['progress'] }}%"></div>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-md bg-slate-50 p-3">
                            <p class="text-xs text-atlantia-ink/55">Aceptacion</p>
                            <p class="text-lg font-black text-atlantia-ink">{{ number_format((float) $profile->acceptance_rate, 1) }}%</p>
                        </div>
                        <div class="rounded-md bg-slate-50 p-3">
                            <p class="text-xs text-atlantia-ink/55">Finalizacion</p>
                            <p class="text-lg font-black text-atlantia-ink">{{ number_format((float) $profile->completion_rate, 1) }}%</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($reward['levels'] as $level)
                            <span class="rounded-full {{ $level['name'] === $reward['current'] ? 'bg-atlantia-wine text-white' : 'bg-slate-100 text-atlantia-ink/65' }} px-3 py-1 text-xs font-black">
                                {{ $level['name'] }}
                            </span>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Ruta actual</p>
                            <h2 class="text-xl font-black text-atlantia-ink">{{ $pedidoActual?->numero_pedido ?? 'Libre' }}</h2>
                        </div>
                        <a href="{{ route('repartidor.pedidos.index') }}" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-xs font-black text-atlantia-wine">Pedidos</a>
                    </div>

                    @if ($pedidoActual)
                        @php
                            $direccion = $pedidoActual->direccion;
                            $accepted = $rutaActual->aceptada_at !== null;
                            $ready = $pedidoActual->estadoValor() === 'listo_para_entrega';
                            $enRuta = $pedidoActual->estadoValor() === 'en_ruta';
                            $destLat = $enRuta ? $direccion?->latitude : $rutaActual->pickup_latitude;
                            $destLng = $enRuta ? $direccion?->longitude : $rutaActual->pickup_longitude;
                            $mapsUrl = $destLat && $destLng ? 'https://www.google.com/maps/dir/?api=1&destination=' . $destLat . ',' . $destLng : null;
                            $wazeUrl = $destLat && $destLng ? 'https://waze.com/ul?ll=' . $destLat . ',' . $destLng . '&navigate=yes' : null;
                        @endphp
                        <div class="mt-3 rounded-lg bg-atlantia-blush/45 p-3">
                            <p class="text-sm font-black text-atlantia-ink">{{ $rutaActual->pickup_name ?? 'Punto de recogida' }}</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">{{ $rutaActual->pickup_address ?? 'Direccion de comercio pendiente' }}</p>
                            <p class="mt-3 text-sm font-black text-atlantia-ink">{{ $direccion?->nombre_contacto ?: $pedidoActual->cliente?->name }}</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">{{ $direccion?->direccion_linea_1 }} {{ $direccion?->municipio ? '· ' . $direccion->municipio : '' }}</p>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs text-atlantia-ink/55">Ganancia</p>
                                <p class="font-black text-emerald-700">Q {{ number_format((float) $rutaActual->estimated_earning, 2) }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs text-atlantia-ink/55">Cobrar</p>
                                <p class="font-black text-atlantia-ink">Q {{ number_format((float) $rutaActual->cash_to_collect, 2) }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs text-atlantia-ink/55">Estado</p>
                                <p class="font-black text-atlantia-ink">{{ str_replace('_', ' ', $pedidoActual->estadoValor()) }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @if ($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine">Google Maps</a>
                            @endif
                            @if ($wazeUrl)
                                <a href="{{ $wazeUrl }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine">Waze</a>
                            @endif
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @if (! $accepted)
                                <form method="POST" action="{{ route('repartidor.pedidos.accept', $pedidoActual) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Aceptar</button></form>
                                <form method="POST" action="{{ route('repartidor.pedidos.reject', $pedidoActual) }}">@csrf @method('PATCH')<input type="hidden" name="reason" value="No disponible"><button class="w-full rounded-md border border-red-200 px-4 py-2.5 text-sm font-black text-red-700">Rechazar</button></form>
                            @elseif (! $rutaActual->arrived_pickup_at)
                                <form method="POST" action="{{ route('repartidor.pedidos.arrived-pickup', $pedidoActual) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue al establecimiento</button></form>
                                <a href="{{ route('repartidor.pedidos.show', $pedidoActual) }}" class="rounded-md border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine">Detalle</a>
                            @elseif ($ready && ! $rutaActual->picked_up_at)
                                <form method="POST" action="{{ route('repartidor.pedidos.pickup', $pedidoActual) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido recogido</button></form>
                                <form method="POST" action="{{ route('repartidor.pedidos.pickup-not-ready', $pedidoActual) }}">@csrf @method('PATCH')<input type="hidden" name="pickup_issue_reason" value="Pedido no listo"><button class="w-full rounded-md border border-amber-200 px-4 py-2.5 text-sm font-black text-amber-700">No esta listo</button></form>
                            @elseif ($enRuta && ! $rutaActual->arrived_customer_at)
                                <form method="POST" action="{{ route('repartidor.pedidos.arrived-customer', $pedidoActual) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue al cliente</button></form>
                                <a href="{{ route('repartidor.pedidos.show', $pedidoActual) }}" class="rounded-md border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine">Confirmar</a>
                            @elseif ($enRuta)
                                <form method="POST" action="{{ route('repartidor.pedidos.deliver', $pedidoActual) }}" enctype="multipart/form-data" class="sm:col-span-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="file" name="foto_entrega" accept="image/*" class="mb-2 w-full rounded-md border border-atlantia-rose/25 p-2 text-sm">
                                    <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido entregado</button>
                                </form>
                            @else
                                <a href="{{ route('repartidor.pedidos.show', $pedidoActual) }}" class="sm:col-span-2 rounded-md border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine">Ver detalle</a>
                            @endif
                        </div>
                    @else
                        <div class="mt-3 rounded-lg border border-dashed border-atlantia-rose/25 p-8 text-center">
                            <p class="font-black text-atlantia-ink">Sin entrega activa</p>
                            <p class="mt-1 text-sm text-atlantia-ink/55">Cuando aceptes una oferta aparecera aqui.</p>
                        </div>
                    @endif
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Tiendas online</p>
                            <h2 class="text-xl font-black text-atlantia-ink">Entregas externas</h2>
                        </div>
                        <a href="{{ route('repartidor.externas.index') }}" class="rounded-md border border-atlantia-rose/25 px-3 py-2 text-xs font-black text-atlantia-wine">Ver</a>
                    </div>
                    <div class="mt-3 space-y-2">
                        @forelse ($externalActive as $order)
                            <a href="{{ route('repartidor.externas.show', $order) }}" class="block rounded-lg border border-atlantia-rose/15 p-3 hover:border-atlantia-wine">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-atlantia-ink">{{ $order->store_name }}</p>
                                        <p class="truncate text-sm text-atlantia-ink/60">{{ $order->delivery_address }}</p>
                                    </div>
                                    <span class="rounded-md bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-700">Q {{ number_format((float) $order->courier_earning, 2) }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="rounded-lg border border-dashed border-atlantia-rose/25 p-6 text-center text-sm font-bold text-atlantia-ink/55">Sin entregas externas activas.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Zonas</p>
                    <h2 class="text-xl font-black text-atlantia-ink">Mayor demanda</h2>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @forelse ($zonas as $zona)
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="font-black text-atlantia-ink">{{ $zona->nombre }}</p>
                                <p class="text-sm text-atlantia-ink/60">{{ $zona->municipio }} · Q {{ number_format((float) $zona->costo_base, 2) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-atlantia-ink/55">No hay zonas activas configuradas.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Incentivos</p>
                    <h2 class="text-xl font-black text-atlantia-ink">Bonificaciones</h2>
                    <div class="mt-3 grid gap-2">
                        @foreach ($promotions as $promotion)
                            <div class="rounded-md bg-emerald-50 p-3">
                                <p class="font-black text-emerald-800">{{ $promotion['title'] }}</p>
                                <p class="text-sm text-emerald-700/75">{{ $promotion['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>
        </div>
    </section>
@endsection
