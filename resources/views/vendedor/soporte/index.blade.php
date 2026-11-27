@extends('layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $etiqueta = 'text-xs font-bold text-atlantia-ink/70';
    @endphp

    <section class="mx-auto max-w-[900px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Ayuda y soporte</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">¿Un pago que no llegó, un problema con un repartidor o con un pedido? Escríbenos y te respondemos aquí mismo.</p>
        </div>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <h2 class="text-base font-black text-atlantia-ink">Nuevo mensaje</h2>
            <form method="POST" action="{{ route('vendedor.soporte.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="block">
                    <span class="{{ $etiqueta }}">¿De qué se trata?</span>
                    <select name="categoria" required class="{{ $campo }}">
                        <option value="">Elige un tema</option>
                        @foreach ($categorias as $clave => $texto)
                            <option value="{{ $clave }}" @selected(old('categoria') === $clave)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    @error('categoria') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Asunto</span>
                    <input type="text" name="asunto" value="{{ old('asunto') }}" required maxlength="120" placeholder="Ej. No me llegó el pago del lunes" class="{{ $campo }}">
                    @error('asunto') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block sm:col-span-2">
                    <span class="{{ $etiqueta }}">Cuéntanos qué pasó</span>
                    <textarea name="mensaje" rows="4" required minlength="10" maxlength="2000" placeholder="Si es sobre un pedido, incluye el número del pedido." class="mt-1 w-full resize-y rounded-lg border border-atlantia-rose/30 bg-white px-3 py-2 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('mensaje') }}</textarea>
                    @error('mensaje') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="{{ $etiqueta }}">Teléfono para llamarte (opcional)</span>
                    <input type="tel" name="telefono" value="{{ old('telefono') }}" maxlength="20" placeholder="5555 1234" class="{{ $campo }}">
                    @error('telefono') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end justify-between gap-3">
                    <label class="inline-flex items-center gap-2 pb-2 text-sm text-atlantia-ink/80">
                        <input type="checkbox" name="urgente" value="1" @checked(old('urgente')) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-blush">
                        Es urgente (afecta mis ventas hoy)
                    </label>
                    <button type="submit" class="h-10 shrink-0 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Enviar</button>
                </div>
            </form>
        </article>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white">
            <h2 class="border-b border-atlantia-rose/10 px-5 py-3 text-base font-black text-atlantia-ink">Tus mensajes</h2>
            <ul class="divide-y divide-atlantia-rose/10">
                @forelse ($mensajes as $mensaje)
                    <li class="px-5 py-3" data-mensaje>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-atlantia-ink">{{ $mensaje->asunto }}</p>
                                <p class="text-xs text-atlantia-ink/55">
                                    {{ $mensaje->tipo === 'cotizacion' ? 'Cotización de plan' : ($categorias[$mensaje->categoria] ?? 'Soporte') }}
                                    · {{ $mensaje->created_at?->format('d/m/Y h:i a') }}
                                    @if ($mensaje->prioridad === 'alta' && $mensaje->tipo === 'soporte') · <span class="font-bold text-rose-700">Urgente</span> @endif
                                </p>
                            </div>
                            @if ($mensaje->atendido)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Respondido</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200">Esperando respuesta</span>
                            @endif
                        </div>
                        <details class="mt-1">
                            <summary class="cursor-pointer text-xs font-bold text-atlantia-wine">Ver mensaje</summary>
                            <p class="mt-1 whitespace-pre-line text-sm text-atlantia-ink/75">{{ $mensaje->mensaje }}</p>
                        </details>
                        @if ($mensaje->respuesta)
                            <div class="mt-2 rounded-lg bg-atlantia-blush/60 px-3 py-2 text-sm text-atlantia-ink">
                                <p class="text-[11px] font-black uppercase text-atlantia-wine">Respuesta de Atlantia · {{ $mensaje->atendido_at?->format('d/m/Y h:i a') }}</p>
                                <p class="mt-0.5 whitespace-pre-line">{{ $mensaje->respuesta }}</p>
                            </div>
                        @elseif ($mensaje->atendido)
                            <p class="mt-1 text-xs text-atlantia-ink/55">Lo atendimos por teléfono o correo el {{ $mensaje->atendido_at?->format('d/m/Y') }}.</p>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-atlantia-ink/55">Aún no nos has escrito.</li>
                @endforelse
            </ul>
        </article>
    </section>
@endsection
