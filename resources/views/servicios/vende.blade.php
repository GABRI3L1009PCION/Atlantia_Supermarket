@extends('layouts.marketplace', ['title' => 'Vende en Atlantia | Atlantia Store'])

@section('content')
    @php
        $planes = config('monetizacion.planes');
        /* Textos con precios: salen de los planes vigentes (el administrador los cambia desde su panel). */
        $dinero = fn ($v): string => 'Q '.(fmod((float) $v, 1.0) == 0.0 ? number_format((float) $v, 0) : number_format((float) $v, 2));
        $pct = fn ($v): string => rtrim(rtrim(number_format((float) $v, 2), '0'), '.').' %';
        $planBase = $planes[\App\Services\Monetizacion\PlanesVendedorService::base()] ?? null;
        $comisionBase = (float) ($planBase['comision'] ?? 0);
        $pagados = collect($planes)->filter(fn ($p, $k) => ($p['precio'] ?? null) !== null && (float) $p['precio'] > 0 && empty($p['cotizacion']))->sortBy('precio');
        $masBarato = $pagados->first();
        $equilibrio = $masBarato && $comisionBase > 0 ? (float) $masBarato['precio'] / ($comisionBase / 100) : null;
        /* Promoción de apertura (se activa desde el panel del administrador). */
        $apertura = \App\Services\Monetizacion\PruebaAperturaService::vigente();
        $diasGratis = \App\Services\Monetizacion\PruebaAperturaService::dias();
        $limiteApertura = \App\Services\Monetizacion\PruebaAperturaService::fechaLimite();
        $textoGratis = $diasGratis === 30 ? 'tu primer mes gratis' : $diasGratis.' días gratis';
        $pasos = [
            ['Solicita tu tienda', 'Llena el formulario con los datos de tu negocio. Es gratis.'],
            ['Te verificamos', 'Revisamos tus datos en 1 a 2 días hábiles y activamos tu panel.'],
            ['Sube tus productos', 'Uno por uno o todos de una vez con Excel.'],
            ['Empieza a vender', 'Recibe pedidos con alerta sonora; nosotros llevamos y cobramos.'],
        ];
        $preguntas = [
            ['¿Cuándo recibo mi dinero?', 'Liquidamos tus ventas cada semana, ya descontada la comisión de tu plan.'],
            ['¿Quién entrega los pedidos?', 'Los repartidores de Atlantia, o puedes ofrecer que el cliente recoja en tu tienda.'],
            ...(\App\Services\Monetizacion\PruebaAperturaService::vigente() ? [['¿Cómo funciona la promoción de apertura?', 'Al aprobar tu tienda usas gratis el plan que elegiste durante '.\App\Services\Monetizacion\PruebaAperturaService::dias().' días, sin tarjeta. En el plan Inicial son '.\App\Services\Monetizacion\PruebaAperturaService::dias().' días sin la comisión de Atlantia. En todos los planes la tarifa de la pasarela por pagos con tarjeta sí se cobra. Al terminar, pagas tu mensualidad para seguir; si no, tu tienda pasa al plan Inicial sin cerrar. Es una sola vez por tienda.']] : []),
            ['¿Puedo cambiar de plan?', 'Sí, cuando quieras desde tu panel. La mensualidad se paga por adelantado y se renueva sola; si no se paga, tu tienda pasa al plan Inicial sin cerrar.'],
            ['¿Por qué pagar mensualidad si puedo vender con el '.$pct($comisionBase).'?', 'Porque con ventas constantes la mensualidad sale más barata que la comisión, y tienes más productos, más promociones y más visibilidad.'],
            ['¿Necesito factura electrónica (FEL)?', 'Sí, para vender necesitas tu NIT. Te guiamos para configurar tu perfil fiscal.'],
        ];
    @endphp

    {{-- Portada --}}
    <section class="bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_55%,#9d1b46_100%)] text-white">
        <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:px-8">
            <div>
                @if ($apertura)
                    <p class="mb-4 inline-flex flex-wrap items-center gap-2 rounded-full bg-white px-4 py-1.5 text-sm font-black text-atlantia-wine shadow-lg" data-aviso-apertura>
                        <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[11px] uppercase tracking-wide text-white">Apertura</span>
                        {{ ucfirst($textoGratis) }} en cualquier plan{{ $limiteApertura ? ' · hasta el '.$limiteApertura->translatedFormat('j \\d\\e F') : '' }}
                    </p>
                @endif
                <p class="text-xs font-black uppercase tracking-[0.25em] text-white/70">Para negocios y emprendedores</p>
                <h1 class="mt-3 text-4xl font-black leading-tight sm:text-5xl">Abre tu local digital en Atlantia</h1>
                <p class="mt-4 max-w-xl text-lg text-white/85">Como tener un local en un centro comercial, pero abierto a todo Puerto Barrios y Santo Tomás desde el teléfono de tus clientes. Nosotros ponemos los clientes, los pagos con tarjeta, los repartidores y la facturación.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('vendedor.solicitar.create') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-black text-atlantia-wine shadow-lg hover:bg-atlantia-blush" data-cta-unirme>Quiero vender en Atlantia</a>
                    <a href="#planes" class="rounded-xl border border-white/50 px-6 py-3 text-sm font-black text-white hover:bg-white/10">Ver planes</a>
                </div>
            </div>
            <ul class="grid gap-3 text-sm">
                @foreach (array_filter(['Sin pagar luz, agua ni local físico', $masBarato ? 'Planes mensuales desde '.$dinero($masBarato['precio']).((float) ($masBarato['comision'] ?? 0) > 0 ? '' : ', sin comisión') : null, $comisionBase > 0 ? 'O empieza con Q 0 al mes y '.$pct($comisionBase).' por venta' : 'O empieza con Q 0 al mes', 'Repartidores y cobro con tarjeta incluidos', 'Promociones, 2x1 y ofertas relámpago']) as $beneficio)
                    <li class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 font-bold"><span class="grid h-6 w-6 place-items-center rounded-full bg-white text-xs text-atlantia-wine">✓</span>{{ $beneficio }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-8 sm:px-6 lg:px-8">
        <div class="relative" data-carrusel-planes>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-black text-atlantia-ink sm:text-3xl">Elige tu plan mensual</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/65">
                        @if ($apertura)
                            <strong class="text-emerald-700">Apertura: {{ $textoGratis }} en el plan que elijas, sin tarjeta.</strong>
                        @endif
                        Precios en quetzales, IVA incluido. Cada plan es 1 local digital y puedes cambiarlo cuando tu negocio crezca.
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-atlantia-rose/30 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush disabled:cursor-default disabled:opacity-35" data-carrusel-anterior aria-label="Planes anteriores">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-atlantia-rose/30 bg-white text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush disabled:cursor-default disabled:opacity-35" data-carrusel-siguiente aria-label="Planes siguientes">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>

            <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-5 overflow-x-auto scroll-smooth px-4 pb-3 pt-5 sm:scroll-px-6 lg:scroll-px-2 [scrollbar-width:none] sm:-mx-6 sm:px-6 lg:-mx-2 lg:px-2 [&::-webkit-scrollbar]:hidden"
                 data-planes-publicos data-carrusel-pista tabindex="0" aria-label="Planes disponibles, desliza para ver más">
                @foreach ($planes as $clave => $plan)
                    <article @class([
                        'relative flex w-[85%] shrink-0 snap-start flex-col rounded-2xl border bg-white p-5 shadow-sm sm:w-[calc(50%-10px)] lg:w-[calc((100%-40px)/3)]',
                        'border-atlantia-wine shadow-lg ring-2 ring-atlantia-wine/15' => $plan['recomendado'] ?? false,
                        'border-atlantia-rose/20' => ! ($plan['recomendado'] ?? false),
                    ]) data-carrusel-item>
                        @if ($plan['recomendado'] ?? false)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-atlantia-wine px-4 py-1 text-xs font-black text-white">Más elegido</span>
                        @endif
                        @php
                            /* Sin repetir lo que ya dicen el precio y la comisión de arriba. */
                            $lineas = collect($plan['beneficios'])->reject(fn ($b) => in_array($b, ['1 local digital', 'Sin comisión por venta'], true)
                                || preg_match('/^Q ?[\d,.]+ al mes/u', $b) || preg_match('/^[\d,.]+ ?% de comisión/u', $b))->take(5);
                        @endphp
                        <h3 class="text-xl font-black text-atlantia-ink">{{ $plan['nombre'] }}</h3>
                        <p class="mt-0.5 line-clamp-2 min-h-[2.5rem] text-xs leading-5 text-atlantia-ink/60">{{ $plan['resumen'] }}</p>
                        <p class="mt-3 flex items-baseline gap-1.5 whitespace-nowrap">
                            <span class="text-3xl font-black text-atlantia-wine">{{ $plan['precio'] === null ? 'A tu medida' : $dinero($plan['precio']) }}</span>
                            <span class="text-sm font-bold text-atlantia-ink/45">{{ $plan['precio'] === null ? 'cotización' : '/ mes' }}</span>
                        </p>
                        <p class="text-sm font-black text-atlantia-ink">{{ $plan['comision'] > 0 ? '+ '.$pct($plan['comision']).' por venta' : 'Sin comisión por venta' }}</p>
                        @if ($apertura && empty($plan['cotizacion']))
                            <p class="mt-1.5 w-fit rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-black text-emerald-700 ring-1 ring-emerald-200" data-gratis-plan>
                                {{ (float) ($plan['precio'] ?? 0) > 0 ? $diasGratis.' días gratis, luego '.$dinero($plan['precio']).'/mes' : $diasGratis.' días sin comisión de Atlantia' }}
                            </p>
                        @endif
                        <ul class="mt-3 flex-1 space-y-1.5 border-t border-atlantia-rose/10 pt-3 text-[13px] leading-5 text-atlantia-ink/75">
                            @foreach ($lineas as $beneficio)
                                <li class="flex gap-2"><span class="text-emerald-600">✓</span>{{ $beneficio }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ ($plan['cotizacion'] ?? false) ? route('contacto').'#escribenos' : route('vendedor.solicitar.create', ['plan' => $clave]) }}" @class([
                            'mt-4 rounded-xl px-5 py-2.5 text-center text-sm font-black transition',
                            'bg-atlantia-wine text-white hover:bg-atlantia-wine-700' => $plan['recomendado'] ?? false,
                            'border border-atlantia-wine text-atlantia-wine hover:bg-atlantia-blush' => ! ($plan['recomendado'] ?? false),
                        ])>{{ ($plan['cotizacion'] ?? false) ? 'Pedir cotización' : 'Empezar con '.$plan['nombre'] }}</a>
                    </article>
                @endforeach
            </div>

            <div class="mt-1 flex justify-center gap-2" data-carrusel-puntos>
                @foreach ($planes as $clave => $plan)
                    <button type="button" class="h-2.5 w-2.5 rounded-full bg-atlantia-rose/25 transition-all aria-[current=true]:w-6 aria-[current=true]:bg-atlantia-wine" data-carrusel-punto="{{ $loop->index }}" aria-label="Ver plan {{ $plan['nombre'] }}"></button>
                @endforeach
            </div>
        </div>

        <script nonce="{{ request()->attributes->get('csp_nonce') }}">
            (() => {
                const raiz = document.querySelector('[data-carrusel-planes]');
                if (!raiz) return;
                const pista = raiz.querySelector('[data-carrusel-pista]');
                const items = [...pista.querySelectorAll('[data-carrusel-item]')];
                const puntos = [...raiz.querySelectorAll('[data-carrusel-punto]')];
                const anterior = raiz.querySelector('[data-carrusel-anterior]');
                const siguiente = raiz.querySelector('[data-carrusel-siguiente]');

                const paso = () => items.length > 1 ? items[1].offsetLeft - items[0].offsetLeft : pista.clientWidth;
                const actual = () => Math.round(pista.scrollLeft / paso());
                const ultimo = () => Math.max(0, Math.ceil((pista.scrollWidth - pista.clientWidth - 4) / paso()));
                /* Plan al que vamos: así varios clics seguidos avanzan de uno en uno aunque la animación no haya terminado. */
                let objetivo = 0;
                let espera;
                const ir = (i) => {
                    objetivo = Math.max(0, Math.min(i, ultimo()));
                    pista.scrollTo({ left: objetivo * paso() });
                };

                const pintar = () => {
                    const max = pista.scrollWidth - pista.clientWidth - 4;
                    anterior.disabled = pista.scrollLeft <= 4;
                    siguiente.disabled = pista.scrollLeft >= max;
                    /* El punto activo es el primer plan visible; al llegar al final se marca el último. */
                    const activo = pista.scrollLeft >= max ? items.length - 1 : actual();
                    puntos.forEach((p, n) => p.setAttribute('aria-current', n === activo ? 'true' : 'false'));
                };

                anterior.addEventListener('click', () => ir(objetivo - 1));
                siguiente.addEventListener('click', () => ir(objetivo + 1));
                puntos.forEach((p) => p.addEventListener('click', () => ir(Number(p.dataset.carruselPunto))));
                pista.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowRight') { e.preventDefault(); ir(objetivo + 1); }
                    if (e.key === 'ArrowLeft') { e.preventDefault(); ir(objetivo - 1); }
                });
                pista.addEventListener('scroll', () => {
                    pintar();
                    /* Si la persona desliza con el dedo, el objetivo pasa a ser donde se detuvo. */
                    clearTimeout(espera);
                    espera = setTimeout(() => { objetivo = Math.min(actual(), ultimo()); }, 160);
                }, { passive: true });
                window.addEventListener('resize', pintar);
                pintar();
            })();
        </script>

        <p class="mx-auto mt-3 max-w-3xl text-center text-xs text-atlantia-ink/60 sm:text-sm">
            @if ($equilibrio)
                ¿Cuál te conviene? Si vendes más de {{ $dinero(ceil($equilibrio / 50) * 50) }} al mes, el plan {{ $masBarato['nombre'] }} ya te sale más barato que el {{ $pct($comisionBase) }} del plan {{ $planBase['nombre'] }}. Después, elige según cuántos productos necesitas.
            @else
                Elige según cuántos productos necesitas y cuánta visibilidad quieres.
            @endif
        </p>
    </section>

    {{-- Como funciona --}}
    <section class="bg-atlantia-blush/40">
        <div class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-black text-atlantia-ink">Cómo funciona</h2>
            <ol class="mt-8 grid gap-4 md:grid-cols-4">
                @foreach ($pasos as [$titulo, $texto])
                    <li class="rounded-2xl bg-white p-5 shadow-sm">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-atlantia-wine text-sm font-black text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-3 font-black text-atlantia-ink">{{ $titulo }}</h3>
                        <p class="mt-1 text-sm text-atlantia-ink/65">{{ $texto }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Preguntas --}}
    <section class="mx-auto w-full max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-center text-3xl font-black text-atlantia-ink">Preguntas frecuentes</h2>
        <div class="mt-8 space-y-3">
            @foreach ($preguntas as [$pregunta, $respuesta])
                <details class="rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer font-black text-atlantia-ink">{{ $pregunta }}</summary>
                    <p class="mt-2 text-sm text-atlantia-ink/70">{{ $respuesta }}</p>
                </details>
            @endforeach
        </div>
        <div class="mt-10 text-center">
            <a href="{{ route('vendedor.solicitar.create') }}" class="inline-flex rounded-xl bg-atlantia-wine px-8 py-4 text-base font-black text-white shadow-lg hover:bg-atlantia-wine-700">Quiero vender en Atlantia</a>
        </div>
    </section>
@endsection
