{{--
    Campana de notificaciones + menú de la cuenta, compartidos por todos los paneles.
    Parámetros:
      $etiquetaRol      Texto bajo el nombre (ej. "Dueño · acceso total", "Vendedor").
      $opcionesCuenta   Lista de ['texto' => ..., 'url' => ..., 'nueva' => bool].
--}}
@php
    $cuentaUsuario = auth()->user();
    $cuentaNombre = $cuentaUsuario?->name ?? 'Cuenta';
    $cuentaPrimerNombre = \Illuminate\Support\Str::of($cuentaNombre)->explode(' ')->first();
    $cuentaIniciales = collect(preg_split('/\s+/', trim($cuentaNombre)) ?: [])
        ->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->join('') ?: 'A';
    $servicioNotificaciones = app(\App\Services\Notificaciones\NotificationService::class);
    $notificacionesPanel = $cuentaUsuario ? $servicioNotificaciones->latest($cuentaUsuario, 8) : collect();
    $noLeidasPanel = $cuentaUsuario ? $servicioNotificaciones->unreadCount($cuentaUsuario) : 0;
@endphp

{{-- Campana de notificaciones --}}
<div class="relative" x-data="{ abierto: false }" x-on:click.outside="abierto = false" x-on:keydown.escape.window="abierto = false" data-campana data-recientes-url="{{ route('notificaciones.recientes') }}">
    <button type="button" x-on:click="abierto = !abierto" :aria-expanded="abierto.toString()" class="relative grid h-9 w-9 place-items-center rounded-xl text-atlantia-ink/60 transition hover:bg-atlantia-blush/50 hover:text-atlantia-wine" aria-label="Notificaciones">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
        <span data-campana-contador @class(['absolute -right-0.5 -top-0.5 min-w-[1.1rem] rounded-full bg-red-600 px-1 text-center text-[10px] font-black leading-[1.1rem] text-white ring-2 ring-white', 'hidden' => $noLeidasPanel === 0])>{{ $noLeidasPanel > 99 ? '99+' : $noLeidasPanel }}</span>
    </button>

    <div class="absolute right-0 top-full z-40 mt-2 hidden w-80 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_18px_40px_rgba(59,6,12,0.14)]" :class="{ 'hidden': !abierto }">
        <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-4 py-3">
            <p class="text-sm font-black text-atlantia-ink">Notificaciones</p>
            @if ($noLeidasPanel > 0)
                <form method="POST" action="{{ route('notificaciones.read-all') }}">
                    @csrf
                    <button class="text-xs font-bold text-atlantia-wine hover:underline">Marcar todas como leídas</button>
                </form>
            @endif
        </div>
        <div class="max-h-96 overflow-y-auto">
            @forelse ($notificacionesPanel as $aviso)
                <a href="{{ route('notificaciones.open', $aviso->id) }}" @class(['flex gap-3 border-b border-atlantia-rose/10 px-4 py-3 transition hover:bg-atlantia-blush/40', 'bg-atlantia-blush/20' => $aviso->read_at === null])>
                    <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-atlantia-wine' => $aviso->read_at === null, 'bg-transparent' => $aviso->read_at !== null])></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-black text-atlantia-ink">{{ $aviso->data['title'] ?? 'Notificación' }}</span>
                        <span class="mt-0.5 block text-xs leading-snug text-atlantia-ink/65">{{ $aviso->data['message'] ?? '' }}</span>
                        <span class="mt-1 block text-[11px] font-semibold text-atlantia-ink/40">{{ \Illuminate\Support\Carbon::parse($aviso->created_at)->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-atlantia-ink/55">No tienes notificaciones.</p>
            @endforelse
        </div>
        <a href="{{ route('notificaciones.index') }}" class="block border-t border-atlantia-rose/10 px-4 py-2.5 text-center text-sm font-black text-atlantia-wine hover:bg-atlantia-blush/40">Ver todas</a>
    </div>
</div>

<span class="mx-1 hidden h-6 w-px bg-atlantia-rose/20 sm:block" aria-hidden="true"></span>

{{-- Menú de la cuenta --}}
<div class="relative" x-data="{ abierto: false }" x-on:click.outside="abierto = false" x-on:keydown.escape.window="abierto = false">
    <button type="button" x-on:click="abierto = !abierto" :aria-expanded="abierto.toString()" aria-haspopup="menu" class="flex items-center gap-2.5 rounded-xl py-1 pl-1 pr-2 transition hover:bg-atlantia-blush/50">
        <span class="grid h-8 w-8 place-items-center rounded-full bg-[linear-gradient(135deg,#3a0004_0%,#6e000a_100%)] text-xs font-black text-white">{{ $cuentaIniciales }}</span>
        <span class="hidden text-left leading-tight md:block">
            <span class="block max-w-40 truncate text-sm font-black text-atlantia-ink">{{ $cuentaPrimerNombre }}</span>
            <span class="block text-[11px] font-semibold text-atlantia-ink/45">{{ $etiquetaRol ?? 'Mi cuenta' }}</span>
        </span>
        <svg class="h-4 w-4 text-atlantia-ink/40 transition" :class="{ 'rotate-180': abierto }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div class="absolute right-0 top-full z-40 mt-2 hidden w-64 overflow-hidden rounded-2xl border border-atlantia-rose/15 bg-white shadow-[0_18px_40px_rgba(59,6,12,0.14)]" :class="{ 'hidden': !abierto }" role="menu">
        <div class="border-b border-atlantia-rose/10 px-4 py-3">
            <p class="truncate text-sm font-black text-atlantia-ink">{{ $cuentaNombre }}</p>
            <p class="truncate text-xs text-atlantia-ink/50">{{ $cuentaUsuario?->email }}</p>
        </div>
        @if (! empty($opcionesCuenta))
            <div class="p-1.5 text-sm">
                @foreach ($opcionesCuenta as $opcion)
                    <a href="{{ $opcion['url'] }}" @if ($opcion['nueva'] ?? false) target="_blank" rel="noopener" @endif class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-bold text-atlantia-ink/75 hover:bg-atlantia-blush/40 hover:text-atlantia-wine" role="menuitem">{{ $opcion['texto'] }}{{ ($opcion['nueva'] ?? false) ? ' ↗' : '' }}</a>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="border-t border-atlantia-rose/10 p-1.5">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-black text-red-700 hover:bg-red-50" role="menuitem">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</div>
