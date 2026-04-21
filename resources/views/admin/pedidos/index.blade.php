@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Pedidos" subtitle="Supervisa el flujo comercial, pagos y reparto de todo el marketplace." />

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1.4fr_0.7fr_0.7fr_auto]">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por numero, cliente, vendedor o UUID" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <select name="estado" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los estados</option>
                        @foreach (['pendiente', 'confirmado', 'preparando', 'en_ruta', 'entregado', 'cancelado'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                        @endforeach
                    </select>
                    <select name="metodo_pago" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los metodos</option>
                        @foreach (['efectivo', 'transferencia', 'tarjeta'] as $metodo)
                            <option value="{{ $metodo }}" @selected(request('metodo_pago') === $metodo)>{{ ucfirst($metodo) }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                </form>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">Pedido</th>
                            <th class="pb-3">Cliente</th>
                            <th class="pb-3">Vendedor</th>
                            <th class="pb-3">Total</th>
                            <th class="pb-3">Pago</th>
                            <th class="pb-3">Entrega</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($pedidos as $pedido)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $pedido->numero_pedido }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $pedido->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $pedido->cliente?->name }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $pedido->vendor?->business_name ?? 'Pedido consolidado' }}</td>
                                <td class="py-3 font-semibold text-atlantia-ink">Q{{ number_format((float) $pedido->total, 2) }}</td>
                                <td class="py-3">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">
                                        {{ $pedido->estado_pago }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $pedido->estado }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $pedido->deliveryRoute?->repartidor?->name ?? 'Sin asignar' }}</p>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.pedidos.show', $pedido->uuid) }}" class="font-semibold text-atlantia-wine hover:underline">
                                        Gestionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-atlantia-ink/60">No hay pedidos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $pedidos->links() }}</div>
        </div>
    </section>
@endsection
