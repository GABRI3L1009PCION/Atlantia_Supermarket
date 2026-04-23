<section
    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
    aria-labelledby="direccion-title"
>
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 id="direccion-title" class="flex items-center gap-3 text-2xl font-bold text-atlantia-ink">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-md bg-atlantia-wine text-base text-white"
                >
                    1
                </span>
                Direccion de entrega
            </h2>
            <p class="mt-2 text-sm text-atlantia-ink/70">
                Selecciona donde quieres recibir tu pedido.
            </p>
        </div>

        <a
            href="{{ route('cliente.direcciones.index') }}"
            class="inline-flex rounded-md px-3 py-2 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush"
        >
            + Administrar
        </a>
    </header>

    @if ($direcciones->isEmpty())
        <div class="mt-4">
            <x-ui.empty-state
                title="No tienes direcciones activas"
                message="Agrega una direccion antes de finalizar tu compra."
            >
                <a href="{{ route('cliente.direcciones.index') }}" class="text-sm font-semibold text-emerald-800">
                    Administrar direcciones
                </a>
            </x-ui.empty-state>
        </div>
    @else
        @error('direccion_id')
            <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $message }}
            </p>
        @enderror

        <div class="mt-5 grid gap-3">
            @foreach ($direcciones as $direccion)
                <label
                    wire:key="checkout-direccion-{{ $direccion->id }}"
                    class="relative cursor-pointer rounded-lg border-2 p-5 transition hover:border-atlantia-wine/60"
                    @class([
                        'border-atlantia-wine bg-atlantia-blush' => $direccionId === $direccion->id,
                        'border-slate-200 bg-white' => $direccionId !== $direccion->id,
                    ])
                >
                    <div class="flex gap-4">
                        <input
                            type="radio"
                            name="direccion_id"
                            value="{{ $direccion->id }}"
                            wire:click="seleccionarDireccion({{ $direccion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="seleccionarDireccion({{ $direccion->id }})"
                            @checked($direccionId === $direccion->id)
                            class="mt-1 border-atlantia-rose text-atlantia-wine focus:ring-atlantia-rose"
                        >

                        <div class="min-w-0 flex-1">
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
                            <p class="mt-3 font-bold text-atlantia-ink">
                                {{ $direccion->nombre_contacto ?: auth()->user()?->name }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">
                                {{ $direccion->direccion_linea_1 }}
                                @if ($direccion->zona_o_barrio)
                                    <br>{{ $direccion->zona_o_barrio }}
                                @endif
                                <br>{{ $direccion->municipio }}
                                @if ($direccion->telefono_contacto)
                                    - {{ $direccion->telefono_contacto }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if (! $direccion->es_principal)
                        <button
                            type="button"
                            class="mt-3 text-sm font-semibold text-atlantia-wine hover:underline"
                            wire:click.prevent="marcarPrincipal({{ $direccion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="marcarPrincipal({{ $direccion->id }})"
                        >
                            <span wire:loading.remove wire:target="marcarPrincipal({{ $direccion->id }})">Usar como principal</span>
                            <span wire:loading wire:target="marcarPrincipal({{ $direccion->id }})">Actualizando...</span>
                        </button>
                    @endif
                </label>
            @endforeach

            <a
                href="{{ route('cliente.direcciones.index') }}"
                class="flex min-h-28 items-center justify-center rounded-lg border-2 border-dashed border-slate-300
                    bg-white px-4 py-6 text-center text-sm font-bold text-atlantia-ink/65 hover:border-atlantia-wine
                    hover:text-atlantia-wine"
            >
                + Agregar nueva direccion
            </a>
        </div>
    @endif
</section>
