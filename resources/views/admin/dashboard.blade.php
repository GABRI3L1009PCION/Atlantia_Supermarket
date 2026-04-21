@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $operacion = $metrics['operacion'];
        $alerts = $metrics['alerts'];
        $recentOrders = $metrics['recent_orders'];
        $monthlySales = $metrics['monthly_sales'];
        $courierStatus = $metrics['courier_status'];
        $maxSale = max(1, (float) $monthlySales->max('total'));
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        <div class="flex flex-col gap-4 border-b border-[#ead8df] pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#8b1d4d]">Control Central · Administracion</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-[#211920] sm:text-4xl">
                    Dashboard general
                </h1>
                <p class="mt-1 text-sm text-[#5f555c]">Operacion, pedidos y control del marketplace</p>
            </div>
            <p class="text-sm font-medium text-[#7c7178]">
                Actualizado {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>

        @if ($alerts['total'] > 0)
            <a
                href="{{ route('admin.antifraude.index') }}"
                class="flex flex-col justify-between gap-3 rounded-lg border border-[#f4ccd8] bg-[#fff0f4] px-4 py-3 text-sm font-bold text-[#4f0b29] shadow-sm sm:flex-row sm:items-center"
            >
                <span>
                    {{ $alerts['total'] }} alertas requieren atencion:
                    {{ $alerts['fraud'] }} antifraude,
                    {{ $alerts['dte_rejected'] }} DTE rechazados,
                    {{ $alerts['stock_low'] }} productos con stock bajo.
                </span>
                <span class="text-[#9a285a]">Revisar -></span>
            </a>
        @endif

        <div>
            <p class="mb-2 text-xs font-black uppercase tracking-wide text-[#8f828a]">
                Hoy · {{ now()->format('d M Y') }}
            </p>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                    <p class="text-sm text-[#6d6269]">Pedidos hoy</p>
                    <p class="mt-3 text-3xl font-black text-[#211920]">{{ number_format($overview['pedidos_hoy']) }}</p>
                    <p class="mt-2 text-xs font-bold text-emerald-700">Operacion activa</p>
                </article>

                <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                    <p class="text-sm text-[#6d6269]">Ventas hoy</p>
                    <p class="mt-3 text-3xl font-black text-[#211920]">Q {{ number_format($overview['ventas_hoy'], 2) }}</p>
                    <p class="mt-2 text-xs font-bold text-emerald-700">Ingresos confirmados</p>
                </article>

                <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                    <p class="text-sm text-[#6d6269]">Ticket promedio</p>
                    <p class="mt-3 text-3xl font-black text-[#211920]">Q {{ number_format($overview['ticket_promedio'], 2) }}</p>
                    <p class="mt-2 text-xs font-bold text-[#8b1d4d]">Pedidos padre</p>
                </article>

                <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                    <p class="text-sm text-[#6d6269]">Tasa entrega</p>
                    <p class="mt-3 text-3xl font-black text-[#211920]">{{ number_format($overview['tasa_entrega'], 0) }}%</p>
                    <p class="mt-2 text-xs font-bold text-amber-700">Cierre operativo</p>
                </article>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
            <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-[#211920]">Operacion en vivo</h2>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase text-emerald-700">
                        En vivo
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-[#f5f0e8] p-4 text-center">
                        <p class="text-3xl font-black text-[#211920]">{{ number_format($operacion['pedidos_pendientes']) }}</p>
                        <p class="text-xs font-semibold text-[#6d6269]">En preparacion</p>
                    </div>

                    <div class="rounded-lg bg-[#fff3db] p-4 text-center">
                        <p class="text-3xl font-black text-[#9a5a00]">{{ number_format($courierStatus['en_ruta']) }}</p>
                        <p class="text-xs font-semibold text-[#6d6269]">En ruta</p>
                    </div>

                    <div class="rounded-lg bg-[#e9f7f0] p-4 text-center">
                        <p class="text-3xl font-black text-emerald-700">{{ number_format($overview['tasa_entrega'], 0) }}%</p>
                        <p class="text-xs font-semibold text-[#6d6269]">Entregados</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 border-t border-[#ead8df] pt-4 sm:grid-cols-2">
                    <p class="text-sm text-[#5f555c]">
                        Repartidores:
                        <strong class="text-[#211920]">{{ number_format($courierStatus['total']) }} activos</strong>
                        / {{ number_format($courierStatus['disponibles']) }} disponibles
                    </p>
                    <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-bold text-[#8b1d4d] hover:underline">
                        {{ number_format($operacion['pedidos_pendientes']) }} pedidos pendientes por revisar
                    </a>
                </div>
            </article>

            <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black text-[#211920]">Alertas operativas</h2>

                <div class="mt-4 divide-y divide-[#ead8df] text-sm">
                    <a href="{{ route('admin.antifraude.index') }}" class="flex justify-between py-3">
                        <span class="font-bold text-rose-700">Antifraude</span>
                        <span class="font-black text-[#211920]">{{ $alerts['fraud'] }}</span>
                    </a>
                    <a href="{{ route('admin.dte.index') }}" class="flex justify-between py-3">
                        <span class="font-bold text-amber-700">DTE rechazados</span>
                        <span class="font-black text-[#211920]">{{ $alerts['dte_rejected'] }}</span>
                    </a>
                    <a href="{{ route('admin.productos.index') }}" class="flex justify-between py-3">
                        <span class="font-bold text-orange-700">Stock bajo</span>
                        <span class="font-black text-[#211920]">{{ $alerts['stock_low'] }}</span>
                    </a>
                    <a href="{{ route('admin.vendedores.index') }}" class="flex justify-between py-3">
                        <span class="font-bold text-sky-700">Vendedores por aprobar</span>
                        <span class="font-black text-[#211920]">{{ $alerts['vendors_pending'] }}</span>
                    </a>
                    <a href="{{ route('admin.ml.monitor') }}" class="flex justify-between py-3">
                        <span class="font-bold text-emerald-700">Monitor ML</span>
                        <span class="font-black text-emerald-700">{{ $alerts['ml_status'] }}</span>
                    </a>
                </div>
            </article>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_0.95fr]">
            <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-[#211920]">Ventas ultimos 6 meses</h2>
                    <a href="{{ route('admin.reportes.index') }}" class="text-sm font-bold text-[#8b1d4d] hover:underline">
                        Ver reportes ->
                    </a>
                </div>

                <div class="mt-6 flex h-36 items-end gap-3 border-b border-[#ead8df]">
                    @foreach ($monthlySales as $sale)
                        <div class="flex flex-1 flex-col items-center justify-end gap-2">
                            <div
                                class="w-full rounded-t bg-[#9a285a]/25"
                                style="height: {{ max(8, round(((float) $sale['total'] / $maxSale) * 100)) }}%;"
                            ></div>
                            <span class="text-[10px] font-semibold text-[#8f828a]">{{ $sale['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="mt-4 text-sm text-[#5f555c]">
                    Total 6m:
                    <strong class="text-[#211920]">Q {{ number_format($monthlySales->sum('total'), 2) }}</strong>
                </p>
            </article>

            <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-[#211920]">Ultimos pedidos</h2>
                    <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-bold text-[#8b1d4d] hover:underline">
                        Ver todos ->
                    </a>
                </div>

                <div class="mt-4 divide-y divide-[#ead8df]">
                    @forelse ($recentOrders as $order)
                        <div class="grid grid-cols-[0.7fr_1fr_1fr_auto] gap-3 py-3 text-sm">
                            <span class="text-[#8f828a]">#{{ $order['numero'] }}</span>
                            <span class="font-bold text-[#211920]">{{ $order['cliente'] }}</span>
                            <span class="font-semibold text-[#8b1d4d]">{{ str_replace('_', ' ', $order['estado']) }}</span>
                            <span class="font-black text-[#211920]">Q {{ number_format($order['total'], 2) }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-[#6d6269]">Sin pedidos recientes.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="rounded-lg border border-[#e6d7de] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-[#211920]">Inteligencia del negocio</h2>
                <span class="text-xs font-bold text-[#8f828a]">Powered by ML</span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('admin.ml.monitor') }}" class="rounded-lg bg-[#f1eefc] p-4">
                    <p class="text-xs font-bold text-[#4d4085]">Monitor ML</p>
                    <p class="mt-2 text-2xl font-black text-[#211920]">Estable</p>
                    <p class="text-xs font-semibold text-[#4d4085]">Drift bajo revision</p>
                </a>

                <a href="{{ route('admin.antifraude.index') }}" class="rounded-lg bg-[#fff0f4] p-4">
                    <p class="text-xs font-bold text-[#9a285a]">Antifraude</p>
                    <p class="mt-2 text-2xl font-black text-[#211920]">{{ $alerts['fraud'] }} casos</p>
                    <p class="text-xs font-semibold text-[#9a285a]">Revision pendiente</p>
                </a>

                <a href="{{ route('admin.dte.index') }}" class="rounded-lg bg-[#e9f7f0] p-4">
                    <p class="text-xs font-bold text-emerald-800">DTE / FEL</p>
                    <p class="mt-2 text-2xl font-black text-[#211920]">{{ $alerts['dte_rejected'] }}</p>
                    <p class="text-xs font-semibold text-emerald-700">rechazos activos</p>
                </a>

                <a href="{{ route('admin.ml.reentrenamiento.index') }}" class="rounded-lg bg-[#fff3db] p-4">
                    <p class="text-xs font-bold text-amber-800">Reentrenamiento</p>
                    <p class="mt-2 text-2xl font-black text-[#211920]">Controlado</p>
                    <p class="text-xs font-semibold text-amber-700">Aprobacion requerida</p>
                </a>
            </div>
        </article>
    </section>
@endsection
