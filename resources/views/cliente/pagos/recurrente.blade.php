@extends('layouts.marketplace')

@section('content')
    <section class="bg-[#fbf7f8] px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto w-full max-w-6xl">
            {{-- Pasos: el pago es el ultimo paso del mismo pedido --}}
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Paso final · Pago</p>
                    <h1 class="text-2xl font-black text-atlantia-ink sm:text-3xl">Paga tu pedido</h1>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Pedido {{ $pedido->numero_pedido }} · pago seguro con tarjeta</p>
                </div>
                @if ($venceEn)
                    <p class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800" data-pago-vence="{{ $venceEn->toIso8601String() }}">
                        Tienes <span data-pago-restante>{{ max(0, (int) now()->diffInMinutes($venceEn)) }} min</span> para completar el pago
                    </p>
                @endif
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_360px] lg:items-start">
                {{-- Formulario de tarjeta (Recurrente, embebido) --}}
                <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_14px_34px_rgba(59,6,12,0.06)]">
                    <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-4 py-3">
                        <p class="flex items-center gap-2 text-sm font-black text-atlantia-ink">
                            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Datos de pago protegidos
                        </p>
                        <span class="text-[11px] font-semibold text-atlantia-ink/50">Atlantia no ve ni guarda tu tarjeta</span>
                    </div>

                    <div class="px-4 py-6 text-center text-sm font-semibold text-atlantia-ink/55" data-pago-cargando>Cargando el formulario de pago…</div>
                    <div class="hidden px-4 py-3 text-sm font-bold" data-pago-aviso role="status" aria-live="polite"></div>
                    <div id="recurrente-checkout-container" class="min-h-[640px] [&_iframe]:!h-[820px] sm:[&_iframe]:!h-[760px]"></div>
                </div>

                {{-- Resumen del pedido --}}
                <aside class="rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-[0_14px_34px_rgba(59,6,12,0.06)] lg:sticky lg:top-24">
                    <h2 class="text-base font-black text-atlantia-ink">Resumen del pedido</h2>
                    <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                        @foreach ($items as $item)
                            <li class="flex justify-between gap-3">
                                <span class="text-atlantia-ink/75">{{ (int) $item->cantidad }} × {{ $item->producto_nombre_snapshot }}</span>
                                <span class="shrink-0 font-semibold text-atlantia-ink">Q {{ number_format((float) $item->subtotal, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex items-end justify-between border-t border-atlantia-rose/10 pt-3">
                        <span class="text-sm font-bold text-atlantia-ink">Total a pagar</span>
                        <span class="text-2xl font-black text-atlantia-wine">Q {{ number_format($total, 2) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-atlantia-ink/50">Incluye envío, IVA y propina si la agregaste.</p>

                    <a href="{{ $canceladoUrl }}" class="mt-4 block rounded-lg border border-atlantia-rose/25 px-4 py-2.5 text-center text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
                        Pagar después o cancelar
                    </a>
                    <p class="mt-3 text-[11px] leading-4 text-atlantia-ink/50">Si no completas el pago a tiempo, el pedido se cancela solo y los productos se liberan.</p>
                </aside>
            </div>
        </div>
    </section>

    <script src="{{ asset('vendor/recurrente-checkout/recurrente-checkout-0.0.5.js') }}" @nonce></script>
    <script @nonce>
        (() => {
            const retorno = @json($retornoUrl);
            const aviso = document.querySelector('[data-pago-aviso]');
            const mostrar = (texto, tipo) => {
                aviso.textContent = texto;
                aviso.className = 'px-4 py-3 text-sm font-bold ' + (tipo === 'error' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800');
            };

            const iniciar = () => {
                document.querySelector('[data-pago-cargando]')?.remove();
                window.RecurrenteCheckout.load({
                    url: @json($checkoutUrl),
                    development: @json($modoDesarrollo),
                    /* El navegador solo avisa: el servidor confirma el pago con Recurrente antes de aprobar el pedido. */
                    onSuccess: () => {
                        mostrar('Pago recibido. Confirmando tu pedido…', 'ok');
                        window.location.href = retorno;
                    },
                    onPaymentInProgress: () => {
                        mostrar('Tu pago está en proceso. Te llevamos a tu pedido…', 'ok');
                        window.setTimeout(() => { window.location.href = retorno; }, 2500);
                    },
                    onFailure: (data) => {
                        mostrar((data && (data.error || data.notice)) || 'El pago no se completó. Revisa los datos de tu tarjeta e inténtalo de nuevo.', 'error');
                    },
                });
            };

            if (window.RecurrenteCheckout) {
                iniciar();
            } else {
                mostrar('No pudimos cargar el formulario de pago. Recarga la página.', 'error');
            }

            /* Cuenta regresiva del tiempo para pagar. */
            const vence = document.querySelector('[data-pago-vence]');
            const restante = document.querySelector('[data-pago-restante]');
            if (vence && restante) {
                const fin = new Date(vence.dataset.pagoVence).getTime();
                const tick = () => {
                    const s = Math.max(0, Math.floor((fin - Date.now()) / 1000));
                    restante.textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')} min`;
                    if (s > 0) window.setTimeout(tick, 1000);
                };
                tick();
            }
        })();
    </script>
@endsection
