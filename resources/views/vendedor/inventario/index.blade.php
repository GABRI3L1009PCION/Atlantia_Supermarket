@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $initials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? 'V'))))
            ->filter()
            ->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->join('') ?: 'V';
        $summary = $dashboard['summary'];
        $lastUpdate = $dashboard['last_update'];
        $statCards = [
            ['label' => 'Stock actual', 'value' => $summary['stock_actual'], 'hint' => 'Unidades', 'tone' => 'rose', 'icon' => 'box'],
            ['label' => 'Stock disponible', 'value' => $summary['stock_disponible'], 'hint' => 'Unidades', 'tone' => 'orange', 'icon' => 'package'],
            ['label' => 'Stock minimo', 'value' => $summary['stock_minimo'], 'hint' => 'Unidades', 'tone' => 'amber', 'icon' => 'alert'],
            ['label' => 'Stock maximo', 'value' => $summary['stock_maximo'], 'hint' => 'Unidades', 'tone' => 'green', 'icon' => 'cube'],
            ['label' => 'Productos activos', 'value' => $summary['productos_activos'], 'hint' => 'Productos', 'tone' => 'blue', 'icon' => 'stack'],
        ];
        $toneClasses = [
            'rose' => 'bg-atlantia-blush text-atlantia-wine',
            'orange' => 'bg-orange-50 text-orange-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'green' => 'bg-emerald-50 text-emerald-600',
            'blue' => 'bg-sky-50 text-sky-600',
        ];
        $icons = [
            'box' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="M4 7.5v9L12 21l8-4.5v-9"/>',
            'package' => '<path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/>',
            'alert' => '<path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5"/><path d="M12 17h.01"/>',
            'cube' => '<path d="m21 16-9 5-9-5V8l9-5 9 5Z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
            'stack' => '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
        ];
        $inputClass = 'w-full rounded-md border border-atlantia-rose/25 bg-white px-2 py-1.5 text-xs font-bold text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20';
    @endphp

    <section class="mx-auto max-w-[1280px] space-y-5 pb-10">
        <div class="grid gap-4 lg:grid-cols-[1fr_330px] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-3xl font-black leading-tight text-atlantia-ink sm:text-4xl">Inventario</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-atlantia-ink/62">Stock actual, minimo y maximo.</p>
            </div>

            <article class="flex min-h-[5.5rem] items-center gap-4 rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-[0_14px_34px_rgba(42,16,24,0.07)]">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-atlantia-blush/70 text-atlantia-wine">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-sm font-black shadow-sm">{{ $initials }}</span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-black text-atlantia-ink">{{ $vendor?->business_name ?? $user?->name }}</span>
                    <span class="mt-1 block text-xs text-atlantia-ink/55">Vendedor local</span>
                </span>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Activo</span>
            </article>
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-atlantia-ink">Resumen de inventario</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/58">Informacion operativa actualizada del modulo.</p>
                </div>
                <div class="text-sm text-atlantia-ink/60 sm:text-right">
                    <p>Ultima actualizacion</p>
                    <p class="font-black text-atlantia-ink">{{ $lastUpdate ? $lastUpdate->format('d/m/Y H:i') : 'Sin movimientos' }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($statCards as $card)
                    <div class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-4">
                            <span class="{{ $toneClasses[$card['tone']] }} grid h-12 w-12 shrink-0 place-items-center rounded-lg">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    {!! $icons[$card['icon']] !!}
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs text-atlantia-ink/60">{{ $card['label'] }}</p>
                                <p class="mt-1 text-2xl font-black leading-none text-atlantia-ink">{{ number_format($card['value']) }}</p>
                                <p class="mt-1 text-xs text-atlantia-ink/55">{{ $card['hint'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <div class="grid gap-4 lg:grid-cols-[1fr_0.9fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-atlantia-ink">Productos con stock bajo</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/58">Productos que requieren atencion.</p>
                    </div>
                    <a href="#inventario-productos" class="text-xs font-black text-atlantia-wine hover:underline">Ver todos</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-atlantia-rose/15 text-left text-xs font-black text-atlantia-ink/55">
                                <th class="py-3">Producto</th>
                                <th class="py-3">SKU</th>
                                <th class="py-3">Stock actual</th>
                                <th class="py-3">Stock minimo</th>
                                <th class="py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-atlantia-rose/12">
                            @forelse ($dashboard['low_stock'] as $item)
                                @php($producto = $item->producto)
                                @php($imageUrl = $producto?->getFirstMediaUrl('productos', 'thumbnail') ?: $producto?->getFirstMediaUrl('productos') ?: ($producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null))
                                <tr>
                                    <td class="py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-md bg-atlantia-blush">
                                                @if ($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $producto?->nombre }}" class="h-full w-full object-contain">
                                                @else
                                                    <span class="font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto?->nombre ?? 'P', 0, 1)) }}</span>
                                                @endif
                                            </div>
                                            <span class="font-bold text-atlantia-ink">{{ $producto?->nombre }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 font-mono text-xs text-atlantia-ink/60">{{ $producto?->sku }}</td>
                                    <td class="py-3 font-black text-rose-700">{{ number_format($item->stock_actual) }}</td>
                                    <td class="py-3 text-atlantia-ink/70">{{ number_format($item->stock_minimo) }}</td>
                                    <td class="py-3"><span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-black text-orange-700 ring-1 ring-orange-200">Bajo stock</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-atlantia-ink/55">No hay productos con stock bajo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-atlantia-ink">Movimientos recientes</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/58">Ultimos cambios en inventario.</p>
                    </div>
                    <span class="text-xs font-black text-atlantia-wine">Ver todos</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['movements'] as $movement)
                        @php($positive = $movement['delta'] >= 0)
                        <div class="flex items-center gap-4 rounded-lg border border-atlantia-rose/15 bg-white p-4">
                            <span class="{{ $positive ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} grid h-10 w-10 shrink-0 place-items-center rounded-full">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    @if ($positive)
                                        <path d="M12 19V5"/><path d="m5 12 7-7 7 7"/>
                                    @else
                                        <path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>
                                    @endif
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-atlantia-ink/55">{{ $movement['type'] }}</p>
                                <p class="truncate text-sm font-black text-atlantia-ink">{{ $movement['product'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-atlantia-ink/55">{{ $movement['date']?->format('d/m/Y H:i') }}</p>
                                <span class="{{ $positive ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} mt-1 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ring-current/15">
                                    {{ $positive ? '+' : '' }}{{ $movement['delta'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-atlantia-rose/15 bg-atlantia-cream/50 p-4 text-sm text-atlantia-ink/60">Aun no hay movimientos recientes.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article id="inventario-productos" class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-atlantia-ink">Productos en inventario</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/58">Ajusta existencias sin editar la ficha comercial del producto.</p>
                </div>
                <span class="w-fit rounded-md bg-atlantia-blush px-3 py-1.5 text-xs font-black text-atlantia-wine">{{ $inventario->total() }} productos</span>
            </div>

            <div class="mt-5 grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-4">
                @forelse ($inventario as $item)
                    @php($producto = $item->producto)
                    @php($imageUrl = $producto?->getFirstMediaUrl('productos', 'thumbnail') ?: $producto?->getFirstMediaUrl('productos') ?: ($producto?->imagenPrincipal?->path ? \Illuminate\Support\Facades\Storage::url($producto->imagenPrincipal->path) : null))
                    @php($disponible = max(0, (int) $item->stock_actual - (int) $item->stock_reservado))
                    @php($isLow = $item->stock_actual <= $item->stock_minimo)
                    <article class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_10px_24px_rgba(68,32,50,0.08)]">
                        <div class="relative h-28 bg-gradient-to-br from-atlantia-cream via-white to-atlantia-blush/30 p-3">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $producto?->nombre }}" class="h-full w-full object-contain drop-shadow-md">
                            @else
                                <div class="grid h-full place-items-center">
                                    <div class="grid h-12 w-12 place-items-center rounded-xl bg-atlantia-blush text-lg font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($producto?->nombre ?? 'P', 0, 1)) }}</div>
                                </div>
                            @endif
                            <span class="{{ $isLow ? 'bg-orange-50 text-orange-700 ring-orange-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }} absolute right-2 top-2 rounded-full px-2 py-0.5 text-[9px] font-black shadow-sm ring-1">
                                {{ $isLow ? 'Bajo stock' : 'Normal' }}
                            </span>
                        </div>
                        <div class="space-y-3 p-3">
                            <div>
                                <h3 class="line-clamp-1 text-sm font-black text-atlantia-ink">{{ $producto?->nombre }}</h3>
                                <p class="mt-1 truncate font-mono text-[10px] font-semibold text-atlantia-ink/45">{{ $producto?->sku }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-atlantia-ink/45">{{ $producto?->categoria?->nombre ?? 'Sin categoria' }}</p>
                            </div>

                            <div class="grid grid-cols-3 gap-2 rounded-xl bg-atlantia-cream/45 p-2 text-center">
                                <div><p class="text-[9px] font-black uppercase text-atlantia-ink/45">Actual</p><p class="text-sm font-black text-atlantia-wine">{{ $item->stock_actual }}</p></div>
                                <div><p class="text-[9px] font-black uppercase text-atlantia-ink/45">Disp.</p><p class="text-sm font-black text-emerald-700">{{ $disponible }}</p></div>
                                <div><p class="text-[9px] font-black uppercase text-atlantia-ink/45">Reserv.</p><p class="text-sm font-black text-atlantia-ink">{{ $item->stock_reservado }}</p></div>
                            </div>

                            <form method="POST" action="{{ route('vendedor.inventario.update', $producto->uuid) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-3 gap-2">
                                    <label><span class="text-[10px] font-black text-atlantia-ink/55">Actual</span><input name="stock_actual" type="number" min="0" value="{{ $item->stock_actual }}" class="{{ $inputClass }}"></label>
                                    <label><span class="text-[10px] font-black text-atlantia-ink/55">Min.</span><input name="stock_minimo" type="number" min="0" value="{{ $item->stock_minimo }}" class="{{ $inputClass }}"></label>
                                    <label><span class="text-[10px] font-black text-atlantia-ink/55">Max.</span><input name="stock_maximo" type="number" min="0" value="{{ $item->stock_maximo }}" class="{{ $inputClass }}"></label>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-atlantia-wine px-3 py-2 text-xs font-black text-white transition hover:bg-atlantia-wine-700">Actualizar stock</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream/60 px-6 py-12 text-center">
                        <p class="text-lg font-black text-atlantia-ink">Aun no hay inventario.</p>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Crea productos para empezar a controlar existencias.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-5">{{ $inventario->links() }}</div>
        </article>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <h2 class="text-lg font-black text-atlantia-ink">Alertas de inventario</h2>
            <p class="mt-1 text-sm text-atlantia-ink/58">Notificaciones importantes sobre tu inventario.</p>
            <div class="mt-4 flex flex-col gap-3 rounded-lg border border-orange-200 bg-orange-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-orange-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 4 9 16H3L12 4Z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-black text-atlantia-ink">{{ $dashboard['low_stock']->count() }} productos tienen stock bajo</p>
                        <p class="text-xs text-atlantia-ink/55">Revisa los productos para evitar quiebres de stock.</p>
                    </div>
                </div>
                <a href="#inventario-productos" class="inline-flex justify-center rounded-lg border border-atlantia-wine/30 bg-white px-5 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">Ver productos</a>
            </div>
        </article>
    </section>
@endsection
