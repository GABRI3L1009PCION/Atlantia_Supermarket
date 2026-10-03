<details class="group relative">
    <summary
        class="relative inline-flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full border border-atlantia-rose/30 bg-white text-atlantia-wine transition hover:bg-atlantia-blush group-open:bg-atlantia-blush [&::-webkit-details-marker]:hidden"
        aria-label="Abrir notificaciones"
    >
        <span class="sr-only">Abrir notificaciones</span>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 17H9C7.9 17 7 16.1 7 15V10.8C7 7.9 9 5.4 11.8 4.8C15 4.1 18 6.5 18 9.6V15C18 16.1 17.1 17 16 17H15Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M10 19C10.5 20 11.1 20.5 12 20.5C12.9 20.5 13.5 20 14 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>

        @if ($noLeidas > 0)
            <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-atlantia-wine px-1 text-[10px] font-black text-white">
                {{ $noLeidas > 99 ? '99+' : $noLeidas }}
            </span>
        @endif
    </summary>

    <div id="panel-notificaciones" class="absolute right-0 z-50 mt-3 w-[22rem] max-w-[calc(100vw-1rem)] overflow-hidden rounded-md border border-atlantia-rose/20 bg-white text-atlantia-ink shadow-2xl" role="dialog" aria-label="Notificaciones">
        <div class="flex items-center justify-between gap-3 border-b border-atlantia-rose/10 px-4 py-3">
            <h3 class="text-sm font-black text-atlantia-ink">Notificaciones</h3>

            @if ($noLeidas > 0)
                <button type="button" wire:click="markAllAsRead" class="inline-flex items-center gap-1 text-[11px] font-black text-atlantia-wine hover:text-atlantia-wine-700">
                    Marcar todas como leidas
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            @endif
        </div>

        <div class="max-h-[25rem] overflow-y-auto">
            @forelse ($notificaciones as $notificacion)
                @php
                    $data = is_array($notificacion->data ?? null) ? $notificacion->data : [];
                    $title = $data['title'] ?? $data['titulo'] ?? 'Notificacion';
                    $message = $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva actualizacion.';
                    $url = $data['url'] ?? null;
                    $type = (string) ($notificacion->type ?? '');
                    $isUnread = $notificacion->read_at === null;
                    $tone = match (true) {
                        str_contains($type, 'pedido') => 'rose',
                        str_contains($type, 'pago'), str_contains($type, 'confirmado') => 'emerald',
                        str_contains($type, 'stock'), str_contains($type, 'inventario') => 'sky',
                        str_contains($type, 'usuario'), str_contains($type, 'vendor') => 'amber',
                        str_contains($type, 'ml'), str_contains($type, 'promo') => 'violet',
                        default => 'rose',
                    };
                    $toneClass = match ($tone) {
                        'emerald' => 'bg-emerald-50 text-emerald-700',
                        'sky' => 'bg-sky-50 text-sky-700',
                        'amber' => 'bg-amber-50 text-amber-700',
                        'violet' => 'bg-violet-50 text-violet-700',
                        default => 'bg-rose-50 text-atlantia-wine',
                    };
                    $href = $url
                        ? ($notificacion->id === 'vendor-pending-requests' ? $url : route('notificaciones.open', $notificacion->id))
                        : null;
                @endphp

                @if ($href)
                    <a href="{{ $href }}" class="flex gap-3 border-b border-atlantia-rose/10 px-4 py-3 text-left transition hover:bg-atlantia-blush/70">
                        <span class="{{ $toneClass }} grid h-9 w-9 shrink-0 place-items-center rounded-full">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3ZM12 12L20 7.5M12 12V21M12 12L4 7.5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-3">
                                <span class="text-xs font-black text-atlantia-ink">{{ $title }}</span>
                                <span class="shrink-0 text-[10px] font-semibold text-atlantia-ink/45">{{ \Illuminate\Support\Carbon::parse($notificacion->created_at)->diffForHumans() }}</span>
                            </span>
                            <span class="mt-1 block text-[11px] leading-4 text-atlantia-ink/70">{{ $message }}</span>
                        </span>
                        @if ($isUnread)
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-atlantia-wine"></span>
                        @endif
                    </a>
                @else
                    <button type="button" wire:click="markAsRead('{{ $notificacion->id }}')" class="flex w-full gap-3 border-b border-atlantia-rose/10 px-4 py-3 text-left transition hover:bg-atlantia-blush/70">
                        <span class="{{ $toneClass }} grid h-9 w-9 shrink-0 place-items-center rounded-full">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-3">
                                <span class="text-xs font-black text-atlantia-ink">{{ $title }}</span>
                                <span class="shrink-0 text-[10px] font-semibold text-atlantia-ink/45">{{ \Illuminate\Support\Carbon::parse($notificacion->created_at)->diffForHumans() }}</span>
                            </span>
                            <span class="mt-1 block text-[11px] leading-4 text-atlantia-ink/70">{{ $message }}</span>
                        </span>
                        @if ($isUnread)
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-atlantia-wine"></span>
                        @endif
                    </button>
                @endif
            @empty
                <div class="px-4 py-8 text-center">
                    <div class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 17H9C7.9 17 7 16.1 7 15V10.8C7 7.9 9 5.4 11.8 4.8C15 4.1 18 6.5 18 9.6V15C18 16.1 17.1 17 16 17H15Z" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-atlantia-ink">Sin notificaciones</p>
                    <p class="mt-1 text-xs text-atlantia-ink/55">Cuando ocurra algo importante aparecera aqui.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-atlantia-rose/10 p-3">
            <a href="{{ route('notificaciones.index') }}" class="inline-flex min-h-9 w-full items-center justify-center rounded-md border border-atlantia-rose/25 bg-white px-4 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</details>
