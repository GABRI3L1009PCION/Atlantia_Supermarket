@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
@php
    $categoriaImagenUrl = static function (?string $imagen): ?string {
        if (! $imagen) {
            return null;
        }

        $path = trim($imagen);

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            $storagePath = parse_url($path, PHP_URL_PATH);

            if (is_string($storagePath) && \Illuminate\Support\Str::contains($storagePath, '/storage/')) {
                return $storagePath;
            }

            return $path;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['/storage/', 'storage/'])) {
            return '/'.ltrim($path, '/');
        }

        $path = \Illuminate\Support\Str::after($path, 'public/');

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return '/storage/'.ltrim($path, '/');
        }

        return null;
    };

    $totalCategorias = $categorias->count();
    $totalActivas = $categorias->where('is_active', true)->count();
    $totalHijas = $categorias->sum(fn ($categoria) => $categoria->children->count());
@endphp

<section class="-mx-4 -my-6 bg-atlantia-cream/40 px-4 py-6 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                <h1 class="mt-2 text-4xl font-black leading-tight text-atlantia-ink">Categorias</h1>
                <p class="mt-3 text-base text-atlantia-ink/60">Organiza el catalogo que ven clientes y vendedores.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md border border-atlantia-rose/35 bg-white px-5 py-3 text-sm font-black text-atlantia-wine shadow-sm transition hover:bg-atlantia-blush"
                    data-open-subcategory-modal
                >
                    <span class="text-xl leading-none">+</span>
                    Agregar subcategoria
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                    data-open-category-modal
                >
                    <span class="text-xl leading-none">+</span>
                    Nueva categoria
                </button>
            </div>
        </div>

        <div class="mx-auto mt-8 grid max-w-6xl gap-5 sm:grid-cols-3">
            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 px-5 py-5 text-center">
                <p class="text-3xl font-black text-atlantia-wine">{{ $totalCategorias }}</p>
                <p class="mt-1 text-sm font-semibold text-atlantia-ink/60">Principales</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 px-5 py-5 text-center">
                <p class="text-3xl font-black text-atlantia-wine">{{ $totalActivas }}</p>
                <p class="mt-1 text-sm font-semibold text-atlantia-ink/60">Activas</p>
            </div>
            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/60 px-5 py-5 text-center">
                <p class="text-3xl font-black text-atlantia-wine">{{ $totalHijas }}</p>
                <p class="mt-1 text-sm font-semibold text-atlantia-ink/60">Subcategorias</p>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-black text-atlantia-wine">Catalogo de categorias</h2>
                    <p class="text-sm text-atlantia-ink/65">Revisa la estructura y abre la edicion solo cuando la necesites.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-[minmax(220px,320px)_220px]">
                    <label class="relative block">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19C15.4 19 19 15.4 19 11C19 6.6 15.4 3 11 3C6.6 3 3 6.6 3 11C3 15.4 6.6 19 11 19Z" stroke="currentColor" stroke-width="2"/><path d="M20.5 20.5L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="search" class="w-full rounded-md border border-atlantia-rose/30 bg-white py-3 pl-12 pr-4 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" placeholder="Buscar categorias..." data-category-search>
                    </label>
                    <label class="relative block">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-atlantia-ink/45">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5H21L14 13V19L10 21V13L3 5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        </span>
                        <select class="w-full appearance-none rounded-md border border-atlantia-rose/30 bg-white py-3 pl-12 pr-9 text-sm font-semibold text-atlantia-ink/70 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" data-category-sort>
                            <option value="custom">Orden: Personalizado</option>
                            <option value="name">Orden: Nombre</option>
                            <option value="active">Orden: Activas primero</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-atlantia-ink/45">v</span>
                    </label>
                </div>
            </div>

            <div class="mt-5 grid gap-2" data-category-list>
                @forelse ($categorias as $categoria)
                    @php($imagenUrl = $categoriaImagenUrl($categoria->imagen))
                    <article
                        class="rounded-xl border border-atlantia-rose/20 bg-white px-4 py-3 transition hover:border-atlantia-rose/40 hover:bg-atlantia-cream/40"
                        data-category-card
                        data-name="{{ strtolower($categoria->nombre) }}"
                        data-search="{{ strtolower($categoria->nombre . ' ' . $categoria->slug . ' ' . $categoria->descripcion) }}"
                        data-order="{{ $categoria->orden }}"
                        data-active="{{ $categoria->is_active ? '1' : '0' }}"
                    >
                        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div class="flex min-w-0 gap-5">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-atlantia-rose/20 bg-atlantia-blush/60">
                                    @if ($imagenUrl)
                                        <img src="{{ $imagenUrl }}" alt="{{ $categoria->nombre }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-xl font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($categoria->nombre, 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-atlantia-ink">{{ $categoria->nombre }}</h3>
                                        <span @class([
                                            'rounded-md px-2.5 py-1 text-xs font-black ring-1',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $categoria->is_active,
                                            'bg-slate-100 text-slate-600 ring-slate-200' => ! $categoria->is_active,
                                        ])>
                                            {{ $categoria->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 max-w-3xl text-sm text-atlantia-ink/60">{{ $categoria->descripcion ?: 'Sin descripcion registrada.' }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-atlantia-ink/55">
                                        <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Slug: {{ $categoria->slug }}</span>
                                        <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Orden: {{ $categoria->orden }}</span>
                                        <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">{{ $categoria->children->count() }} subcategorias</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-3 lg:justify-end">
                                <button
                                    type="button"
                                    class="inline-flex min-w-28 items-center justify-center gap-2 rounded-md border border-atlantia-rose/30 px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                                    data-edit-category
                                    data-action="{{ route('admin.categorias.update', $categoria) }}"
                                    data-id="{{ $categoria->id }}"
                                    data-parent-id="{{ $categoria->parent_id }}"
                                    data-name="{{ e($categoria->nombre) }}"
                                    data-slug="{{ e($categoria->slug) }}"
                                    data-description="{{ e($categoria->descripcion) }}"
                                    data-order="{{ $categoria->orden }}"
                                    data-active="{{ $categoria->is_active ? '1' : '0' }}"
                                    data-image="{{ $imagenUrl }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20H8L18.5 9.5C19.6 8.4 19.6 6.6 18.5 5.5C17.4 4.4 15.6 4.4 14.5 5.5L4 16V20Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M13 7L17 11" stroke="currentColor" stroke-width="2"/></svg>
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex min-w-36 items-center justify-center gap-2 rounded-md border border-atlantia-rose/30 px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                                    data-open-subcategory-modal
                                    data-parent-id="{{ $categoria->id }}"
                                    data-parent-name="{{ e($categoria->nombre) }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8V5H18V8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6 16V13H18V16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 8V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 19H8V16H4V19Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 19H20V16H16V19Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                    Subcategorias
                                </button>
                                <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}" onsubmit="return confirm('Desactivar la categoria {{ addslashes($categoria->nombre) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-red-50 hover:text-red-600">
                                        Desactivar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream px-4 py-10 text-center">
                        <p class="text-base font-black text-atlantia-ink">Sin categorias registradas.</p>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Crea la primera categoria para organizar el catalogo.</p>
                        <button type="button" class="mt-4 rounded-lg bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700" data-open-category-modal>
                            Crear categoria
                        </button>
                    </div>
                @endforelse
                <div class="hidden rounded-xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream px-4 py-10 text-center" data-category-empty-search>
                    <p class="text-base font-black text-atlantia-ink">No hay categorias con ese criterio.</p>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Prueba con otro nombre, slug o descripcion.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div
    id="category-create-modal"
    class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="category-create-title"
>
    <div class="max-h-[92vh] w-full max-w-2xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                <h2 id="category-create-title" class="mt-1 text-2xl font-black text-atlantia-ink" data-create-title>Nueva categoria</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60" data-create-subtitle>Completa los datos para agregarla al catalogo.</p>
            </div>
            <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-category-modal>
                Cerrar
            </button>
        </div>

        <form method="POST" action="{{ route('admin.categorias.store') }}" enctype="multipart/form-data" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5">
            @csrf

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    Revisa los campos marcados antes de guardar la categoria.
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Imagen</label>
                    <input name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                    @error('imagen') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Categoria padre</label>
                    <select name="parent_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-create-parent>
                        <option value="">Sin categoria padre</option>
                        @foreach ($categorias as $categoriaPadre)
                            <option value="{{ $categoriaPadre->id }}" @selected((string) old('parent_id') === (string) $categoriaPadre->id)>{{ $categoriaPadre->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-atlantia-ink/55" data-create-parent-help>Dejalo vacio para crear una categoria principal.</p>
                    @error('parent_id') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                    <input name="nombre" type="text" value="{{ old('nombre') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required data-create-name>
                    @error('nombre') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Slug</label>
                    <input name="slug" type="text" value="{{ old('slug') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-create-slug>
                    @error('slug') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Descripcion</label>
                    <textarea name="descripcion" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">{{ old('descripcion') }}</textarea>
                    @error('descripcion') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                    <input name="orden" type="number" min="0" value="{{ old('orden', 0) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                    @error('orden') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <label class="inline-flex w-full items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-2.5 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="rounded border-atlantia-rose text-atlantia-wine">
                        <span class="font-semibold text-atlantia-ink">Visible en catalogo</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-category-modal>
                    Cancelar
                </button>
                <x-ui.button type="submit"><span data-create-submit>Crear categoria</span></x-ui.button>
            </div>
        </form>
    </div>
</div>

<div
    id="category-edit-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="category-edit-title"
>
    <div class="max-h-[92vh] w-full max-w-2xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                <h2 id="category-edit-title" class="mt-1 text-2xl font-black text-atlantia-ink">Editar categoria</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Actualiza nombre, jerarquia, imagen y visibilidad.</p>
            </div>
            <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-modal>
                Cerrar
            </button>
        </div>

        <form method="POST" action="#" enctype="multipart/form-data" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5" data-edit-form>
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Imagen actual</label>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-atlantia-rose/25 bg-atlantia-cream">
                            <img src="" alt="" class="hidden h-full w-full object-cover" data-edit-image-preview>
                            <span class="text-sm font-black text-atlantia-wine" data-edit-image-fallback>CA</span>
                        </div>
                        <input name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="min-w-0 flex-1 rounded-md border border-atlantia-rose/35 px-3 py-2">
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Categoria padre</label>
                    <select name="parent_id" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-parent>
                        <option value="">Sin categoria padre</option>
                        @foreach ($categorias as $categoriaPadre)
                            <option value="{{ $categoriaPadre->id }}" data-category-option-id="{{ $categoriaPadre->id }}">{{ $categoriaPadre->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Nombre</label>
                    <input name="nombre" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required data-edit-name>
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Slug</label>
                    <input name="slug" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-slug>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-semibold text-atlantia-ink">Descripcion</label>
                    <textarea name="descripcion" rows="3" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-description></textarea>
                </div>
                <div>
                    <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                    <input name="orden" type="number" min="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-order>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex w-full items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-2.5 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-atlantia-rose text-atlantia-wine" data-edit-active>
                        <span class="font-semibold text-atlantia-ink">Visible en catalogo</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-modal>
                    Cancelar
                </button>
                <x-ui.button type="submit">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (() => {
        const createModal = document.getElementById('category-create-modal');
        const editModal = document.getElementById('category-edit-modal');
        const createParent = createModal?.querySelector('[data-create-parent]');
        const createTitle = createModal?.querySelector('[data-create-title]');
        const createSubtitle = createModal?.querySelector('[data-create-subtitle]');
        const createParentHelp = createModal?.querySelector('[data-create-parent-help]');
        const createSubmit = createModal?.querySelector('[data-create-submit]');
        const createName = createModal?.querySelector('[data-create-name]');
        const createSlug = createModal?.querySelector('[data-create-slug]');
        const categoryList = document.querySelector('[data-category-list]');
        const categorySearch = document.querySelector('[data-category-search]');
        const categorySort = document.querySelector('[data-category-sort]');
        const emptySearch = document.querySelector('[data-category-empty-search]');
        const categoryCards = [...document.querySelectorAll('[data-category-card]')];

        const openModal = (modal) => {
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = (modal) => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            document.body.style.overflow = '';
        };

        const openCreateModal = (mode = 'categoria', parentId = '', parentName = '') => {
            if (mode === 'subcategoria') {
                createTitle.textContent = 'Nueva subcategoria';
                createSubtitle.textContent = parentName ? `Se agregara dentro de ${parentName}.` : 'Elige la categoria principal donde vivira.';
                createParentHelp.textContent = 'La subcategoria debe pertenecer a una categoria principal.';
                createSubmit.textContent = 'Crear subcategoria';
                createParent.required = true;
                createParent.value = parentId;
            } else {
                createTitle.textContent = 'Nueva categoria';
                createSubtitle.textContent = 'Completa los datos para agregarla al catalogo.';
                createParentHelp.textContent = 'Dejalo vacio para crear una categoria principal.';
                createSubmit.textContent = 'Crear categoria';
                createParent.required = false;
                createParent.value = '';
            }

            openModal(createModal);
            createName?.focus();
        };

        document.querySelectorAll('[data-open-category-modal]').forEach((button) => {
            button.addEventListener('click', () => openCreateModal('categoria'));
        });

        document.querySelectorAll('[data-open-subcategory-modal]').forEach((button) => {
            button.addEventListener('click', () => openCreateModal('subcategoria', button.dataset.parentId || '', button.dataset.parentName || ''));
        });

        document.querySelectorAll('[data-close-category-modal]').forEach((button) => {
            button.addEventListener('click', () => closeModal(createModal));
        });

        createModal?.addEventListener('click', (event) => {
            if (event.target === createModal) closeModal(createModal);
        });

        createName?.addEventListener('input', () => {
            if (!createSlug || createSlug.dataset.manual) return;
            createSlug.value = createName.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-');
        });

        createSlug?.addEventListener('input', () => {
            createSlug.dataset.manual = createSlug.value ? '1' : '';
        });

        const applyCategoryControls = () => {
            const term = (categorySearch?.value || '').trim().toLowerCase();
            let visible = 0;
            const sortedCards = [...categoryCards].sort((a, b) => {
                if (categorySort?.value === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                }

                if (categorySort?.value === 'active') {
                    return Number(b.dataset.active || 0) - Number(a.dataset.active || 0)
                        || Number(a.dataset.order || 0) - Number(b.dataset.order || 0);
                }

                return Number(a.dataset.order || 0) - Number(b.dataset.order || 0);
            });

            sortedCards.forEach((card) => {
                const show = !term || (card.dataset.search || '').includes(term);
                card.classList.toggle('hidden', !show);
                if (show) visible++;
                categoryList?.insertBefore(card, emptySearch || null);
            });

            emptySearch?.classList.toggle('hidden', visible > 0);
        };

        categorySearch?.addEventListener('input', applyCategoryControls);
        categorySort?.addEventListener('change', applyCategoryControls);
        applyCategoryControls();

        document.querySelectorAll('[data-edit-category]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = editModal.querySelector('[data-edit-form]');
                const parent = editModal.querySelector('[data-edit-parent]');
                const image = editModal.querySelector('[data-edit-image-preview]');
                const fallback = editModal.querySelector('[data-edit-image-fallback]');

                form.action = button.dataset.action;
                editModal.querySelector('[data-edit-name]').value = button.dataset.name || '';
                editModal.querySelector('[data-edit-slug]').value = button.dataset.slug || '';
                editModal.querySelector('[data-edit-description]').value = button.dataset.description || '';
                editModal.querySelector('[data-edit-order]').value = button.dataset.order || '0';
                editModal.querySelector('[data-edit-active]').checked = button.dataset.active === '1';

                parent.querySelectorAll('[data-category-option-id]').forEach((option) => {
                    option.hidden = option.dataset.categoryOptionId === button.dataset.id;
                });
                parent.value = button.dataset.parentId || '';

                if (button.dataset.image) {
                    image.src = button.dataset.image;
                    image.classList.remove('hidden');
                    fallback.classList.add('hidden');
                } else {
                    image.src = '';
                    image.classList.add('hidden');
                    fallback.textContent = (button.dataset.name || 'CA').slice(0, 2).toUpperCase();
                    fallback.classList.remove('hidden');
                }

                openModal(editModal);
                editModal.querySelector('[data-edit-name]')?.focus();
            });
        });

        document.querySelectorAll('[data-close-edit-modal]').forEach((button) => {
            button.addEventListener('click', () => closeModal(editModal));
        });

        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) closeModal(editModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            closeModal(createModal);
            closeModal(editModal);
        });
    })();
</script>
@endpush
