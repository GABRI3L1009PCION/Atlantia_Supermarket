@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Zonas de entrega" subtitle="Gestiona la cobertura operativa por municipio y costo base." />

            <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
                <form method="POST" action="{{ route('admin.zonas-entrega.store') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear zona global</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                            <input name="nombre" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Slug</label>
                            <input name="slug" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Descripcion</label>
                            <textarea name="descripcion" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"></textarea>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Municipio</label>
                                <select name="municipio" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                    @foreach (['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'] as $municipio)
                                        <option value="{{ $municipio }}">{{ $municipio }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Costo base</label>
                                <input name="costo_base" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Latitud centro</label>
                                <input name="latitude_centro" type="number" step="0.00000001" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Longitud centro</label>
                                <input name="longitude_centro" type="number" step="0.00000001" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                    </div>

                    <label class="mt-4 inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="activa" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                        <span>Zona activa para asignacion</span>
                    </label>

                    <x-ui.button type="submit" class="mt-5 w-full">Guardar zona</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Cobertura activa</h2>

                    <div class="mt-5 space-y-4">
                        @forelse ($zonas as $zona)
                            <form method="POST" action="{{ route('admin.zonas-entrega.update', $zona) }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr_0.8fr_0.8fr_auto]">
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Zona</label>
                                        <input name="nombre" type="text" value="{{ $zona->nombre }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                        <input name="slug" type="hidden" value="{{ $zona->slug }}">
                                        <textarea name="descripcion" rows="2" class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">{{ $zona->descripcion }}</textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Municipio</label>
                                        <select name="municipio" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                            @foreach (['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'] as $municipio)
                                                <option value="{{ $municipio }}" @selected($zona->municipio === $municipio)>{{ $municipio }}</option>
                                            @endforeach
                                        </select>
                                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                                            <input name="latitude_centro" type="number" step="0.00000001" value="{{ $zona->latitude_centro }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="Latitud">
                                            <input name="longitude_centro" type="number" step="0.00000001" value="{{ $zona->longitude_centro }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="Longitud">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Costo base</label>
                                        <input name="costo_base" type="number" step="0.01" min="0" value="{{ $zona->costo_base }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                    </div>
                                    <div class="flex items-end">
                                        <label class="inline-flex items-center gap-2 rounded-md border border-atlantia-rose/25 bg-white px-3 py-2 text-sm">
                                            <input type="hidden" name="activa" value="0">
                                            <input type="checkbox" name="activa" value="1" @checked($zona->activa) class="rounded border-atlantia-rose text-atlantia-wine">
                                            <span>Activa</span>
                                        </label>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <x-ui.button type="submit">Guardar</x-ui.button>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.zonas-entrega.destroy', $zona) }}" class="flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Eliminar zona</button>
                            </form>
                        @empty
                            <p class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream px-4 py-6 text-center text-atlantia-ink/60">
                                No hay zonas registradas.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-4">{{ $zonas->links() }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
