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

<section class="mx-auto max-w-7xl space-y-4 pb-10">
    {{-- Encabezado --}}
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-atlantia-ink">Categorías</h1>
            <p class="mt-1 text-sm text-atlantia-ink/60">
                <strong class="text-atlantia-ink">{{ $totalCategorias }} categorías</strong> · {{ $totalActivas }} activas · {{ $totalHijas }} subcategorías. Así se organiza lo que ven los clientes.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-atlantia-rose/25 bg-white px-4 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-open-subcategory-modal>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Subcategoría
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700" data-open-category-modal>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                Nueva categoría
            </button>
        </div>
    </header>

    <div class="overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_10px_26px_rgba(59,6,12,0.05)]">
        {{-- Búsqueda y orden --}}
        <div class="flex flex-col gap-2 border-b border-atlantia-rose/10 p-3 sm:flex-row">
            <label class="relative block flex-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-atlantia-ink/40">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM20.5 20.5l-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <input type="search" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-9 pr-3 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" placeholder="Buscar categoría" aria-label="Buscar categoría" data-category-search>
            </label>
            <select class="h-10 rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20 sm:w-56" aria-label="Ordenar" data-category-sort>
                <option value="custom">Orden de la tienda</option>
                <option value="name">Nombre A-Z</option>
                <option value="active">Activas primero</option>
            </select>
        </div>

        {{-- Lista: una fila por categoría --}}
        <div class="divide-y divide-atlantia-rose/10" data-category-list>
            @forelse ($categorias as $categoria)
                @php
                    $imagenUrl = $categoriaImagenUrl($categoria->imagen);
                @endphp
                <article
                    class="px-4 py-3 transition hover:bg-atlantia-cream/50"
                    data-category-card
                    data-name="{{ strtolower($categoria->nombre) }}"
                    data-search="{{ strtolower($categoria->nombre.' '.$categoria->slug.' '.$categoria->descripcion) }}"
                    data-order="{{ $categoria->orden }}"
                    data-active="{{ $categoria->is_active ? '1' : '0' }}"
                >
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl border border-atlantia-rose/15 bg-atlantia-blush/50">
                            @if ($imagenUrl)
                                <img src="{{ $imagenUrl }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <span class="text-sm font-black text-atlantia-wine">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($categoria->nombre, 0, 2)) }}</span>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="font-black text-atlantia-ink">{{ $categoria->nombre }}</span>
                                @unless ($categoria->is_active)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-600">Inactiva</span>
                                @endunless
                            </p>
                            <p class="truncate text-xs text-atlantia-ink/55">{{ $categoria->descripcion ?: 'Sin descripción.' }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-4 text-xs text-atlantia-ink/60 sm:w-56 sm:justify-end">
                        <span><strong class="text-sm text-atlantia-ink">{{ number_format((int) ($categoria->productos_count ?? 0)) }}</strong> productos</span>
                        <span><strong class="text-sm text-atlantia-ink">{{ $categoria->children->count() }}</strong> subcat.</span>
                        <span class="text-atlantia-ink/40" title="Posición en la tienda">#{{ $categoria->orden }}</span>
                    </div>

                    <div class="flex shrink-0 gap-1.5 sm:w-[17.5rem] sm:justify-end">
                        <button
                            type="button"
                            class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush"
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
                        >Editar</button>
                        <button
                            type="button"
                            class="rounded-lg border border-atlantia-rose/30 px-3 py-1.5 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                            data-open-subcategory-modal
                            data-parent-id="{{ $categoria->id }}"
                            data-parent-name="{{ e($categoria->nombre) }}"
                            title="Agregar subcategoría a {{ $categoria->nombre }}"
                        >+ Subcategoría</button>
                        @if ($categoria->is_active)
                            <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}" data-confirm="¿Desactivar la categoría {{ $categoria->nombre }}? Dejará de verse en la tienda.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-black text-atlantia-ink/55 transition hover:bg-red-50 hover:text-red-700">Desactivar</button>
                            </form>
                        @endif
                    </div>
                  </div>
                </article>
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="font-black text-atlantia-ink">Todavía no hay categorías.</p>
                    <p class="mt-1 text-sm text-atlantia-ink/55">Crea la primera para organizar el catálogo.</p>
                    <button type="button" class="mt-4 rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700" data-open-category-modal>Crear categoría</button>
                </div>
            @endforelse
            <div class="hidden px-4 py-12 text-center" data-category-empty-search>
                <p class="font-black text-atlantia-ink">No hay categorías con esa búsqueda.</p>
                <p class="mt-1 text-sm text-atlantia-ink/55">Prueba con otro nombre o palabra de la descripción.</p>
            </div>
        </div>

        {{-- Paginador (10 por página) --}}
        <div class="flex flex-col items-center justify-between gap-2 border-t border-atlantia-rose/10 px-4 py-3 sm:flex-row" data-category-pager>
            <p class="text-xs font-semibold text-atlantia-ink/55" data-category-pager-info></p>
            <nav class="flex flex-wrap gap-1" aria-label="Páginas de categorías" data-category-pager-pages></nav>
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

        const POR_PAGINA = 10;
        let paginaActual = 1;
        const paginador = document.querySelector('[data-category-pager]');
        const paginadorInfo = document.querySelector('[data-category-pager-info]');
        const paginadorBotones = document.querySelector('[data-category-pager-pages]');

        const pintarPaginador = (total) => {
            const paginas = Math.max(1, Math.ceil(total / POR_PAGINA));
            paginaActual = Math.min(paginaActual, paginas);
            const desde = total === 0 ? 0 : (paginaActual - 1) * POR_PAGINA + 1;
            const hasta = Math.min(total, paginaActual * POR_PAGINA);
            paginador?.classList.toggle('hidden', total === 0);
            if (paginadorInfo) paginadorInfo.textContent = `Mostrando ${desde}-${hasta} de ${total} categorías`;
            if (!paginadorBotones) return;

            const boton = (texto, pagina, { activo = false, deshabilitado = false, etiqueta = '' } = {}) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = texto;
                b.disabled = deshabilitado;
                if (etiqueta) b.setAttribute('aria-label', etiqueta);
                if (activo) b.setAttribute('aria-current', 'page');
                b.className = 'grid h-8 min-w-8 place-items-center rounded-lg px-2 text-xs font-black transition '
                    + (activo ? 'bg-atlantia-wine text-white' : 'border border-atlantia-rose/25 text-atlantia-wine hover:bg-atlantia-blush')
                    + (deshabilitado ? ' pointer-events-none opacity-40' : '');
                b.addEventListener('click', () => { paginaActual = pagina; applyCategoryControls(false); });
                return b;
            };

            paginadorBotones.replaceChildren(
                boton('‹', paginaActual - 1, { deshabilitado: paginaActual === 1, etiqueta: 'Página anterior' }),
                ...Array.from({ length: paginas }, (_, i) => boton(String(i + 1), i + 1, { activo: i + 1 === paginaActual })),
                boton('›', paginaActual + 1, { deshabilitado: paginaActual === paginas, etiqueta: 'Página siguiente' }),
            );
        };

        const applyCategoryControls = (reiniciar = true) => {
            if (reiniciar === true) paginaActual = 1;
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

            const inicio = (paginaActual - 1) * POR_PAGINA;
            const coincidencias = sortedCards.filter((card) => !term || (card.dataset.search || '').includes(term));
            // Si la página quedó vacía (por ejemplo al buscar), vuelve a la última que tenga resultados.
            if (inicio >= coincidencias.length && paginaActual > 1) {
                paginaActual = Math.max(1, Math.ceil(coincidencias.length / POR_PAGINA));
            }
            const desde = (paginaActual - 1) * POR_PAGINA;

            sortedCards.forEach((card) => {
                const indice = coincidencias.indexOf(card);
                const show = indice !== -1 && indice >= desde && indice < desde + POR_PAGINA;
                card.classList.toggle('hidden', !show);
                if (show) visible++;
                categoryList?.insertBefore(card, emptySearch || null);
            });

            emptySearch?.classList.toggle('hidden', visible > 0 || categoryCards.length === 0);
            pintarPaginador(coincidencias.length);
        };

        categorySearch?.addEventListener('input', () => applyCategoryControls());
        categorySort?.addEventListener('change', () => applyCategoryControls());
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
