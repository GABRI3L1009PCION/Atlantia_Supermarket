@extends('layouts.app')

@section('content')
    @php
        $openTickets = $metrics['open_tickets'];
        $emergencies = $metrics['emergencies'];
        $closedTickets = $metrics['closed_tickets'];
        $agents = $metrics['agents'];
    @endphp

    <section class="mx-auto max-w-7xl space-y-6">
        <x-page-header title="Soporte de repartidores" subtitle="Asignacion a asesores, respuestas, estados, historial y emergencias." />

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <h2 class="text-lg font-black text-red-800">Emergencias activas</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($emergencies as $ticket)
                        <div class="rounded-lg bg-white p-3">
                            <p class="font-black text-atlantia-ink">{{ $ticket->user?->name }}</p>
                            <p class="mt-1 text-sm text-atlantia-ink/60">{{ $ticket->message }}</p>
                            <p class="mt-2 text-xs font-bold text-red-700">Ticket {{ $ticket->uuid }}</p>
                        </div>
                    @empty
                        <p class="text-sm font-bold text-red-700/75">No hay emergencias abiertas.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-black text-atlantia-ink">Tickets abiertos</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($openTickets as $ticket)
                        <div class="rounded-xl border border-atlantia-rose/15 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-atlantia-ink">{{ $ticket->user?->name }} · {{ ucfirst(str_replace('_', ' ', $ticket->type)) }}</p>
                                    <p class="text-sm text-atlantia-ink/60">{{ $ticket->status }} · prioridad {{ $ticket->priority }} · {{ $ticket->assignedTo?->name ?? 'Sin asignar' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-atlantia-ink">{{ optional($ticket->last_message_at)->format('d/m/Y H:i') }}</span>
                            </div>

                            <div class="mt-3 space-y-2 rounded-lg bg-slate-50 p-3">
                                @foreach ($ticket->messages as $message)
                                    <div class="{{ $message->is_internal ? 'border-l-4 border-amber-300 pl-3' : '' }}">
                                        <p class="text-xs font-black text-atlantia-ink/60">{{ $message->user?->name ?? strtoupper($message->sender_type) }} · {{ $message->created_at?->format('d/m H:i') }}</p>
                                        <p class="text-sm text-atlantia-ink">{{ $message->message }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 grid gap-3 xl:grid-cols-3">
                                <form method="POST" action="{{ route('empleado.soporte-repartidores.assign', $ticket) }}" class="grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="assigned_to_user_id" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" @selected($ticket->assigned_to_user_id === $agent->id)>{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg border border-atlantia-rose/25 px-4 py-2 text-sm font-black text-atlantia-wine">Asignar</button>
                                </form>

                                <form method="POST" action="{{ route('empleado.soporte-repartidores.reply', $ticket) }}" class="grid gap-2 xl:col-span-2">
                                    @csrf
                                    <textarea name="message" rows="3" placeholder="Respuesta para el repartidor" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm"></textarea>
                                    <div class="flex flex-wrap gap-2">
                                        <select name="status" class="rounded-lg border border-atlantia-rose/20 px-3 py-2 text-sm">
                                            @foreach (['in_progress' => 'En proceso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Responder</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-atlantia-rose/20 px-4 py-10 text-center text-sm font-bold text-atlantia-ink/55">No hay tickets abiertos.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-black text-atlantia-ink">Historial reciente</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($closedTickets as $ticket)
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="font-black text-atlantia-ink">{{ $ticket->user?->name }}</p>
                        <p class="text-sm text-atlantia-ink/60">{{ ucfirst(str_replace('_', ' ', $ticket->type)) }} · {{ $ticket->status }}</p>
                    </div>
                @empty
                    <p class="text-sm font-bold text-atlantia-ink/55">Sin historial cerrado reciente.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
