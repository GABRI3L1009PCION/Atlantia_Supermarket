@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Delivery como servicio</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Entregas externas</h1>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine">Admin</a>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Nueva solicitud</h2>
                <form method="POST" action="{{ route('admin.entregas-externas.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="text-sm font-bold text-atlantia-ink sm:col-span-2">Tienda<input name="store_name" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required></label>
                    <label class="text-sm font-bold text-atlantia-ink">Referencia<input name="external_reference" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Canal<input name="source_channel" value="manual" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink sm:col-span-2">Direccion recogida<input name="pickup_address" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required></label>
                    <label class="text-sm font-bold text-atlantia-ink">Lat recogida<input name="pickup_latitude" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Lng recogida<input name="pickup_longitude" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Cliente<input name="customer_name" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required></label>
                    <label class="text-sm font-bold text-atlantia-ink">Telefono cliente<input name="customer_phone" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink sm:col-span-2">Direccion entrega<input name="delivery_address" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required></label>
                    <label class="text-sm font-bold text-atlantia-ink">Lat entrega<input name="delivery_latitude" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Lng entrega<input name="delivery_longitude" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Pago<select name="payment_method" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"><option value="digital">Digital</option><option value="cash">Efectivo</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option></select></label>
                    <label class="text-sm font-bold text-atlantia-ink">Cobrar cliente<input name="amount_to_collect" type="number" min="0" step="0.01" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Pagar tienda<input name="amount_to_pay_store" type="number" min="0" step="0.01" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <label class="text-sm font-bold text-atlantia-ink">Propina<input name="tip_amount" type="number" min="0" step="0.01" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm"></label>
                    <button class="rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white sm:col-span-2">Crear solicitud</button>
                </form>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-atlantia-ink">Solicitudes</h2>
                    <form method="GET" class="flex gap-2">
                        <input name="q" value="{{ request('q') }}" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" placeholder="Buscar">
                        <button class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine">Filtrar</button>
                    </form>
                </div>

                <div class="mt-3 divide-y divide-atlantia-rose/10">
                    @forelse ($orders as $order)
                        <div class="py-3">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <a href="{{ route('admin.entregas-externas.show', $order) }}" class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-atlantia-blush px-2 py-1 text-xs font-black text-atlantia-wine">{{ $order->status }}</span>
                                        <span class="text-xs text-atlantia-ink/45">{{ $order->external_reference }}</span>
                                    </div>
                                    <p class="mt-2 truncate font-black text-atlantia-ink">{{ $order->store_name }} -> {{ $order->customer_name }}</p>
                                    <p class="truncate text-sm text-atlantia-ink/55">{{ $order->delivery_address }}</p>
                                </a>
                                <form method="POST" action="{{ route('admin.entregas-externas.assign', $order) }}" class="flex shrink-0 gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="repartidor_id" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required>
                                        <option value="">Repartidor</option>
                                        @foreach ($repartidores as $repartidor)
                                            <option value="{{ $repartidor->id }}" @selected((int) $order->repartidor_id === (int) $repartidor->id)>{{ $repartidor->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-md bg-atlantia-wine px-3 py-2 text-xs font-black text-white">Ofertar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin solicitudes externas.</p>
                    @endforelse
                </div>
                <div class="mt-3">{{ $orders->links() }}</div>
            </article>
        </div>
    </section>
@endsection
