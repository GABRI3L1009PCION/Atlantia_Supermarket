<section wire:poll.15s class="rounded-lg border border-atlantia-rose/30 bg-white p-4">
    <h2 class="font-semibold text-atlantia-ink">Rastreo en vivo</h2>
    @if ($ubicacion)
        <p class="mt-2 text-sm text-atlantia-ink/70">
            Latitud {{ $ubicacion->latitude }}, longitud {{ $ubicacion->longitude }}.
        </p>
    @else
        <p class="mt-2 text-sm text-atlantia-ink/70">Aun no hay ubicacion GPS registrada.</p>
    @endif
</section>

