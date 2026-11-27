@extends('layouts.app')

@section('content')
    @php
        $maxHora = max(1, (float) $resumen['horas']->max('monto'));
        $tabla = 'w-full text-left text-sm';
        $th = 'px-4 py-2 text-xs uppercase text-atlantia-ink/55';
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-black text-atlantia-ink">Datos de mercado</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Qué se vende, cuándo y dónde. Solo cifras agregadas: no incluye datos personales de clientes. Colonias con menos de 3 pedidos no se muestran.</p>
            </div>
            <a href="{{ route('admin.datos-mercado.exportar', $filtros) }}" class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Descargar CSV</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-2xl border border-atlantia-rose/20 bg-white p-4">
            <label class="text-xs font-black">Desde<input type="date" name="desde" value="{{ $filtros['desde'] }}" class="mt-1 block rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
            <label class="text-xs font-black">Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="mt-1 block rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
            <label class="text-xs font-black">Categoría
                <select name="categoria_id" class="mt-1 block rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm"><option value="">Todas</option>@foreach ($categorias as $c)<option value="{{ $c->id }}" @selected($filtros['categoria_id'] === $c->id)>{{ $c->nombre }}</option>@endforeach</select></label>
            <label class="text-xs font-black">Tienda
                <select name="vendor_id" class="mt-1 block rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm"><option value="">Todas</option>@foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($filtros['vendor_id'] === $v->id)>{{ $v->business_name }}</option>@endforeach</select></label>
            <button class="rounded-lg border border-atlantia-wine px-4 py-2 text-sm font-black text-atlantia-wine">Aplicar</button>
        </form>

        <div class="grid gap-5 lg:grid-cols-2">
            <article class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <h2 class="border-b border-atlantia-rose/10 px-4 py-3 font-black text-atlantia-ink">Productos más vendidos</h2>
                <table class="{{ $tabla }}" data-tabla-productos>
                    <thead><tr><th class="{{ $th }}">Producto</th><th class="{{ $th }} text-right">Unidades</th><th class="{{ $th }} text-right">Monto</th></tr></thead>
                    <tbody>
                        @forelse ($resumen['productos']->take(15) as $fila)
                            <tr class="border-t border-atlantia-rose/10"><td class="px-4 py-2">{{ $fila->producto }}</td><td class="px-4 py-2 text-right">{{ number_format($fila->unidades) }}</td><td class="px-4 py-2 text-right">Q {{ number_format((float) $fila->monto, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-atlantia-ink/55">Sin ventas en este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </article>

            <article class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <h2 class="border-b border-atlantia-rose/10 px-4 py-3 font-black text-atlantia-ink">Ventas por hora del día</h2>
                <div class="space-y-1.5 p-4">
                    @forelse ($resumen['horas'] as $fila)
                        <div class="flex items-center gap-3 text-xs">
                            <span class="w-12 shrink-0 font-bold text-atlantia-ink/70">{{ str_pad((string) $fila->hora, 2, '0', STR_PAD_LEFT) }}:00</span>
                            <span class="h-3 rounded-full bg-atlantia-wine" style="width: {{ max(2, round((float) $fila->monto / $maxHora * 100)) }}%"></span>
                            <span class="shrink-0 text-atlantia-ink/60">Q {{ number_format((float) $fila->monto, 0) }} · {{ $fila->pedidos }} ped.</span>
                        </div>
                    @empty
                        <p class="text-center text-sm text-atlantia-ink/55">Sin datos.</p>
                    @endforelse
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <h2 class="border-b border-atlantia-rose/10 px-4 py-3 font-black text-atlantia-ink">Por categoría</h2>
                <table class="{{ $tabla }}">
                    <thead><tr><th class="{{ $th }}">Categoría</th><th class="{{ $th }} text-right">Unidades</th><th class="{{ $th }} text-right">Monto</th></tr></thead>
                    <tbody>
                        @foreach ($resumen['categorias'] as $fila)
                            <tr class="border-t border-atlantia-rose/10"><td class="px-4 py-2">{{ $fila->categoria }}</td><td class="px-4 py-2 text-right">{{ number_format($fila->unidades) }}</td><td class="px-4 py-2 text-right">Q {{ number_format((float) $fila->monto, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </article>

            <article class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm">
                <h2 class="border-b border-atlantia-rose/10 px-4 py-3 font-black text-atlantia-ink">Por colonia</h2>
                <table class="{{ $tabla }}">
                    <thead><tr><th class="{{ $th }}">Colonia</th><th class="{{ $th }} text-right">Pedidos</th><th class="{{ $th }} text-right">Monto</th></tr></thead>
                    <tbody>
                        @forelse ($resumen['colonias'] as $fila)
                            <tr class="border-t border-atlantia-rose/10"><td class="px-4 py-2">{{ $fila->colonia }} <span class="text-xs text-atlantia-ink/50">{{ $fila->municipio }}</span></td><td class="px-4 py-2 text-right">{{ $fila->pedidos }}</td><td class="px-4 py-2 text-right">Q {{ number_format((float) $fila->monto, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-atlantia-ink/55">Aún no hay suficientes pedidos por colonia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </article>
        </div>
    </section>
@endsection
