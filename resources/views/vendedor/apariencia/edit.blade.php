@extends('layouts.app')

@section('content')
    @php
        $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendor->business_name, 0, 2));
        $descripcion = old('descripcion', $vendor->descripcion);
        $elegidas = (array) old('categorias', $categoriasElegidas);
        $maximo = \App\Support\CategoriasNegocio::MAXIMO;
    @endphp

    <section class="mx-auto max-w-5xl space-y-4 pb-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-atlantia-ink">Perfil de la tienda</h1>
                <p class="mt-1 text-sm text-atlantia-ink/60">Así te ven tus clientes. Toca la portada o el logo para cambiarlos y elige qué vendes.</p>
            </div>
            <a href="{{ route('comercios.show', ['vendor' => $vendor->slug]) }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-lg border border-atlantia-rose/25 bg-white px-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">
                Ver mi tienda
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendedor.apariencia.update') }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-sm" data-apariencia>
            @csrf
            @method('PUT')

            {{-- Portada --}}
            <label class="group relative block w-full aspect-[4/1] sm:aspect-[6/1] cursor-pointer overflow-hidden bg-[linear-gradient(120deg,#3a0004_0%,#5e0008_60%,#7d1a22_100%)]">
                <img src="{{ $portadaUrl }}" alt="Portada actual" class="absolute inset-0 h-full w-full object-cover {{ $portadaUrl ? '' : 'hidden' }}" data-vista-portada>
                <span class="absolute inset-0 z-10 grid place-items-center bg-black/0 transition group-hover:bg-black/30">
                    <span class="inline-flex items-center gap-2 rounded-lg bg-white/95 px-4 py-2 text-sm font-bold text-atlantia-ink shadow {{ $portadaUrl ? 'opacity-0 group-hover:opacity-100' : '' }} transition" data-texto-portada>
                        <svg class="h-4 w-4 text-atlantia-wine" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.5" stroke="currentColor" stroke-width="1.8"/></svg>
                        {{ $portadaUrl ? 'Cambiar portada' : 'Subir portada' }}
                    </span>
                </span>
                <input type="file" name="portada" accept="image/jpeg,image/png,image/webp" class="sr-only" data-archivo="portada">
            </label>

            <div class="relative flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end">
                {{-- Logo --}}
                <label class="group relative -mt-12 grid h-24 w-24 shrink-0 cursor-pointer place-items-center overflow-hidden rounded-2xl bg-white p-2 text-2xl font-black text-atlantia-wine shadow-lg ring-4 ring-white">
                    <img src="{{ $logoUrl }}" alt="Logo actual" class="max-h-full max-w-full object-contain {{ $logoUrl ? '' : 'hidden' }}" data-vista-logo>
                    <span class="{{ $logoUrl ? 'hidden' : '' }}" data-iniciales>{{ $initials }}</span>
                    <span class="absolute inset-x-0 bottom-0 bg-black/55 py-1 text-center text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">Cambiar</span>
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-archivo="logo">
                </label>
                <div class="min-w-0 pb-1">
                    <p class="text-xl font-black text-atlantia-ink">{{ $vendor->business_name }}</p>
                    <p class="text-sm text-atlantia-ink/55">{{ $vendor->categoriaTexto() ?: 'Comercio local' }}</p>
                </div>
            </div>

            <fieldset class="border-t border-atlantia-rose/10 px-5 py-4" data-categorias data-maximo="{{ $maximo }}">
                <legend class="sr-only">Categorías de tu tienda</legend>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-xs font-black text-atlantia-ink">¿Qué vendes? <span class="font-semibold text-atlantia-ink/50">Elige de 1 a {{ $maximo }}; así te encuentran más fácil.</span></p>
                    <p class="text-xs font-semibold text-atlantia-ink/50"><span data-categorias-cuenta>{{ count($elegidas) }}</span>/{{ $maximo }}</p>
                </div>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($opcionesCategorias as $clave => $etiqueta)
                        <label class="inline-flex cursor-pointer select-none items-center gap-1.5 rounded-full border border-atlantia-rose/25 bg-white px-3 py-1.5 text-xs font-bold text-atlantia-ink/75 transition hover:border-atlantia-rose/50 has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-wine has-[:checked]:text-white has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-40">
                            <input type="checkbox" name="categorias[]" value="{{ $clave }}" class="sr-only" @checked(in_array($clave, $elegidas, true))>
                            <svg class="hidden h-3.5 w-3.5 [label:has(:checked)_&]:block" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 12 4 4 8-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $etiqueta }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-5 border-t border-atlantia-rose/10 p-5 md:grid-cols-[minmax(0,1fr)_16rem]">
                <div>
                    <label for="descripcion" class="flex items-center justify-between text-xs font-black text-atlantia-ink">
                        Cuéntale a tus clientes qué vendes
                        <span class="font-semibold text-atlantia-ink/45"><span data-contador>{{ mb_strlen((string) $descripcion) }}</span>/500</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" rows="4" maxlength="500" placeholder="Ej. Abarrotes, productos de limpieza y cuidado personal. Entregamos en todo Santo Tomás." class="mt-1 w-full resize-none rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm leading-6 focus:border-atlantia-wine focus:outline-none focus:ring-2 focus:ring-atlantia-blush" data-descripcion>{{ $descripcion }}</textarea>
                </div>

                <div class="space-y-2 rounded-xl bg-[#f8f5f4] p-4 text-xs leading-5 text-atlantia-ink/65">
                    <p class="font-black text-atlantia-ink">Consejos</p>
                    <p><strong>Portada:</strong> foto horizontal de tu local o tus productos. Medida exacta <strong>1800 × 300 px</strong> (o 2400 × 400 para más nitidez), hasta 5 MB. Pon el nombre y el texto al centro: en celulares se recortan un poco los lados.</p>
                    <p><strong>Logo:</strong> imagen cuadrada, mínimo 120 × 120 px, hasta 2 MB.</p>
                    <p>Formatos: JPG, PNG o WEBP.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-atlantia-rose/10 bg-[#fbf9f8] px-5 py-3">
                @if ($portadaUrl)
                    <button type="submit" form="quitar-portada" class="text-sm font-bold text-red-700 hover:underline" data-confirm="¿Quitar la portada de tu tienda?">Quitar portada</button>
                @else
                    <span></span>
                @endif
                <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-6 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar cambios</button>
            </div>
        </form>

        @if ($portadaUrl)
            <form id="quitar-portada" method="POST" action="{{ route('vendedor.apariencia.portada.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </section>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            const form = document.querySelector('[data-apariencia]');
            if (!form) return;

            const mostrar = (input, imagen, ocultar) => {
                const archivo = input.files?.[0];
                if (!archivo || !archivo.type.startsWith('image/')) return;
                imagen.src = URL.createObjectURL(archivo);
                imagen.classList.remove('hidden');
                ocultar?.classList.add('hidden');
            };

            form.querySelector('[data-archivo="portada"]')?.addEventListener('change', (evento) => {
                mostrar(evento.target, form.querySelector('[data-vista-portada]'));
                const texto = form.querySelector('[data-texto-portada]');
                texto?.classList.add('opacity-0', 'group-hover:opacity-100');
            });

            form.querySelector('[data-archivo="logo"]')?.addEventListener('change', (evento) => {
                mostrar(evento.target, form.querySelector('[data-vista-logo]'), form.querySelector('[data-iniciales]'));
            });

            const grupo = form.querySelector('[data-categorias]');
            if (grupo) {
                const maximo = Number(grupo.dataset.maximo || 3);
                const casillas = [...grupo.querySelectorAll('input[type="checkbox"]')];
                const cuenta = grupo.querySelector('[data-categorias-cuenta]');
                const actualizar = () => {
                    const marcadas = casillas.filter((casilla) => casilla.checked).length;
                    casillas.forEach((casilla) => { casilla.disabled = !casilla.checked && marcadas >= maximo; });
                    if (cuenta) cuenta.textContent = marcadas;
                };
                casillas.forEach((casilla) => casilla.addEventListener('change', actualizar));
                actualizar();
            }

            const descripcion = form.querySelector('[data-descripcion]');
            const contador = form.querySelector('[data-contador]');
            descripcion?.addEventListener('input', () => { contador.textContent = descripcion.value.length; });
        })();
    </script>
@endpush
