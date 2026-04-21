@extends('layouts.super-admin')

@section('content')
    @php
        $platform = $metrics['platform'];
        $services = $metrics['services'];
        $release = $metrics['release'];
        $branches = $metrics['branches'];
        $audit = $metrics['audit'];
        $models = $metrics['models'];
        $trainingJobs = $metrics['training_jobs'];
        $healthyServices = $services->where('status', 'operativo')->count();
        $totalServices = max(1, $services->count());
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-wide text-[#f4a7c5]">Plataforma Atlantia</p>
                <h1 class="mt-2 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Centro de control de plataforma
                </h1>
                <p class="mt-2 text-sm text-white/55">
                    Infraestructura, actualizaciones y gobierno del sistema
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:flex">
                <a
                    href="{{ route('admin.ml.reentrenamiento.index') }}"
                    class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-white/10"
                >
                    Reentrenar ML
                </a>
                <a
                    href="{{ route('admin.usuarios.index') }}"
                    class="rounded-lg bg-[#9a285a] px-4 py-3 text-center text-sm font-black text-white transition hover:bg-[#b63a70]"
                >
                    Administrar accesos
                </a>
            </div>
        </div>

        <article class="rounded-lg border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <p>
                    <strong class="text-amber-300">Revision de despliegue:</strong>
                    ambiente {{ $release['environment'] }}, version {{ $release['current'] }}, rama {{ $release['branch'] }}.
                </p>
                <a href="{{ route('admin.auditoria.index') }}" class="font-black text-amber-300 hover:underline">
                    Ver auditoria ->
                </a>
            </div>
        </article>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <p class="text-sm text-white/45">Ambiente</p>
                <p class="mt-2 text-2xl font-black text-emerald-300">{{ ucfirst($platform['environment']) }}</p>
                <p class="mt-1 text-xs font-bold text-white/40">configuracion activa</p>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <p class="text-sm text-white/45">Usuarios</p>
                <p class="mt-2 text-2xl font-black text-white">{{ number_format($platform['users']) }}</p>
                <p class="mt-1 text-xs font-bold text-emerald-300">{{ number_format($platform['admins']) }} admins</p>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <p class="text-sm text-white/45">Super admins</p>
                <p class="mt-2 text-2xl font-black text-white">{{ number_format($platform['super_admins']) }}</p>
                <p class="mt-1 text-xs font-bold text-[#f4a7c5]">acceso total</p>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <p class="text-sm text-white/45">Vendedores</p>
                <p class="mt-2 text-2xl font-black text-white">{{ number_format($platform['vendors']) }}</p>
                <p class="mt-1 text-xs font-bold text-white/40">inquilinos virtuales</p>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <p class="text-sm text-white/45">Productos</p>
                <p class="mt-2 text-2xl font-black text-white">{{ number_format($platform['products']) }}</p>
                <p class="mt-1 text-xs font-bold text-white/40">catalogo activo</p>
            </article>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-white">Servicios en produccion</h2>
                    <span class="text-xs font-black text-emerald-300">
                        {{ $healthyServices }} de {{ $totalServices }} operativos
                    </span>
                </div>

                <div class="mt-4 divide-y divide-white/10">
                    @foreach ($services as $service)
                        @php
                            $statusClass = match ($service['status']) {
                                'operativo' => 'text-emerald-300',
                                'configurar' => 'text-amber-300',
                                default => 'text-rose-300',
                            };
                        @endphp

                        <div class="grid grid-cols-[1fr_auto] gap-4 py-3">
                            <div>
                                <p class="font-black text-white">{{ $service['name'] }}</p>
                                <p class="text-xs font-semibold text-white/40">{{ $service['detail'] }}</p>
                            </div>
                            <span class="{{ $statusClass }} text-sm font-black">{{ $service['status'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-white">Acciones rapidas</h2>
                    <span class="rounded bg-rose-500/15 px-2 py-1 text-xs font-black text-rose-200">ACCESO TOTAL</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.reportes.index') }}" class="rounded-lg border border-white/10 bg-white/5 px-3 py-4 text-center text-sm font-black text-white hover:bg-white/10">
                        Revisar reportes
                    </a>
                    <a href="{{ route('admin.auditoria.index') }}" class="rounded-lg border border-white/10 bg-white/5 px-3 py-4 text-center text-sm font-black text-white hover:bg-white/10">
                        Ver trazas
                    </a>
                    <a href="{{ route('admin.roles-permisos.index') }}" class="rounded-lg border border-white/10 bg-white/5 px-3 py-4 text-center text-sm font-black text-white hover:bg-white/10">
                        Rotar accesos
                    </a>
                    <a href="{{ route('admin.ml.reentrenamiento.index') }}" class="rounded-lg border border-white/10 bg-white/5 px-3 py-4 text-center text-sm font-black text-white hover:bg-white/10">
                        Reentrenar modelos
                    </a>
                    <a href="{{ route('admin.usuarios.index') }}" class="rounded-lg border border-white/10 bg-white/5 px-3 py-4 text-center text-sm font-black text-white hover:bg-white/10">
                        Crear admin
                    </a>
                    <button
                        type="button"
                        class="rounded-lg border border-rose-400/30 bg-rose-500/10 px-3 py-4 text-sm font-black text-rose-200"
                        title="Accion reservada para despliegue productivo controlado"
                    >
                        Bloqueo emergencia
                    </button>
                </div>

                <p class="mt-4 text-center text-xs font-semibold text-white/35">
                    Todas las acciones quedan en auditoria
                </p>
            </article>
        </div>

        <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-white">Proceso de actualizacion</h2>
                <p class="text-xs font-semibold text-white/40">Controlado por super admin</p>
            </div>

            <div class="mt-4 grid gap-2 md:grid-cols-5">
                @foreach ($release['pipeline'] as $step)
                    @php
                        $stepClass = match ($step['status']) {
                            'lista' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200',
                            'espera' => 'border-amber-400/40 bg-amber-400/10 text-amber-200',
                            default => 'border-white/10 bg-white/5 text-white/45',
                        };
                    @endphp

                    <div class="{{ $stepClass }} rounded-lg border px-3 py-4 text-center">
                        <p class="text-sm font-black">{{ $step['label'] }}</p>
                        <p class="mt-1 text-xs font-semibold opacity-75">{{ $step['detail'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>

        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <h2 class="text-xl font-black text-white">Cobertura operativa</h2>

                <div class="mt-4 divide-y divide-white/10">
                    @foreach ($branches as $branch)
                        <div class="grid grid-cols-[1fr_auto] gap-4 py-3 text-sm">
                            <div>
                                <p class="font-black text-white">{{ $branch['name'] }}</p>
                                <p class="text-xs font-bold text-emerald-300">{{ $branch['status'] }}</p>
                            </div>
                            <p class="font-black text-white/55">{{ number_format($branch['orders']) }} ped.</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
                <h2 class="text-xl font-black text-white">Registro de auditoria</h2>

                <div class="mt-4 divide-y divide-white/10">
                    @forelse ($audit as $log)
                        <div class="py-3 text-sm">
                            <p class="font-black text-white">{{ $log->event }}</p>
                            <p class="text-xs text-white/45">
                                {{ optional($log->created_at)->format('H:i') }}
                                · {{ $log->ip_address ?? 'sin IP' }}
                                · {{ $log->method ?? 'sistema' }}
                            </p>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-white/45">Aun no hay eventos de auditoria.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="rounded-lg border border-white/10 bg-white/[0.035] p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-white">Modelos inteligentes en produccion</h2>
                <a href="{{ route('admin.ml.monitor') }}" class="text-sm font-black text-[#f4a7c5] hover:underline">
                    Monitor ML ->
                </a>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @forelse ($models as $model)
                    @php
                        $metricas = $model->metricas ?? [];
                    @endphp

                    <div class="rounded-lg border border-[#7c4dff]/50 bg-[#1b1430] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-black text-[#cbb8ff]">{{ $model->nombre_modelo }}</p>
                            <span class="text-xs font-black text-[#cbb8ff]">{{ $model->version }}</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-white/55">Estado: {{ $model->estado }}</p>
                        <p class="mt-1 text-xs font-bold text-emerald-300">
                            MAPE {{ $metricas['mape'] ?? 'sin metrica' }}
                        </p>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-white/15 bg-white/5 p-4 md:col-span-3">
                        <p class="font-black text-white">Sin modelos desplegados</p>
                        <p class="mt-1 text-sm text-white/45">
                            Cuando el servicio ML registre versiones, apareceran aqui para gobierno del super admin.
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($trainingJobs->isNotEmpty())
                <div class="mt-4 rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="text-sm font-black text-white">Ultimos procesos de entrenamiento</p>
                    <div class="mt-3 grid gap-2 md:grid-cols-3">
                        @foreach ($trainingJobs as $job)
                            <div class="rounded-lg bg-black/20 p-3 text-sm">
                                <p class="font-black text-white">{{ $job->modelo_nombre }}</p>
                                <p class="text-xs text-white/45">{{ $job->estado }} · {{ optional($job->inicio_at)->format('d/m/Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
