@extends('layouts.marketplace')

@section('content')
    @php
        $items = $carrito->items;
        $subtotal = $items->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
        $itemsCount = $items->sum(fn ($item) => (int) $item->cantidad);
    @endphp

    {{-- El fondo arranca en blanco para unirse sin corte con la zona del buscador. --}}
    <section class="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#fbeef3_22rem,#fdf8fa_100%)] px-4 pb-12 pt-8 sm:px-6 lg:px-8">
        <svg class="pointer-events-none absolute inset-x-0 top-0 h-56 w-full text-white/70" viewBox="0 0 1440 220" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path d="M0 150C220 110 420 60 700 80s520 70 740 10V0H0Z" fill="currentColor" opacity=".55" />
            <path d="M0 190C260 150 520 120 820 140s460 30 620-20" stroke="currentColor" stroke-width="2" opacity=".9" />
        </svg>

        <div class="relative mx-auto max-w-7xl" data-carrito>
            <header class="flex items-center gap-4 sm:gap-5">
                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-atlantia-blush text-atlantia-wine shadow-[0_10px_24px_rgba(127,18,32,0.10)] sm:h-20 sm:w-20">
                    <svg class="h-8 w-8 sm:h-10 sm:w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h2l2.4 10.2a1.5 1.5 0 0 0 1.5 1.1h8.6a1.5 1.5 0 0 0 1.5-1.1L21 7H6.2" />
                        <circle cx="9.5" cy="19.5" r="1.3" />
                        <circle cx="17" cy="19.5" r="1.3" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-4xl font-black leading-tight text-atlantia-ink sm:text-5xl">Carrito</h1>
                    <p class="mt-1 text-sm text-atlantia-ink/65 sm:text-base">Productos seleccionados para tu compra.</p>
                </div>
            </header>

            @if ($items->isEmpty())
                <div class="mt-8 rounded-2xl border border-atlantia-rose/15 bg-white p-10 text-center shadow-[0_18px_44px_rgba(51,5,9,0.08)]">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </span>
                    <h2 class="mt-5 text-xl font-black text-atlantia-wine">Tu carrito est&aacute; vac&iacute;o</h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">Agrega productos del cat&aacute;logo para preparar tu pedido.</p>
                    <a href="{{ route('catalogo.index') }}" class="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-atlantia-wine px-6 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine/90">
                        Ver cat&aacute;logo
                    </a>
                </div>
            @else
                {{-- Ambas tarjetas se estiran a la misma altura para que queden niveladas. --}}
                <div class="mt-8 grid items-stretch gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                    {{-- Lista de productos --}}
                    <section class="flex flex-col rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_18px_44px_rgba(51,5,9,0.08)]">
                        <div class="mx-4 flex items-center gap-4 border-b border-atlantia-rose/20 py-4 sm:mx-5 sm:py-5">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 8h14l-1 12a1.5 1.5 0 0 1-1.5 1.4h-9A1.5 1.5 0 0 1 6 20Z" />
                                    <path d="M9 10V6a3 3 0 0 1 6 0v4" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-black text-atlantia-wine sm:text-xl">Productos en tu carrito ({{ $itemsCount }})</h2>
                        </div>

                        @php
                            /* Estilo DiDi: cada tienda es una entrega separada con su propio repartidor. */
                            $gruposTienda = $items->groupBy(fn ($item) => (int) ($item->producto?->vendor_id ?? 0));
                            $totalEntregas = $gruposTienda->count();
                            $pendientesMinimo = \App\Support\PedidoMinimo::pendientes($items)->keyBy(fn ($g) => (int) ($g['vendor']?->id ?? 0));
                        @endphp

                        @if ($totalEntregas > 1)
                            <div class="mx-4 mt-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:mx-5" data-aviso-entregas>
                                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 15h12V6H3v9Zm12-6h3l3 3v3h-6V9ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                <p><strong>Tu pedido llegará en {{ $totalEntregas }} entregas.</strong> Cada tienda envía lo suyo con su propio repartidor, por eso cada una lleva su envío. Si prefieres pagar un solo envío, deja productos de una sola tienda.</p>
                            </div>
                        @endif

                        <div class="mx-4 flex-1 sm:mx-5">
                            @foreach ($gruposTienda as $vendorId => $itemsTienda)
                            @php
                                $tienda = $itemsTienda->first()->producto?->vendor;
                                $cantidadTienda = (int) $itemsTienda->sum('cantidad');
                            @endphp
                            <div class="mt-4 overflow-hidden rounded-2xl border border-atlantia-rose/15" data-grupo-tienda="{{ $vendorId }}">
                                <div class="flex flex-wrap items-center justify-between gap-2 bg-atlantia-blush/45 px-4 py-2.5">
                                    <p class="flex items-center gap-2 text-sm font-black text-atlantia-wine">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16v10H4V10Zm-1-5h18l-1 5H4L3 5Zm6 15v-5h6v5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                        {{ $tienda?->business_name ?? 'Atlantia Store' }}
                                    </p>
                                    <span class="text-xs font-bold text-atlantia-ink/60">
                                        {{ $totalEntregas > 1 ? 'Entrega '.$loop->iteration.' de '.$totalEntregas.' · ' : '' }}{{ $cantidadTienda }} {{ $cantidadTienda === 1 ? 'producto' : 'productos' }} · <x-price :amount="$itemsTienda->sum(fn ($i) => (float) $i->precio_unitario_snapshot * (int) $i->cantidad)" class="font-black text-atlantia-ink" data-grupo-subtotal />
                                    </span>
                                </div>
                                @if ($pendientesMinimo->has($vendorId))
                                    @php
                                        $minimoTienda = $pendientesMinimo->get($vendorId);
                                        $avanceMinimo = $minimoTienda['minimo'] > 0 ? min(100, round($minimoTienda['subtotal'] / $minimoTienda['minimo'] * 100)) : 100;
                                    @endphp
                                    <div class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900" data-falta-minimo>
                                        <p class="font-bold">Agrega Q {{ number_format($minimoTienda['falta'], 2) }} más de esta tienda para completar su pedido mínimo de Q {{ number_format($minimoTienda['minimo'], 2) }}.</p>
                                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-amber-200"><span class="block h-full rounded-full bg-amber-500" style="width: {{ $avanceMinimo }}%"></span></div>
                                    </div>
                                @endif
                                <div class="divide-y divide-atlantia-rose/20 px-3 sm:px-4">
                            @foreach ($itemsTienda as $item)
                                @php
                                    $lineTotal = (float) $item->precio_unitario_snapshot * (int) $item->cantidad;
                                    $inventarioItem = $item->producto?->inventario;
                                    $maximoItem = $inventarioItem === null
                                        ? (int) $item->cantidad
                                        : max((int) $item->cantidad, min(99, (int) $inventarioItem->stock_actual - (int) $inventarioItem->stock_reservado));
                                    $imagePath = $item->producto?->imagenPrincipal?->path;
                                    $imageSrc = $imagePath
                                        ? (str_starts_with($imagePath, 'http') ? $imagePath : asset('storage/' . $imagePath))
                                        : null;
                                @endphp

                                <article class="grid grid-cols-[88px_minmax(0,1fr)] gap-4 py-6 transition-opacity sm:grid-cols-[150px_minmax(0,1fr)_auto] sm:gap-6 sm:pl-2" data-item data-precio="{{ number_format((float) $item->precio_unitario_snapshot, 2, '.', '') }}">
                                    <div class="grid aspect-[150/146] w-full place-items-center overflow-hidden rounded-2xl bg-atlantia-blush/70 p-2 sm:p-3">
                                        @if ($imageSrc)
                                            <img
                                                src="{{ $imageSrc }}"
                                                alt="{{ $item->producto?->nombre ?? 'Producto' }}"
                                                class="h-full w-full object-contain mix-blend-multiply"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="px-2 text-center text-xs font-semibold text-atlantia-ink/55">Sin imagen</span>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="text-lg font-black leading-snug text-atlantia-ink sm:text-xl">
                                            {{ $item->producto?->nombre ?? 'Producto no disponible' }}
                                        </h3>
                                        <p class="mt-1.5 text-sm text-atlantia-ink/75">
                                            Precio unitario:
                                            <x-price :amount="$item->precio_unitario_snapshot" class="font-black text-atlantia-ink" />
                                        </p>

                                        <form method="POST" action="{{ route('cliente.carrito.items.update', $item) }}" class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-3" data-cantidad-form>
                                            @csrf
                                            @method('PUT')
                                            <label for="cantidad-{{ $item->id }}" class="text-sm text-atlantia-ink/70">Cantidad</label>
                                            <div class="grid h-11 grid-cols-[44px_60px_44px] overflow-hidden rounded-xl border border-atlantia-rose/25 bg-white shadow-sm">
                                                <button
                                                    type="button"
                                                    class="grid place-items-center border-r border-atlantia-rose/20 bg-atlantia-blush/40 text-atlantia-wine transition hover:bg-atlantia-blush"
                                                    data-cart-step="-1"
                                                    data-cart-target="cantidad-{{ $item->id }}"
                                                    aria-label="Reducir cantidad"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                                                </button>
                                                <input
                                                    id="cantidad-{{ $item->id }}"
                                                    name="cantidad"
                                                    type="number"
                                                    min="1"
                                                    max="{{ $maximoItem }}"
                                                    value="{{ $item->cantidad }}"
                                                    data-cantidad-anterior="{{ $item->cantidad }}"
                                                    class="h-full w-full border-0 bg-white px-1 text-center text-base font-black text-atlantia-ink [appearance:textfield] focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                >
                                                <button
                                                    type="button"
                                                    class="grid place-items-center border-l border-atlantia-rose/20 bg-atlantia-blush/40 text-atlantia-wine transition hover:bg-atlantia-blush"
                                                    data-cart-step="1"
                                                    data-cart-target="cantidad-{{ $item->id }}"
                                                    aria-label="Aumentar cantidad"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                                </button>
                                            </div>
                                            <span class="text-xs text-atlantia-ink/50" data-cantidad-estado>Máx. {{ $maximoItem }}</span>
                                            <button type="submit" class="sr-only">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M21 12a9 9 0 0 1-15.5 6.2L3 16" />
                                                    <path d="M3 21v-5h5" />
                                                    <path d="M3 12a9 9 0 0 1 15.5-6.2L21 8" />
                                                    <path d="M21 3v5h-5" />
                                                </svg>
                                                Actualizar
                                            </button>
                                        </form>
                                    </div>

                                    <div class="col-span-2 flex items-center justify-between gap-4 border-t border-atlantia-rose/10 pt-3 sm:col-span-1 sm:flex-col sm:items-end sm:justify-start sm:gap-5 sm:border-0 sm:pt-6">
                                        <x-price :amount="$lineTotal" class="text-xl font-black text-atlantia-wine sm:text-2xl" data-linea-total />
                                        <form method="POST" action="{{ route('cliente.carrito.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-black text-red-600 transition hover:bg-red-50">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                            </div>
                            @endforeach
                        </div>

                        <div class="mx-4 border-t border-atlantia-rose/20 py-5 sm:mx-5">
                            <a href="{{ route('catalogo.index') }}" class="inline-flex items-center gap-3 pl-2 text-base font-black text-atlantia-wine transition hover:text-atlantia-wine/80">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                                Seguir comprando
                            </a>
                        </div>
                    </section>

                    {{-- Resumen del pedido --}}
                    <aside class="flex flex-col overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_18px_44px_rgba(51,5,9,0.10)]">
                        <div class="flex items-center gap-4 bg-[linear-gradient(100deg,#3a0004_0%,#5e0008_55%,#5e0c27_100%)] px-6 py-5 text-white">
                            <span class="grid h-12 w-12 place-items-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <ellipse cx="12" cy="5.5" rx="7" ry="2.5" />
                                    <path d="M5 5.5v4.5c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V5.5" />
                                    <path d="M5 10v4.5c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5V10" />
                                    <path d="M5 14.5V19c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-4.5" />
                                </svg>
                            </span>
                            <h2 class="text-lg font-black sm:text-xl">Resumen del pedido</h2>
                        </div>

                        <div class="px-6 py-5">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-base text-atlantia-ink/80">Subtotal</span>
                                <x-price :amount="$subtotal" class="text-lg font-black text-atlantia-ink" data-resumen-subtotal />
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-4 border-b border-atlantia-rose/20 pb-5">
                                <span class="text-base text-atlantia-ink/80">Env&iacute;o @if ($totalEntregas > 1)<span class="block text-xs font-bold text-atlantia-ink/55">{{ $totalEntregas }} entregas, una por tienda</span>@endif</span>
                                <span class="max-w-[170px] text-right text-sm italic leading-snug text-atlantia-ink/60">Se calcula al confirmar direcci&oacute;n</span>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-4 rounded-xl bg-atlantia-blush/70 px-4 py-3.5">
                                <span class="text-lg font-black text-atlantia-ink">Total estimado</span>
                                <x-price :amount="$subtotal" class="text-2xl font-black text-atlantia-wine" data-resumen-total />
                            </div>

                            @if ($pendientesMinimo->isNotEmpty())
                                <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900" data-bloqueo-minimo>Completa el pedido mínimo de cada tienda para continuar.</p>
                            @endif
                            <a href="{{ route('cliente.checkout.create') }}" data-ir-pagar @class([
                                'mt-4 flex h-14 w-full items-center justify-center gap-3 rounded-xl bg-[linear-gradient(180deg,#8d1a45_0%,#6b0f2f_100%)] px-4 text-lg font-black text-white shadow-[0_12px_26px_rgba(107,15,47,0.30)] transition hover:brightness-110',
                                'pointer-events-none opacity-45' => $pendientesMinimo->isNotEmpty(),
                            ]) @if ($pendientesMinimo->isNotEmpty()) aria-disabled="true" tabindex="-1" @endif>
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 4h2l2.4 10.2a1.5 1.5 0 0 0 1.5 1.1h8.6a1.5 1.5 0 0 0 1.5-1.1L21 7H6.2" />
                                    <circle cx="9.5" cy="19.5" r="1.3" />
                                    <circle cx="17" cy="19.5" r="1.3" />
                                </svg>
                                Realizar pedido
                            </a>

                            <p class="mt-4 flex items-center justify-center gap-2 text-sm text-atlantia-ink/70">
                                <svg class="h-5 w-5 text-atlantia-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        (() => {
            const contenedor = document.querySelector('[data-carrito]');
            if (!contenedor) return;

            const esperas = new Map();
            let cola = Promise.resolve();
            let enCurso = 0;

            const formato = (valor) => `Q ${valor.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            /* Recalcula en pantalla al instante (el cobro real siempre lo calcula el servidor). */
            const recalcular = () => {
                let total = 0;
                contenedor.querySelectorAll('[data-grupo-tienda]').forEach((grupo) => {
                    let subtotal = 0;
                    grupo.querySelectorAll('[data-item]').forEach((item) => {
                        const campo = item.querySelector('input[name="cantidad"]');
                        const cantidad = Math.min(Number(campo?.max || 99), Math.max(Number(campo?.min || 1), Math.round(Number(campo?.value) || 0)));
                        const linea = Number(item.dataset.precio || 0) * cantidad;
                        subtotal += linea;
                        const celda = item.querySelector('[data-linea-total]');
                        if (celda) celda.textContent = formato(linea);
                    });
                    const celda = grupo.querySelector('[data-grupo-subtotal]');
                    if (celda) celda.textContent = formato(subtotal);
                    total += subtotal;
                });
                contenedor.querySelectorAll('[data-resumen-subtotal], [data-resumen-total]').forEach((celda) => { celda.textContent = formato(total); });
            };

            /* Mientras haya cambios sin guardar, "Realizar pedido" espera. */
            const pagar = () => contenedor.querySelector('[data-ir-pagar]');
            const marcarPendiente = () => {
                const boton = pagar();
                if (!boton || boton.dataset.esperando) return;
                boton.dataset.esperando = '1';
                boton.classList.add('opacity-70', 'cursor-wait');
            };
            const liberar = () => {
                if (esperas.size > 0 || enCurso > 0) return;
                const boton = pagar();
                if (!boton) return;
                delete boton.dataset.esperando;
                boton.classList.remove('opacity-70', 'cursor-wait');
            };

            /* Trae la página del carrito y reemplaza su contenido: subtotales, total y pedido mínimo quedan al día. */
            const refrescar = async () => {
                if (esperas.size > 0) return; /* Hay más cambios en camino: se refresca después del último. */
                const respuesta = await fetch(window.location.href, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                if (!respuesta.ok) return;
                const nuevo = new DOMParser().parseFromString(await respuesta.text(), 'text/html').querySelector('[data-carrito]');
                if (nuevo) contenedor.innerHTML = nuevo.innerHTML;
                window.Livewire?.dispatch?.('carrito.actualizado');
            };

            const guardar = (form) => {
                const input = form.querySelector('input[name="cantidad"]');
                const estado = form.querySelector('[data-cantidad-estado]');
                if (!input) return;

                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const valor = Math.min(max, Math.max(min, Math.round(Number(input.value) || min)));
                input.value = valor;
                if (String(valor) === input.dataset.cantidadAnterior) return;

                if (estado) estado.textContent = 'Guardando…';
                enCurso += 1;

                cola = cola.then(async () => {
                    const respuesta = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (respuesta.status === 422) {
                        const datos = await respuesta.json().catch(() => ({}));
                        await refrescar();
                        const aviso = contenedor.querySelector(`#${CSS.escape(input.id)}`)?.closest('form')?.querySelector('[data-cantidad-estado]');
                        if (aviso) {
                            aviso.textContent = datos.message || datos.errors?.cantidad?.[0] || 'No se pudo cambiar la cantidad.';
                            aviso.className = 'text-xs font-bold text-red-600';
                        }
                        return;
                    }

                    if (!respuesta.ok) {
                        window.location.reload();
                        return;
                    }

                    await refrescar();
                }).catch(() => window.location.reload()).finally(() => {
                    enCurso -= 1;
                    liberar();
                });

                return cola;
            };

            const guardarTodo = () => {
                esperas.forEach((espera, form) => {
                    clearTimeout(espera);
                    guardar(form);
                });
                esperas.clear();
                return cola;
            };

            const programar = (form, espera = 300) => {
                recalcular();
                marcarPendiente();
                clearTimeout(esperas.get(form));
                esperas.set(form, setTimeout(() => {
                    esperas.delete(form);
                    guardar(form);
                    liberar();
                }, espera));
            };

            contenedor.addEventListener('click', async (evento) => {
                const boton = evento.target.closest('[data-ir-pagar]');
                if (!boton || !boton.dataset.esperando) return;

                evento.preventDefault();
                evento.stopImmediatePropagation();
                const texto = boton.lastChild;
                const original = texto?.textContent;
                if (texto) texto.textContent = ' Guardando cambios…';
                await guardarTodo();
                if (texto) texto.textContent = original;
                window.location.href = boton.href;
            }, true);

            contenedor.addEventListener('click', (evento) => {
                const boton = evento.target.closest('[data-cart-step]');
                if (!boton) return;

                const input = document.getElementById(boton.dataset.cartTarget);
                if (!input) return;

                const min = Number(input.min || 1);
                const max = Number(input.max || 99);
                const actual = Number(input.value || min);
                const siguiente = Math.min(max, Math.max(min, actual + Number(boton.dataset.cartStep || 0)));
                const estado = input.closest('form')?.querySelector('[data-cantidad-estado]');

                if (siguiente === actual && estado) {
                    estado.textContent = actual >= max ? `Solo hay ${max} disponibles` : `Mínimo ${min}`;
                    estado.className = 'text-xs font-bold text-amber-700';
                }

                input.value = siguiente;
                programar(input.closest('form'));
            });

            contenedor.addEventListener('input', (evento) => {
                if (evento.target.matches('[data-cantidad-form] input[name="cantidad"]') && evento.target.value !== '') {
                    programar(evento.target.closest('form'), 700);
                }
            });

            contenedor.addEventListener('change', (evento) => {
                if (evento.target.matches('[data-cantidad-form] input[name="cantidad"]')) {
                    programar(evento.target.closest('form'), 0);
                }
            });

            contenedor.addEventListener('submit', (evento) => {
                const form = evento.target.closest('[data-cantidad-form]');
                if (!form) return;
                evento.preventDefault();
                programar(form, 0);
            });
        })();
    </script>
@endpush
