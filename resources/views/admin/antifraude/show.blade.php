@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <x-page-header :title="'Alerta ' . $alert->tipo" subtitle="Contexto del pedido, score de riesgo y trazabilidad de resolucion." />

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Pedido</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $alert->pedido?->numero_pedido ?? 'Sin pedido' }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Cliente</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $alert->user?->name ?? 'Sin usuario' }}</p>
                        </div>
                        <div class="rounded-xl border border-rose-200 bg-white p-4">
                            <p class="text-sm text-atlantia-ink/55">Score</p>
                            <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format((float) $alert->score_riesgo, 2) }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Estado</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $alert->resuelta ? 'Resuelta' : ($alert->revisada ? 'Revisada' : 'Pendiente') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Detalle tecnico</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($alert->detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Resolver alerta</h2>

                    @if (! $alert->resuelta)
                        <form method="POST" action="{{ route('admin.antifraude.resolve', $alert->uuid) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')

                            <input type="hidden" name="resuelta" value="1">
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Accion</label>
                                <select name="accion" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                    @foreach (['revision_manual', 'pedido_aprobado', 'pedido_bloqueado', 'cliente_contactado', 'escalado'] as $accion)
                                        <option value="{{ $accion }}">{{ ucfirst(str_replace('_', ' ', $accion)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-atlantia-ink">Notas</label>
                                <textarea name="notas" rows="4" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"></textarea>
                            </div>

                            <x-ui.button type="submit" class="w-full">Marcar como resuelta</x-ui.button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-atlantia-ink/70">Esta alerta ya fue cerrada por {{ $alert->revisadaPor?->name ?? 'el equipo' }}.</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Pedido relacionado</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">Vendedor</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $alert->pedido?->vendor?->business_name ?? 'Sin vendedor' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Metodo de pago</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $alert->pedido?->metodo_pago ?? 'Sin dato' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Total</dt>
                            <dd class="font-semibold text-atlantia-ink">Q{{ number_format((float) ($alert->pedido?->total ?? 0), 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>
@endsection
