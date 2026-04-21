@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-full py-2">
        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <aside class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <x-page-header :title="$log->event" subtitle="Detalle completo del evento auditado." />

                    <dl class="mt-6 space-y-3 text-sm">
                        <div>
                            <dt class="text-atlantia-ink/55">Usuario</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $log->user?->name ?? 'Sistema' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Request ID</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $log->request_id ?? 'Sin request' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Metodo</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $log->method ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">URL</dt>
                            <dd class="font-semibold break-all text-atlantia-ink">{{ $log->url ?? 'Sin URL' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">IP</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $log->ip_address ?? 'Sin IP' }}</dd>
                        </div>
                        <div>
                            <dt class="text-atlantia-ink/55">Fecha</dt>
                            <dd class="font-semibold text-atlantia-ink">{{ $log->created_at?->format('d/m/Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <div class="space-y-6">
                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Cambios anteriores</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Cambios nuevos</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div class="rounded-2xl border border-atlantia-rose/20 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Contexto adicional</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </section>
@endsection
