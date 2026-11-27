@extends('layouts.app')

@section('content')
    @php
        $pestana = in_array(request('tab'), ['planes', 'publicidad', 'historial'], true) ? request('tab') : 'planes';
        $actual = $planes[$planActual] ?? null;
        $limite = app(\App\Services\Monetizacion\PlanesVendedorService::class)->limiteProductos($vendor);
        $usados = \App\Models\Producto::query()->where('vendor_id', $vendor->id)->count();
        $precioTexto = fn (array $plan): string => $plan['precio'] === null ? 'A tu medida' : ($plan['precio'] > 0 ? 'Q '.(fmod((float) $plan['precio'], 1.0) == 0.0 ? number_format((float) $plan['precio'], 0) : number_format((float) $plan['precio'], 2)) : 'Gratis');
        $campo = 'h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $pestanas = ['planes' => 'Planes', 'publicidad' => 'Publicidad', 'historial' => 'Lo que has contratado'];
        $enPrueba = $vendor->enPrueba();
        $diasPrueba = \App\Services\Monetizacion\PruebaAperturaService::dias();
        $limitePromo = \App\Services\Monetizacion\PruebaAperturaService::fechaLimite();
        $quedan = $enPrueba ? max(1, (int) ceil(now()->diffInHours($vendor->prueba_hasta) / 24)) : 0;
        $pruebaConMensualidad = $enPrueba && \App\Services\Monetizacion\PlanesVendedorService::tieneMensualidad((string) $vendor->prueba_plan);
    @endphp

    <section class="mx-auto max-w-[1180px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Mi plan y publicidad</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Elige cuánto quieres crecer y da a conocer tus productos.</p>
            </div>
            @if ($actual)
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 rounded-xl border border-atlantia-rose/15 bg-white px-4 py-2.5 text-sm" data-plan-actual>
                    <span>Tu plan: <strong class="text-atlantia-wine">{{ $actual['nombre'] }}</strong></span>
                    <span class="text-atlantia-ink/60">{{ $precioTexto($actual) }}{{ $actual['precio'] > 0 ? '/mes' : '' }}{{ $actual['comision'] > 0 ? ' + '.$actual['comision'].' % por venta' : '' }}</span>
                    <span @class(['font-bold', 'text-amber-700' => $limite > 0 && $usados >= $limite * 0.8, 'text-atlantia-ink/70' => ! ($limite > 0 && $usados >= $limite * 0.8)])>{{ $usados }} de {{ $limite > 0 ? number_format($limite) : '∞' }} productos</span>
                    @if ($enPrueba)
                        <span class="font-bold text-emerald-700">Gratis hasta el {{ $vendor->prueba_hasta->format('d/m/Y') }}</span>
                    @elseif ($vendor->plan_hasta && ($actual['precio'] ?? 0) > 0)
                        <span class="text-atlantia-ink/60">Vence el {{ $vendor->plan_hasta->format('d/m/Y') }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if ($enPrueba)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" data-prueba-activa>
                <p>
                    <strong>Prueba gratis de apertura.</strong>
                    @if ($pruebaConMensualidad)
                        Usas el plan {{ $planes[$vendor->prueba_plan]['nombre'] ?? $vendor->prueba_plan }} sin pagar hasta el {{ $vendor->prueba_hasta->format('d/m/Y') }} (quedan {{ $quedan }} {{ $quedan === 1 ? 'día' : 'días' }}). Para seguir con él, paga tu primera mensualidad; empieza a contar cuando termine la prueba, no pierdes ningún día.
                    @else
                        Vendes sin pagar la comisión de Atlantia hasta el {{ $vendor->prueba_hasta->format('d/m/Y') }} (quedan {{ $quedan }} {{ $quedan === 1 ? 'día' : 'días' }}). La tarifa de la pasarela por pagos con tarjeta sí se cobra.
                    @endif
                </p>
                @if ($pruebaConMensualidad)
                    <form method="POST" action="{{ route('vendedor.plan.elegir') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $vendor->prueba_plan }}">
                        <button type="submit" class="h-9 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white hover:bg-emerald-800">Pagar para seguir</button>
                    </form>
                @endif
            </div>
        @elseif ($pruebaDisponible)
            <div class="rounded-xl border border-atlantia-wine/20 bg-atlantia-blush px-4 py-3 text-sm text-atlantia-ink" data-prueba-disponible>
                <strong class="text-atlantia-wine">Promoción de apertura:</strong> prueba cualquier plan {{ $diasPrueba }} días gratis, sin tarjeta.
                En el plan Inicial significa {{ $diasPrueba }} días sin la comisión de Atlantia (la tarifa de la pasarela por pagos con tarjeta sí se cobra). Es una sola vez por tienda{{ $limitePromo ? ' y está disponible hasta el '.$limitePromo->format('d/m/Y') : '' }}.
            </div>
        @endif

        @if ($vendor->plan !== $planActual)
            <p class="rounded-xl bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900">Elegiste el plan {{ $planes[$vendor->plan]['nombre'] ?? $vendor->plan }}, pero mientras no pagues la mensualidad tu tienda sigue con el plan {{ $actual['nombre'] ?? $planActual }}.</p>
        @endif

        <nav class="flex gap-1 border-b border-atlantia-rose/15" aria-label="Secciones">
            @foreach ($pestanas as $clave => $texto)
                <a href="{{ route('vendedor.plan.index', ['tab' => $clave]) }}" @class([
                    'border-b-2 px-3 py-2.5 text-sm font-bold transition',
                    'border-atlantia-wine text-atlantia-wine' => $pestana === $clave,
                    'border-transparent text-atlantia-ink/55 hover:text-atlantia-wine' => $pestana !== $clave,
                ]) @if ($pestana === $clave) aria-current="page" @endif>{{ $texto }}@if ($clave === 'historial' && $servicios->isNotEmpty()) <span class="text-xs opacity-70">{{ $servicios->count() }}</span>@endif</a>
            @endforeach
        </nav>

        @if ($pestana === 'planes')
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" data-planes>
                @foreach ($planes as $clave => $plan)
                    @php
                        $esActual = $clave === $planActual;
                    @endphp
                    <article @class([
                        'relative flex flex-col rounded-xl border bg-white p-4',
                        'border-atlantia-wine ring-2 ring-atlantia-wine/15' => $esActual,
                        'border-atlantia-rose/20' => ! $esActual,
                    ]) data-plan="{{ $clave }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h2 class="text-lg font-black text-atlantia-ink">{{ $plan['nombre'] }}</h2>
                                <p class="text-xs font-semibold text-atlantia-ink/55">{{ $plan['comision'] > 0 ? '+ '.$plan['comision'].' % por cada venta' : 'Sin comisión por venta' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black leading-none text-atlantia-wine" data-precio-plan>{{ $precioTexto($plan) }}</p>
                                @if (($plan['precio'] ?? 0) > 0)
                                    <p class="text-xs text-atlantia-ink/50">al mes</p>
                                @elseif ($plan['precio'] === null)
                                    <p class="text-xs text-atlantia-ink/50">bajo cotización</p>
                                @endif
                            </div>
                        </div>
                        @if ($plan['recomendado'] ?? false)
                            <span class="mt-2 w-fit rounded-full bg-atlantia-blush px-2.5 py-0.5 text-[11px] font-black text-atlantia-wine">Recomendado</span>
                        @endif
                        <ul class="mt-3 flex-1 space-y-1 text-sm text-atlantia-ink/75">
                            @foreach ($plan['beneficios'] as $beneficio)
                                <li class="flex gap-2"><span class="text-emerald-600" aria-hidden="true">✓</span><span>{{ $beneficio }}</span></li>
                            @endforeach
                        </ul>
                        <div class="mt-4">
                            @if ($esActual && ! ($pruebaDisponible && ! ($plan['cotizacion'] ?? false)))
                                <p class="rounded-lg bg-atlantia-blush px-3 py-2 text-center text-sm font-black text-atlantia-wine">Tu plan actual{{ $enPrueba && $vendor->prueba_plan === $clave ? ' · gratis' : '' }}</p>
                            @elseif ($pruebaDisponible && ! ($plan['cotizacion'] ?? false))
                                <form method="POST" action="{{ route('vendedor.plan.prueba') }}" data-confirm="¿Probar el plan {{ $plan['nombre'] }} gratis por {{ $diasPrueba }} días? Solo se puede usar una vez por tienda.">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $clave }}">
                                    <button type="submit" class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-800" data-probar-gratis="{{ $clave }}">
                                        {{ ($plan['precio'] ?? 0) > 0 ? 'Probar '.$diasPrueba.' días gratis' : $diasPrueba.' días sin comisión' }}
                                    </button>
                                </form>
                                @if (($plan['precio'] ?? 0) > 0)
                                    <p class="mt-1 text-center text-[11px] text-atlantia-ink/55">Luego {{ $precioTexto($plan) }} al mes. Sin tarjeta.</p>
                                @endif
                            @elseif ($plan['cotizacion'] ?? false)
                                @if ($cotizacionPendiente)
                                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-center text-sm font-bold text-emerald-700" data-cotizacion-enviada>Solicitud enviada el {{ $cotizacionPendiente->created_at?->format('d/m/Y') }} · te llamaremos</p>
                                @else
                                    <button type="button" class="w-full rounded-lg border border-atlantia-wine px-3 py-2 text-center text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush" data-abrir-cotizacion>Pedir cotización</button>
                                @endif
                            @else
                                <form method="POST" action="{{ route('vendedor.plan.elegir') }}" data-confirm="¿Cambiar al plan {{ $plan['nombre'] }}{{ ($plan['precio'] ?? 0) > 0 ? ' por Q '.(fmod((float) $plan['precio'], 1.0) == 0.0 ? number_format((float) $plan['precio'], 0) : number_format((float) $plan['precio'], 2)).' al mes' : '' }}?">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $clave }}">
                                    <button type="submit" class="w-full rounded-lg bg-atlantia-wine px-3 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700">
                                        {{ ($plan['precio'] ?? 0) > 0 ? ($vendor->plan === $clave ? 'Pagar mensualidad' : 'Cambiar a '.$plan['nombre']) : 'Pasar a '.$plan['nombre'] }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            @unless ($pagosEnLinea)
                <p class="text-xs text-atlantia-ink/55">El cobro en línea aún no está activo: al elegir un plan pagado te contactaremos para el pago.</p>
            @endunless
        @elseif ($pestana === 'publicidad')
            @if ($productos->isEmpty())
                <p class="rounded-xl border border-dashed border-atlantia-rose/30 bg-white px-6 py-10 text-center text-sm text-atlantia-ink/60">Primero publica productos en tu tienda para poder anunciarlos.</p>
            @endif
            <div class="grid gap-3 lg:grid-cols-3">
                <form method="POST" action="{{ route('vendedor.publicidad.destacado') }}" class="flex flex-col rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    @csrf
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-base font-black text-atlantia-ink">Oferta destacada</h2>
                        <p class="text-right text-lg font-black leading-none text-atlantia-wine">Q {{ number_format($precios['destacado']['semana'], 0) }}<span class="block text-xs font-normal text-atlantia-ink/50">por semana</span></p>
                    </div>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Tu producto encabeza el aviso de ofertas que reciben los clientes esa semana.</p>
                    <div class="mt-3 flex-1 space-y-2">
                        <select name="producto_id" required aria-label="Producto" class="{{ $campo }}">
                            <option value="">Elige un producto</option>
                            @foreach ($productos as $producto)<option value="{{ $producto->id }}">{{ $producto->nombre }}</option>@endforeach
                        </select>
                        <label class="block text-xs font-bold text-atlantia-ink/70">Semana que empieza el
                            <input type="date" name="semana" min="{{ now()->toDateString() }}" required class="mt-1 {{ $campo }}">
                        </label>
                    </div>
                    <button type="submit" class="mt-3 h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700" @disabled($productos->isEmpty())>Contratar</button>
                </form>

                <form method="POST" action="{{ route('vendedor.publicidad.patrocinado') }}" class="flex flex-col rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    @csrf
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-base font-black text-atlantia-ink">Producto patrocinado</h2>
                        <p class="text-right text-lg font-black leading-none text-atlantia-wine">Q {{ number_format($precios['patrocinado']['semana'], 0) }}<span class="block text-xs font-normal text-atlantia-ink/50">por semana</span></p>
                    </div>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Sale primero cuando buscan algo parecido o entran a su categoría.</p>
                    <div class="mt-3 flex-1 space-y-2">
                        <select name="producto_id" required aria-label="Producto" class="{{ $campo }}">
                            <option value="">Elige un producto</option>
                            @foreach ($productos as $producto)<option value="{{ $producto->id }}">{{ $producto->nombre }}</option>@endforeach
                        </select>
                        <label class="block text-xs font-bold text-atlantia-ink/70">Por cuánto tiempo
                            <select name="semanas" class="mt-1 {{ $campo }}">
                                @foreach ([1, 2, 4, 8] as $semanas)<option value="{{ $semanas }}">{{ $semanas }} {{ $semanas === 1 ? 'semana' : 'semanas' }} · Q {{ number_format($precios['patrocinado']['semana'] * $semanas, 0) }}</option>@endforeach
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="mt-3 h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700" @disabled($productos->isEmpty())>Contratar</button>
                </form>

                <form method="POST" action="{{ route('vendedor.publicidad.banner') }}" enctype="multipart/form-data" class="flex flex-col rounded-xl border border-atlantia-rose/20 bg-white p-4">
                    @csrf
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-base font-black text-atlantia-ink">Banner en la portada</h2>
                        <p class="text-right text-lg font-black leading-none text-atlantia-wine">desde Q {{ number_format(collect($precios['banners']['posiciones'])->min('semana'), 0) }}<span class="block text-xs font-normal text-atlantia-ink/50">por semana</span></p>
                    </div>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Tu imagen en el carrusel principal. Al tocarla llevan a tu tienda.</p>
                    <div class="mt-3 flex-1 space-y-2">
                        <select name="posicion" aria-label="Posición" class="{{ $campo }}">
                            @foreach ($precios['banners']['posiciones'] as $posicion => $precio)
                                <option value="{{ $posicion }}">Posición {{ $posicion }} · Q {{ number_format($precio['semana'], 0) }}/sem · Q {{ number_format($precio['mes'], 0) }}/mes</option>
                            @endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="periodo" aria-label="Duración" class="{{ $campo }}"><option value="semana">1 semana</option><option value="mes">1 mes</option></select>
                            <input type="date" name="inicia" min="{{ now()->toDateString() }}" required aria-label="Empieza el" class="{{ $campo }}">
                        </div>
                        <label class="block text-xs font-bold text-atlantia-ink/70">Imagen para computadora (1500 × 600 px)
                            <input type="file" name="desktop_image" accept="image/*" required class="mt-1 block w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-atlantia-blush file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-atlantia-wine">
                        </label>
                        <label class="block text-xs font-bold text-atlantia-ink/70">Imagen para celular <span class="font-normal">(opcional)</span>
                            <input type="file" name="mobile_image" accept="image/*" class="mt-1 block w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-atlantia-blush file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-atlantia-wine">
                        </label>
                        @error('desktop_image') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="mt-3 h-10 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Contratar</button>
                </form>
            </div>
            @unless ($pagosEnLinea)
                <p class="text-xs text-atlantia-ink/55">El cobro en línea aún no está activo: al contratar te contactaremos para el pago.</p>
            @endunless
        @else
            <div class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white">
                @if ($servicios->isEmpty())
                    <p class="px-6 py-10 text-center text-sm text-atlantia-ink/60">Aún no has contratado planes ni publicidad.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead class="bg-[#fbf8f7] text-xs font-bold text-atlantia-ink/55">
                                <tr><th class="px-4 py-2.5">Servicio</th><th class="px-4 py-2.5">Fechas</th><th class="px-4 py-2.5">Monto</th><th class="px-4 py-2.5">Estado</th><th class="px-4 py-2.5">Resultados</th></tr>
                            </thead>
                            <tbody class="divide-y divide-atlantia-rose/10">
                                @foreach ($servicios as $servicio)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-atlantia-ink">{{ $servicio->etiquetaTipo() }}</td>
                                        <td class="px-4 py-3 text-xs text-atlantia-ink/70">{{ $servicio->inicia_at?->format('d/m/Y') }} – {{ $servicio->termina_at?->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">{{ (float) $servicio->monto > 0 ? 'Q '.number_format((float) $servicio->monto, 2) : 'Incluido' }}</td>
                                        <td class="px-4 py-3 text-xs font-bold">{{ $servicio->etiquetaEstado() }}</td>
                                        <td class="px-4 py-3 text-xs text-atlantia-ink/70">
                                            @if (in_array($servicio->tipo, ['banner', 'patrocinado'], true))
                                                {{ number_format($servicio->tipo === 'banner' ? (int) ($servicio->referencia?->vistas ?? 0) : $servicio->vistas) }} vistas ·
                                                {{ number_format($servicio->tipo === 'banner' ? (int) ($servicio->referencia?->clics ?? 0) : $servicio->clics) }} clics
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </section>

    {{-- Ventana para pedir cotización del plan Personalizado --}}
    @unless ($cotizacionPendiente)
        <div @class(['fixed inset-0 z-[80] items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm', 'flex' => $errors->hasAny(['productos', 'telefono', 'horario', 'mensaje']), 'hidden' => ! $errors->hasAny(['productos', 'telefono', 'horario', 'mensaje'])]) data-ventana-cotizacion role="dialog" aria-modal="true" aria-labelledby="titulo-cotizacion">
            <form method="POST" action="{{ route('vendedor.plan.cotizacion') }}" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl" data-protect-submit>
                @csrf
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="titulo-cotizacion" class="text-lg font-black text-atlantia-ink">Plan a tu medida</h2>
                        <p class="mt-0.5 text-sm text-atlantia-ink/60">Cuéntanos un poco y te llamamos con un precio para tu tienda.</p>
                    </div>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-atlantia-ink/50 hover:bg-atlantia-blush" data-cerrar-cotizacion aria-label="Cerrar">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <label class="block">
                        <span class="text-xs font-bold text-atlantia-ink">¿Cuántos productos quieres vender?</span>
                        <input type="number" name="productos" min="1" max="100000" value="{{ old('productos') }}" placeholder="Ej. 2500" required class="{{ $campo }} mt-1">
                        @error('productos') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Teléfono</span>
                            <input type="tel" name="telefono" value="{{ old('telefono', $vendor->telefono_publico ?? auth()->user()->phone) }}" required class="{{ $campo }} mt-1">
                            @error('telefono') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Mejor hora</span>
                            <select name="horario" class="{{ $campo }} mt-1">
                                <option value="cualquiera">Cualquiera</option>
                                <option value="manana" @selected(old('horario') === 'manana')>Mañana</option>
                                <option value="tarde" @selected(old('horario') === 'tarde')>Tarde</option>
                            </select>
                        </label>
                    </div>
                    <label class="block">
                        <span class="text-xs font-bold text-atlantia-ink">¿Algo más que debamos saber? <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                        <textarea name="mensaje" rows="3" maxlength="1000" placeholder="Ej. Tengo dos sucursales y quiero banner todo el mes." class="mt-1 w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('mensaje') }}</textarea>
                    </label>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-lg px-4 text-sm font-bold text-atlantia-ink/60 hover:bg-atlantia-blush" data-cerrar-cotizacion>Cancelar</button>
                    <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Enviar solicitud</button>
                </div>
            </form>
        </div>

        <script @nonce>
            (() => {
                const ventana = document.querySelector('[data-ventana-cotizacion]');
                if (!ventana) return;
                const abrir = () => { ventana.classList.remove('hidden'); ventana.classList.add('flex'); ventana.querySelector('input[name="productos"]')?.focus(); };
                const cerrar = () => { ventana.classList.add('hidden'); ventana.classList.remove('flex'); };
                document.querySelectorAll('[data-abrir-cotizacion]').forEach((boton) => boton.addEventListener('click', abrir));
                ventana.querySelectorAll('[data-cerrar-cotizacion]').forEach((boton) => boton.addEventListener('click', cerrar));
                ventana.addEventListener('click', (evento) => { if (evento.target === ventana) cerrar(); });
                document.addEventListener('keydown', (evento) => { if (evento.key === 'Escape') cerrar(); });
            })();
        </script>
    @endunless
@endsection
