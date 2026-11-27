@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-5xl py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="{{ $vendor->business_name }}" subtitle="Detalle comercial, fiscal y operativo del vendedor." />

            <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Comercio</p>
                        <p class="mt-2 text-sm text-atlantia-ink/65">Nombre comercial</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->business_name }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Municipio</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->municipio }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Direccion comercial</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->direccion_comercial }}</p>
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Contacto</p>
                        <p class="mt-2 text-sm text-atlantia-ink/65">Usuario</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->user?->name }}</p>
                        <p class="text-sm text-atlantia-ink/70">{{ $vendor->user?->email }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Telefono publico</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->telefono_publico ?: 'No definido' }}</p>
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Fiscal</p>
                        <p class="mt-2 text-sm text-atlantia-ink/65">NIT</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->fiscalProfile?->nit ?: 'Pendiente' }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Razon social</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->fiscalProfile?->razon_social ?: 'Pendiente' }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Regimen SAT</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->fiscalProfile?->regimen_sat ?: 'Pendiente' }}</p>
                    </div>

                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-xs font-semibold uppercase text-atlantia-rose">Actividad</p>
                        <p class="mt-2 text-sm text-atlantia-ink/65">Productos</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->productos->count() }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Zonas entrega</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->deliveryZones->count() }}</p>
                        <p class="mt-3 text-sm text-atlantia-ink/65">Comisiones</p>
                        <p class="font-semibold text-atlantia-ink">{{ $vendor->commissions->count() }}</p>
                    </div>
                </div>

                <aside class="space-y-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4">
                        <p class="text-sm font-bold text-atlantia-wine">Estado actual</p>
                        <p class="mt-2 rounded-md bg-atlantia-blush px-3 py-2 text-sm font-semibold text-atlantia-wine">
                            {{ $vendor->status }}
                        </p>

                        <div class="mt-4 space-y-3">
                            @if ($vendor->status === 'pending')
                                <form method="POST" action="{{ route('admin.vendedores.approve', $vendor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" class="w-full">Aprobar vendedor</x-ui.button>
                                </form>
                            @endif

                            @if ($vendor->status !== 'suspended')
                                <form method="POST" action="{{ route('admin.vendedores.suspend', $vendor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="motivo_suspension" value="Suspension administrativa desde panel.">
                                    <button type="submit" class="w-full rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                        Suspender vendedor
                                    </button>
                                </form>
                            @endif

                            @if ($vendor->status === 'suspended')
                                <form method="POST" action="{{ route('admin.vendedores.reactivate', $vendor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                        Reactivar vendedor
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @php($planVigente = app(\App\Services\Monetizacion\PlanesVendedorService::class)->planActual($vendor))
                    <form method="POST" action="{{ route('admin.vendedores.plan', $vendor) }}" class="rounded-xl border border-atlantia-rose/20 bg-white p-4" x-data="{ plan: '{{ $vendor->plan }}' }" data-admin-plan>
                        @csrf
                        <p class="text-sm font-bold text-atlantia-wine">Plan mensual</p>
                        <p class="mt-1 text-xs text-atlantia-ink/60">
                            Vigente: {{ config('monetizacion.planes.'.$planVigente.'.nombre') }}
                            @if ($vendor->plan_hasta) · pagado hasta {{ $vendor->plan_hasta->format('d/m/Y') }} @endif
                        </p>
                        <select name="plan" x-model="plan" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                            @foreach (config('monetizacion.planes') as $clave => $datosPlan)
                                <option value="{{ $clave }}">{{ $datosPlan['nombre'] }} · {{ $datosPlan['precio'] === null ? 'cotización' : 'Q '.(fmod((float) $datosPlan['precio'], 1.0) == 0.0 ? number_format((float) $datosPlan['precio'], 0) : number_format((float) $datosPlan['precio'], 2)) }}</option>
                            @endforeach
                        </select>
                        <div class="mt-2 grid grid-cols-3 gap-2" x-show="plan === 'personalizado'" x-cloak>
                            <input name="precio" type="number" min="0" step="1" value="{{ $vendor->plan === 'personalizado' ? (int) $vendor->monthly_rent : '' }}" placeholder="Q/mes" aria-label="Precio mensual" class="rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                            <input name="comision" type="number" min="0" max="50" step="0.5" value="{{ $vendor->plan === 'personalizado' ? (float) $vendor->commission_percentage : 0 }}" placeholder="% venta" aria-label="Comisión" class="rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                            <input name="limite_productos" type="number" min="1" value="{{ $vendor->limite_productos }}" placeholder="Productos" aria-label="Límite de productos" class="rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                        </div>
                        <button type="submit" class="mt-3 w-full rounded-md bg-atlantia-wine px-4 py-2 text-sm font-semibold text-white hover:bg-atlantia-wine-700">Asignar y registrar pago del mes</button>
                    </form>

                    @php($estadoHorario = \App\Support\HorarioTienda::estado($vendor))
                    <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4" data-admin-pausa>
                        <p class="text-sm font-bold text-atlantia-wine">Recepción de pedidos</p>
                        <p class="mt-2 text-sm font-semibold text-atlantia-ink">
                            {{ $estadoHorario['abierto'] ? 'Abierta' : ($estadoHorario['pausado'] ? 'En pausa' : 'Cerrada') }}
                            · {{ \App\Support\HorarioTienda::legible($estadoHorario['apertura']) }} a {{ \App\Support\HorarioTienda::legible($estadoHorario['cierre']) }}
                        </p>
                        @if ($estadoHorario['mensaje'])
                            <p class="mt-1 text-xs text-atlantia-ink/60">{{ $estadoHorario['mensaje'] }}</p>
                        @endif
                        @if ($estadoHorario['pausado'])
                            <form method="POST" action="{{ route('admin.vendedores.reanudar', $vendor) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Reanudar pedidos</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.vendedores.pausar', $vendor) }}" class="mt-3 flex gap-2">
                                @csrf
                                <select name="duracion" class="min-w-0 flex-1 rounded-md border border-atlantia-rose/30 px-2 py-2 text-sm">
                                    @foreach (\App\Http\Controllers\Vendedor\OperacionTiendaController::PAUSAS as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($loop->index === 1)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-md bg-atlantia-wine px-4 py-2 text-sm font-semibold text-white hover:bg-atlantia-wine-700">Pausar</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.vendedores.destroy', $vendor) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm font-bold text-red-800">Eliminar vendedor</p>
                        <p class="mt-2 text-sm text-red-700/80">
                            Se ocultaran todos sus productos y la cuenta quedara suspendida con eliminacion logica.
                        </p>
                        <button type="submit" class="mt-4 rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                            Eliminar vendedor
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
@endsection
