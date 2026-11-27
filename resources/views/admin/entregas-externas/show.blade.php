@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php [$estadoTexto, $estadoClase] = $order->estadoVisible(); @endphp
    <section class="mx-auto max-w-5xl space-y-4 pb-10">
        <a href="{{ route('admin.entregas-externas.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-atlantia-wine hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Entregas externas
        </a>
        <header class="rounded-2xl bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_100%)] p-5 text-white shadow-[0_14px_34px_rgba(74,7,14,0.2)]">
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black">{{ $order->store_name }} → {{ $order->customer_name }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-white/75">
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-black text-atlantia-wine">{{ $estadoTexto }}</span>
                        @if ($order->external_reference) <span>Ref. {{ $order->external_reference }}</span> @endif
                    </p>
                </div>
                <span class="rounded-xl bg-white px-3 py-2 text-right text-atlantia-wine"><span class="block text-[11px] font-bold text-atlantia-ink/55">Costo del envío</span><span class="text-lg font-black">Q {{ number_format((float) $order->delivery_fee, 2) }}</span></span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[1fr_0.8fr]">
            <article class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
                <h2 class="text-lg font-black text-atlantia-ink">Ruta</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-atlantia-cream/60 p-3">
                        <p class="text-xs font-black uppercase text-atlantia-ink/45">Recogida</p>
                        <p class="mt-1 font-black text-atlantia-ink">{{ $order->store_name }}</p>
                        <p class="text-sm text-atlantia-ink/60">{{ $order->pickup_address }}</p>
                    </div>
                    <div class="rounded-xl bg-atlantia-cream/60 p-3">
                        <p class="text-xs font-black uppercase text-atlantia-ink/45">Entrega</p>
                        <p class="mt-1 font-black text-atlantia-ink">{{ $order->customer_name }}</p>
                        <p class="text-sm text-atlantia-ink/60">{{ $order->delivery_address }}</p>
                    </div>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-4">
                    <div class="rounded-md bg-atlantia-blush/60 p-3"><p class="text-xs text-atlantia-ink/55">Distancia (km)</p><p class="font-black text-atlantia-ink">{{ number_format((float) $order->estimated_distance_km, 1) }}</p></div>
                    <div class="rounded-md bg-atlantia-blush/60 p-3"><p class="text-xs text-atlantia-ink/55">Tiempo (min)</p><p class="font-black text-atlantia-ink">{{ $order->estimated_time_min }}</p></div>
                    <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Repartidor</p><p class="font-black text-emerald-700">{{ $order->repartidor?->name ?? 'Sin asignar' }}</p></div>
                    <div class="rounded-md bg-emerald-50 p-3"><p class="text-xs text-emerald-700/70">Gana el repartidor</p><p class="font-black text-emerald-700">Q {{ number_format((float) $order->courier_earning, 2) }}</p></div>
                </div>
            </article>

            <article class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
                <h2 class="text-lg font-black text-atlantia-ink">Ofrecer a un repartidor</h2>
                <form method="POST" action="{{ route('admin.entregas-externas.assign', $order) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-bold text-atlantia-ink">Repartidor
                        <select name="repartidor_id" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required>
                            <option value="">Elegir repartidor</option>
                            @foreach ($repartidores as $repartidor)
                                <option value="{{ $repartidor->id }}" @selected((int) $order->repartidor_id === (int) $repartidor->id)>{{ $repartidor->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-atlantia-ink">Cuánto le pagas (Q)
                        <input name="estimated_gain" type="number" min="0" step="0.01" value="{{ $order->courier_earning }}" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                    </label>
                    <button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Enviar oferta</button>
                </form>
            </article>
        </div>

        <article class="rounded-2xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
            <h2 class="text-lg font-black text-atlantia-ink">Ofertas enviadas</h2>
            <div class="mt-3 divide-y divide-atlantia-rose/10">
                @forelse ($order->offers as $offer)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="font-black text-atlantia-ink">{{ $offer->repartidor?->name ?? 'Repartidor' }}</p>
                            <p class="text-sm text-atlantia-ink/55">{{ ['pending' => 'Esperando respuesta', 'offered' => 'Esperando respuesta', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada', 'expired' => 'Venció', 'cancelled' => 'Cancelada'][$offer->status instanceof BackedEnum ? $offer->status->value : (string) $offer->status] ?? $offer->status }} · vence {{ $offer->expires_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="font-black text-emerald-700">Q {{ number_format((float) $offer->estimated_gain, 2) }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Todavía no has ofrecido esta entrega a ningún repartidor.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
