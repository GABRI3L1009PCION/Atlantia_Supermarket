<form wire:submit.prevent="buscar" class="w-full" role="search">
    <label for="catalogo-search" class="sr-only">Buscar productos</label>

    <div class="flex gap-2">
        <input
            id="catalogo-search"
            type="search"
            wire:model.live.debounce.500ms="search"
            placeholder="Buscar frutas, abarrotes, bebidas o productos locales"
            class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm
                focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600"
        >

        @if ($search)
            <x-ui.button type="button" variant="secondary" wire:click="limpiar">
                Limpiar
            </x-ui.button>
        @endif

        <x-ui.button type="submit">
            Buscar
        </x-ui.button>
    </div>

    @error('search')
        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
    @enderror
</form>
