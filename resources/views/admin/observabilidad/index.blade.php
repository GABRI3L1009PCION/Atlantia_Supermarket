@extends('layouts.super-admin')

@section('content')
    @php
        $summary = $snapshot['summary'];
        $checks = collect($snapshot['checks']);
        $release = $snapshot['release'];
        $severityClass = match ($snapshot['status']) {
            'error' => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        };
    @endphp

    <section class="mx-auto max-w-7xl space-y-6 pb-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-wide text-atlantia-rose">Operacion y seguridad</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-atlantia-ink sm:text-4xl">
                    Observabilidad operativa
                </h1>
                <p class="mt-2 max-w-3xl text-sm text-atlantia-ink/65">
                    Salud del sistema, alertas activas, respaldo, scheduler y contexto de release desde una sola pantalla.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('health') }}"
                    target="_blank"
                    rel="noreferrer"
                    class="rounded-lg border border-atlantia-rose/20 bg-white px-4 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                >
                    Ver /health
                </a>
                <a
                    href="{{ route('admin.auditoria.index') }}"
                    class="rounded-lg bg-atlantia-wine px-4 py-3 text-sm font-black text-white transition hover:bg-atlantia-wine-700"
                >
                    Abrir auditoria
                </a>
            </div>
        </div>

        <article class="{{ $severityClass }} rounded-2xl border px-5 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-wide">Estado actual</p>
                    <p class="mt-1 text-2xl font-black">
                        {{ match ($snapshot['status']) { 'error' => 'Incidente activo', 'warning' => 'Riesgos por atender', default => 'Operacion saludable' } }}
                    </p>
                </div>
                <p class="text-sm font-semibold">
                    Actualizado {{ \Illuminate\Support\Carbon::parse($snapshot['generated_at'])->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </article>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Checks OK</p>
                <p class="mt-2 text-3xl font-black text-emerald-600">{{ number_format($summary['ok']) }}</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Warnings</p>
                <p class="mt-2 text-3xl font-black text-amber-600">{{ number_format($summary['warning']) }}</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Errores</p>
                <p class="mt-2 text-3xl font-black text-rose-600">{{ number_format($summary['error']) }}</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Jobs fallidos</p>
                <p class="mt-2 text-3xl font-black text-atlantia-wine">{{ number_format($summary['failed_jobs']) }}</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Auditoria 24h</p>
                <p class="mt-2 text-3xl font-black text-atlantia-ink">{{ number_format($summary['audit_events_24h']) }}</p>
            </article>
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <p class="text-sm text-atlantia-ink/55">Fallos ML 24h</p>
                <p class="mt-2 text-3xl font-black text-sky-700">{{ number_format($summary['ml_failed_jobs_24h']) }}</p>
            </article>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-atlantia-ink">Chequeos operativos</h2>
                    <span class="rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                        {{ $checks->count() }} checks
                    </span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($checks as $check)
                        @php
                            $tone = match ($check['status']) {
                                'error' => 'border-rose-200 bg-rose-50 text-rose-700',
                                'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                                default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            };
                        @endphp

                        <div class="rounded-2xl border p-4 {{ $tone }}">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-black">{{ $check['label'] }}</p>
                                    <p class="mt-1 text-sm">{{ $check['detail'] }}</p>
                                </div>
                                <span class="rounded-full bg-white/70 px-3 py-1 text-xs font-black uppercase">
                                    {{ $check['status'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <div class="space-y-5">
                <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Respuesta a incidentes</h2>
                    <ol class="mt-4 space-y-3 text-sm text-atlantia-ink/75">
                        @foreach ($snapshot['response_steps'] as $step)
                            <li class="rounded-xl bg-atlantia-cream px-4 py-3 font-semibold">
                                {{ $loop->iteration }}. {{ $step }}
                            </li>
                        @endforeach
                    </ol>
                </article>

                <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-atlantia-ink">Release y despliegue</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-xl bg-atlantia-cream px-4 py-3">
                            <p class="font-black text-atlantia-ink">Ambiente {{ ucfirst($release['environment']) }}</p>
                            <p class="mt-1 text-atlantia-ink/65">Version {{ $release['version'] }} - {{ $release['app_url'] ?: 'APP_URL sin definir' }}</p>
                        </div>
                        <div class="rounded-xl bg-atlantia-cream px-4 py-3">
                            <p class="font-black text-atlantia-ink">Healthcheck</p>
                            <p class="mt-1 text-atlantia-ink/65">{{ $release['healthcheck_url'] }}</p>
                        </div>
                        <div class="rounded-xl bg-atlantia-cream px-4 py-3">
                            <p class="font-black text-atlantia-ink">Staging</p>
                            <p class="mt-1 text-atlantia-ink/65">{{ $release['staging_url'] ?: 'Pendiente de dominio staging' }}</p>
                        </div>
                        <div class="rounded-xl bg-atlantia-cream px-4 py-3">
                            <p class="font-black text-atlantia-ink">Runbook</p>
                            <p class="mt-1 text-atlantia-ink/65">{{ $release['runbook_path'] }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1fr_1fr]">
            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-atlantia-ink">Alertas activas</h2>
                    <span class="text-xs font-black text-atlantia-ink/50">{{ count($snapshot['incidents']) }} elementos</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($snapshot['incidents'] as $incident)
                        @php
                            $badge = $incident['status'] === 'error' ? 'bg-rose-100 text-rose-700' : ($incident['status'] === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                        @endphp
                        <div class="rounded-2xl border border-atlantia-rose/10 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-atlantia-ink">{{ $incident['title'] }}</p>
                                <span class="rounded-full px-3 py-1 text-xs font-black uppercase {{ $badge }}">{{ $incident['status'] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-atlantia-ink/70">{{ $incident['detail'] }}</p>
                            <p class="mt-2 text-sm font-semibold text-atlantia-wine">{{ $incident['action'] }}</p>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
                            No hay alertas activas. La operacion esta estable en este momento.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-atlantia-rose/10 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black text-atlantia-ink">Ultimo respaldo conocido</h2>
                @php
                    $backup = $summary['latest_backup'];
                @endphp
                <div class="mt-4 rounded-2xl bg-atlantia-cream p-4 text-sm text-atlantia-ink/75">
                    <p><strong class="text-atlantia-ink">Archivo:</strong> {{ $backup['path'] ?? 'Sin archivo detectado' }}</p>
                    <p class="mt-2"><strong class="text-atlantia-ink">Fecha:</strong> {{ $backup['timestamp'] ?? 'Sin timestamp' }}</p>
                    <p class="mt-2"><strong class="text-atlantia-ink">Antiguedad:</strong> {{ isset($backup['age_hours']) ? $backup['age_hours'].' horas' : 'No disponible' }}</p>
                    <p class="mt-2"><strong class="text-atlantia-ink">Tamano:</strong> {{ isset($backup['size_mb']) ? $backup['size_mb'].' MB' : 'No disponible' }}</p>
                </div>

                <div class="mt-4 rounded-2xl border border-atlantia-rose/10 p-4 text-sm text-atlantia-ink/70">
                    <p class="font-black text-atlantia-ink">Checklist minimo antes de liberar</p>
                    <ul class="mt-3 space-y-2">
                        <li>1. Staging funcional con secretos reales equivalentes.</li>
                        <li>2. `/health` en verde y snapshot sin errores criticos.</li>
                        <li>3. Push Android firmado y backend con imagen inmutable.</li>
                        <li>4. Respaldo reciente probado con restauracion de muestra.</li>
                    </ul>
                </div>
            </article>
        </div>
    </section>
@endsection
