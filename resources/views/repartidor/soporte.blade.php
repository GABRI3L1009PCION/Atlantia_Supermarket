@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Soporte local</p>
                <h1 class="text-2xl font-black text-atlantia-ink">Seguridad y ayuda</h1>
            </div>
            <form method="POST" action="{{ route('repartidor.emergencia.store') }}">
                @csrf
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-black text-white">Boton de emergencia</button>
            </form>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
            <article class="rounded-lg border border-atlantia-rose/15 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-black text-atlantia-ink">Nuevo caso</h2>
                <form method="POST" action="{{ route('repartidor.soporte.tickets.store') }}" class="mt-3 space-y-3">
                    @csrf
                    <label class="block text-sm font-bold text-atlantia-ink">
                        Tipo
                        <select name="type" class="mt-1 w-full rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm">
                            <option value="support_chat">Chat directo con soporte</option>
                            <option value="closed_business">Negocio cerrado</option>
                            <option value="store_problem">Problema con establecimiento</option>
                            <option value="customer_problem">Problema con cliente</option>
                            <option value="damaged_order">Pedido dañado</option>
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
                <h2 class="text-lg font-black text-atlantia-ink">Casos recientes</h2>
                <div class="mt-3 divide-y divide-atlantia-rose/10">
                    @forelse ($tickets as $ticket)
                        <div class="py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-atlantia-ink">{{ ucfirst(str_replace('_', ' ', $ticket->type)) }}</p>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-atlantia-ink">{{ $ticket->status }}</span>
                            </div>
                            <p class="mt-1 text-sm text-atlantia-ink/60">{{ $ticket->message }}</p>
                            <p class="mt-1 text-xs text-atlantia-ink/45">{{ $ticket->created_at->format('d/m/Y H:i') }} · {{ $ticket->priority }}</p>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm font-bold text-atlantia-ink/55">Sin casos recientes.</p>
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
                <p class="font-black text-sky-800">Zonas seguras</p>
                <p class="mt-1 text-sm text-sky-700/75">Operacion puede configurar zonas y restricciones por perfil.</p>
            </div>
            <div class="rounded-lg bg-amber-50 p-4">
                <p class="font-black text-amber-800">Pagos transparentes</p>
                <p class="mt-1 text-sm text-amber-700/75">Cada entrega genera movimientos de ganancia, propina, bono y efectivo.</p>
            </div>
        </div>
    </section>
@endsection
