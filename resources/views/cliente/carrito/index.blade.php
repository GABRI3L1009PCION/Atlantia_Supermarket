@extends('layouts.marketplace')

@section('content')
    @php
        $items = $carrito->items;
        $subtotal = $items->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
        $itemsCount = $items->sum(fn ($item) => (int) $item->cantidad);
    @endphp

    <section class="relative overflow-hidden bg-white px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-atlantia-blush/70 to-transparent"></div>
        <div class="pointer-events-none absolute -top-20 left-0 h-44 w-[55%] rounded-br-[80%] bg-atlantia-blush/45"></div>

        <div class="relative mx-auto max-w-7xl">
            <header>
                <p class="text-xs font-black uppercase tracking-[0.08em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">Carrito</h1>
                <p class="mt-2 text-sm text-atlantia-ink/60">Productos seleccionados para tu compra.</p>
            </header>

            @if ($items->isEmpty())
                <div class="mt-8 rounded-xl border border-atlantia-rose/20 bg-white p-10 text-center shadow-[0_14px_34px_rgba(52,7,22,0.08)]">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </span>
                    <h2 class="mt-5 text-xl font-black text-atlantia-wine">Tu carrito est&aacute; vac&iacute;o</h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">Agrega productos del cat&aacute;logo para preparar tu pedido.</p>
                    <a href="{{ route('catalogo.index') }}" class="mt-6 inline-flex h-11 items-center justify-center rounded-lg bg-atlantia-wine px-6 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                        Ver cat&aacute;logo
                    </a>
                </div>
            @else
                <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-xl border border-atlantia-rose/20 bg-white shadow-[0_14px_34px_rgba(52,7,22,0.08)]">
                        <div class="flex items-center gap-3 border-b border-atlantia-rose/15 bg-white px-5 py-5">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                    <path d="M3 6h18" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">Productos en tu carrito ({{ $itemsCount }})</h2>
                        </div>

                        <div class="divide-y divide-atlantia-rose/15 px-5">
                            @foreach ($items as $item)
                                @php
                                    $lineTotal = (float) $item->precio_unitario_snapshot * (int) $item->cantidad;
                                    $imagePath = $item->producto?->imagenPrincipal?->path;
                                    $imageSrc = $imagePath
                                        ? (str_starts_with($imagePath, 'http') ? $imagePath : asset('storage/' . $imagePath))
                                        : null;
                                @endphp

                                <article class="grid gap-4 py-6 sm:grid-cols-[112px_minmax(0,1fr)_auto] sm:items-start">
                                    <div class="h-28 w-28 overflow-hidden rounded-lg border border-atlantia-rose/15 bg-atlantia-blush">
                                        @if ($imageSrc)
                                            <img
                                                src="{{ $imageSrc }}"
                                                alt="{{ $item->producto?->nombre ?? 'Producto' }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full items-center justify-center px-3 text-center text-xs font-semibold text-atlantia-ink/55">
                                                Sin imagen
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black uppercase tracking-[0.08em] text-atlantia-rose">
                                            {{ $item->producto?->vendor?->business_name ?? 'Atlantia Supermarket' }}
                                        </p>
                                        <h3 class="mt-1 text-lg font-black text-atlantia-ink">
                                            {{ $item->producto?->nombre ?? 'Producto no disponible' }}
                                        </h3>
                                        <p class="mt-2 text-sm text-atlantia-ink/70">
                                            Precio unitario:
                                            <x-price :amount="$item->precio_unitario_snapshot" class="font-black text-atlantia-ink" />
                                        </p>

                                        <form method="POST" action="{{ route('cliente.carrito.items.update', $item) }}" class="mt-5 flex flex-wrap items-center gap-3">
                                            @csrf
                                            @method('PUT')
                                            <label for="cantidad-{{ $item->id }}" class="text-xs font-semibold text-atlantia-ink/70">Cantidad</label>
                                            <div class="grid h-10 grid-cols-[36px_54px_36px] overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white">
                                                <button
                                                    type="button"
                                                    class="grid place-items-center bg-atlantia-blush/70 text-atlantia-wine transition hover:bg-atlantia-blush"
                                                    data-cart-step="-1"
                                                    data-cart-target="cantidad-{{ $item->id }}"
                                                    aria-label="Reducir cantidad"
                                                >
                                                    -
                                                </button>
                                                <input
                                                    id="cantidad-{{ $item->id }}"
                                                    name="cantidad"
                                                    type="number"
                                                    min="1"
                                                    max="99"
                                                    value="{{ $item->cantidad }}"
                                                    class="h-full border-0 bg-white px-2 text-center text-sm font-black text-atlantia-ink focus:ring-0"
                                                >
                                                <button
                                                    type="button"
                                                    class="grid place-items-center bg-atlantia-blush/70 text-atlantia-wine transition hover:bg-atlantia-blush"
                                                    data-cart-step="1"
                                                    data-cart-target="cantidad-{{ $item->id }}"
                                                    aria-label="Aumentar cantidad"
                                                >
                                                    +
                                                </button>
                                            </div>
                                            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-3 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M21 12a9 9 0 0 0-15.5-6.2L3 8" />
                                                    <path d="M3 3v5h5" />
                                                    <path d="M3 12a9 9 0 0 0 15.5 6.2L21 16" />
                                                    <path d="M21 21v-5h-5" />
                                                </svg>
                                                Actualizar
                                            </button>
                                        </form>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end sm:justify-start">
                                        <x-price :amount="$lineTotal" class="text-base font-black text-atlantia-ink" />
                                        <form method="POST" action="{{ route('cliente.carrito.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="M19 6l-1 14H6L5 6" />
                                                </svg>
                                                Quitar
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="border-t border-atlantia-rose/15 px-5 py-5">
                            <a href="{{ route('catalogo.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-atlantia-wine transition hover:text-atlantia-wine/80">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                                Seguir comprando
                            </a>
                        </div>
                    </section>

                    <aside class="h-fit overflow-hidden rounded-xl border border-atlantia-rose/20 bg-white shadow-[0_14px_34px_rgba(52,7,22,0.08)]">
                        <div class="flex items-center gap-3 border-b border-atlantia-rose/15 bg-atlantia-blush/60 px-5 py-5">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-atlantia-wine">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 3h6" />
                                    <path d="M8 7h8" />
                                    <path d="M7 11h10" />
                                    <path d="M6 15h12" />
                                    <path d="M5 19h14" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-black text-atlantia-wine">Resumen del pedido</h2>
                        </div>

                        <div class="space-y-4 px-5 py-5 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-atlantia-ink/75">Subtotal</span>
                                <x-price :amount="$subtotal" class="font-black text-atlantia-ink" />
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-atlantia-ink/75">Env&iacute;o</span>
                                <span class="max-w-[160px] text-right text-xs text-atlantia-ink/55">Se calcula al confirmar direcci&oacute;n</span>
                            </div>

                            <div class="border-t border-atlantia-rose/15 pt-4">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-black text-atlantia-ink">Total estimado</span>
                                    <x-price :amount="$subtotal" class="text-lg font-black text-atlantia-wine" />
                                </div>
                            </div>

                            <a href="{{ route('cliente.checkout.create') }}" class="flex h-12 w-full items-center justify-center rounded-lg bg-atlantia-wine px-4 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                                Realizar pedido
                            </a>

                            <p class="flex items-center justify-center gap-2 text-xs font-semibold text-atlantia-ink/45">
                                <svg class="h-4 w-4 text-atlantia-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                </svg>
                                Pago seguro y confirmaci&oacute;n inmediata
                            </p>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.querySelectorAll('[data-cart-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.cartTarget);
                if (!input) return;

                const step = Number(button.dataset.cartStep || 0);
                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const current = Number(input.value || min);

                input.value = Math.min(max, Math.max(min, current + step));
            });
        });
    </script>
@endpush
