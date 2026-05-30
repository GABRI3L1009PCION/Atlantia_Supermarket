<div
    x-data="{ open: @entangle('abierto').live }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-on:keydown.escape.window="$wire.closeModal()"
    class="fixed inset-0 z-50 flex items-center justify-center px-3 py-4 sm:px-5 sm:py-6"
    style="display:none"
>
    <div class="absolute inset-0 bg-[rgba(28,19,24,0.52)] backdrop-blur-[3px]" wire:click="closeModal" aria-hidden="true"></div>

    @if ($producto)
        @php
            $legacyPath = $producto->imagenPrincipal?->path;
            $legacyImage = $legacyPath
                ? (str_starts_with($legacyPath, 'http') ? $legacyPath : asset('storage/' . $legacyPath))
                : ($producto->imagen && str_starts_with($producto->imagen, 'http') ? $producto->imagen : null);
            $mainImage = $producto->getFirstMediaUrl('productos', 'full') ?: $legacyImage;
            $tieneOferta = $producto->precio_oferta && (float) $producto->precio_oferta < (float) $producto->precio_base;
            $precioMostrar = $tieneOferta ? $producto->precio_oferta : $producto->precio_base;
            $descuento = $tieneOferta ? round((1 - $producto->precio_oferta / $producto->precio_base) * 100) : 0;
            $stockActual = (int) ($producto->inventario?->stock_actual ?? 0);
            $enStock = $stockActual > 0;
            $unidadResumen = trim(collect([
                $producto->peso_gramos
                    ? ($producto->peso_gramos >= 1000
                        ? rtrim(rtrim(number_format($producto->peso_gramos / 1000, 2, '.', ''), '0'), '.') . ' kg'
                        : $producto->peso_gramos . ' g')
                    : null,
                $producto->unidad_medida ? 'por ' . $producto->unidad_medida : null,
            ])->filter()->implode(' '));
            $vendorName = $producto->vendor?->business_name ?? 'Atlantia Supermarket';
        @endphp

        <div
            class="relative z-10 grid w-full max-w-[840px] overflow-hidden rounded-[28px] border border-[#f1dfe5] bg-white sm:max-h-[88vh]"
            style="box-shadow: 0 20px 48px rgba(45, 20, 31, 0.16), 0 8px 20px rgba(45, 20, 31, 0.08)"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 sm:scale-[0.98]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            <div class="grid min-h-0 lg:grid-cols-[280px_1fr]">
                <div class="relative overflow-hidden bg-[linear-gradient(180deg,#fef8fa_0%,#f9eef2_100%)] p-3 sm:p-5 lg:h-auto lg:min-h-[220px] lg:max-h-none lg:p-3">
                    <div class="absolute left-3 top-3 z-20 sm:left-5 sm:top-5 lg:left-4 lg:top-4">
                        <livewire:cliente.wishlist-button
                            :producto-id="$producto->id"
                            :key="'modal-wishlist-' . $producto->id"
                        />
                    </div>

                    @if ($tieneOferta)
                        <div class="absolute right-14 top-3 z-20 sm:right-20 sm:top-5 lg:right-4 lg:top-4">
                            <span class="rounded-full bg-atlantia-wine px-2 py-0.5 text-[10px] font-black text-white shadow-sm sm:px-3 sm:py-1 sm:text-xs">
                                -{{ $descuento }}%
                            </span>
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click="closeModal"
                        class="absolute right-3 top-3 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-white/70 bg-white/95 text-[#8d274a] shadow-[0_10px_25px_rgba(111,17,45,0.14)] backdrop-blur transition hover:bg-white sm:right-5 sm:top-5 lg:hidden"
                        aria-label="Cerrar"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="flex min-h-[170px] items-center justify-center rounded-[22px] bg-[linear-gradient(180deg,#fffdfd_0%,#fbf1f4_100%)] shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] sm:min-h-[280px] lg:min-h-[190px]">
                        @if ($mainImage)
                            <img
                                src="{{ $mainImage }}"
                                alt="{{ $producto->nombre }}"
                                class="h-full max-h-[155px] w-full object-contain object-center sm:max-h-[250px] lg:max-h-[370px] lg:rounded-[20px] lg:object-cover lg:aspect-[4/5]"
                                loading="eager"
                            >
                        @else
                            <div class="flex h-full min-h-[190px] w-full items-center justify-center rounded-[20px] bg-[#f0dde4] text-sm font-semibold text-atlantia-ink/55">
                                Imagen no disponible
                            </div>
                        @endif
                    </div>
                </div>

                <div class="relative flex min-h-0 flex-col bg-white">
                    <div class="hidden justify-end px-3 pt-2 sm:px-4 sm:pt-3 lg:flex">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#ecd8df] bg-white text-[#60404c] transition hover:bg-[#fbf4f7] sm:h-9 sm:w-9"
                            aria-label="Cerrar"
                        >
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-3 sm:px-6 sm:pb-6 lg:px-5">
                        <div class="mx-auto flex max-w-[410px] flex-col gap-3">
                            <div class="flex items-center gap-2 text-atlantia-wine">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span class="text-[0.72rem] font-black uppercase tracking-[0.2em]">
                                    {{ $producto->categoria?->nombre ?? 'Producto' }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <h2 id="modal-title" class="text-[1.9rem] font-black leading-[0.92] text-[#2c1820] sm:text-[2.05rem]">
                                    {{ $producto->nombre }}
                                </h2>

                                <div class="flex flex-wrap items-end gap-x-3 gap-y-2">
                                    <span class="text-[2.4rem] font-black leading-none text-atlantia-wine sm:text-[2.05rem]">
                                        Q{{ number_format((float) $precioMostrar, 2) }}
                                    </span>
                                    @if ($tieneOferta)
                                        <span class="pb-1 text-base font-semibold text-[#b59aa4] line-through">
                                            Q{{ number_format((float) $producto->precio_base, 2) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2.5">
                                @if ($enStock)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[0.92rem] font-bold text-emerald-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] sm:text-sm">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                        {{ $stockActual }} en stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-700 sm:text-sm">
                                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                                        Agotado
                                    </span>
                                @endif

                                @if ($unidadResumen !== '')
                                    <span class="border-l border-[#ead3da] pl-3 text-[0.92rem] text-[#7f6a73] sm:text-sm">
                                        {{ $unidadResumen }}
                                    </span>
                                @endif
                            </div>

                            @if ($producto->descripcion)
                                <p class="max-w-[40ch] text-[0.92rem] leading-6 text-[#5e4d55] sm:text-[0.9rem] sm:leading-5">
                                    {{ $producto->descripcion }}
                                </p>
                            @endif

                            <div class="rounded-[22px] border border-[#efdbe2] bg-[linear-gradient(135deg,#fffdfd_0%,#fcf5f8_100%)] p-3.5 shadow-[0_10px_30px_rgba(123,17,50,0.06)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,#9b1746_0%,#6f112d_100%)] text-[1.45rem] font-black text-white shadow-[0_8px_20px_rgba(111,17,45,0.20)]">
                                        {{ mb_strtoupper(mb_substr($vendorName, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-[#a18a93]">Vendido por</p>
                                        <p class="truncate text-[1rem] font-bold text-[#3c202a]">{{ $vendorName }}</p>
                                    </div>
                                    <svg class="h-6 w-6 shrink-0 text-[#6b6065]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/>
                                    </svg>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('cliente.carrito.items.store') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="producto_id" value="{{ $producto->id }}">

                                <div class="space-y-2.5">
                                    <span class="text-[0.96rem] font-semibold text-[#2c1820]">Cantidad</span>

                                    <div class="flex items-center overflow-hidden rounded-[16px] border border-[#ead5dc] bg-white shadow-[0_8px_24px_rgba(123,17,50,0.04)]">
                                        <button
                                            type="button"
                                            class="flex h-14 flex-1 items-center justify-center text-[1.75rem] font-light text-atlantia-wine transition hover:bg-[#fbf3f6]"
                                            onclick="const i=document.getElementById('mq-{{ $producto->id }}');i.value=Math.max(1, +i.value - 1)"
                                            aria-label="Reducir cantidad"
                                        >
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3">
                                                <path stroke-linecap="round" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <input
                                            id="mq-{{ $producto->id }}"
                                            type="number"
                                            name="cantidad"
                                            value="1"
                                            min="1"
                                            max="{{ max(1, $stockActual) }}"
                                            class="h-14 w-18 border-x border-[#ead5dc] bg-white text-center text-[1.35rem] font-bold text-[#2c1820] focus:outline-none"
                                        >
                                        <button
                                            type="button"
                                            class="flex h-14 flex-1 items-center justify-center text-[1.75rem] font-light text-atlantia-wine transition hover:bg-[#fbf3f6]"
                                            onclick="const i=document.getElementById('mq-{{ $producto->id }}');i.value=Math.min(+i.max, +i.value + 1)"
                                            aria-label="Aumentar cantidad"
                                        >
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3">
                                                <path stroke-linecap="round" d="M12 4v16M4 12h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <button
                                        type="submit"
                                        @disabled(!$enStock)
                                        class="inline-flex h-14 flex-1 items-center justify-center gap-3 rounded-[18px] bg-[linear-gradient(135deg,#a31549_0%,#8f123b_52%,#780f31_100%)] px-6 text-[1.05rem] font-black text-white shadow-[0_16px_32px_rgba(123,17,50,0.26)] transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45 sm:h-10 sm:rounded-2xl sm:text-[0.9rem]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-8 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                        </svg>
                                        {{ $enStock ? 'Agregar al carrito' : 'Agotado' }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="closeModal"
                                        class="inline-flex h-14 items-center justify-center rounded-[18px] border border-[#e8c7d2] bg-white px-6 text-[1.05rem] font-bold text-atlantia-wine shadow-[0_10px_26px_rgba(111,17,45,0.06)] transition hover:bg-[#faf4f6] sm:h-10 sm:rounded-2xl sm:text-sm"
                                    >
                                        Volver
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
