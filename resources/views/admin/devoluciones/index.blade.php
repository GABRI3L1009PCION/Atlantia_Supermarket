@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header
                title="Devoluciones"
                subtitle="Revisa solicitudes, aprueba reembolsos y conserva trazabilidad operativa."
            />

            <div class="mt-6 space-y-4">
                @forelse ($devoluciones as $devolucion)
                    <article class="rounded-xl border border-atlantia-rose/25 bg-atlantia-cream p-5">
                        <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
                            <div>
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black uppercase tracking-normal text-atlantia-rose">
                                            {{ str_replace('_', ' ', $devolucion->motivo) }}
                                        </p>
                                        <h2 class="mt-1 text-xl font-black text-atlantia-ink">
                                            Pedido {{ $devolucion->pedido?->numero_pedido }}
                                        </h2>
                                        <p class="mt-1 text-sm text-atlantia-ink/60">
                                            {{ $devolucion->user?->name }} · {{ $devolucion->created_at?->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="rounded-md bg-amber-100 px-3 py-2 text-xs font-black uppercase text-amber-800">
                                        Pendiente
                                    </span>
                                </div>

                                <p class="mt-4 rounded-md bg-white px-4 py-3 text-sm leading-6 text-atlantia-ink/75">
                                    {{ $devolucion->descripcion }}
                                </p>

                                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                    <div class="rounded-md bg-white p-3">
                                        <span class="block text-atlantia-ink/55">Total pedido</span>
                                        <strong>Q {{ number_format((float) $devolucion->pedido?->total, 2) }}</strong>
                                    </div>
                                    <div class="rounded-md bg-white p-3">
                                        <span class="block text-atlantia-ink/55">Estado pedido</span>
                                        <strong>
                                            {{ str_replace('_', ' ', $devolucion->pedido?->estadoValor() ?? 'sin estado') }}
                                        </strong>
                                    </div>
                                    <div class="rounded-md bg-white p-3">
                                        <span class="block text-atlantia-ink/55">Pago</span>
                                        <strong>
                                            {{ str_replace('_', ' ', $devolucion->pedido?->estadoPagoValor() ?? 'sin pago') }}
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.devoluciones.update', $devolucion) }}"
                                class="rounded-lg border border-atlantia-rose/25 bg-white p-4"
                                data-protect-submit
                            >
                                @csrf
                                @method('PATCH')

                                <label class="text-sm font-bold text-atlantia-ink">Decision</label>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <label class="rounded-md border border-emerald-200 px-3 py-2 text-sm font-bold text-emerald-800">
                                        <input type="radio" name="decision" value="aprobada" class="mr-2" required>
                                        Aprobar
                                    </label>
                                    <label class="rounded-md border border-red-200 px-3 py-2 text-sm font-bold text-red-800">
                                        <input type="radio" name="decision" value="rechazada" class="mr-2" required>
                                        Rechazar
                                    </label>
                                </div>

                                <label for="monto-{{ $devolucion->uuid }}" class="mt-4 block text-sm font-bold text-atlantia-ink">
                                    Monto de reembolso
                                </label>
                                <input
                                    id="monto-{{ $devolucion->uuid }}"
                                    name="monto_reembolso"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $devolucion->pedido?->total }}"
                                    value="{{ number_format((float) $devolucion->pedido?->total, 2, '.', '') }}"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"
                                >

                                <label for="notas-{{ $devolucion->uuid }}" class="mt-4 block text-sm font-bold text-atlantia-ink">
                                    Notas administrativas
                                </label>
                                <textarea
                                    id="notas-{{ $devolucion->uuid }}"
                                    name="notas_admin"
                                    rows="3"
                                    class="mt-2 w-full rounded-md border border-atlantia-rose/35 px-3 py-2"
                                    placeholder="Explica la resolucion para auditoria."
                                ></textarea>

                                <x-ui.button type="submit" class="mt-4 w-full">Resolver devolucion</x-ui.button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-atlantia-rose/30 bg-atlantia-cream p-8 text-center">
                        <h2 class="text-xl font-black text-atlantia-ink">No hay devoluciones pendientes</h2>
                        <p class="mt-2 text-sm text-atlantia-ink/60">Cuando un cliente solicite una revision aparecera aqui.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5">{{ $devoluciones->links() }}</div>
        </div>
    </section>
@endsection
