@extends('layouts.marketplace', ['title' => 'Anúnciate | Atlantia Store'])

@section('content')
    @php
        $publicidad = config('monetizacion');
        $whatsapp = \App\Services\Notificaciones\WhatsAppService::normalizarTelefono(config('atlantia.support.whatsapp'));
    @endphp

    <section class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-atlantia-rose">Publicidad</p>
        <h1 class="mt-2 text-3xl font-black text-atlantia-ink sm:text-4xl">Anuncia tu marca en Atlantia</h1>
        <p class="mt-2 max-w-2xl text-atlantia-ink/65">Llega a familias de Puerto Barrios y Santo Tomás justo cuando están comprando. Te mostramos cuántas personas vieron y tocaron tu anuncio.</p>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Banner en la portada</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Tu imagen en el carrusel principal.</p>
                <ul class="mt-4 space-y-1 text-sm">
                    @foreach ($publicidad['banners']['posiciones'] as $posicion => $precio)
                        <li class="flex justify-between"><span>Posición {{ $posicion }}</span><strong>Q {{ number_format($precio['semana'], 0) }}/sem · Q {{ number_format($precio['mes'], 0) }}/mes</strong></li>
                    @endforeach
                </ul>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Oferta destacada</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Encabeza el aviso de ofertas que reciben los clientes en su teléfono.</p>
                <p class="mt-4 text-2xl font-black text-atlantia-wine">Q {{ number_format($publicidad['destacado']['semana'], 0) }}<span class="text-sm text-atlantia-ink/50"> / semana</span></p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Producto patrocinado</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Arriba de los resultados cuando buscan algo parecido.</p>
                <p class="mt-4 text-2xl font-black text-atlantia-wine">Q {{ number_format($publicidad['patrocinado']['semana'], 0) }}<span class="text-sm text-atlantia-ink/50"> / semana</span></p>
            </article>
        </div>

        <div class="mt-8 rounded-2xl bg-atlantia-blush/50 p-6">
            <h2 class="text-lg font-black text-atlantia-ink">¿Cómo contrato?</h2>
            <p class="mt-1 text-sm text-atlantia-ink/70">Si vendes en Atlantia, contrátalo desde tu panel en “Mi plan y publicidad”. Si eres una empresa sin tienda en Atlantia, escríbenos y te ayudamos con el diseño del banner.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero anunciar mi negocio en Atlantia Store.') }}" target="_blank" rel="noopener" class="rounded-xl bg-[#25D366] px-5 py-3 text-sm font-black text-white">Escribir por WhatsApp</a>
                @endif
                <a href="{{ route('contacto') }}" class="rounded-xl border border-atlantia-wine px-5 py-3 text-sm font-black text-atlantia-wine">Formulario de contacto</a>
            </div>
        </div>
    </section>
@endsection
