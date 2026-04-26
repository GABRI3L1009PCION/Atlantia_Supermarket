@php
    $overview = $metrics['overview'] ?? [];
    $operacion = $metrics['operacion'] ?? [];
    $alerts = $metrics['alerts'] ?? [];
    $recentOrders = $metrics['recent_orders'] ?? collect();
    $monthlySales = $metrics['monthly_sales'] ?? collect();
    $notifications = $metrics['notifications'] ?? collect();
    $courierStatus = $metrics['courier_status'] ?? [];
    $quickLinks = $metrics['quick_links'] ?? collect();
    $maxSale = max(1, (float) collect($monthlySales)->max('total'));
@endphp

<section wire:poll.60s="refreshMetrics" class="space-y-5" aria-labelledby="dashboard-metricas-title">
    <header class="flex flex-col gap-3 border-b border-atlantia-rose/20 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-atlantia-wine">Control central</p>
            <h2 id="dashboard-metricas-title" class="mt-1 text-2xl font-black text-atlantia-ink">
                Metricas Atlantia
            </h2>
            <p class="mt-1 text-sm text-atlantia-ink/65">
                Pedidos, alertas y operacion actual del marketplace.
            </p>
        </div>
        <p class="text-sm font-semibold text-atlantia-ink/60">
            Actualizado {{ $lastRefreshed }}
        </p>
    </header>

    @if (($alerts['total'] ?? 0) > 0)
        <div class="rounded-lg border border-atlantia-rose/25 bg-atlantia-blush px-4 py-3 text-sm font-semibold text-atlantia-wine">
            {{ number_format((int) $alerts['total']) }} alertas requieren atencion:
            {{ number_format((int) ($alerts['fraud'] ?? 0)) }} antifraude,
            {{ number_format((int) ($alerts['dte_rejected'] ?? 0)) }} DTE rechazados y
            {{ number_format((int) ($alerts['stock_low'] ?? 0)) }} productos con stock bajo.
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-atlantia-ink/60">Pedidos hoy</p>
            <p class="mt-3 text-3xl font-black text-atlantia-ink">
                {{ number_format((int) ($overview['pedidos_hoy'] ?? 0)) }}
            </p>
            <p class="mt-2 text-xs font-bold text-emerald-700">Operacion activa</p>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-atlantia-ink/60">Ventas hoy</p>
            <p class="mt-3 text-3xl font-black text-atlantia-ink">
                Q {{ number_format((float) ($overview['ventas_hoy'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs font-bold text-emerald-700">Ingresos confirmados</p>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-atlantia-ink/60">Ticket promedio</p>
            <p class="mt-3 text-3xl font-black text-atlantia-ink">
                Q {{ number_format((float) ($overview['ticket_promedio'] ?? 0), 2) }}
            </p>
            <p class="mt-2 text-xs font-bold text-atlantia-wine">Pedidos padre</p>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-atlantia-ink/60">Tasa entrega</p>
            <p class="mt-3 text-3xl font-black text-atlantia-ink">
                {{ number_format((float) ($overview['tasa_entrega'] ?? 0), 0) }}%
            </p>
            <p class="mt-2 text-xs font-bold text-amber-700">Cierre operativo</p>
        </article>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-black text-atlantia-ink">Operacion en vivo</h3>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase text-emerald-700">
                    Poll 60s
                </span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-atlantia-blush p-4 text-center">
                    <p class="text-3xl font-black text-atlantia-ink">
                        {{ number_format((int) ($operacion['pedidos_pendientes'] ?? 0)) }}
                    </p>
                    <p class="text-xs font-semibold text-atlantia-ink/60">En preparacion</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-4 text-center">
                    <p class="text-3xl font-black text-amber-700">
                        {{ number_format((int) ($courierStatus['en_ruta'] ?? 0)) }}
                    </p>
                    <p class="text-xs font-semibold text-atlantia-ink/60">En ruta</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 text-center">
                    <p class="text-3xl font-black text-emerald-700">
                        {{ number_format((int) ($courierStatus['disponibles'] ?? 0)) }}
                    </p>
                    <p class="text-xs font-semibold text-atlantia-ink/60">Repartidores disponibles</p>
                </div>
            </div>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-atlantia-ink">Alertas operativas</h3>
            <div class="mt-4 divide-y divide-atlantia-rose/15 text-sm">
                <a href="{{ route('admin.antifraude.index') }}" class="flex justify-between py-3">
                    <span class="font-bold text-rose-700">Antifraude</span>
                    <span class="font-black text-atlantia-ink">{{ number_format((int) ($alerts['fraud'] ?? 0)) }}</span>
                </a>
                <a href="{{ route('admin.dte.index') }}" class="flex justify-between py-3">
                    <span class="font-bold text-amber-700">DTE rechazados</span>
                    <span class="font-black text-atlantia-ink">{{ number_format((int) ($alerts['dte_rejected'] ?? 0)) }}</span>
                </a>
                <a href="{{ route('admin.productos.index') }}" class="flex justify-between py-3">
                    <span class="font-bold text-orange-700">Stock bajo</span>
                    <span class="font-black text-atlantia-ink">{{ number_format((int) ($alerts['stock_low'] ?? 0)) }}</span>
                </a>
                <a href="{{ route('admin.vendedores.index') }}" class="flex justify-between py-3">
                    <span class="font-bold text-sky-700">Vendedores por aprobar</span>
                    <span class="font-black text-atlantia-ink">{{ number_format((int) ($alerts['vendors_pending'] ?? 0)) }}</span>
                </a>
            </div>
        </article>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_0.95fr]">
        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-atlantia-ink">Ventas ultimos 6 meses</h3>
            <div class="mt-6 flex h-36 items-end gap-3 border-b border-atlantia-rose/20">
                @foreach ($monthlySales as $sale)
                    <div class="flex flex-1 flex-col items-center justify-end gap-2">
                        <div
                            class="w-full rounded-t bg-atlantia-wine/25"
                            style="height: {{ max(8, round(((float) $sale['total'] / $maxSale) * 100)) }}%;"
                        ></div>
                        <span class="text-[10px] font-semibold text-atlantia-ink/55">{{ $sale['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-sm text-atlantia-ink/65">
                Total 6m:
                <strong class="text-atlantia-ink">Q {{ number_format(collect($monthlySales)->sum('total'), 2) }}</strong>
            </p>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-atlantia-ink">Ultimos pedidos</h3>
            <div class="mt-4 divide-y divide-atlantia-rose/15">
                @forelse ($recentOrders as $order)
                    <div class="grid grid-cols-[0.75fr_1fr_0.85fr_auto] gap-3 py-3 text-sm">
                        <span class="text-atlantia-ink/55">#{{ $order['numero'] }}</span>
                        <span class="font-bold text-atlantia-ink">{{ $order['cliente'] }}</span>
                        <span class="font-semibold text-atlantia-wine">{{ str_replace('_', ' ', $order['estado']) }}</span>
                        <span class="font-black text-atlantia-ink">Q {{ number_format($order['total'], 2) }}</span>
                    </div>
                @empty
                    <p class="py-6 text-sm text-atlantia-ink/65">Sin pedidos recientes.</p>
                @endforelse
            </div>
        </article>
    </div>

    <div class="grid gap-4 xl:grid-cols-[0.85fr_1.15fr]">
        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-atlantia-ink">Notificaciones</h3>
            <div class="mt-4 space-y-3">
                @forelse ($notifications as $notification)
                    <div class="rounded-lg bg-atlantia-blush px-4 py-3 text-sm">
                        <p class="font-bold text-atlantia-wine">{{ $notification['title'] }}</p>
                        <p class="mt-1 text-atlantia-ink/70">{{ $notification['description'] }}</p>
                    </div>
                @empty
                    <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                        Sin notificaciones operativas pendientes.
                    </p>
                @endforelse
            </div>
        </article>

        <article class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-atlantia-ink">Accesos rapidos</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($quickLinks as $link)
                    <a href="{{ $link['route'] }}" class="rounded-lg border border-atlantia-rose/15 p-4 transition hover:bg-atlantia-blush">
                        <p class="font-bold text-atlantia-wine">{{ $link['title'] }}</p>
                        <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">{{ $link['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </article>
    </div>
</section>
