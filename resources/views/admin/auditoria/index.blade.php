@extends(auth()->user()?->isSuperAdmin() && request()->routeIs('admin.*') ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="space-y-6 rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
            <x-page-header title="Auditoria" subtitle="Consulta el rastro append-only de acciones sensibles y eventos operativos." />

            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                    <p class="text-sm text-atlantia-ink/55">Eventos ultimas 24h</p>
                    <p class="mt-2 text-2xl font-bold text-atlantia-wine">{{ $dashboard['eventos_24h'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Usuarios activos</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $dashboard['usuarios_activos_24h'] }}</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-white p-4">
                    <p class="text-sm text-atlantia-ink/55">Requests unicos</p>
                    <p class="mt-2 text-2xl font-bold text-sky-600">{{ $dashboard['requests_unicos_24h'] }}</p>
                </div>
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-4 md:col-span-2">
                    <p class="text-sm text-atlantia-ink/55">Eventos mas frecuentes</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($dashboard['eventos_top'] as $evento => $total)
                            <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">{{ $evento }} · {{ $total }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-atlantia-rose/20 bg-atlantia-cream p-4">
                <form method="GET" class="grid gap-3 xl:grid-cols-[1.2fr_0.9fr_0.8fr_0.8fr_auto]">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por evento, URL, request o usuario" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <select name="user_id" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los usuarios</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) request('user_id') === (string) $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="event" value="{{ request('event') }}" placeholder="Evento" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                    <select name="method" class="rounded-md border border-atlantia-rose/35 px-3 py-2">
                        <option value="">Todos los metodos</option>
                        @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                            <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-atlantia-rose/20 text-left text-atlantia-ink/55">
                            <th class="pb-3">Evento</th>
                            <th class="pb-3">Usuario</th>
                            <th class="pb-3">Request</th>
                            <th class="pb-3">Ruta</th>
                            <th class="pb-3">Fecha</th>
                            <th class="pb-3 text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-atlantia-rose/15">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="py-3">
                                    <span class="rounded-md bg-atlantia-blush px-3 py-1 text-xs font-bold text-atlantia-wine">{{ $log->event }}</span>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $log->user?->name ?? 'Sistema' }}</td>
                                <td class="py-3 text-atlantia-ink/70">{{ $log->request_id ?? 'Sin request' }}</td>
                                <td class="py-3 text-atlantia-ink/70">
                                    <p class="font-semibold">{{ $log->method ?? 'N/A' }}</p>
                                    <p class="text-xs text-atlantia-ink/55">{{ $log->url ?? 'Sin URL' }}</p>
                                </td>
                                <td class="py-3 text-atlantia-ink/70">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.auditoria.show', $log) }}" class="font-semibold text-atlantia-wine hover:underline">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-atlantia-ink/60">No hay eventos de auditoria para estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $logs->links() }}</div>
        </div>
    </section>
@endsection
