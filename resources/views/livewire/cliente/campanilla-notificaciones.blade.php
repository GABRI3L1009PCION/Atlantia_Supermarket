<div
    x-data="{ open: false }"
    class="relative"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click.stop="open = !open"
        class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-atlantia-rose/30 bg-white text-atlantia-wine transition hover:bg-atlantia-blush"
        aria-label="Abrir notificaciones"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="panel-notificaciones"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 17H9C7.9 17 7 16.1 7 15V10.8C7 7.9 9 5.4 11.8 4.8C15 4.1 18 6.5 18 9.6V15C18 16.1 17.1 17 16 17H15Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M10 19C10.5 20 11.1 20.5 12 20.5C12.9 20.5 13.5 20 14 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>

        @if ($noLeidas > 0)
            <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-atlantia-wine px-1 text-[10px] font-black text-white">
                {{ $noLeidas > 99 ? '99+' : $noLeidas }}
            </span>
        @endif
    </button>

    <div
        id="panel-notificaciones"
        x-show="open"
        x-cloak
        x-transition.opacity.scale.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-30 mt-3 w-[22rem] rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-2xl"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-black text-atlantia-ink">Notificaciones</h3>
                <p class="text-xs text-atlantia-ink/55">Ultimas 10 novedades de tu cuenta.</p>
            </div>

            <div class="flex items-center gap-2">
                @if ($noLeidas > 0)
                    <button
                        type="button"
                        wire:click="markAllAsRead"
                        @click="open = false"
                        class="text-xs font-bold text-atlantia-wine hover:underline"
                    >
                        Marcar todas
                    </button>
                @endif

                <button
                    type="button"
                    @click="open = false"
                    class="rounded-full p-1 text-atlantia-ink/45 transition hover:bg-atlantia-blush hover:text-atlantia-wine"
                    aria-label="Cerrar notificaciones"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="mt-4 max-h-96 space-y-3 overflow-y-auto">
            @forelse ($notificaciones as $notificacion)
                @php($data = is_array($notificacion->data ?? null) ? $notificacion->data : [])
                <button
                    type="button"
                    wire:click="markAsRead('{{ $notificacion->id }}')"
                    @click="open = false"
                    class="w-full rounded-xl border border-atlantia-rose/20 p-3 text-left transition hover:bg-atlantia-blush"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-atlantia-ink">{{ $data['title'] ?? $data['titulo'] ?? 'Notificacion' }}</p>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/70">
                                {{ $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva actualizacion.' }}
                            </p>
                        </div>
                        @if ($notificacion->read_at === null)
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-atlantia-ink/45">
                        {{ \Illuminate\Support\Carbon::parse($notificacion->created_at)->diffForHumans() }}
                    </p>
                </button>
            @empty
                <div class="rounded-xl bg-atlantia-blush p-4 text-sm text-atlantia-ink/70">
                    No tienes notificaciones recientes.
                </div>
            @endforelse
        </div>
    </div>
</div>
