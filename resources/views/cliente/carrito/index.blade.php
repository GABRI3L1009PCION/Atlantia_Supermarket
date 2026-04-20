@extends('layouts.marketplace')

@section('content')
    @php
        $items = $carrito->items;
        $subtotal = $items->sum(fn ($item) => (float) $item->precio_unitario_snapshot * (int) $item->cantidad);
    @endphp

    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Carrito" subtitle="Productos seleccionados para tu compra." />

        @if ($items->isEmpty())
            <div class="mt-8 rounded-lg border border-atlantia-rose/30 bg-white p-8 text-center shadow-sm">
                <h2 class="text-lg font-bold text-atlantia-wine">Tu carrito esta vacio</h2>
                <p class="mt-2 text-sm text-atlantia-ink/70">
                    Agrega productos del catalogo para preparar tu pedido.
                </p>
                <a
                    href="{{ route('catalogo.index') }}"
                    class="mt-5 inline-flex rounded-md bg-atlantia-wine px-5 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                >
                    Ver catalogo
                </a>
            </div>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="space-y-4">
                    @foreach ($items as $item)
                        <article class="grid gap-4 rounded-lg border border-atlantia-rose/25 bg-white p-4 shadow-sm sm:grid-cols-[96px_1fr_auto]">
                            <div class="h-24 w-24 overflow-hidden rounded-md bg-atlantia-blush">
                                @if ($item->producto?->imagenPrincipal?->path)
                                    <img
                                        src="{{ asset('storage/' . $item->producto->imagenPrincipal->path) }}"
                                        alt="{{ $item->producto->nombre }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center text-xs text-atlantia-ink/55">
                                        Sin imagen
                                    </div>
                                @endif
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase text-atlantia-wine">
                                    {{ $item->producto?->vendor?->business_name ?? 'Atlantia Supermarket' }}
                                </p>
                                <h2 class="mt-1 text-base font-bold text-atlantia-ink">
                                    {{ $item->producto?->nombre ?? 'Producto no disponible' }}
                                </h2>
                                <p class="mt-2 text-sm text-atlantia-ink/70">
                                    Precio unitario:
                                    <x-price :amount="$item->precio_unitario_snapshot" class="text-atlantia-ink" />
                                </p>

                                <form method="POST" action="{{ route('cliente.carrito.items.update', $item) }}" class="mt-3 flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label for="cantidad-{{ $item->id }}" class="text-sm font-semibold text-atlantia-ink">
                                        Cantidad
                                    </label>
                                    <input
                                        id="cantidad-{{ $item->id }}"
                                        name="cantidad"
                                        type="number"
                                        min="1"
                                        max="99"
                                        value="{{ $item->cantidad }}"
                                        class="h-10 w-20 rounded-md border border-atlantia-rose/40 px-3 text-sm focus:border-atlantia-wine focus:ring-atlantia-rose"
                                    >
                                    <x-ui.button type="submit" variant="secondary">Actualizar</x-ui.button>
                                </form>
                            </div>

                            <div class="flex flex-col items-start justify-between gap-3 sm:items-end">
                                <x-price
                                    :amount="(float) $item->precio_unitario_snapshot * (int) $item->cantidad"
                                    class="text-lg text-atlantia-wine"
                                />
                                <form method="POST" action="{{ route('cliente.carrito.items.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">
                                        Quitar
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="h-fit rounded-lg border border-atlantia-rose/25 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-atlantia-wine">Resumen del pedido</h2>
                    <div class="mt-4 space-y-3 text-sm text-atlantia-ink">
                        <div class="flex items-center justify-between">
                            <span>Subtotal</span>
                            <x-price :amount="$subtotal" />
                        </div>
                        <div class="flex items-center justify-between text-atlantia-ink/70">
                            <span>Envio</span>
                            <span>Se calcula al confirmar direccion</span>
                        </div>
                    </div>

                    @auth
                        <a
                            href="{{ route('cliente.checkout.create') }}"
                            class="mt-5 flex w-full items-center justify-center rounded-md bg-atlantia-wine px-4 py-3 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                        >
                            Realizar pedido
                        </a>
                    @else
                        <button
                            type="button"
                            data-open-register-modal
                            class="mt-5 flex w-full items-center justify-center rounded-md bg-atlantia-wine px-4 py-3 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                        >
                            Realizar pedido
                        </button>
                    @endauth
                </aside>
            </div>
        @endif
    </section>

    @guest
        <div
            id="registro-requerido-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-atlantia-ink/70 px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="registro-requerido-title"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-xl">
                <p class="text-sm font-bold uppercase text-atlantia-wine">Ya casi esta listo</p>
                <h2 id="registro-requerido-title" class="mt-2 text-2xl font-bold text-atlantia-ink">
                    Crea tu cuenta para realizar el pedido
                </h2>
                <p class="mt-3 text-sm text-atlantia-ink/75">
                    Puedes seguir agregando productos al carrito. Para confirmar compra, guardar direccion y dar
                    seguimiento a tu entrega necesitamos que te registres o ingreses a tu cuenta.
                </p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <a
                        href="{{ route('register') }}"
                        class="rounded-md bg-atlantia-wine px-4 py-3 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                    >
                        Registrarme
                    </a>
                    <a
                        href="{{ route('login') }}"
                        class="rounded-md border border-atlantia-rose/40 px-4 py-3 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush"
                    >
                        Iniciar sesion
                    </a>
                </div>

                <button
                    type="button"
                    data-close-register-modal
                    class="mt-4 text-sm font-semibold text-atlantia-ink/70 hover:text-atlantia-wine"
                >
                    Seguir comprando
                </button>
            </div>
        </div>

        @push('scripts')
            <script>
                document.querySelectorAll('[data-open-register-modal]').forEach((button) => {
                    button.addEventListener('click', () => {
                        document.getElementById('registro-requerido-modal')?.classList.remove('hidden');
                        document.getElementById('registro-requerido-modal')?.classList.add('flex');
                    });
                });

                document.querySelectorAll('[data-close-register-modal]').forEach((button) => {
                    button.addEventListener('click', () => {
                        document.getElementById('registro-requerido-modal')?.classList.add('hidden');
                        document.getElementById('registro-requerido-modal')?.classList.remove('flex');
                    });
                });
            </script>
        @endpush
    @endguest
@endsection
