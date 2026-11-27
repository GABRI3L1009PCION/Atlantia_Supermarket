@extends('layouts.marketplace')

@section('content')
    @php
        $mensajeInvitacion = "Te regalo Q ".number_format($recompensa, 2)." para tu primera compra en Atlantia Store, el súper a domicilio de Puerto Barrios y Santo Tomás: {$enlace}";
    @endphp

    <section class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Mi saldo e invitaciones" subtitle="Usa tu saldo a favor en tu próxima compra e invita a tus amigos." />

        <div class="mt-6 grid gap-5 lg:grid-cols-[1fr_1.2fr]">
            <article class="rounded-2xl bg-[linear-gradient(135deg,#5e0008_0%,#3a0004_100%)] p-6 text-white shadow-lg" data-saldo-disponible>
                <p class="text-xs font-black uppercase tracking-wide text-white/70">Saldo a favor</p>
                <p class="mt-2 text-4xl font-black">Q {{ number_format($saldo, 2) }}</p>
                <p class="mt-2 text-sm text-white/80">Se aplica al pagar: en el último paso marca “Usar mi saldo a favor”.</p>
            </article>

            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm" data-referidos>
                <p class="text-xs font-black uppercase tracking-wide text-atlantia-wine">Invita a un amigo</p>
                <h2 class="mt-1 text-xl font-black text-atlantia-ink">Tú recibes Q {{ number_format($recompensa, 2) }} y tu amigo también</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">Cuando tu amigo reciba su primer pedido, a los dos les llega el saldo.</p>

                <div class="mt-4 flex items-center gap-2 rounded-xl bg-atlantia-blush/60 p-3">
                    <span class="text-xs font-bold text-atlantia-ink/60">Tu código</span>
                    <span class="font-mono text-lg font-black tracking-wider text-atlantia-wine">{{ $codigo }}</span>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <a href="https://wa.me/?text={{ rawurlencode($mensajeInvitacion) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 py-3 text-sm font-black text-white hover:brightness-95">
                        Invitar por WhatsApp
                    </a>
                    <button type="button" data-copiar-enlace="{{ $enlace }}" class="rounded-xl border border-atlantia-rose/30 px-4 py-3 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">
                        Copiar enlace
                    </button>
                </div>

                <p class="mt-4 text-xs text-atlantia-ink/55">{{ $invitados }} {{ $invitados === 1 ? 'amigo se registró' : 'amigos se registraron' }} · {{ $invitadosConCompra }} ya compraron.</p>
            </article>
        </div>

        <x-activar-avisos class="mt-6" titulo="Recibe avisos de tus pedidos, productos nuevos y ofertas" />

        <section class="mt-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Movimientos</h2>
            @forelse ($movimientos as $movimiento)
                <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 py-3 last:border-0">
                    <div>
                        <p class="text-sm font-bold text-atlantia-ink">{{ $movimiento->descripcion }}</p>
                        <p class="text-xs text-atlantia-ink/50">{{ $movimiento->created_at->format('d/m/Y h:i a') }}</p>
                    </div>
                    <p @class(['font-black', 'text-emerald-700' => $movimiento->monto > 0, 'text-atlantia-ink' => $movimiento->monto <= 0])>
                        {{ $movimiento->monto > 0 ? '+' : '−' }} Q {{ number_format(abs((float) $movimiento->monto), 2) }}
                    </p>
                </div>
            @empty
                <p class="mt-3 text-sm text-atlantia-ink/60">Todavía no tienes movimientos.</p>
            @endforelse
        </section>
    </section>

    <script @nonce>
        document.querySelector('[data-copiar-enlace]')?.addEventListener('click', async (evento) => {
            const boton = evento.currentTarget;
            try {
                await navigator.clipboard.writeText(boton.dataset.copiarEnlace);
                boton.textContent = '¡Enlace copiado!';
            } catch (_) {
                window.prompt('Copia tu enlace:', boton.dataset.copiarEnlace);
            }
        });
    </script>
@endsection
