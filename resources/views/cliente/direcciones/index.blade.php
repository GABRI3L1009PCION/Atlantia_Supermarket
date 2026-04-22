@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header
            title="Mis direcciones"
            subtitle="Guarda los lugares donde quieres recibir tus pedidos de Atlantia."
        />

        <div class="grid gap-6 lg:grid-cols-[420px_1fr]">
            <form
                method="POST"
                action="{{ route('cliente.direcciones.store') }}"
                class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-6"
            >
                @csrf

                <h2 class="text-xl font-bold text-atlantia-ink">Agregar direccion</h2>
                <p class="mt-1 text-sm text-atlantia-ink/70">
                    Escribe la direccion como se la dirias a un repartidor local.
                </p>

                <div class="mt-5 grid gap-4">
                    <div>
                        <label for="alias" class="text-sm font-bold text-atlantia-ink">Nombre corto</label>
                        <input
                            id="alias"
                            name="alias"
                            type="text"
                            value="{{ old('alias', 'Casa') }}"
                            placeholder="Casa, Trabajo, Oficina"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >
                        @error('alias')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nombre_contacto" class="text-sm font-bold text-atlantia-ink">
                            Quien recibe
                        </label>
                        <input
                            id="nombre_contacto"
                            name="nombre_contacto"
                            type="text"
                            value="{{ old('nombre_contacto', auth()->user()?->name) }}"
                            placeholder="Nombre de la persona que recibe"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >
                        @error('nombre_contacto')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="telefono_contacto" class="text-sm font-bold text-atlantia-ink">
                            Telefono de contacto
                        </label>
                        <input
                            id="telefono_contacto"
                            name="telefono_contacto"
                            type="tel"
                            value="{{ old('telefono_contacto') }}"
                            placeholder="Ej. 55123344"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >
                        @error('telefono_contacto')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="municipio" class="text-sm font-bold text-atlantia-ink">Municipio</label>
                        <select
                            id="municipio"
                            name="municipio"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >
                            @foreach (['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'] as $municipio)
                                <option value="{{ $municipio }}" @selected(old('municipio') === $municipio)>
                                    {{ $municipio }}
                                </option>
                            @endforeach
                        </select>
                        @error('municipio')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="zona_o_barrio" class="text-sm font-bold text-atlantia-ink">
                            Zona, barrio o colonia
                        </label>
                        <input
                            id="zona_o_barrio"
                            name="zona_o_barrio"
                            type="text"
                            value="{{ old('zona_o_barrio') }}"
                            placeholder="Ej. Colonia San Manuel, Barrio El Rastro"
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >
                        @error('zona_o_barrio')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="direccion_linea_1" class="text-sm font-bold text-atlantia-ink">
                            Direccion exacta
                        </label>
                        <textarea
                            id="direccion_linea_1"
                            name="direccion_linea_1"
                            rows="3"
                            placeholder="Ej. 5a avenida, casa azul, porton negro, frente a la tienda..."
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >{{ old('direccion_linea_1') }}</textarea>
                        @error('direccion_linea_1')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="referencia" class="text-sm font-bold text-atlantia-ink">
                            Referencia para encontrar el lugar
                        </label>
                        <textarea
                            id="referencia"
                            name="referencia"
                            rows="2"
                            placeholder="Ej. A dos cuadras del parque, preguntar por Maria..."
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >{{ old('referencia') }}</textarea>
                        @error('referencia')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-md bg-atlantia-blush px-4 py-3 text-sm text-atlantia-ink">
                        <input
                            type="checkbox"
                            name="es_principal"
                            value="1"
                            @checked(old('es_principal', true))
                            class="mt-1 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                        >
                        <span>Usar como direccion principal para mis compras.</span>
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md bg-atlantia-wine px-5 py-3
                            text-sm font-bold text-white transition hover:bg-atlantia-wine-700 focus:outline-none
                            focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
                    >
                        Guardar direccion
                    </button>
                </div>
            </form>

            <div class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-atlantia-ink">Direcciones guardadas</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/70">
                            Puedes tener varias direcciones y elegir una en checkout.
                        </p>
                    </div>

                    <a
                        href="{{ route('cliente.checkout.create') }}"
                        class="inline-flex rounded-md border border-atlantia-rose/40 px-4 py-2 text-sm font-bold
                            text-atlantia-wine hover:bg-atlantia-blush"
                    >
                        Volver al checkout
                    </a>
                </div>

                @if ($direcciones->isEmpty())
                    <div class="mt-6 rounded-lg border-2 border-dashed border-slate-300 p-8 text-center">
                        <h3 class="font-bold text-atlantia-ink">Aun no tienes direcciones</h3>
                        <p class="mt-2 text-sm text-atlantia-ink/70">
                            Agrega tu primera direccion para poder finalizar pedidos.
                        </p>
                    </div>
                @else
                    <div class="mt-6 grid gap-4">
                        @foreach ($direcciones as $direccion)
                            <article
                                class="rounded-lg border p-5"
                                @class([
                                    'border-atlantia-wine bg-atlantia-blush' => $direccion->es_principal,
                                    'border-slate-200 bg-white' => ! $direccion->es_principal,
                                ])
                            >
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded bg-white px-2 py-1 text-xs font-bold uppercase text-atlantia-wine">
                                                {{ $direccion->alias }}
                                            </span>
                                            @if ($direccion->es_principal)
                                                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">
                                                    Principal
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="mt-3 font-bold text-atlantia-ink">
                                            {{ $direccion->nombre_contacto }}
                                        </h3>
                                        <p class="mt-1 text-sm leading-6 text-atlantia-ink/75">
                                            {{ $direccion->direccion_linea_1 }}
                                            @if ($direccion->zona_o_barrio)
                                                <br>{{ $direccion->zona_o_barrio }}
                                            @endif
                                            <br>{{ $direccion->municipio }} - {{ $direccion->telefono_contacto }}
                                            @if ($direccion->referencia)
                                                <br>Referencia: {{ $direccion->referencia }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @if (! $direccion->es_principal)
                                            <form
                                                method="POST"
                                                action="{{ route('cliente.direcciones.update', $direccion) }}"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="alias" value="{{ $direccion->alias }}">
                                                <input type="hidden" name="nombre_contacto" value="{{ $direccion->nombre_contacto }}">
                                                <input type="hidden" name="telefono_contacto" value="{{ $direccion->telefono_contacto }}">
                                                <input type="hidden" name="municipio" value="{{ $direccion->municipio }}">
                                                <input type="hidden" name="zona_o_barrio" value="{{ $direccion->zona_o_barrio }}">
                                                <input type="hidden" name="direccion_linea_1" value="{{ $direccion->direccion_linea_1 }}">
                                                <input type="hidden" name="direccion_linea_2" value="{{ $direccion->direccion_linea_2 }}">
                                                <input type="hidden" name="referencia" value="{{ $direccion->referencia }}">
                                                <input type="hidden" name="es_principal" value="1">
                                                <button
                                                    type="submit"
                                                    class="rounded-md border border-atlantia-rose/40 px-3 py-2 text-xs
                                                        font-bold text-atlantia-wine hover:bg-white"
                                                >
                                                    Hacer principal
                                                </button>
                                            </form>
                                        @endif

                                        <form
                                            method="POST"
                                            action="{{ route('cliente.direcciones.destroy', $direccion) }}"
                                            onsubmit="return confirm('Deseas eliminar esta direccion?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="rounded-md border border-red-200 px-3 py-2 text-xs font-bold
                                                    text-red-700 hover:bg-red-50"
                                            >
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
