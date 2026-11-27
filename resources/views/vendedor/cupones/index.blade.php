@extends('layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $etiqueta = 'text-xs font-bold text-atlantia-ink/70';
        $estado = function ($cupon): array {
            if (! $cupon->activo) {
                return ['Pausado', 'bg-slate-100 text-slate-600 ring-slate-200'];
            }
            if ($cupon->fecha_fin && $cupon->fecha_fin->isPast()) {
                return ['Vencido', 'bg-rose-50 text-rose-700 ring-rose-200'];
            }
            if ($cupon->usos_maximos && $cupon->usos_actuales >= $cupon->usos_maximos) {
                return ['Agotado', 'bg-rose-50 text-rose-700 ring-rose-200'];
            }
            if ($cupon->fecha_inicio && $cupon->fecha_inicio->isFuture()) {
                return ['Programado', 'bg-sky-50 text-sky-700 ring-sky-200'];
            }

            return ['Activo', 'bg-emerald-50 text-emerald-700 ring-emerald-200'];
        };
        $textoDescuento = fn ($cupon): string => $cupon->tipo === 'porcentaje'
            ? rtrim(rtrim(number_format((float) $cupon->valor, 2), '0'), '.').' % de descuento'
            : 'Q'.number_format((float) $cupon->valor, 2).' de descuento';
        $abrirNuevo = $errors->any() && old('_cupon') === null;
    @endphp

    <section class="mx-auto max-w-[1100px] space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-black text-atlantia-ink">Cupones</h1>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Crea códigos de descuento para tus clientes. Solo descuentan productos de {{ $vendor->business_name }} y el descuento sale de tu venta.</p>
            </div>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg bg-atlantia-wine px-4 text-sm font-bold text-white hover:bg-atlantia-wine-700" data-abrir-cupon>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo cupón
            </button>
        </div>

        {{-- Formulario para crear --}}
        <form method="POST" action="{{ route('vendedor.cupones.store') }}" @class(['rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm', 'hidden' => ! $abrirNuevo]) data-form-cupon>
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-black text-atlantia-ink">Nuevo cupón</h2>
                <button type="button" class="text-sm font-bold text-atlantia-ink/50 hover:text-atlantia-wine" data-cerrar-cupon>Cerrar ✕</button>
            </div>
            @include('vendedor.cupones.partials.campos', ['cupon' => null])
            <div class="mt-4 flex justify-end">
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Crear cupón</button>
            </div>
        </form>

        <article class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            @if ($cupones->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-3xl" aria-hidden="true">🎟️</p>
                    <p class="mt-2 text-base font-black text-atlantia-ink">Aún no tienes cupones</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-atlantia-ink/60">Un cupón como <strong>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit(preg_replace('/[^A-Za-z0-9]/', '', $vendor->business_name), 8, '')) }}10</strong> ayuda a que te compren por primera vez o vuelvan. Compártelo en tus redes o en tu local.</p>
                </div>
            @else
                <ul class="divide-y divide-atlantia-rose/10">
                    @foreach ($cupones as $cupon)
                        @php [$estadoTexto, $estadoClase] = $estado($cupon); @endphp
                        <li class="px-4 py-3" data-cupon="{{ $cupon->codigo }}">
                            <div class="grid items-center gap-3 sm:grid-cols-[minmax(0,1fr)_8rem_9rem_auto]">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="rounded-lg border border-dashed border-atlantia-wine/40 bg-atlantia-blush/40 px-2.5 py-1 font-mono text-sm font-black tracking-wide text-atlantia-wine hover:bg-atlantia-blush" data-copiar="{{ $cupon->codigo }}" title="Copiar código">{{ $cupon->codigo }}</button>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $estadoClase }}">{{ $estadoTexto }}</span>
                                    </div>
                                    <p class="mt-1 text-sm font-bold text-atlantia-ink">{{ $textoDescuento($cupon) }}{{ $cupon->maximo_descuento ? ' (máx. Q'.number_format((float) $cupon->maximo_descuento, 2).')' : '' }}</p>
                                    <p class="text-xs text-atlantia-ink/55">
                                        {{ (float) $cupon->minimo_compra > 0 ? 'Desde Q'.number_format((float) $cupon->minimo_compra, 2).' en tus productos' : 'Sin compra mínima' }}
                                        @if ($cupon->solo_primera_compra) · solo clientes nuevos @endif
                                        @if ($cupon->un_uso_por_cliente) · una vez por cliente @endif
                                        @if ($cupon->fecha_fin) · hasta el {{ $cupon->fecha_fin->format('d/m/Y') }} @endif
                                    </p>
                                </div>
                                <p class="text-sm text-atlantia-ink/70"><span class="text-xs text-atlantia-ink/45 sm:block">Usos</span> {{ $cupon->usos_actuales }}{{ $cupon->usos_maximos ? ' de '.$cupon->usos_maximos : '' }}</p>
                                <p class="text-sm text-atlantia-ink/70" title="Lo que has dado de descuento con este cupón"><span class="text-xs text-atlantia-ink/45 sm:block">Te ha costado</span> Q{{ number_format((float) ($descontado[$cupon->id] ?? 0), 2) }}</p>
                                <div class="flex items-center gap-1.5 sm:justify-end">
                                    <form method="POST" action="{{ route('vendedor.cupones.toggle', $cupon) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold text-atlantia-wine hover:bg-atlantia-blush">{{ $cupon->activo ? 'Pausar' : 'Activar' }}</button>
                                    </form>
                                    <details class="relative" data-close-outside>
                                        <summary class="grid h-9 w-9 cursor-pointer list-none place-items-center rounded-lg text-atlantia-ink/55 hover:bg-atlantia-blush hover:text-atlantia-wine [&::-webkit-details-marker]:hidden" aria-label="Más opciones de {{ $cupon->codigo }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                        </summary>
                                        <div class="absolute right-0 top-full z-30 mt-1 w-48 rounded-xl border border-atlantia-rose/15 bg-white p-1.5 shadow-[0_18px_40px_rgba(42,16,24,0.18)]">
                                            <button type="button" class="flex h-9 w-full items-center rounded-lg px-3 text-left text-sm font-semibold text-atlantia-ink/80 hover:bg-atlantia-blush" data-editar-cupon="{{ $cupon->uuid }}">Editar</button>
                                            <form method="POST" action="{{ route('vendedor.cupones.destroy', $cupon) }}" data-confirm="¿Eliminar el cupón {{ $cupon->codigo }}? Tus clientes ya no podrán usarlo.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex h-9 w-full items-center rounded-lg px-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('vendedor.cupones.update', $cupon) }}" @class(['mt-3 rounded-lg border border-atlantia-rose/15 bg-[#fbf8f7] p-3', 'hidden' => old('_cupon') !== $cupon->uuid]) data-form-editar="{{ $cupon->uuid }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_cupon" value="{{ $cupon->uuid }}">
                                @include('vendedor.cupones.partials.campos', ['cupon' => $cupon])
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" class="h-9 rounded-lg px-3 text-xs font-bold text-atlantia-ink/60 hover:bg-white" data-cancelar-editar="{{ $cupon->uuid }}">Cancelar</button>
                                    <button type="submit" class="h-9 rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Guardar cambios</button>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <p class="text-xs text-atlantia-ink/50">El cliente escribe el código al pagar. Si su carrito tiene productos de varias tiendas, tu cupón solo descuenta lo tuyo. Tus promociones automáticas (2x1, %) están en <a href="{{ route('vendedor.promociones.index') }}" class="font-bold text-atlantia-wine hover:underline">Promociones</a>.</p>
    </section>

    <script @nonce>
        (() => {
            const formNuevo = document.querySelector('[data-form-cupon]');
            document.querySelector('[data-abrir-cupon]')?.addEventListener('click', () => { formNuevo.classList.remove('hidden'); formNuevo.querySelector('input[name="codigo"]')?.focus(); });
            document.querySelector('[data-cerrar-cupon]')?.addEventListener('click', () => formNuevo.classList.add('hidden'));
            document.querySelectorAll('[data-editar-cupon]').forEach((boton) => boton.addEventListener('click', () => {
                document.querySelector(`[data-form-editar="${boton.dataset.editarCupon}"]`)?.classList.remove('hidden');
                boton.closest('details')?.removeAttribute('open');
            }));
            document.querySelectorAll('[data-cancelar-editar]').forEach((boton) => boton.addEventListener('click', () => {
                document.querySelector(`[data-form-editar="${boton.dataset.cancelarEditar}"]`)?.classList.add('hidden');
            }));
            document.querySelectorAll('[data-copiar]').forEach((boton) => boton.addEventListener('click', async () => {
                try { await navigator.clipboard.writeText(boton.dataset.copiar); } catch (_) { return; }
                const original = boton.textContent;
                boton.textContent = '¡Copiado!';
                setTimeout(() => { boton.textContent = original; }, 1200);
            }));
            document.querySelectorAll('[data-tipo-cupon]').forEach((select) => {
                const actualizar = () => {
                    const form = select.closest('form');
                    const esPorcentaje = select.value === 'porcentaje';
                    form.querySelector('[data-prefijo-valor]').textContent = esPorcentaje ? '%' : 'Q';
                    form.querySelector('[data-campo-maximo]')?.classList.toggle('hidden', !esPorcentaje);
                };
                select.addEventListener('change', actualizar);
                actualizar();
            });
        })();
    </script>
@endsection
