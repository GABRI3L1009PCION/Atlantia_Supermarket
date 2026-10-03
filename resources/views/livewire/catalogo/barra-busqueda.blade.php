<form wire:submit.prevent="buscar" class="mx-auto w-full max-w-[430px]" role="search">
    <label for="catalogo-search" class="sr-only">Buscar productos</label>

    <div class="grid grid-cols-[minmax(0,1fr)_auto]">
        <div class="min-w-0">
            <input
                id="catalogo-search"
                type="search"
                wire:model.live.debounce.500ms="search"
                placeholder="Buscar en todo el supermercado..."
                class="h-10 w-full rounded-l-md border border-r-0 border-atlantia-rose/25 bg-white px-4 text-sm text-atlantia-ink shadow-sm outline-none transition placeholder:text-atlantia-ink/45 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/25"
            >
        </div>

        <button
            type="submit"
            class="inline-flex h-10 items-center justify-center gap-1.5 rounded-r-md bg-atlantia-wine px-5 text-sm font-black text-white shadow-sm transition hover:bg-atlantia-wine-700 focus:outline-none focus:ring-2 focus:ring-atlantia-rose focus:ring-offset-2"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/>
                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2.3"/>
            </svg>
            Buscar
        </button>
    </div>

    @error('search')
        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
    @enderror
</form>
