@extends('layouts.marketplace', ['title' => 'Atlantia Eventos | Atlantia Store'])

@section('content')
    @php
        $whatsapp = \App\Services\Notificaciones\WhatsAppService::normalizarTelefono(config('atlantia.support.whatsapp'));
    @endphp

    <section class="bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_60%,#9d1b46_100%)] text-white">
        <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-xs font-black uppercase tracking-[0.25em] text-white/70">Atlantia Eventos</p>
            <h1 class="mt-3 text-4xl font-black leading-tight sm:text-5xl">Tu fiesta, sin correr al súper</h1>
            <p class="mt-3 max-w-2xl text-lg text-white/85">Combos de bebidas, desechables, hielo y botanas para cumpleaños, reuniones y celebraciones, entregados a la hora que elijas.</p>
            <ol class="mt-6 flex flex-wrap gap-3 text-sm font-bold">
                <li class="rounded-full bg-white/10 px-4 py-2">1. Elige tu combo</li>
                <li class="rounded-full bg-white/10 px-4 py-2">2. Al pagar elige “Programado”</li>
                <li class="rounded-full bg-white/10 px-4 py-2">3. Lo llevamos a la hora de tu evento</li>
            </ol>
        </div>
    </section>

    <section class="mx-auto w-full max-w-[1680px] px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="mb-4 text-2xl font-black text-atlantia-ink">Combos y productos para eventos</h2>
        @if ($productos->isEmpty())
            <p class="rounded-xl bg-atlantia-blush/50 p-6 text-center text-atlantia-ink/70">Estamos preparando los combos. Mientras tanto, cotiza tu evento con nosotros.</p>
        @else
            <div class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-6" data-eventos-productos>
                @foreach ($productos as $producto)
                    <x-storefront-product :producto="$producto" />
                @endforeach
            </div>
        @endif

        <div class="mt-10 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-6">
            <div>
                <h2 class="text-xl font-black text-atlantia-ink">¿Evento grande? Te armamos una cotización</h2>
                <p class="mt-1 text-sm text-atlantia-ink/65">Bodas, empresas, graduaciones o más de 50 personas: dinos fecha, cantidad de invitados y lo que necesitas.</p>
            </div>
            <div class="mt-4 flex shrink-0 gap-2 sm:mt-0">
                @if ($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero cotizar un evento con Atlantia.') }}" target="_blank" rel="noopener" class="rounded-xl bg-[#25D366] px-5 py-3 text-sm font-black text-white">WhatsApp</a>
                @endif
                <a href="{{ route('contacto') }}" class="rounded-xl bg-atlantia-wine px-5 py-3 text-sm font-black text-white">Pedir cotización</a>
            </div>
        </div>
    </section>
@endsection
