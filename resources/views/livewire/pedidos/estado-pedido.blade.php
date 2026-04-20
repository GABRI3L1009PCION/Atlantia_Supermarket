<section class="rounded-lg border border-atlantia-rose/30 bg-white p-4">
    <h2 class="font-semibold text-atlantia-ink">Estado: {{ $pedido->estado }}</h2>
    <div class="mt-3 space-y-2 text-sm text-atlantia-ink/70">
        @foreach ($pedido->estados as $estado)
            <p>{{ $estado->estado }} - {{ optional($estado->created_at)->format('d/m/Y H:i') }}</p>
        @endforeach
    </div>
</section>

