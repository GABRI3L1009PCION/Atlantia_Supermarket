@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
    @endphp

    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <x-page-header
                    title="Banners hero"
                    subtitle="Administra el banner principal del storefront con versiones desktop y mobile."
                />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream px-4 py-3">
                        <p class="text-sm font-black text-atlantia-wine">{{ $banners->count() }} banners configurados</p>
                        <p class="text-xs text-atlantia-ink/60">Desktop, mobile y vigencia.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg bg-atlantia-wine px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700"
                        data-open-create-modal
                    >
                        Crear nuevo banner
                    </button>
                </div>
            </div>

            <div class="mt-7 rounded-xl border border-atlantia-rose/20 bg-white p-5">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl font-black text-atlantia-wine">Banners configurados</h2>
                    <p class="text-sm text-atlantia-ink/65">Revisa previews, estado y fechas sin editar todo en pantalla.</p>
                </div>

                <div class="mt-5 grid gap-4">
                    @forelse ($banners as $banner)
                        @php
                            $desktopUrl = $banner->getFirstMediaUrl('hero_desktop');
                            $mobileUrl = $banner->getFirstMediaUrl('hero_mobile');
                        @endphp

                        <article class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream/50 p-4 transition hover:border-atlantia-rose/40 hover:bg-atlantia-cream">
                            <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr_auto] xl:items-start">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-atlantia-ink">{{ $banner->nombre }}</h3>
                                        <span @class([
                                            'rounded-md px-2.5 py-1 text-xs font-black ring-1',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $banner->is_active,
                                            'bg-slate-100 text-slate-600 ring-slate-200' => ! $banner->is_active,
                                        ])>
                                            {{ $banner->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1 text-xs font-semibold text-atlantia-ink/55">
                                            Orden {{ $banner->orden }}
                                        </span>
                                    </div>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div class="overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                                            <p class="mb-2 text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Desktop</p>
                                            @if ($desktopUrl)
                                                <img src="{{ $desktopUrl }}" alt="{{ $banner->nombre }}" class="h-32 w-full rounded-md object-cover">
                                            @else
                                                <div class="grid h-32 place-items-center rounded-md bg-atlantia-blush text-xs font-semibold text-atlantia-ink/55">Sin imagen</div>
                                            @endif
                                        </div>
                                        <div class="overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                                            <p class="mb-2 text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Mobile</p>
                                            @if ($mobileUrl)
                                                <img src="{{ $mobileUrl }}" alt="{{ $banner->nombre }}" class="h-32 w-full rounded-md object-cover">
                                            @else
                                                <div class="grid h-32 place-items-center rounded-md bg-atlantia-blush text-xs font-semibold text-atlantia-ink/55">Usa desktop por defecto</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-3 rounded-lg border border-atlantia-rose/15 bg-white p-4 text-sm">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Inicia</p>
                                        <p class="mt-1 font-semibold text-atlantia-ink">{{ optional($banner->inicia_en)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Termina</p>
                                        <p class="mt-1 font-semibold text-atlantia-ink">{{ optional($banner->termina_en)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 xl:flex-col">
                                    <button
                                        type="button"
                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                                        data-edit-banner
                                        data-action="{{ route('admin.hero-banners.update', $banner->uuid) }}"
                                        data-name="{{ e($banner->nombre) }}"
                                        data-order="{{ $banner->orden }}"
                                        data-active="{{ $banner->is_active ? '1' : '0' }}"
                                        data-start="{{ optional($banner->inicia_en)->format('Y-m-d\TH:i') }}"
                                        data-end="{{ optional($banner->termina_en)->format('Y-m-d\TH:i') }}"
                                        data-desktop="{{ $desktopUrl }}"
                                        data-mobile="{{ $mobileUrl }}"
                                    >
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('admin.hero-banners.destroy', $banner->uuid) }}" onsubmit="return confirm('Eliminar el banner {{ addslashes($banner->nombre) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream px-4 py-10 text-center">
                            <p class="text-base font-black text-atlantia-ink">No hay banners hero configurados.</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">Crea el primer banner para mostrarlo en el storefront.</p>
                            <button type="button" class="mt-4 rounded-lg bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white hover:bg-atlantia-wine-700" data-open-create-modal>
                                Crear banner
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="banner-create-title"
    >
        <div class="max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="banner-create-title" class="mt-1 text-2xl font-black text-atlantia-ink">Crear nuevo banner</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Sube las imagenes y define su orden de aparicion.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                    Cerrar
                </button>
            </div>

            <form
                method="POST"
                action="{{ route('admin.hero-banners.store') }}"
                enctype="multipart/form-data"
                class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5"
            >
                @csrf

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        Revisa los campos marcados antes de guardar el banner.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Nombre interno</label>
                        <input name="nombre" type="text" value="{{ old('nombre') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('nombre') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                        <input name="orden" type="number" min="0" value="{{ old('orden', 0) }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('orden') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex w-full items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-2.5 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="rounded border-atlantia-rose text-atlantia-wine">
                            <span class="font-semibold text-atlantia-ink">Activo</span>
                        </label>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Inicia en</label>
                        <input name="inicia_en" type="datetime-local" value="{{ old('inicia_en') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('inicia_en') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Termina en</label>
                        <input name="termina_en" type="datetime-local" value="{{ old('termina_en') }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('termina_en') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Imagen desktop</label>
                        <input name="desktop_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20" required>
                        @error('desktop_image') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Imagen mobile</label>
                        <input name="mobile_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2 outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                        @error('mobile_image') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <x-ui.button type="submit">Guardar banner</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <div
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-edit-banner-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="banner-edit-title"
    >
        <div class="max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="banner-edit-title" class="mt-1 text-2xl font-black text-atlantia-ink">Editar banner</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Actualiza imagenes, vigencia y estado.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-banner>
                    Cerrar
                </button>
            </div>

            <form method="POST" action="#" enctype="multipart/form-data" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5" data-edit-banner-form>
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-atlantia-ink">Nombre interno</label>
                        <input name="nombre" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required data-edit-name>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                        <input name="orden" type="number" min="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-order>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex w-full items-center gap-2 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-2.5 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-atlantia-rose text-atlantia-wine" data-edit-active>
                            <span class="font-semibold text-atlantia-ink">Activo</span>
                        </label>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Inicia en</label>
                        <input name="inicia_en" type="datetime-local" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-start>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Termina en</label>
                        <input name="termina_en" type="datetime-local" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" data-edit-end>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Nueva imagen desktop</label>
                        <input name="desktop_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2">
                        <div class="mt-3 overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                            <p class="mb-2 text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Actual desktop</p>
                            <img src="" alt="" class="hidden h-28 w-full rounded-md object-cover" data-edit-desktop-preview>
                            <div class="grid h-28 place-items-center rounded-md bg-atlantia-blush text-xs font-semibold text-atlantia-ink/55" data-edit-desktop-empty>Sin imagen</div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-atlantia-ink">Nueva imagen mobile</label>
                        <input name="mobile_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2">
                        <div class="mt-3 overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                            <p class="mb-2 text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Actual mobile</p>
                            <img src="" alt="" class="hidden h-28 w-full rounded-md object-cover" data-edit-mobile-preview>
                            <div class="grid h-28 place-items-center rounded-md bg-atlantia-blush text-xs font-semibold text-atlantia-ink/55" data-edit-mobile-empty>Usa desktop por defecto</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-banner>
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
        const modal = document.querySelector('[data-edit-banner-modal]');
        const form = modal?.querySelector('[data-edit-banner-form]');

        const setPreview = (selectorImage, selectorEmpty, url) => {
            const image = modal.querySelector(selectorImage);
            const empty = modal.querySelector(selectorEmpty);

            if (url) {
                image.src = url;
                image.classList.remove('hidden');
                empty.classList.add('hidden');
                return;
            }

            image.src = '';
            image.classList.add('hidden');
            empty.classList.remove('hidden');
        };

        const openModal = () => {
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            document.body.style.overflow = 'hidden';
            modal?.querySelector('[data-edit-name]')?.focus();
        };

        const closeModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-edit-banner]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action;
                modal.querySelector('[data-edit-name]').value = button.dataset.name || '';
                modal.querySelector('[data-edit-order]').value = button.dataset.order || '0';
                modal.querySelector('[data-edit-active]').checked = button.dataset.active === '1';
                modal.querySelector('[data-edit-start]').value = button.dataset.start || '';
                modal.querySelector('[data-edit-end]').value = button.dataset.end || '';
                setPreview('[data-edit-desktop-preview]', '[data-edit-desktop-empty]', button.dataset.desktop || '');
                setPreview('[data-edit-mobile-preview]', '[data-edit-mobile-empty]', button.dataset.mobile || '');
                openModal();
            });
        });

        document.querySelectorAll('[data-close-edit-banner]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal?.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeModal();
        });
    })();
</script>
@endpush
