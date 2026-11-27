@extends('layouts.app')

@section('content')
    @php
        $estados = ['pendiente' => 'Sin revisar', 'confirmada' => 'Confirmadas', 'descartada' => 'Descartadas', 'todas' => 'Todas'];
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Denuncias</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">
                Lo que reportan los clientes sobre las tiendas. Con {{ (int) config('atlantia.vendedores.denuncias_para_pausa', 3) }} denuncias de clientes distintos en {{ (int) config('atlantia.vendedores.dias_denuncias', 7) }} días, la tienda se pausa sola y sus pagos se retienen hasta que la revises. {{ $pendientes }} sin revisar.
            </p>
        </div>

        @if ($enRevision->isNotEmpty())
            <article class="rounded-xl border border-rose-200 bg-rose-50 p-4" data-tiendas-en-revision>
                <h2 class="text-sm font-black text-rose-800">Tiendas pausadas en revisión</h2>
                <ul class="mt-2 divide-y divide-rose-200/60">
                    @foreach ($enRevision as $tienda)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <div class="text-sm">
                                <a href="{{ route('admin.vendedores.show', $tienda) }}" class="font-black text-rose-900 hover:underline">{{ $tienda->business_name }}</a>
                                <p class="text-xs text-rose-800/80">{{ $tienda->motivo_retencion }} · desde el {{ $tienda->pagos_retenidos_at?->format('d/m/Y h:i a') }} · {{ $tienda->denuncias_pendientes }} sin revisar</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.denuncias.reactivar', $tienda) }}" data-confirm="¿Reactivar {{ $tienda->business_name }}? Vuelve a recibir pedidos y se liberan sus pagos.">
                                    @csrf
                                    <button type="submit" class="h-9 rounded-lg bg-white px-3 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-50">Reactivar</button>
                                </form>
                                <a href="{{ route('admin.vendedores.show', $tienda) }}" class="h-9 rounded-lg bg-rose-700 px-3 text-xs font-bold leading-9 text-white hover:bg-rose-800">Suspender o dar de baja</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endif

        <nav class="flex gap-1 rounded-lg bg-atlantia-blush/50 p-1" aria-label="Estado">
            @foreach ($estados as $clave => $texto)
                <a href="{{ route('admin.denuncias.index', $clave !== 'pendiente' ? ['estado' => $clave] : []) }}" class="rounded-md px-3 py-1 text-xs font-bold {{ $estado === $clave ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/65 hover:text-atlantia-ink' }}">{{ $texto }}</a>
            @endforeach
        </nav>

        <div class="space-y-2">
            @forelse ($denuncias as $denuncia)
                <article class="rounded-xl border border-atlantia-rose/15 bg-white p-4" data-denuncia="{{ $denuncia->uuid }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase text-rose-700">{{ \App\Models\DenunciaTienda::MOTIVOS[$denuncia->motivo] ?? $denuncia->motivo }}</p>
                            <h2 class="text-sm font-black text-atlantia-ink">
                                <a href="{{ $denuncia->vendor ? route('admin.vendedores.show', $denuncia->vendor) : '#' }}" class="hover:underline">{{ $denuncia->vendor?->business_name ?? 'Tienda eliminada' }}</a>
                            </h2>
                            <p class="text-xs text-atlantia-ink/55">
                                Por {{ $denuncia->cliente?->name ?? 'cliente' }}{{ $denuncia->cliente?->email ? ' · '.$denuncia->cliente->email : '' }} · {{ $denuncia->created_at?->format('d/m/Y h:i a') }}
                                @if ($denuncia->pedido)
                                    · pedido <a href="{{ route('admin.pedidos.show', $denuncia->pedido->pedidoPadre ?? $denuncia->pedido) }}" class="font-bold text-atlantia-wine hover:underline">#{{ ($denuncia->pedido->pedidoPadre ?? $denuncia->pedido)->numero_pedido }}</a>
                                @endif
                            </p>
                        </div>
                        @if ($denuncia->estado !== 'pendiente')
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $denuncia->estado === 'confirmada' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-slate-100 text-slate-600' }}">{{ $denuncia->estado === 'confirmada' ? 'Confirmada' : 'Descartada' }}</span>
                        @endif
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm text-atlantia-ink/80">{{ $denuncia->descripcion }}</p>

                    @if ($denuncia->estado === 'pendiente')
                        <form method="POST" action="{{ route('admin.denuncias.resolver', $denuncia) }}" class="mt-3 flex flex-wrap items-end gap-2 border-t border-atlantia-rose/10 pt-3">
                            @csrf
                            <input name="resolucion" maxlength="1000" placeholder="Nota de lo que revisaste (opcional)" class="h-9 min-w-[220px] flex-1 rounded-lg border border-atlantia-rose/30 px-3 text-sm">
                            <button type="submit" name="estado" value="descartada" class="h-9 rounded-lg border border-atlantia-rose/25 px-3 text-xs font-bold text-atlantia-ink/70 hover:bg-atlantia-blush">Descartar</button>
                            <button type="submit" name="estado" value="confirmada" class="h-9 rounded-lg bg-rose-700 px-3 text-xs font-bold text-white hover:bg-rose-800">Confirmar</button>
                        </form>
                    @elseif ($denuncia->resolucion || $denuncia->revisor)
                        <p class="mt-2 text-xs text-atlantia-ink/55">{{ $denuncia->revisor?->name ? 'Revisó '.$denuncia->revisor->name.': ' : '' }}{{ $denuncia->resolucion }}</p>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-atlantia-rose/25 bg-white px-4 py-8 text-center text-sm text-atlantia-ink/55">{{ $estado === 'pendiente' ? 'No hay denuncias sin revisar.' : 'No hay denuncias aquí.' }}</p>
            @endforelse
        </div>

        {{ $denuncias->links() }}
    </section>
@endsection
