@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Soporte local</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Seguridad, tickets y emergencias</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="tel:{{ preg_replace('/\s+/', '', $supportCenter['phone'] ?? '') }}" class="rounded-md border border-atlantia-rose/25 px-4 py-2 text-sm font-black text-atlantia-wine">
                    Llamar {{ $supportCenter['phone'] ?? '' }}
                </a>
                <form method="POST" action="{{ route('repartidor.emergencia.store') }}">
                    @csrf
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-black text-white">Reportar emergencia</button>
                </form>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Abrir nuevo caso</h2>
                <p class="mt-1 text-sm text-atlantia-ink/60">Tiempo promedio de respuesta: {{ $supportCenter['average_response_minutes'] ?? 2 }} minutos.</p>
                <form method="POST" action="{{ route('repartidor.soporte.tickets.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block text-sm font-bold text-atlantia-ink">
                        Tipo
                        <select name="type" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                            <option value="support_chat">Chat directo con soporte</option>
                            <option value="closed_business">Negocio cerrado</option>
                            <option value="store_problem">Problema con establecimiento</option>
                            <option value="customer_problem">Problema con cliente</option>
                            <option value="damaged_order">Pedido danado</option>
                            <option value="incomplete_order">Pedido incompleto</option>
                            <option value="payment_problem">Cobros y pagos</option>
                            <option value="forgotten_item">Objeto olvidado</option>
                            <option value="accident">Accidente</option>
                            <option value="insurance">Cobertura de seguro</option>
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-atlantia-ink">
                        Prioridad
                        <select name="priority" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                            <option value="normal">Normal</option>
                            <option value="high">Alta</option>
                            <option value="critical">Critica</option>
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-atlantia-ink">
                        Mensaje
                        <textarea name="message" rows="5" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm" required></textarea>
                    </label>
                    <button class="w-full rounded-md bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white">Enviar a soporte</button>
                </form>
            </article>

            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Conversacion e historial</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($tickets as $ticket)
                        <div class="rounded-xl border border-atlantia-rose/15 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-black text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $ticket->type)) }}</p>
                                    <p class="text-xs text-atlantia-ink/45">{{ $ticket->created_at->format('d/m/Y H:i') }} · {{ $ticket->priority }} · {{ $ticket->assignedTo?->name ?? 'Sin asesor asignado' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-atlantia-ink">{{ $ticket->status }}</span>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($ticket->messages as $message)
                                    @continue($message->is_internal)
                                    <div class="{{ $message->sender_type === 'courier' ? 'ml-auto bg-atlantia-blush text-atlantia-wine' : 'bg-slate-50 text-atlantia-ink' }} max-w-[90%] rounded-xl px-3 py-2 text-sm">
                                        <p class="font-bold">{{ $message->sender_type === 'courier' ? 'Tu mensaje' : ($message->user?->name ?? 'Soporte Atlantia') }}</p>
                                        <p class="mt-1">{{ $message->message }}</p>
                                    </div>
                                @endforeach
                            </div>
                            @if ($ticket->status !== 'closed')
                                <form method="POST" action="{{ route('repartidor.soporte.tickets.reply', $ticket) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="message" placeholder="Escribe una respuesta..." class="flex-1 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm">
                                    <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Responder</button>
                                </form>
                                <form method="POST" action="{{ route('repartidor.soporte.tickets.status', $ticket) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="closed">
                                    <input name="note" placeholder="Nota de cierre (opcional)" class="flex-1 rounded-lg border border-atlantia-rose/25 px-3 py-2 text-sm">
                                    <button class="rounded-lg border border-atlantia-rose/25 px-4 py-2 text-sm font-black text-atlantia-wine">Cerrar caso</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/25 px-4 py-10 text-center text-sm font-bold text-atlantia-ink/55">Sin conversaciones abiertas por ahora.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg bg-emerald-50 p-4">
                <p class="font-black text-emerald-800">Seguro activo</p>
                <p class="mt-1 text-sm text-emerald-700/75">Aplica durante aceptacion, recogida, ruta y entrega activa.</p>
            </div>
            <div class="rounded-lg bg-sky-50 p-4">
                <p class="font-black text-sky-800">Telefono real de soporte</p>
                <p class="mt-1 text-sm text-sky-700/75">{{ $supportCenter['emergency_phone'] ?? ($supportCenter['phone'] ?? '') }}</p>
            </div>
            <div class="rounded-lg bg-amber-50 p-4">
                <p class="font-black text-amber-800">Historial centralizado</p>
                <p class="mt-1 text-sm text-amber-700/75">Cada respuesta, asignacion y cierre queda guardado en el ticket.</p>
            </div>
        </div>
    </section>
@endsection
