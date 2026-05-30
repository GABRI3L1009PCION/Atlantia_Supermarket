@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@push('styles')
    <style nonce="{{ request()->attributes->get('csp_nonce') }}">
        @keyframes dashboard-icon-float {
            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-3px) scale(1.03);
            }
        }

        @keyframes dashboard-icon-swing {
            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(-5deg);
            }
        }

        @keyframes dashboard-alert-pop {
            0%,
            100% {
                box-shadow: 0 0 0 0 rgb(139 29 77 / 0);
                transform: scale(1);
            }

            45% {
                box-shadow: 0 0 0 6px rgb(139 29 77 / 0.08);
                transform: scale(1.08);
            }
        }

        @keyframes dashboard-spark-draw {
            from {
                stroke-dashoffset: 90;
            }

            to {
                stroke-dashoffset: 0;
            }
        }

        .admin-dashboard-surface span:has(> svg) {
            animation: dashboard-icon-float 2.7s ease-in-out infinite;
            transform-origin: center;
            will-change: transform;
        }

        .admin-dashboard-surface span:has(> svg) > svg {
            animation: dashboard-icon-swing 2.7s ease-in-out infinite;
            transform-origin: center;
        }

        .admin-dashboard-surface .dashboard-alert-icon {
            animation: dashboard-alert-pop 2.4s ease-in-out infinite;
            transform-origin: center;
        }

        .admin-dashboard-surface .dashboard-sparkline {
            stroke-dasharray: 90;
            animation: dashboard-spark-draw 2.6s ease-in-out infinite alternate;
        }

        @media (prefers-reduced-motion: reduce) {
            .admin-dashboard-surface span:has(> svg),
            .admin-dashboard-surface span:has(> svg) > svg,
            .admin-dashboard-surface .dashboard-alert-icon,
            .admin-dashboard-surface .dashboard-sparkline {
                animation: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $overview = $metrics['overview'];
        $operacion = $metrics['operacion'];
        $alerts = $metrics['alerts'];
        $recentOrders = $metrics['recent_orders'];
        $monthlySales = $metrics['monthly_sales'];
        $courierStatus = $metrics['courier_status'];
        $maxSale = max(1, (float) $monthlySales->max('total'));

        $estadoClasses = [
            'entregado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'confirmado' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'pendiente' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'en_ruta' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            'cancelado' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
    @endphp

    <section class="admin-dashboard-surface -mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-white px-4 py-5 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-[#211920] sm:text-3xl">
                        Dashboard general
                    </h1>
                    <p class="mt-1 text-xs font-medium text-[#6f626a]">Operacion, pedidos y control del marketplace</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-[#6f626a]">
                    <span class="inline-flex items-center gap-2 rounded-md bg-[#fbf7f9] px-3 py-2 font-semibold ring-1 ring-[#ead8df]">
                        <svg class="h-4 w-4 text-[#8b1d4d]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 3V6M17 3V6M4 9H20M6 5H18C19.1 5 20 5.9 20 7V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V7C4 5.9 4.9 5 6 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        Actualizado {{ now()->format('d/m/Y H:i') }}
                    </span>
                </div>
            </header>

            @if ($alerts['total'] > 0)
                <a
                    href="{{ route('admin.antifraude.index') }}"
                    class="mt-4 flex flex-col justify-between gap-2 rounded-md border border-[#f3c8d5] bg-[#fff5f8] px-4 py-3 text-xs font-bold text-[#7a183f] sm:flex-row sm:items-center"
                >
                    <span>
                        {{ number_format($alerts['total']) }} alertas requieren atencion:
                        {{ number_format($alerts['fraud']) }} antifraude,
                        {{ number_format($alerts['dte_rejected']) }} DTE rechazados,
                        {{ number_format($alerts['stock_low']) }} productos con stock bajo.
                    </span>
                    <span>Revisar -></span>
                </a>
            @endif

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-[#fff0f5] text-[#8b1d4d]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 7V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V7M6 7H18V20H6V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M10 11H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#7c7178]">Pedidos hoy</p>
                            <p class="text-2xl font-black leading-tight text-[#211920]">{{ number_format($overview['pedidos_hoy']) }}</p>
                            <p class="text-[11px] font-black text-emerald-700">Operacion activa</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 2V22M16 7.5C15.4 6.6 14.2 6 12.8 6H11.5C9.9 6 8.7 6.9 8.7 8.2C8.7 10 10.5 10.5 12.4 10.9C14.4 11.4 16.1 12 16.1 13.8C16.1 15.2 14.8 16.1 13.1 16.1H11.8C10.1 16.1 8.8 15.4 8 14.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#7c7178]">Ventas hoy</p>
                            <p class="text-2xl font-black leading-tight text-[#211920]">Q {{ number_format($overview['ventas_hoy'], 2) }}</p>
                            <p class="text-[11px] font-black text-emerald-700">Ingresos confirmados</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-[#fff0f5] text-[#8b1d4d]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7H20V17H4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M8 7V17M16 7V17M10 12H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#7c7178]">Ticket promedio</p>
                            <p class="text-2xl font-black leading-tight text-[#211920]">Q {{ number_format($overview['ticket_promedio'], 2) }}</p>
                            <p class="text-[11px] font-black text-[#8b1d4d]">Pedidos padre</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7H14V17H4V7ZM14 10H18L20 13V17H14V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M7 19C8.1 19 9 18.1 9 17M17 19C18.1 19 19 18.1 19 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-[#7c7178]">Tasa entrega</p>
                            <p class="text-2xl font-black leading-tight text-[#211920]">{{ number_format($overview['tasa_entrega'], 0) }}%</p>
                            <p class="text-[11px] font-black text-amber-700">Cierre operativo</p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-black text-[#211920]">Operacion en vivo</h2>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase text-emerald-700 ring-1 ring-emerald-100">
                            En vivo
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="flex items-center gap-3 rounded-md bg-[#f8f5ef] p-4">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-[#8b6b2f] shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3L20 7V17L12 21L4 17V7L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M4 7L12 11L20 7M12 11V21" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-2xl font-black text-[#211920]">{{ number_format($operacion['pedidos_pendientes']) }}</p>
                                <p class="text-[11px] font-semibold text-[#6d6269]">En preparacion</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-md bg-[#fff8eb] p-4">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-amber-700 shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7H14V17H4V7ZM14 10H18L20 13V17H14V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M7 19C8.1 19 9 18.1 9 17M17 19C18.1 19 19 18.1 19 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-2xl font-black text-[#9a5a00]">{{ number_format($courierStatus['en_ruta']) }}</p>
                                <p class="text-[11px] font-semibold text-[#6d6269]">En ruta</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-md bg-emerald-50 p-4">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-emerald-700 shadow-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-2xl font-black text-emerald-700">{{ number_format($overview['tasa_entrega'], 0) }}%</p>
                                <p class="text-[11px] font-semibold text-[#6d6269]">Entregados</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 border-t border-[#ead8df] pt-3 text-xs sm:grid-cols-2">
                        <p class="text-[#5f555c]">
                            Repartidores:
                            <strong class="text-[#211920]">{{ number_format($courierStatus['total']) }} activos</strong>
                            / {{ number_format($courierStatus['disponibles']) }} disponibles
                        </p>
                        <a href="{{ route('admin.pedidos.index') }}" class="font-black text-[#8b1d4d] hover:underline">
                            {{ number_format($operacion['pedidos_pendientes']) }} pedidos pendientes por revisar
                        </a>
                    </div>
                </article>

                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <h2 class="text-base font-black text-[#211920]">Alertas operativas</h2>

                    <div class="mt-3 divide-y divide-[#ead8df] text-xs">
                        <a href="{{ route('admin.antifraude.index') }}" class="flex items-center justify-between py-2">
                            <span class="inline-flex items-center gap-2 font-black text-rose-700">
                                <span class="dashboard-alert-icon grid h-5 w-5 place-items-center rounded bg-rose-50">!</span>
                                Antifraude
                            </span>
                            <span class="font-black text-[#211920]">{{ number_format($alerts['fraud']) }}</span>
                        </a>
                        <a href="{{ route('admin.dte.index') }}" class="flex items-center justify-between py-2">
                            <span class="inline-flex items-center gap-2 font-black text-amber-700">
                                <span class="dashboard-alert-icon grid h-5 w-5 place-items-center rounded bg-amber-50">!</span>
                                DTE rechazados
                            </span>
                            <span class="font-black text-[#211920]">{{ number_format($alerts['dte_rejected']) }}</span>
                        </a>
                        <a href="{{ route('admin.productos.index') }}" class="flex items-center justify-between py-2">
                            <span class="inline-flex items-center gap-2 font-black text-orange-700">
                                <span class="dashboard-alert-icon grid h-5 w-5 place-items-center rounded bg-orange-50">!</span>
                                Stock bajo
                            </span>
                            <span class="font-black text-[#211920]">{{ number_format($alerts['stock_low']) }}</span>
                        </a>
                        <a href="{{ route('admin.vendedores.index') }}" class="flex items-center justify-between py-2">
                            <span class="inline-flex items-center gap-2 font-black text-sky-700">
                                <span class="dashboard-alert-icon grid h-5 w-5 place-items-center rounded bg-sky-50">+</span>
                                Vendedores por aprobar
                            </span>
                            <span class="font-black text-[#211920]">{{ number_format($alerts['vendors_pending']) }}</span>
                        </a>
                        <a href="{{ route('admin.ml.monitor') }}" class="flex items-center justify-between py-2">
                            <span class="inline-flex items-center gap-2 font-black text-emerald-700">
                                <span class="dashboard-alert-icon grid h-5 w-5 place-items-center rounded bg-emerald-50">ML</span>
                                Monitor ML
                            </span>
                            <span class="font-black text-emerald-700">{{ $alerts['ml_status'] }}</span>
                        </a>
                    </div>
                </article>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-[1fr_0.95fr]">
                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-black text-[#211920]">Ventas ultimos 6 meses</h2>
                        <a href="{{ route('admin.reportes.index') }}" class="text-xs font-black text-[#8b1d4d] hover:underline">
                            Ver reportes ->
                        </a>
                    </div>

                    <div class="mt-4 h-40 rounded-md border border-[#f0e3e8] bg-[linear-gradient(to_bottom,transparent_24%,#f3e8ed_25%,transparent_26%,transparent_49%,#f3e8ed_50%,transparent_51%,transparent_74%,#f3e8ed_75%,transparent_76%)] p-3">
                        <div class="flex h-full items-end gap-4">
                            @foreach ($monthlySales as $sale)
                                <div class="flex flex-1 flex-col items-center justify-end gap-2">
                                    <div
                                        class="w-full rounded-t bg-[#9a285a] shadow-[0_10px_20px_rgba(154,40,90,0.18)]"
                                        style="height: {{ max(8, round(((float) $sale['total'] / $maxSale) * 100)) }}%;"
                                        title="Q {{ number_format($sale['total'], 2) }}"
                                    ></div>
                                    <span class="text-[10px] font-semibold text-[#8f828a]">{{ $sale['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 rounded-md border border-[#ead8df] bg-white px-3 py-2 text-xs text-[#5f555c]">
                        Total 6m:
                        <strong class="ml-2 text-sm text-[#8b1d4d]">Q {{ number_format($monthlySales->sum('total'), 2) }}</strong>
                    </div>
                </article>

                <article class="rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-black text-[#211920]">Ultimos pedidos</h2>
                        <a href="{{ route('admin.pedidos.index') }}" class="text-xs font-black text-[#8b1d4d] hover:underline">
                            Ver todos ->
                        </a>
                    </div>

                    <div class="mt-3 overflow-hidden rounded-md border border-[#ead8df]">
                        <div class="grid grid-cols-[0.9fr_1fr_0.75fr_auto] bg-[#fbf7f9] px-3 py-2 text-[11px] font-black uppercase text-[#7c7178]">
                            <span>ID Pedido</span>
                            <span>Cliente</span>
                            <span>Estado</span>
                            <span>Total</span>
                        </div>
                        <div class="divide-y divide-[#ead8df]">
                            @forelse ($recentOrders as $order)
                                @php
                                    $estado = $order['estado'];
                                    $badgeClass = $estadoClasses[$estado] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                                @endphp
                                <div class="grid grid-cols-[0.9fr_1fr_0.75fr_auto] items-center gap-3 px-3 py-2 text-xs">
                                    <span class="truncate font-semibold text-[#7c7178]">{{ $order['numero'] }}</span>
                                    <span class="truncate text-[#211920]">{{ $order['cliente'] }}</span>
                                    <span>
                                        <span class="inline-flex rounded-full px-2 py-1 text-[10px] font-black ring-1 {{ $badgeClass }}">
                                            {{ str_replace('_', ' ', $estado) }}
                                        </span>
                                    </span>
                                    <span class="font-black text-[#211920]">Q {{ number_format($order['total'], 2) }}</span>
                                </div>
                            @empty
                                <p class="px-3 py-6 text-center text-sm text-[#6d6269]">Sin pedidos recientes.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            </div>

            <article class="mt-4 rounded-md border border-[#ead8df] bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-[#211920]">Inteligencia del negocio</h2>
                    <span class="text-[11px] font-black text-[#8f828a]">Powered by ML</span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <a href="{{ route('admin.ml.monitor') }}" class="grid min-h-[92px] grid-cols-[44px_1fr_74px] items-center gap-3 rounded-md bg-[#f4f0ff] p-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-white text-[#6c4cc9] shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3V5M12 19V21M4.2 7.5L5.9 8.5M18.1 15.5L19.8 16.5M4.2 16.5L5.9 15.5M18.1 8.5L19.8 7.5M8 12C8 9.8 9.8 8 12 8C14.2 8 16 9.8 16 12C16 14.2 14.2 16 12 16C9.8 16 8 14.2 8 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-black text-[#5a42a5]">Monitor ML</span>
                            <span class="block text-lg font-black leading-tight text-[#211920]">Estable</span>
                            <span class="block text-[11px] font-semibold text-[#6c4cc9]">Drift bajo revision</span>
                        </span>
                        <svg class="h-12 w-full text-[#8d72e5]" viewBox="0 0 88 42" fill="none" aria-hidden="true">
                            <path class="dashboard-sparkline" d="M2 32L15 28L27 34L40 15L52 22L64 10L76 18L86 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.antifraude.index') }}" class="grid min-h-[92px] grid-cols-[44px_1fr_74px] items-center gap-3 rounded-md bg-[#fff0f6] p-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-white text-[#c02d68] shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L19 6V11.5C19 16.2 16 19.8 12 21C8 19.8 5 16.2 5 11.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-black text-[#9a285a]">Antifraude</span>
                            <span class="block text-lg font-black leading-tight text-[#211920]">{{ number_format($alerts['fraud']) }} casos</span>
                            <span class="block text-[11px] font-semibold text-[#9a285a]">Revision pendiente</span>
                        </span>
                        <svg class="h-12 w-full text-[#ff8ab5]" viewBox="0 0 88 42" fill="none" aria-hidden="true">
                            <path class="dashboard-sparkline" d="M2 34L13 30L24 32L36 18L47 30L58 26L70 11L86 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.dte.index') }}" class="grid min-h-[92px] grid-cols-[44px_1fr_74px] items-center gap-3 rounded-md bg-emerald-50 p-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-white text-emerald-700 shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 3H14L19 8V21H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M14 3V8H19M10 13H16M10 17H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-black text-emerald-800">DTE / FEL</span>
                            <span class="block text-lg font-black leading-tight text-[#211920]">{{ number_format($alerts['dte_rejected']) }}</span>
                            <span class="block text-[11px] font-semibold text-emerald-700">rechazos activos</span>
                        </span>
                        <svg class="h-12 w-full text-emerald-300" viewBox="0 0 88 42" fill="none" aria-hidden="true">
                            <path class="dashboard-sparkline" d="M2 31L17 30L30 29L43 26L56 12L67 27L78 25L86 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.ml.reentrenamiento.index') }}" class="grid min-h-[92px] grid-cols-[44px_1fr_74px] items-center gap-3 rounded-md bg-[#fff6e8] p-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-white text-amber-700 shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20 12C20 16.4 16.4 20 12 20C8.7 20 5.8 18 4.6 15.1M4 12C4 7.6 7.6 4 12 4C15.3 4 18.2 6 19.4 8.9M19.6 4.9V8.9H15.6M4.4 19.1V15.1H8.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-xs font-black text-amber-800">Reentrenamiento</span>
                            <span class="block text-lg font-black leading-tight text-[#211920]">Controlado</span>
                            <span class="block text-[11px] font-semibold text-amber-700">Aprobacion requerida</span>
                        </span>
                        <svg class="h-12 w-full text-amber-300" viewBox="0 0 88 42" fill="none" aria-hidden="true">
                            <path class="dashboard-sparkline" d="M2 34L14 19L25 31L37 27L49 9L61 29L73 15L86 25" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                </div>
            </article>
        </div>
    </section>
@endsection
