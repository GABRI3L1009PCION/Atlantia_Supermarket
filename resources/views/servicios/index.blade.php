@extends('layouts.marketplace', ['title' => 'Servicios | Atlantia Store'])

@section('content')
    @php
        /* Precios reales: salen de la configuración y de lo que el administrador ajusta en su panel. */
        $dinero = fn ($v): string => 'Q'.(fmod((float) $v, 1.0) == 0.0 ? number_format((float) $v, 0) : number_format((float) $v, 2));
        $envioDesde = collect((array) config('monetizacion.envios.mismo_municipio', []))->filter(fn ($v) => is_numeric($v))->min();
        $membresia = (float) config('atlantia.membresia.precio', 49);
        $minimoGratis = (float) config('atlantia.membresia.minimo_envio_gratis', 100);
        $planes = collect((array) config('monetizacion.planes', []));
        $planBase = $planes->get(\App\Services\Monetizacion\PlanesVendedorService::base());
        $publicidadDesde = collect([
            config('monetizacion.destacado.semana'),
            config('monetizacion.patrocinado.semana'),
            ...collect((array) config('monetizacion.banners.posiciones', []))->pluck('semana')->all(),
        ])->filter(fn ($v) => is_numeric($v) && $v > 0)->min();

        $iconos = [
            'envio' => '<path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
            'evento' => '<path d="M4 21h16M6 21V11h12v10M12 11V7m0 0a2 2 0 1 0-2-2M12 7a2 2 0 1 1 2-2"/>',
            'membresia' => '<path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 15.6 7.1 18.2l.9-5.5-4-3.9L9.5 8z"/>',
            'tienda' => '<path d="M4 10h16l-1-5H5zM6 10v10h12V10M9 20v-6h6v6"/>',
            'anuncio' => '<path d="M3 11v2a2 2 0 0 0 2 2h1l4 4V5L6 9H5a2 2 0 0 0-2 2zM14 8a5 5 0 0 1 0 8M17 5a9 9 0 0 1 0 14"/>',
        ];

        $paraTi = [
            [
                'icono' => 'envio', 'titulo' => 'Atlantia Envíos', 'etiqueta' => 'Mensajería local',
                'texto' => 'Mandamos tu paquete, documentos o encargo de puerta a puerta.',
                'puntos' => ['Puerto Barrios y Santo Tomás', 'Sigues al repartidor en vivo', 'Pagas con tarjeta o en efectivo'],
                'precio' => $envioDesde !== null ? 'Desde '.$dinero($envioDesde) : null,
                'cta' => 'Enviar algo', 'href' => route('envios.create'),
            ],
            [
                'icono' => 'evento', 'titulo' => 'Atlantia Eventos', 'etiqueta' => 'Fiestas y reuniones',
                'texto' => 'Combos listos para cumpleaños y celebraciones, entregados a la hora que elijas.',
                'puntos' => ['Bebidas, desechables y hielo', 'Programa la entrega con anticipación', 'Todo en un solo pedido'],
                'precio' => null,
                'cta' => 'Ver combos', 'href' => route('eventos'),
            ],
            ...(! \App\Services\Clientes\MembresiaService::habilitada() ? [] : [[
                'icono' => 'membresia', 'titulo' => 'Membresía Envío Gratis', 'etiqueta' => 'Para clientes frecuentes',
                'texto' => 'Olvídate del costo de envío en tus compras de todo el mes.',
                'puntos' => ['Envío gratis desde '.$dinero($minimoGratis).' de compra', 'Se renueva sola cada mes', 'Cancelas cuando quieras'],
                'precio' => $dinero($membresia).' al mes',
                'cta' => 'Conocer la membresía', 'href' => route('cliente.membresia.index'),
            ]]),
        ];

        $paraNegocios = [
            [
                'icono' => 'tienda', 'titulo' => 'Vende en Atlantia', 'etiqueta' => 'Tu tienda en línea',
                'texto' => 'Abre tu local digital: nosotros ponemos los clientes, los pagos con tarjeta y los repartidores.',
                'puntos' => ['Panel para pedidos, inventario y reportes', 'Cupones y promociones propias', 'Facturación electrónica (FEL)'],
                'precio' => \App\Services\Monetizacion\PruebaAperturaService::vigente()
                    ? (\App\Services\Monetizacion\PruebaAperturaService::dias() === 30 ? 'Primer mes gratis' : \App\Services\Monetizacion\PruebaAperturaService::dias().' días gratis')
                    : ($planBase ? ((float) ($planBase['comision'] ?? 0) > 0 ? 'Desde Q0 al mes' : 'Planes desde Q0') : null),
                'cta' => 'Ver planes', 'href' => route('vende'),
            ],
            [
                'icono' => 'anuncio', 'titulo' => 'Anúnciate', 'etiqueta' => 'Publicidad',
                'texto' => 'Pon tu marca frente a los clientes de todo el puerto, en la portada y en las búsquedas.',
                'puntos' => ['Banners en la portada', 'Productos patrocinados en búsquedas', 'Ofertas destacadas en el aviso semanal'],
                'precio' => $publicidadDesde !== null ? 'Desde '.$dinero($publicidadDesde).' por semana' : null,
                'cta' => 'Ver publicidad', 'href' => route('anunciate'),
            ],
        ];
    @endphp

    {{-- Portada --}}
    <section class="bg-[linear-gradient(135deg,#3a0004_0%,#5e0008_55%,#9d1b46_100%)] text-white">
        <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <p class="text-xs font-black uppercase tracking-[0.25em] text-white/70">Servicios Atlantia</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight sm:text-5xl">Más que un súper a domicilio</h1>
            <p class="mt-4 max-w-2xl text-lg text-white/85">Envíos, combos para tus eventos, tiendas en línea y publicidad para negocios de Puerto Barrios y Santo Tomás.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="#para-negocios" class="rounded-xl bg-white px-6 py-3 text-sm font-black text-atlantia-wine shadow-lg transition hover:bg-atlantia-blush">Para tu negocio</a>
                <a href="#para-ti" class="rounded-xl border border-white/50 px-6 py-3 text-sm font-black text-white transition hover:bg-white/10">Servicios para ti</a>
            </div>
            <dl class="mt-10 grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Cobertura', 'Puerto Barrios y Santo Tomás'],
                    ['Pagos', 'Tarjeta, transferencia o efectivo'],
                    ['Repartidores', 'Locales, con seguimiento en vivo'],
                ] as [$dato, $valor])
                    <div class="rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                        <dt class="text-[11px] font-black uppercase tracking-wide text-white/60">{{ $dato }}</dt>
                        <dd class="mt-0.5 text-sm font-bold">{{ $valor }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @foreach ([
        ['id' => 'para-negocios', 'titulo' => 'Para tu negocio', 'texto' => 'Vende más y llega a más clientes del puerto.', 'items' => $paraNegocios, 'columnas' => 'lg:grid-cols-2'],
        ['id' => 'para-ti', 'titulo' => 'Para ti', 'texto' => 'Lo que necesitas, sin salir de casa.', 'items' => $paraTi, 'columnas' => 'lg:grid-cols-3'],
    ] as $grupo)
        <section id="{{ $grupo['id'] }}" class="mx-auto w-full max-w-6xl scroll-mt-28 px-4 pt-12 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-2xl font-black text-atlantia-ink sm:text-3xl">{{ $grupo['titulo'] }}</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">{{ $grupo['texto'] }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 {{ $grupo['columnas'] }}">
                @foreach ($grupo['items'] as $servicio)
                    <article class="group flex flex-col rounded-2xl border border-atlantia-rose/15 bg-white p-6 shadow-sm transition hover:border-atlantia-wine/30 hover:shadow-md" data-servicio-card>
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-atlantia-rose to-atlantia-wine text-white shadow-md shadow-atlantia-wine/20">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $iconos[$servicio['icono']] !!}</svg>
                            </span>
                            @if ($servicio['precio'])
                                <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">{{ $servicio['precio'] }}</span>
                            @endif
                        </div>
                        <p class="mt-5 text-[11px] font-black uppercase tracking-wide text-atlantia-rose">{{ $servicio['etiqueta'] }}</p>
                        <h3 class="mt-1 text-xl font-black text-atlantia-ink">{{ $servicio['titulo'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-atlantia-ink/65">{{ $servicio['texto'] }}</p>
                        <ul class="mt-4 flex-1 space-y-2 border-t border-atlantia-rose/10 pt-4 text-sm text-atlantia-ink/75">
                            @foreach ($servicio['puntos'] as $punto)
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                    {{ $punto }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ $servicio['href'] }}" class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-atlantia-wine px-5 text-sm font-black text-white transition hover:bg-atlantia-wine-700">
                            {{ $servicio['cta'] }}
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Cierre --}}
    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 rounded-2xl border border-atlantia-rose/20 bg-atlantia-blush/45 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-atlantia-ink">¿Necesitas algo que no está aquí?</h2>
                <p class="text-sm text-atlantia-ink/65">Cuéntanos qué buscas y te decimos cómo te podemos ayudar.</p>
            </div>
            <a href="{{ route('contacto') }}#escribenos" class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-atlantia-rose/40 bg-white px-6 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">Escríbenos</a>
        </div>
    </section>
@endsection
