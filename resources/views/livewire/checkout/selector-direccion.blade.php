<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="direccion-title">
    <h2 id="direccion-title" class="text-lg font-semibold text-slate-950">Direccion de entrega</h2>
    <p class="mt-1 text-sm text-slate-600">Selecciona donde quieres recibir tu pedido.</p>

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
        <div class="mt-4 grid gap-3">
            @foreach ($direcciones as $direccion)
                <label
                    wire:key="checkout-direccion-{{ $direccion->id }}"
                    class="cursor-pointer rounded-lg border p-4"
                    @class([
                        'border-emerald-600 bg-emerald-50' => $direccionId === $direccion->id,
                        'border-slate-200 bg-white' => $direccionId !== $direccion->id,
                    ])
                >
                    <div class="flex gap-3">
                        <input
                            type="radio"
                            name="direccion_id"
                            value="{{ $direccion->id }}"
                            wire:click="seleccionarDireccion({{ $direccion->id }})"
                            @checked($direccionId === $direccion->id)
                            class="mt-1"
                        >

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-950">{{ $direccion->alias }}</span>
                                @if ($direccion->es_principal)
                                    <x-ui.badge variant="success">Principal</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $direccion->municipio }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $direccion->direccion_linea_1 }}</p>
                        </div>
                    </div>

                    @if (! $direccion->es_principal)
                        <button
                            type="button"
                            class="mt-3 text-sm font-semibold text-emerald-800"
                            wire:click.prevent="marcarPrincipal({{ $direccion->id }})"
                        >
                            Usar como principal
                        </button>
                    @endif
                </label>
            @endforeach
        </div>
    @endif
</section>
