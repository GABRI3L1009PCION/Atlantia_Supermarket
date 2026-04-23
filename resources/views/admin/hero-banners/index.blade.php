@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header
                title="Banners hero"
                subtitle="Administra el banner principal del storefront con versiones desktop y mobile."
            />

            <div class="grid gap-6 xl:grid-cols-[430px_1fr]">
                <form
                    method="POST"
                    action="{{ route('admin.hero-banners.store') }}"
                    enctype="multipart/form-data"
                    class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5"
                >
                    @csrf
                    <h2 class="text-lg font-bold text-atlantia-wine">Crear banner</h2>

                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Nombre interno</label>
                            <input name="nombre" type="text" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Orden</label>
                                <input name="orden" type="number" min="0" value="0" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <label class="mt-6 inline-flex items-center gap-2 text-sm">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-atlantia-rose text-atlantia-wine">
                                <span>Activo</span>
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Inicia en</label>
                                <input name="inicia_en" type="datetime-local" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Termina en</label>
                                <input name="termina_en" type="datetime-local" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Imagen desktop</label>
                            <input name="desktop_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2" required>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Imagen mobile</label>
                            <input name="mobile_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2">
                        </div>
                    </div>

                    <x-ui.button type="submit" class="mt-5 w-full">Guardar banner</x-ui.button>
                </form>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5">
                    <h2 class="text-lg font-bold text-atlantia-wine">Banners configurados</h2>

                    <div class="mt-5 space-y-4">
                        @forelse ($banners as $banner)
                            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                <form
                                    method="POST"
                                    action="{{ route('admin.hero-banners.update', $banner->uuid) }}"
                                    enctype="multipart/form-data"
                                    class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr_auto]"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div class="space-y-3">
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Nombre</label>
                                            <input name="nombre" type="text" value="{{ $banner->nombre }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2" required>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Desktop</label>
                                                <input name="desktop_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Mobile</label>
                                                <input name="mobile_image" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-atlantia-rose/35 bg-white px-3 py-2">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-3">
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Orden</label>
                                                <input name="orden" type="number" min="0" value="{{ $banner->orden }}" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                            </div>
                                            <label class="mt-6 inline-flex items-center gap-2 rounded-md border border-atlantia-rose/25 bg-white px-3 py-2 text-sm">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($banner->is_active) class="rounded border-atlantia-rose text-atlantia-wine">
                                                <span>Activo</span>
                                            </label>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Inicia</label>
                                                <input
                                                    name="inicia_en"
                                                    type="datetime-local"
                                                    value="{{ optional($banner->inicia_en)->format('Y-m-d\TH:i') }}"
                                                    class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Termina</label>
                                                <input
                                                    name="termina_en"
                                                    type="datetime-local"
                                                    value="{{ optional($banner->termina_en)->format('Y-m-d\TH:i') }}"
                                                    class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"
                                                >
                                            </div>
                                        </div>

                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div class="overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Preview desktop</p>
                                                @if ($banner->getFirstMediaUrl('hero_desktop'))
                                                    <img src="{{ $banner->getFirstMediaUrl('hero_desktop') }}" alt="{{ $banner->nombre }}" class="h-28 w-full rounded-md object-cover">
                                                @else
                                                    <div class="grid h-28 place-items-center rounded-md bg-atlantia-blush text-xs text-atlantia-ink/55">Sin imagen</div>
                                                @endif
                                            </div>
                                            <div class="overflow-hidden rounded-lg border border-atlantia-rose/20 bg-white p-2">
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-atlantia-ink/55">Preview mobile</p>
                                                @if ($banner->getFirstMediaUrl('hero_mobile'))
                                                    <img src="{{ $banner->getFirstMediaUrl('hero_mobile') }}" alt="{{ $banner->nombre }}" class="h-28 w-full rounded-md object-cover">
                                                @else
                                                    <div class="grid h-28 place-items-center rounded-md bg-atlantia-blush text-xs text-atlantia-ink/55">Usa desktop por defecto</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-start gap-2">
                                        <x-ui.button type="submit">Guardar</x-ui.button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.hero-banners.destroy', $banner->uuid) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream px-4 py-6 text-center text-atlantia-ink/60">
                                No hay banners hero configurados.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
