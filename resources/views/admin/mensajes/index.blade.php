@extends('layouts.app')

@section('content')
    @php
        $tipos = ['' => 'Todos', 'soporte' => 'Ayuda a tiendas', 'cotizacion' => 'Cotizaciones', 'contacto' => 'Contacto'];
        $etiquetaTipo = ['soporte' => 'Ayuda a tienda', 'cotizacion' => 'Cotización', 'contacto' => 'Contacto'];
        $estados = ['pendientes' => 'Sin atender', 'atendidos' => 'Atendidos', 'todos' => 'Todos'];
        $totalPendientes = (int) $pendientes->sum();
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Mensajes</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Ayuda que piden las tiendas, cotizaciones de planes y mensajes de contacto. {{ $totalPendientes }} sin atender.</p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <nav class="flex flex-wrap gap-1.5" aria-label="Tipo de mensaje">
                @foreach ($tipos as $clave => $texto)
                    @php $n = $clave === '' ? $totalPendientes : (int) ($pendientes[$clave] ?? 0); @endphp
                    <a href="{{ route('admin.mensajes.index', array_filter(['tipo' => $clave, 'estado' => $estado !== 'pendientes' ? $estado : null])) }}"
                       class="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs font-bold {{ $tipo === $clave ? 'bg-atlantia-wine text-white' : 'border border-atlantia-rose/25 bg-white text-atlantia-ink hover:bg-atlantia-blush' }}">
                        {{ $texto }}
                        @if ($n > 0)<span class="rounded-full {{ $tipo === $clave ? 'bg-white/25' : 'bg-atlantia-blush text-atlantia-wine' }} px-1.5 text-[11px]">{{ $n }}</span>@endif
                    </a>
                @endforeach
            </nav>
            <nav class="flex gap-1 rounded-lg bg-atlantia-blush/50 p-1" aria-label="Estado">
                @foreach ($estados as $clave => $texto)
                    <a href="{{ route('admin.mensajes.index', array_filter(['tipo' => $tipo, 'estado' => $clave !== 'pendientes' ? $clave : null])) }}"
                       class="rounded-md px-3 py-1 text-xs font-bold {{ $estado === $clave ? 'bg-white text-atlantia-wine shadow-sm' : 'text-atlantia-ink/65 hover:text-atlantia-ink' }}">{{ $texto }}</a>
                @endforeach
            </nav>
        </div>

        <div class="space-y-2">
            @forelse ($mensajes as $mensaje)
                <article class="rounded-xl border {{ $mensaje->atendido ? 'border-atlantia-rose/10 bg-white/70' : 'border-atlantia-rose/20 bg-white' }} p-4" data-mensaje="{{ $mensaje->uuid }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full bg-atlantia-blush px-2 py-0.5 text-[11px] font-bold text-atlantia-wine">{{ $etiquetaTipo[$mensaje->tipo] ?? 'Contacto' }}</span>
                                @if ($mensaje->categoria)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $categorias[$mensaje->categoria] ?? $mensaje->categoria }}</span>
                                @endif
                                @if ($mensaje->prioridad === 'alta' && ! $mensaje->atendido)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200">Urgente</span>
                                @endif
                            </div>
                            <h2 class="mt-1 text-sm font-black text-atlantia-ink">{{ $mensaje->asunto }}</h2>
                            <p class="text-xs text-atlantia-ink/55">
                                {{ $mensaje->nombre }} · {{ $mensaje->email }}@if ($mensaje->telefono) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $mensaje->telefono) }}" class="font-bold text-atlantia-wine hover:underline">{{ $mensaje->telefono }}</a>@endif
                                · {{ $mensaje->created_at?->format('d/m/Y h:i a') }}
                            </p>
                        </div>
                        @if ($mensaje->atendido)
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">Atendido</span>
                        @endif
                    </div>

                    <p class="mt-2 whitespace-pre-line text-sm text-atlantia-ink/80">{{ $mensaje->mensaje }}</p>

                    @if ($mensaje->atendido)
                        <p class="mt-2 text-xs text-atlantia-ink/55">Atendido por {{ $mensaje->atendidoPor?->name ?? 'el equipo' }} el {{ $mensaje->atendido_at?->format('d/m/Y h:i a') }}.</p>
                        @if ($mensaje->respuesta)
                            <div class="mt-1 rounded-lg bg-atlantia-blush/60 px-3 py-2 text-sm text-atlantia-ink"><span class="font-bold">Respuesta:</span> {{ $mensaje->respuesta }}</div>
                        @endif
                    @else
                        <form method="POST" action="{{ route('admin.mensajes.atender', $mensaje) }}" class="mt-3 space-y-2 border-t border-atlantia-rose/10 pt-3">
                            @csrf
                            @if ($mensaje->user_id)
                                <textarea name="respuesta" rows="2" maxlength="2000" placeholder="Respuesta (opcional). Si escribes algo, le aparece en su panel." class="w-full resize-y rounded-lg border border-atlantia-rose/30 px-3 py-2 text-sm outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush"></textarea>
                            @else
                                <p class="text-xs text-atlantia-ink/55">No tiene cuenta: respóndele por correo o teléfono y luego márcalo como atendido.</p>
                            @endif
                            <div class="flex justify-end">
                                <button type="submit" class="h-9 rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Marcar como atendido</button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-atlantia-rose/25 bg-white px-4 py-8 text-center text-sm text-atlantia-ink/55">
                    {{ $estado === 'pendientes' ? 'No hay mensajes sin atender. ¡Todo al día!' : 'No hay mensajes aquí.' }}
                </p>
            @endforelse
        </div>

        {{ $mensajes->links() }}
    </section>
@endsection
