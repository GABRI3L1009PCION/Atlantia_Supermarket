@extends('layouts.marketplace', ['title' => 'Novedades y ofertas | Atlantia Store'])

@section('content')
    @php
        $promociones = app(\App\Services\Promociones\PromocionService::class);
        $secciones = [
            ['titulo' => 'Ofertas destacadas de la semana', 'productos' => $destacados, 'etiqueta' => 'Destacado'],
            ['titulo' => 'En oferta', 'productos' => $ofertas, 'etiqueta' => null],
            ['titulo' => 'Recién llegados', 'productos' => $nuevos, 'etiqueta' => 'Nuevo'],
        ];
    @endphp

    <section class="mx-auto w-full max-w-[1680px] px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Novedades y ofertas" subtitle="Lo nuevo y lo más barato de la semana en Atlantia Store." />

        <x-activar-avisos class="mt-4" titulo="¿Te avisamos cuando haya ofertas nuevas?" />

        @foreach ($secciones as $seccion)
            @continue($seccion['productos']->isEmpty())
            <h2 class="mb-4 mt-8 text-2xl font-black text-atlantia-ink">{{ $seccion['titulo'] }}</h2>
            <div class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-6" data-novedades="{{ $loop->index }}">
                @foreach ($seccion['productos'] as $producto)
                    <x-storefront-product
                        :producto="$producto"
                        :etiqueta="$promociones->promocionPara($producto)?->etiqueta() ?? $seccion['etiqueta']"
                    />
                @endforeach
            </div>
        @endforeach

        @if ($destacados->isEmpty() && $ofertas->isEmpty() && $nuevos->isEmpty())
            <p class="mt-8 rounded-xl bg-atlantia-blush/50 p-6 text-center text-atlantia-ink/70">Muy pronto tendremos ofertas nuevas para ti.</p>
        @endif
    </section>
@endsection
