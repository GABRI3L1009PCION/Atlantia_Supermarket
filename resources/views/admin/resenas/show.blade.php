@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <x-page-header :title="$resena->titulo" subtitle="Moderacion manual con contexto del comprador, producto y flags ML." />

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Producto</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $resena->producto?->nombre }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Cliente</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $resena->cliente?->name }}</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Calificacion</p>
                            <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $resena->calificacion }}/5</p>
                        </div>
                        <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                            <p class="text-sm text-atlantia-ink/55">Estado</p>
                            <p class="mt-2 font-semibold text-atlantia-ink">{{ $resena->aprobada ? 'Aprobada' : 'Pendiente' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-5">
                        <p class="whitespace-pre-line text-sm leading-7 text-atlantia-ink">{{ $resena->contenido }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Flags ML</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($resena->reviewFlags as $flag)
                            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-atlantia-ink">{{ $flag->razon_ml }}</p>
                                    <span class="rounded-md bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">
                                        {{ number_format((float) $flag->score_sospecha, 2) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-atlantia-ink/55">
                                    {{ $flag->revisada ? 'Revisada' : 'Pendiente de revision' }}
                                    @if ($flag->accion_tomada)
                                        · {{ $flag->accion_tomada }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-atlantia-ink/60">No hay flags ML asociados a esta resena.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Moderar</h2>

                    <form method="POST" action="{{ route('admin.resenas.moderate', $resena->uuid) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Aprobacion</label>
                            <select name="aprobada" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2">
                                <option value="1" @selected($resena->aprobada)>Aprobar</option>
                                <option value="0" @selected(! $resena->aprobada)>Mantener bloqueada</option>
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="flagged_ml" value="0">
                            <input type="checkbox" name="flagged_ml" value="1" @checked($resena->flagged_ml) class="rounded border-atlantia-rose text-atlantia-wine">
                            <span>Mantener marcada por ML</span>
                        </label>
                        <div>
                            <label class="text-sm font-semibold text-atlantia-ink">Notas</label>
                            <textarea name="notas" rows="4" class="mt-1 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"></textarea>
                        </div>

                        <x-ui.button type="submit" class="w-full">Guardar moderacion</x-ui.button>
                    </form>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Contexto</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">Pedido</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $resena->pedido?->numero_pedido ?? 'Sin pedido' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Vendedor</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $resena->producto?->vendor?->business_name ?? 'Sin vendedor' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Moderada por</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $resena->moderadaPor?->name ?? 'Sin moderacion previa' }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>
@endsection
