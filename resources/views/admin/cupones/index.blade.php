@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                <x-page-header
                    title="Cupones y descuentos"
                    subtitle="Crea codigos promocionales, controla su uso y activa campañas comerciales."
                />

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-sm text-atlantia-ink/55">Activos</p>
                        <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ $cupones->where('activo', true)->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-sm text-atlantia-ink/55">Usos acumulados</p>
                        <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ number_format((int) $cupones->sum('usos_actuales')) }}</p>
                    </div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-sm text-atlantia-ink/55">Primera compra</p>
                        <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ $cupones->where('solo_primera_compra', true)->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                        <p class="text-sm text-atlantia-ink/55">Vigentes hoy</p>
                        <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ $cupones->filter(fn ($cupon) => $cupon->activo)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-wine">Crear cupon</h2>

                    <form method="POST" action="{{ route('admin.cupones.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Codigo</label>
                            <input type="text" name="codigo" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Tipo</label>
                                <select name="tipo" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                                    <option value="porcentaje">Porcentaje</option>
                                    <option value="monto_fijo">Monto fijo</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Valor</label>
                                <input type="number" step="0.01" min="0.01" name="valor" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Minimo compra</label>
                                <input type="number" step="0.01" min="0" name="minimo_compra" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Maximo descuento</label>
                                <input type="number" step="0.01" min="0" name="maximo_descuento" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Usos maximos</label>
                                <input type="number" min="1" name="usos_maximos" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                            </div>
                            <div>
                                <label class="text-sm font-bold text-atlantia-ink">Fecha inicio</label>
                                <input type="datetime-local" name="fecha_inicio" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Fecha fin</label>
                            <input type="datetime-local" name="fecha_fin" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3">
                        </div>
                        <div>
                            <label class="text-sm font-bold text-atlantia-ink">Descripcion</label>
                            <textarea name="descripcion" rows="3" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3"></textarea>
                        </div>
                        <label class="flex items-center gap-3 text-sm font-semibold text-atlantia-ink">
                            <input type="checkbox" name="activo" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                            Activo
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-atlantia-ink">
                            <input type="checkbox" name="solo_primera_compra" value="1" class="rounded border-atlantia-rose text-atlantia-wine">
                            Solo primera compra
                        </label>
                        <x-ui.button type="submit" class="w-full">Guardar cupon</x-ui.button>
                    </form>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-wine">Cupones registrados</h2>

                    <div class="mt-5 space-y-4">
                        @forelse ($cupones as $cupon)
                            <article class="rounded-2xl border border-atlantia-rose/20 p-5">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="text-lg font-black text-atlantia-ink">{{ $cupon->codigo }}</h3>
                                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $cupon->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $cupon->activo ? 'Activo' : 'Pausado' }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-atlantia-ink/65">{{ $cupon->descripcion ?: 'Sin descripcion operativa.' }}</p>
                                        <div class="mt-3 flex flex-wrap gap-4 text-sm text-atlantia-ink/70">
                                            <span>Tipo: {{ str_replace('_', ' ', $cupon->tipo) }}</span>
                                            <span>Valor: {{ number_format((float) $cupon->valor, 2) }}</span>
                                            <span>Usos: {{ $cupon->usos_actuales }}{{ $cupon->usos_maximos ? ' / ' . $cupon->usos_maximos : '' }}</span>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('admin.cupones.destroy', $cupon) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="secondary">Eliminar</x-ui.button>
                                    </form>
                                </div>

                                <form method="POST" action="{{ route('admin.cupones.update', $cupon) }}" class="mt-5 grid gap-4 border-t border-atlantia-rose/20 pt-5 xl:grid-cols-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="codigo" value="{{ $cupon->codigo }}" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    <select name="tipo" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                        <option value="porcentaje" @selected($cupon->tipo === 'porcentaje')>Porcentaje</option>
                                        <option value="monto_fijo" @selected($cupon->tipo === 'monto_fijo')>Monto fijo</option>
                                    </select>
                                    <input type="number" step="0.01" name="valor" value="{{ $cupon->valor }}" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    <input type="number" step="0.01" name="minimo_compra" value="{{ $cupon->minimo_compra }}" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    <input type="number" step="0.01" name="maximo_descuento" value="{{ $cupon->maximo_descuento }}" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    <input type="number" name="usos_maximos" value="{{ $cupon->usos_maximos }}" class="rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-atlantia-ink">
                                        <input type="checkbox" name="activo" value="1" @checked($cupon->activo) class="rounded border-atlantia-rose text-atlantia-wine">
                                        Activo
                                    </label>
                                    <label class="flex items-center gap-2 text-sm font-semibold text-atlantia-ink">
                                        <input type="checkbox" name="solo_primera_compra" value="1" @checked($cupon->solo_primera_compra) class="rounded border-atlantia-rose text-atlantia-wine">
                                        Primera compra
                                    </label>
                                    <div class="xl:col-span-2">
                                        <input type="datetime-local" name="fecha_inicio" value="{{ optional($cupon->fecha_inicio)->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    </div>
                                    <div class="xl:col-span-2">
                                        <input type="datetime-local" name="fecha_fin" value="{{ optional($cupon->fecha_fin)->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">
                                    </div>
                                    <div class="xl:col-span-6">
                                        <textarea name="descripcion" rows="2" class="w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm">{{ $cupon->descripcion }}</textarea>
                                    </div>
                                    <div class="xl:col-span-6">
                                        <x-ui.button type="submit">Actualizar cupon</x-ui.button>
                                    </div>
                                </form>
                            </article>
                        @empty
                            <x-ui.empty-state
                                title="Aun no hay cupones"
                                message="Crea tu primer codigo promocional para activar descuentos en checkout."
                            />
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $cupones->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
