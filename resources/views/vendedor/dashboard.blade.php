@extends('layouts.app')

@section('content')
    @php
        $overview = $metrics['overview'];
        $operacion = $metrics['operacion'];
        $pedidos = $metrics['pedidos_recientes'];
        $quickLinks = collect($metrics['quick_links'])
            ->map(function (array $link) {
                return match ($link['title']) {
                    'Productos' => $link + ['icon' => 'bag', 'tone' => 'rose'],
                    'Inventario' => $link + ['icon' => 'box', 'tone' => 'green'],
                    'Pedidos' => $link + ['icon' => 'clipboard', 'tone' => 'orange'],
                    default => $link + ['icon' => 'chart', 'tone' => 'blue'],
                };
            });

        $quickIconPaths = [
            'bag' => '<path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/><path d="M12 12v9"/>',
            'clipboard' => '<path d="M9 4h6l1 2h3v15H5V6h3l1-2Z"/><path d="M9 11h6"/><path d="M9 15h6"/>',
            'chart' => '<path d="M4 19h16"/><path d="M7 15l4-4 3 3 5-7"/><path d="M17 7h2v2"/>',
        ];

        $toneClasses = [
            'rose' => ['icon' => 'bg-rose-50 text-atlantia-wine', 'glow' => 'bg-atlantia-blush/50'],
            'green' => ['icon' => 'bg-emerald-50 text-emerald-600', 'glow' => 'bg-emerald-50'],
            'orange' => ['icon' => 'bg-orange-50 text-orange-500', 'glow' => 'bg-orange-50'],
            'blue' => ['icon' => 'bg-sky-50 text-sky-500', 'glow' => 'bg-sky-50'],
            'violet' => ['icon' => 'bg-violet-50 text-violet-500', 'glow' => 'bg-violet-50'],
        ];

        $stats = [
            ['label' => 'Ventas de hoy', 'value' => 'Q' . number_format($overview['ventas_hoy'], 2), 'hint' => 'Ingresos registrados este dia', 'icon' => 'wallet', 'tone' => 'rose'],
            ['label' => 'Ventas del mes', 'value' => 'Q' . number_format($overview['ventas_mes'], 2), 'hint' => 'Acumulado mensual', 'icon' => 'growth', 'tone' => 'green'],
            ['label' => 'Pedidos pendientes', 'value' => number_format($overview['pedidos_pendientes']), 'hint' => 'Requieren atencion', 'icon' => 'clipboard', 'tone' => 'orange'],
            ['label' => 'Productos publicados', 'value' => number_format($overview['productos_publicados']), 'hint' => 'Visibles en catalogo', 'icon' => 'bag', 'tone' => 'blue'],
        ];

        $operationCards = [
            'productos_activos' => ['label' => 'Productos activos', 'hint' => 'En venta', 'tone' => 'green', 'icon' => 'tag'],
            'stock_bajo' => ['label' => 'Stock bajo', 'hint' => 'Productos', 'tone' => 'orange', 'icon' => 'alert'],
            'comisiones_pendientes' => ['label' => 'Comisiones pendientes', 'hint' => 'Por cobrar', 'tone' => 'violet', 'icon' => 'coin'],
            'sugerencias_reabasto' => ['label' => 'Sugerencias de reabasto', 'hint' => 'Pendientes de revisar', 'tone' => 'blue', 'icon' => 'box'],
        ];

        $smallIconPaths = [
            'wallet' => '<path d="M4 7h14a2 2 0 0 1 2 2v8H6a2 2 0 0 1-2-2V7Z"/><path d="M16 11h5v4h-5a2 2 0 0 1 0-4Z"/><path d="M4 7a2 2 0 0 1 2-2h10"/>',
            'growth' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-7"/><path d="M17 7h2v2"/>',
            'clipboard' => '<path d="M9 4h6l1 2h3v15H5V6h3l1-2Z"/><path d="M9 11h6"/><path d="M9 15h4"/>',
            'bag' => '<path d="M7 8V6a5 5 0 0 1 10 0v2"/><path d="M5 8h14l-1 11H6L5 8Z"/>',
            'tag' => '<path d="m20 13-7 7L4 11V4h7l9 9Z"/><circle cx="8" cy="8" r="1.5"/>',
            'alert' => '<path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5"/><path d="M12 17h.01"/>',
            'coin' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v8"/><path d="M9.5 10.5c0-1.2 1.1-2 2.5-2s2.5.8 2.5 2-1.1 1.8-2.5 1.8-2.5.6-2.5 1.8 1.1 2 2.5 2 2.5-.8 2.5-2"/>',
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/><path d="M12 12v9"/>',
            'heart' => '<path d="M20.8 8.6c0 5.1-8.8 10.4-8.8 10.4S3.2 13.7 3.2 8.6A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.4Z"/>',
            'receipt' => '<path d="M6 3v18l2-1 2 1 2-1 2 1 2-1 2 1V3Z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        ];
    @endphp

    <section class="mx-auto max-w-[1220px] space-y-4 py-1 sm:space-y-5">
        <div class="rounded-lg border border-atlantia-rose/15 bg-white px-4 py-5 shadow-[0_14px_45px_rgba(122,31,61,0.06)] sm:px-6 lg:px-7">
            <div class="grid gap-5 xl:grid-cols-[1.05fr_2fr] xl:items-center">
                <div>
                    <p class="text-sm font-black text-atlantia-wine">¡Bienvenido de nuevo!</p>
                    <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Panel de vendedor</h1>
                    <p class="mt-3 max-w-md text-sm leading-6 text-atlantia-ink/60">
                        Control comercial de tu tienda, pedidos, inventario y predicciones.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($quickLinks as $link)
                        @php($tone = $toneClasses[$link['tone']])
                        <a href="{{ $link['route'] }}" class="group min-h-[10rem] rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-atlantia-wine/35 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <span class="{{ $tone['icon'] }} grid h-10 w-10 shrink-0 place-items-center rounded-lg">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $quickIconPaths[$link['icon']] !!}
                                    </svg>
                                </span>
                                <span class="text-atlantia-ink/45 transition group-hover:translate-x-0.5 group-hover:text-atlantia-wine" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-5 text-sm font-black text-atlantia-ink">{{ $link['title'] }}</p>
                            <p class="mt-2 text-xs leading-5 text-atlantia-ink/58">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                @php($tone = $toneClasses[$stat['tone']])
                <article class="min-h-[8.25rem] rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.04)]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="{{ $tone['glow'] }} grid h-11 w-11 shrink-0 place-items-center rounded-full">
                                <span class="{{ $tone['icon'] }} grid h-8 w-8 place-items-center rounded-full">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $smallIconPaths[$stat['icon']] !!}
                                    </svg>
                                </span>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-black text-atlantia-ink">{{ $stat['label'] }}</p>
                                <p class="mt-3 text-2xl font-black leading-none text-atlantia-ink">{{ $stat['value'] }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-black text-atlantia-ink/24">--</span>
                    </div>
                    <p class="mt-3 pl-14 text-xs leading-5 text-atlantia-ink/55">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_1fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_12px_32px_rgba(42,16,24,0.04)] sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-atlantia-blush/60 text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                {!! $smallIconPaths['receipt'] !!}
                            </svg>
                        </span>
                        <h2 class="text-base font-black text-atlantia-ink">Pedidos recientes</h2>
                    </div>
                    <a href="{{ route('vendedor.pedidos.index') }}" class="text-xs font-black text-atlantia-wine transition hover:text-atlantia-wine-700">Ver pedidos</a>
                </div>

                @if ($pedidos->isEmpty())
                    <div class="grid min-h-[14.75rem] place-items-center px-4 py-6 text-center">
                        <div>
                            <div class="mx-auto h-24 w-24">
                                <svg viewBox="0 0 120 120" fill="none" aria-hidden="true">
                                    <path d="M31 48 60 33l29 15-29 16-29-16Z" fill="#E9A6B5"/>
                                    <path d="m31 48 29 16v35L31 81V48Z" fill="#D7748B"/>
                                    <path d="m89 48-29 16v35l29-18V48Z" fill="#F1B9C4"/>
                                    <path d="m45 40 15-7 12 18-16 8-11-19Z" fill="#F8CDD5"/>
                                    <path d="m75 40-15-7-12 18 16 8 11-19Z" fill="#D98798"/>
                                    <path d="M60 64v35" stroke="#C75F7B" stroke-width="3"/>
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-black text-atlantia-ink">Aun no hay pedidos recientes.</p>
                            <p class="mt-1 text-xs leading-5 text-atlantia-ink/55">Los pedidos apareceran aqui cuando recibas nuevos.</p>
                        </div>
                    </div>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-atlantia-rose/15">
                                @foreach ($pedidos as $pedido)
                                    <tr>
                                        <td class="py-3">
                                            <p class="font-black text-atlantia-ink">{{ $pedido->numero_pedido }}</p>
                                            <p class="text-xs text-atlantia-ink/55">{{ $pedido->created_at?->format('d/m/Y H:i') }}</p>
                                        </td>
                                        <td class="py-3 font-black text-atlantia-wine">Q{{ number_format((float) $pedido->total, 2) }}</td>
                                        <td class="py-3 text-right">
                                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                                                {{ str_replace('_', ' ', $pedido->estadoValor()) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/65 p-4 shadow-[0_12px_32px_rgba(42,16,24,0.04)] sm:p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-white text-atlantia-wine shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            {!! $smallIconPaths['heart'] !!}
                        </svg>
                    </span>
                    <h2 class="text-base font-black text-atlantia-ink">Salud operativa</h2>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($operationCards as $key => $card)
                        @php($tone = $toneClasses[$card['tone']])
                        <div class="min-h-[6.5rem] rounded-lg border border-atlantia-rose/10 bg-white p-4 shadow-sm">
                            <div class="flex gap-3">
                                <span class="{{ $tone['icon'] }} grid h-9 w-9 shrink-0 place-items-center rounded-full">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $smallIconPaths[$card['icon']] !!}
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-black leading-5 text-atlantia-ink">{{ $card['label'] }}</p>
                                    <p class="mt-1 text-2xl font-black leading-none text-atlantia-ink">{{ number_format((float) ($operacion[$key] ?? 0)) }}</p>
                                    <p class="mt-1 text-xs text-atlantia-ink/55">{{ $card['hint'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
@endsection
