@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Categorias" subtitle="Mantiene ordenado el catalogo que ven clientes y vendedores." />

            <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
                <form method="POST" action="{{ route('admin.categorias.store') }}" class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear categoria</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Categoria padre</label>
                            <select name="parent_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="">Sin categoria padre</option>
                                @foreach ($categorias as $categoriaPadre)
                                    <option value="{{ $categoriaPadre->id }}">{{ $categoriaPadre->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
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
                                <label class="text-sm font-semibold text-atlantia-ink">Icono</label>
                                <input name="icon" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="shopping-basket">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                                <input name="orden" type="number" min="0" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>
                    </div>

                    <label class="mt-4 inline-flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                        <span>Visible en catalogo</span>
                    </label>

                    <x-ui.button type="submit" class="mt-5 w-full">Guardar categoria</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Arbol de categorias</h2>

                    <div class="mt-5 space-y-4">
                        @forelse ($categorias as $categoria)
                            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}" class="grid gap-3 xl:grid-cols-[1fr_0.8fr_0.7fr_auto]">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Nombre</label>
                                        <input name="nombre" type="text" value="{{ $categoria->nombre }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                        <textarea name="descripcion" rows="2" class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">{{ $categoria->descripcion }}</textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Organizacion</label>
                                        <select name="parent_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                            <option value="">Categoria raiz</option>
                                            @foreach ($categorias as $categoriaPadre)
                                                @continue((int) $categoriaPadre->id === (int) $categoria->id)
                                                <option value="{{ $categoriaPadre->id }}" @selected((int) $categoria->parent_id === (int) $categoriaPadre->id)>{{ $categoriaPadre->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                                            <input name="slug" type="text" value="{{ $categoria->slug }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="slug">
                                            <input name="icon" type="text" value="{{ $categoria->icon }}" class="rounded-md border border-atlantia-rose/35 px-3 py-2" placeholder="icono">
                                        </div>
                                    </div>
                                    <div class="grid gap-2">
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Orden</label>
                                            <input name="orden" type="number" min="0" value="{{ $categoria->orden }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                        </div>
                                        <label class="inline-flex items-center gap-2 rounded-md border border-atlantia-rose/25 bg-white px-3 py-2 text-sm">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($categoria->is_active) class="rounded border-atlantia-rose text-atlantia-wine">
                                            <span>Activa</span>
                                        </label>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <x-ui.button type="submit">Guardar</x-ui.button>
                                    </div>
                                </form>

                                @if ($categoria->children->isNotEmpty())
                                    <div class="mt-3 rounded-lg border border-atlantia-rose/15 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Subcategorias</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($categoria->children as $child)
                                                <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-semibold text-atlantia-wine">
                                                    {{ $child->nombre }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}" class="mt-3 flex justify-end">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">
                                        Desactivar categoria
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream px-4 py-6 text-center text-atlantia-ink/60">
                                No hay categorias registradas.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
