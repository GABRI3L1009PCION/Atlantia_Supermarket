@extends('layouts.marketplace', ['title' => 'Códigos de respaldo | Atlantia Store'])

@section('content')
<x-auth.panel titulo="Guarda tus códigos de respaldo" subtitulo="Sirven para entrar si pierdes o cambias tu teléfono." etiqueta="Último paso">
    <div class="space-y-4" x-data="{ guardados: false, copiado: false }">
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Solo se muestran esta vez.</strong> Cada código funciona una sola vez. Anótalos o toma una captura y guárdalos en un lugar seguro.
        </p>

        <ol class="grid grid-cols-2 gap-2 rounded-2xl border border-atlantia-rose/20 bg-atlantia-blush/25 p-4 font-mono text-base font-black tracking-wider text-atlantia-ink" data-codigos-respaldo>
            @foreach ($codigos as $codigo)
                <li class="rounded-lg bg-white px-3 py-2 text-center shadow-sm">{{ $codigo }}</li>
            @endforeach
        </ol>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush"
                x-on:click="navigator.clipboard?.writeText(@js(implode("\n", $codigos))); copiado = true">
                <span x-text="copiado ? '✓ Copiados' : 'Copiar códigos'">Copiar códigos</span>
            </button>
            <button type="button" data-print-page class="rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush">Imprimir</button>
        </div>

        <label class="flex items-start gap-2 text-sm text-atlantia-ink/75">
            <input type="checkbox" x-model="guardados" class="mt-0.5 h-4 w-4 rounded border-atlantia-rose text-atlantia-wine">
            Ya guardé mis códigos en un lugar seguro.
        </label>

        <a href="{{ $destino }}" class="block w-full rounded-xl bg-atlantia-wine px-4 py-3 text-center text-sm font-black text-white transition hover:bg-atlantia-wine-700"
            :class="{ 'pointer-events-none opacity-50': !guardados }" :aria-disabled="(!guardados).toString()">
            Entrar a mi panel
        </a>
    </div>
</x-auth.panel>
@endsection
