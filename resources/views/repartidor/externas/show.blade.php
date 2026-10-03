@extends('layouts.app')

@section('content')
    @php
        $pickupMaps = $order->pickup_latitude && $order->pickup_longitude
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $order->pickup_latitude . ',' . $order->pickup_longitude
            : null;
        $deliveryMaps = $order->delivery_latitude && $order->delivery_longitude
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $order->delivery_latitude . ',' . $order->delivery_longitude
            : null;
        $pickupWaze = $order->pickup_latitude && $order->pickup_longitude
            ? 'https://waze.com/ul?ll=' . $order->pickup_latitude . ',' . $order->pickup_longitude . '&navigate=yes'
            : null;
        $deliveryWaze = $order->delivery_latitude && $order->delivery_longitude
            ? 'https://waze.com/ul?ll=' . $order->delivery_latitude . ',' . $order->delivery_longitude . '&navigate=yes'
            : null;
        $goToPickup = in_array($order->status, ['accepted', 'assigned', 'arrived_pickup', 'pickup_not_ready'], true);
        $goToCustomer = in_array($order->status, ['picked_up', 'arrived_customer'], true);
    @endphp

    <section class="mx-auto max-w-4xl space-y-4">
        <header class="rounded-lg bg-atlantia-wine p-4 text-white">
            <a href="{{ route('repartidor.externas.index') }}" class="text-xs font-black text-white/70">Entregas externas</a>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black">{{ $order->external_reference }}</h1>
                    <p class="text-sm text-white/70">{{ $order->store_name }} · {{ $order->status }}</p>
                </div>
                <span class="rounded-md bg-white px-3 py-2 text-sm font-black text-atlantia-wine">Q {{ number_format((float) $order->courier_earning, 2) }}</span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Recogida</p>
                <h2 class="mt-1 text-xl font-black text-atlantia-ink">{{ $order->store_name }}</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">{{ $order->pickup_address }}</p>
                @if ($order->pickup_notes)
                    <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ $order->pickup_notes }}</p>
                @endif
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @if ($pickupMaps)<a href="{{ $pickupMaps }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Google Maps</a>@endif
                    @if ($pickupWaze)<a href="{{ $pickupWaze }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Waze</a>@endif
                </div>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-ink/45">Entrega</p>
                <h2 class="mt-1 text-xl font-black text-atlantia-ink">{{ $order->customer_name }}</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">{{ $order->delivery_address }}</p>
                @if ($order->delivery_notes)
                    <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ $order->delivery_notes }}</p>
                @endif
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @if ($deliveryMaps)<a href="{{ $deliveryMaps }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Google Maps</a>@endif
                    @if ($deliveryWaze)<a href="{{ $deliveryWaze }}" target="_blank" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-center text-sm font-black text-atlantia-wine">Waze</a>@endif
                </div>
                @if ($order->customer_phone)
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <a href="tel:{{ $order->customer_phone }}" class="rounded-md bg-emerald-50 px-4 py-2 text-center text-sm font-black text-emerald-700">Llamar</a>
                        <a href="https://wa.me/502{{ preg_replace('/\D+/', '', $order->customer_phone) }}" target="_blank" class="rounded-md bg-emerald-50 px-4 py-2 text-center text-sm font-black text-emerald-700">WhatsApp</a>
                    </div>
                @endif
            </article>
        </div>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Efectivo</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-4">
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Cobrar cliente</p><p class="font-black text-atlantia-ink">Q {{ number_format((float) $order->amount_to_collect, 2) }}</p></div>
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Pagar tienda</p><p class="font-black text-atlantia-ink">Q {{ number_format((float) $order->amount_to_pay_store, 2) }}</p></div>
                <div class="rounded-md bg-slate-50 p-3"><p class="text-xs text-atlantia-ink/55">Cambio</p><p class="font-black text-atlantia-ink">Q {{ number_format((float) $order->change_required, 2) }}</p></div>
                <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Ganancia</p><p class="font-black text-emerald-700">Q {{ number_format((float) $order->courier_earning + (float) $order->tip_amount, 2) }}</p></div>
            </div>
            <form method="POST" action="{{ route('repartidor.externas.cash-issue', $order) }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                @csrf
                @method('PATCH')
                <input name="cash_notes" class="min-w-0 flex-1 rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="No tengo efectivo suficiente">
                <button class="rounded-md border border-amber-200 px-4 py-2 text-sm font-black text-amber-700">Reportar efectivo</button>
            </form>
        </article>

        <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Acciones</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @if ($goToPickup && ! $order->arrived_pickup_at)
                    <form method="POST" action="{{ route('repartidor.externas.arrived-pickup', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue a tienda</button></form>
                @endif
                @if (in_array($order->status, ['arrived_pickup', 'pickup_not_ready'], true))
                    <form method="POST" action="{{ route('repartidor.externas.picked-up', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido recogido</button></form>
                    <form method="POST" action="{{ route('repartidor.externas.pickup-not-ready', $order) }}">@csrf @method('PATCH')<input type="hidden" name="issue_reason" value="Pedido no listo"><button class="w-full rounded-md border border-amber-200 px-4 py-2.5 text-sm font-black text-amber-700">No esta listo</button></form>
                @endif
                @if ($goToCustomer && ! $order->arrived_customer_at)
                    <form method="POST" action="{{ route('repartidor.externas.arrived-customer', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Llegue al cliente</button></form>
                @endif
                @if ($order->status === 'arrived_customer')
                    <form method="POST" action="{{ route('repartidor.externas.deliver', $order) }}" enctype="multipart/form-data" class="space-y-2 sm:col-span-2">
                        @csrf
                        @method('PATCH')
                        <input name="confirmation_code" class="w-full rounded-md border border-atlantia-rose/25 px-3 py-2 text-sm" placeholder="Codigo de confirmacion">
                        <input type="file" name="proof_photo" accept="image/*" class="w-full rounded-md border border-atlantia-rose/25 p-2 text-sm">
                        <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Pedido entregado</button>
                    </form>
                @endif
            </div>
        </article>
    </section>
@endsection
