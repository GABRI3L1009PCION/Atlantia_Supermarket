@extends('layouts.app')

@section('content')
    <section class="-mx-4 -my-6 min-h-[calc(100vh-4rem)] bg-white px-4 py-6 text-atlantia-ink sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="flex flex-col gap-4 rounded-md border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-1 text-3xl font-black text-atlantia-ink">Notificaciones</h1>
                    <p class="mt-1 text-sm text-atlantia-ink/60">
                        Revisa novedades de pedidos, inventario, pagos y actividad operativa.
                    </p>
                </div>

                <form method="POST" action="{{ route('notificaciones.read-all') }}">
                    @csrf
                    <button type="submit" @disabled($noLeidas === 0) class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-atlantia-rose/30 px-4 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush disabled:cursor-not-allowed disabled:opacity-45">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Marcar todas como leidas
                    </button>
                </form>
            </div>

            <div class="mt-5 overflow-hidden rounded-md border border-atlantia-rose/20 bg-white shadow-sm">
                @forelse ($notificaciones as $notificacion)
                    @php
                        $data = is_array($notificacion->data ?? null) ? $notificacion->data : [];
                        $title = $data['title'] ?? 'Notificacion';
                        $message = $data['message'] ?? 'Tienes una nueva actualizacion.';
                        $url = $data['url'] ?? null;
                        $isUnread = $notificacion->read_at === null;
                        $type = (string) ($notificacion->type ?? '');
                        $toneClass = match (true) {
                            str_contains($type, 'pedido') => 'bg-rose-50 text-atlantia-wine',
                            str_contains($type, 'stock'), str_contains($type, 'inventario') => 'bg-sky-50 text-sky-700',
                            str_contains($type, 'pago'), str_contains($type, 'confirmado') => 'bg-emerald-50 text-emerald-700',
                            str_contains($type, 'ml') => 'bg-violet-50 text-violet-700',
                            default => 'bg-atlantia-blush text-atlantia-wine',
                        };
                    @endphp

                    <article class="flex flex-col gap-4 border-b border-atlantia-rose/10 p-4 last:border-b-0 sm:flex-row sm:items-center">
                        <div class="{{ $toneClass }} grid h-11 w-11 shrink-0 place-items-center rounded-full">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3ZM12 12L20 7.5M12 12V21M12 12L4 7.5" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-black text-atlantia-ink">{{ $title }}</h2>
                                @if ($isUnread)
                                    <span class="rounded-full bg-atlantia-wine px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-white">Nueva</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-6 text-atlantia-ink/65">{{ $message }}</p>
                            <p class="mt-1 text-xs font-semibold text-atlantia-ink/45">
                                {{ \Illuminate\Support\Carbon::parse($notificacion->created_at)->diffForHumans() }}
                            </p>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            @if ($url)
                                <a href="{{ route('notificaciones.open', $notificacion->id) }}" class="inline-flex min-h-9 items-center justify-center rounded-md bg-atlantia-wine px-4 text-xs font-black text-white transition hover:bg-atlantia-wine-700">
                                    Abrir
                                </a>
                            @endif

                            @if ($isUnread)
                                <form method="POST" action="{{ route('notificaciones.read', $notificacion->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-md border border-atlantia-rose/30 px-4 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                        Marcar leida
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-16 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 17H9C7.9 17 7 16.1 7 15V10.8C7 7.9 9 5.4 11.8 4.8C15 4.1 18 6.5 18 9.6V15C18 16.1 17.1 17 16 17H15Z" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M10 19C10.5 20 11.1 20.5 12 20.5C12.9 20.5 13.5 20 14 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-lg font-black text-atlantia-ink">No hay notificaciones registradas</h2>
                        <p class="mt-1 text-sm text-atlantia-ink/60">Cuando ocurra algo importante en tu cuenta aparecera aqui.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
