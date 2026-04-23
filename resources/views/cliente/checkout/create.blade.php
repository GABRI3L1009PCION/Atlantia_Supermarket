@extends('layouts.marketplace')

@section('content')
    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header
            title="Finalizar compra"
            subtitle="Confirma tu direccion, pago y datos de facturacion electronica."
        />

        <ol
            class="mb-8 grid gap-3 rounded-lg border border-atlantia-rose/20 bg-white p-4 shadow-sm
                sm:grid-cols-3"
            aria-label="Progreso de compra"
        >
            <li class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white"
                >
                    OK
                </span>
                <span class="text-sm font-bold text-atlantia-ink">Carrito</span>
                <span class="hidden h-0.5 flex-1 bg-emerald-600 sm:block"></span>
            </li>
            <li class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-atlantia-wine text-sm font-bold text-white"
                >
                    2
                </span>
                <span class="text-sm font-bold text-atlantia-ink">Checkout</span>
                <span class="hidden h-0.5 flex-1 bg-slate-200 sm:block"></span>
            </li>
            <li class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-600"
                >
                    3
                </span>
                <span class="text-sm font-semibold text-slate-500">Confirmacion</span>
            </li>
        </ol>

        <form method="POST" action="{{ route('cliente.checkout.store') }}" class="grid gap-6 lg:grid-cols-[1fr_380px]" data-protect-submit>
            @csrf

            <div class="space-y-6">
                <livewire:checkout.selector-direccion />

                <section
                    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
                    aria-labelledby="entrega-title"
                >
                    <h2 id="entrega-title" class="flex items-center gap-3 text-2xl font-bold text-atlantia-ink">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-md bg-atlantia-wine text-base text-white"
                        >
                            2
                        </span>
                        Tipo de entrega
                    </h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">
                        Elige la forma mas comoda para recibir tu pedido.
                    </p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <label class="checkout-choice text-center">
                            <input type="radio" name="tipo_entrega" value="domicilio" checked class="sr-only">
                            <span class="block text-sm font-bold tracking-normal text-sky-700" aria-hidden="true">DOM</span>
                            <span class="mt-2 block font-bold text-atlantia-ink">Domicilio</span>
                            <span class="mt-1 block text-sm text-atlantia-ink/65">45-60 min</span>
                            <span class="mt-2 block font-bold text-atlantia-wine">Segun zona</span>
                        </label>

                        <label class="checkout-choice text-center">
                            <input type="radio" name="tipo_entrega" value="recoger" class="sr-only">
                            <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">TDA</span>
                            <span class="mt-2 block font-bold text-atlantia-ink">Recoger en tienda</span>
                            <span class="mt-1 block text-sm text-atlantia-ink/65">Hoy, desde 3:00 pm</span>
                            <span class="mt-2 block font-bold text-atlantia-wine">Gratis</span>
                        </label>

                        <label class="checkout-choice text-center">
                            <input type="radio" name="tipo_entrega" value="programado" class="sr-only">
                            <span class="block text-sm font-bold tracking-normal text-atlantia-wine" aria-hidden="true">PRO</span>
                            <span class="mt-2 block font-bold text-atlantia-ink">Programado</span>
                            <span class="mt-1 block text-sm text-atlantia-ink/65">Elige horario</span>
                            <span class="mt-2 block font-bold text-atlantia-wine">Q 15.00</span>
                        </label>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-sm font-bold text-atlantia-ink">Ventana de entrega</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-4">
                            @foreach (['2-4 pm', '4-6 pm', '6-8 pm', '8-10 am'] as $ventana)
                                <label class="checkout-window">
                                    <input
                                        type="radio"
                                        name="ventana_entrega"
                                        value="{{ $ventana }}"
                                        @checked($loop->index === 1)
                                        class="sr-only"
                                    >
                                    <span class="block text-xs uppercase text-atlantia-ink/50">
                                        {{ $loop->last ? 'Manana' : 'Hoy' }}
                                    </span>
                                    {{ $ventana }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="notas" class="text-sm font-bold text-atlantia-ink">
                            Instrucciones para el repartidor
                            <span class="font-normal text-atlantia-ink/55">(opcional)</span>
                        </label>
                        <textarea
                            id="notas"
                            name="notas"
                            rows="3"
                            placeholder="Ej. Tocar timbre, dejar con portero, llamar al llegar..."
                            class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                focus:border-atlantia-wine focus:ring-atlantia-rose"
                        >{{ old('notas') }}</textarea>
                    </div>
                </section>

                <section
                    class="rounded-lg border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7"
                    aria-labelledby="facturacion-title"
                >
                    <h2 id="facturacion-title" class="flex items-center gap-3 text-2xl font-bold text-atlantia-ink">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-md bg-atlantia-wine text-base text-white"
                        >
                            3
                        </span>
                        Facturacion electronica FEL
                    </h2>
                    <p class="mt-2 text-sm text-atlantia-ink/70">
                        Cada vendedor emitira su propio DTE FEL al confirmar el pedido.
                    </p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <label class="checkout-choice">
                            <input type="radio" name="facturacion_tipo" value="datos" checked class="sr-only">
                            <span class="font-bold text-atlantia-ink">Factura con mis datos</span>
                            <span class="mt-1 block text-sm text-atlantia-ink/65">Llega por correo al confirmar.</span>
                        </label>
                        <label class="checkout-choice">
                            <input type="radio" name="facturacion_tipo" value="cf" class="sr-only">
                            <span class="font-bold text-atlantia-ink">Consumidor Final (CF)</span>
                            <span class="mt-1 block text-sm text-atlantia-ink/65">Impresa al entregar el pedido.</span>
                        </label>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nit_facturacion" class="text-sm font-bold text-atlantia-ink">NIT</label>
                            <input
                                id="nit_facturacion"
                                name="nit_facturacion"
                                type="text"
                                value="{{ old('nit_facturacion') }}"
                                placeholder="Ej. 452198-6"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >
                        </div>
                        <div>
                            <label for="razon_social" class="text-sm font-bold text-atlantia-ink">
                                Nombre / Razon social
                            </label>
                            <input
                                id="razon_social"
                                name="razon_social"
                                type="text"
                                value="{{ old('razon_social', auth()->user()?->name) }}"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >
                        </div>
                        <div>
                            <label for="correo_facturacion" class="text-sm font-bold text-atlantia-ink">
                                Correo electronico
                            </label>
                            <input
                                id="correo_facturacion"
                                name="correo_facturacion"
                                type="email"
                                value="{{ old('correo_facturacion', auth()->user()?->email) }}"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >
                        </div>
                        <div>
                            <label for="direccion_fiscal" class="text-sm font-bold text-atlantia-ink">
                                Direccion fiscal
                            </label>
                            <input
                                id="direccion_fiscal"
                                name="direccion_fiscal"
                                type="text"
                                value="{{ old('direccion_fiscal') }}"
                                placeholder="Municipio, zona o barrio"
                                class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3 text-sm
                                    focus:border-atlantia-wine focus:ring-atlantia-rose"
                            >
                        </div>
                    </div>
                </section>

                <livewire:checkout.formulario-pago />
            </div>

            <aside class="lg:sticky lg:top-6 lg:h-fit">
                <livewire:checkout.resumen-multivendedor />
            </aside>
        </form>
    </section>
@endsection
