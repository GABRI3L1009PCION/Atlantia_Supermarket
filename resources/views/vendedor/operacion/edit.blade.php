@extends('layouts.app')

@section('content')
    @php
        $apertura = old('hora_apertura', substr((string) ($vendor->hora_apertura ?? $estado['apertura']), 0, 5));
        $cierre = old('hora_cierre', substr((string) ($vendor->hora_cierre ?? $estado['cierre']), 0, 5));
        $tono = $estado['pausado'] ? 'pausa' : ($estado['abierto'] ? 'abierta' : 'cerrada');
        $tonos = [
            'abierta' => ['border-emerald-200 bg-emerald-50', 'bg-emerald-500', 'Abierta, recibiendo pedidos'],
            'cerrada' => ['border-amber-200 bg-amber-50', 'bg-amber-500', 'Cerrada por horario'],
            'pausa' => ['border-rose-200 bg-rose-50', 'bg-rose-500', 'En pausa: no recibes pedidos'],
        ];
        $campo = 'h-10 w-full rounded-lg border border-atlantia-rose/30 px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Horario y pausa</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Cuándo atiendes, cuánto tardas en preparar y cómo te avisamos de pedidos nuevos.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            {{-- Estado y pausa --}}
            <article class="rounded-xl border p-5 {{ $tonos[$tono][0] }}" data-estado-tienda>
                <p class="text-xs font-bold uppercase tracking-wide text-atlantia-ink/55">Ahora mismo</p>
                <p class="mt-1 flex items-center gap-2 text-xl font-black text-atlantia-ink">
                    <span class="h-2.5 w-2.5 rounded-full {{ $tonos[$tono][1] }}"></span>
                    {{ $tonos[$tono][2] }}
                </p>
                @if ($estado['mensaje'])
                    <p class="mt-1 text-sm text-atlantia-ink/70">{{ $estado['mensaje'] }}</p>
                @elseif ($estado['abierto'])
                    <p class="mt-1 text-sm text-atlantia-ink/70">Cierras hoy a las {{ \App\Support\HorarioTienda::legible($estado['cierre']) }}.</p>
                @endif
                @if (! $estado['abierto'] && ! $estado['pausado'])
                    <p class="mt-1 text-xs text-atlantia-ink/55">Fuera de horario solo recibes pedidos programados.</p>
                @endif

                @if ($estado['pausado'])
                    <form method="POST" action="{{ route('vendedor.operacion.reanudar') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="h-10 w-full rounded-lg bg-emerald-600 px-5 text-sm font-bold text-white hover:bg-emerald-700 sm:w-auto">Reanudar pedidos ahora</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('vendedor.operacion.pausar') }}" class="mt-4 border-t border-black/5 pt-4">
                        @csrf
                        <p class="text-sm font-black text-atlantia-ink">Pausar la tienda</p>
                        <p class="text-xs text-atlantia-ink/60">Si tienes muchos pedidos o se te terminó algo. Los clientes verán que no estás recibiendo pedidos.</p>
                        <div class="mt-2.5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($pausas as $valor => $etiqueta)
                                <label class="cursor-pointer rounded-lg border border-atlantia-rose/25 bg-white px-2 py-2 text-center text-sm font-bold text-atlantia-ink has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-wine has-[:checked]:text-white">
                                    <input type="radio" name="duracion" value="{{ $valor }}" class="sr-only" @checked($loop->index === 1)>
                                    {{ $etiqueta }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-2 flex gap-2">
                            <label for="motivo" class="sr-only">Motivo (opcional, solo para ti)</label>
                            <input id="motivo" name="motivo" maxlength="160" placeholder="Motivo (opcional, solo lo ves tú)" class="{{ $campo }} bg-white">
                            <button type="submit" class="h-10 shrink-0 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700">Pausar</button>
                        </div>
                    </form>
                @endif
            </article>

            {{-- Horario y preferencias --}}
            <form method="POST" action="{{ route('vendedor.operacion.update') }}" class="rounded-xl border border-atlantia-rose/15 bg-white p-5 shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-black text-atlantia-ink">Horario de atención</h2>

                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="hora_apertura" class="text-xs font-bold text-atlantia-ink/70">Abres a las</label>
                        <input id="hora_apertura" name="hora_apertura" type="time" value="{{ $apertura }}" required class="mt-1 {{ $campo }}">
                        @error('hora_apertura') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="hora_cierre" class="text-xs font-bold text-atlantia-ink/70">Cierras a las</label>
                        <input id="hora_cierre" name="hora_cierre" type="time" value="{{ $cierre }}" required class="mt-1 {{ $campo }}">
                        @error('hora_cierre') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="tiempo_preparacion_min" class="text-xs font-bold text-atlantia-ink/70">Preparas un pedido en</label>
                        <div class="relative mt-1">
                            <input id="tiempo_preparacion_min" name="tiempo_preparacion_min" type="number" min="5" max="180" value="{{ old('tiempo_preparacion_min', $vendor->tiempo_preparacion_min ?? 20) }}" required class="{{ $campo }} pr-12">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-atlantia-ink/50">min</span>
                        </div>
                        @error('tiempo_preparacion_min') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 border-t border-atlantia-rose/10 pt-4">
                    <label for="pedido_minimo" class="text-sm font-black text-atlantia-ink">Pedido mínimo</label>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <div class="relative w-36">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-bold text-atlantia-ink/45">Q</span>
                            <input id="pedido_minimo" name="pedido_minimo" type="number" min="0" max="5000" step="1" value="{{ old('pedido_minimo', $vendor->pedido_minimo !== null ? (int) $vendor->pedido_minimo : '') }}" placeholder="Sin mínimo" class="{{ $campo }} pl-7">
                        </div>
                        <p class="min-w-0 flex-1 text-xs text-atlantia-ink/55">Lo menos que te pueden pedir en productos, sin contar el envío. Déjalo vacío si no quieres mínimo.</p>
                    </div>
                    @error('pedido_minimo') <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 border-t border-atlantia-rose/10 pt-4">
                    <span>
                        <span class="block text-sm font-black text-atlantia-ink">Sonido al llegar un pedido</span>
                        <span class="block text-xs text-atlantia-ink/55">Suena en la pantalla de Pedidos y te deja imprimir la comanda.</span>
                    </span>
                    <span class="relative inline-flex shrink-0 items-center">
                        <input type="checkbox" name="alerta_sonora_pedidos" value="1" @checked(old('alerta_sonora_pedidos', $vendor->alerta_sonora_pedidos ?? true)) class="peer sr-only">
                        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-atlantia-wine peer-focus-visible:ring-2 peer-focus-visible:ring-atlantia-rose"></span>
                        <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar cambios</button>
                </div>
            </form>
        </div>
    </section>
@endsection
