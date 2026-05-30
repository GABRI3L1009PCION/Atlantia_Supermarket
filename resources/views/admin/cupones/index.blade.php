@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.app')

@section('content')
    @php
        $showCreateModal = $errors->any();
        $couponTypes = ['porcentaje' => 'Porcentaje', 'monto_fijo' => 'Monto fijo'];
        $metrics = $cuponMetrics ?? [
            'activos' => $cupones->where('activo', true)->count(),
            'usos' => (int) $cupones->sum('usos_actuales'),
            'primera_compra' => $cupones->where('solo_primera_compra', true)->count(),
            'vigentes' => $cupones->where('activo', true)->count(),
        ];
    @endphp

    <section class="-mx-4 -my-6 bg-atlantia-cream/40 px-4 py-6 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-2xl border border-atlantia-rose/20 bg-white px-5 py-7 shadow-sm sm:px-8 lg:px-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.2em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h1 class="mt-3 text-4xl font-black leading-tight text-atlantia-ink sm:text-5xl">Cupones y descuentos</h1>
                    <p class="mt-3 text-base text-atlantia-ink/65 sm:text-lg">
                        Crea codigos promocionales, controla su uso y activa campanas comerciales.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-3 rounded-lg bg-atlantia-wine px-6 py-4 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine-700"
                    data-open-create-modal
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                    Crear nuevo cupon
                </button>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-5">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20 12.5V8.75A2.75 2.75 0 0 0 17.25 6H6.75A2.75 2.75 0 0 0 4 8.75v6.5A2.75 2.75 0 0 0 6.75 18h10.5A2.75 2.75 0 0 0 20 15.25V12.5Z" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M8 9.25h.01M15 8.5l-6 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base text-atlantia-ink/70">Activos</p>
                            <p class="mt-1 text-4xl font-black text-atlantia-wine">{{ number_format((int) ($metrics['activos'] ?? 0)) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-5">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 18V10M12 18V6M18 18v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M4 19.5h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base text-atlantia-ink/70">Usos acumulados</p>
                            <p class="mt-1 text-4xl font-black text-atlantia-wine">{{ number_format((int) ($metrics['usos'] ?? 0)) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-5">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M5.5 10h13v9h-13z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M12 14v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base text-atlantia-ink/70">Primera compra</p>
                            <p class="mt-1 text-4xl font-black text-atlantia-wine">{{ number_format((int) ($metrics['primera_compra'] ?? 0)) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-atlantia-rose/20 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-5">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 3.75v2.5M17 3.75v2.5M4.75 9.5h14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M6.75 5.5h10.5A2.75 2.75 0 0 1 20 8.25v8.5a2.75 2.75 0 0 1-2.75 2.75H6.75A2.75 2.75 0 0 1 4 16.75v-8.5A2.75 2.75 0 0 1 6.75 5.5Z" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-base text-atlantia-ink/70">Vigentes hoy</p>
                            <p class="mt-1 text-4xl font-black text-atlantia-wine">{{ number_format((int) ($metrics['vigentes'] ?? 0)) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-7 rounded-2xl border border-atlantia-rose/20 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-atlantia-blush text-atlantia-wine">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 8.5V6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75V8.5a2.5 2.5 0 0 0 0 5v1.75A1.75 1.75 0 0 1 18.25 17H5.75A1.75 1.75 0 0 1 4 15.25V13.5a2.5 2.5 0 0 0 0-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M12 6v2M12 11v2M12 16v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-2xl font-black text-atlantia-wine">Cupones registrados</h2>
                        <p class="mt-2 text-base text-atlantia-ink/65">Administra codigos, vigencia y reglas sin saturar la pantalla.</p>
                    </div>
                </div>

                <div class="mt-7 grid gap-4 lg:grid-cols-2">
                    @forelse ($cupones as $cupon)
                        <article class="rounded-xl border border-atlantia-rose/20 bg-white p-4 shadow-sm transition hover:border-atlantia-rose/40 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-xl font-black text-atlantia-ink">{{ $cupon->codigo }}</h3>
                                        <span class="rounded-md px-3 py-1 text-xs font-black {{ $cupon->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $cupon->activo ? 'Activo' : 'Pausado' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 line-clamp-2 text-sm text-atlantia-ink/65">{{ $cupon->descripcion ?: 'Sin descripcion operativa.' }}</p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-xs font-black text-atlantia-wine transition hover:bg-atlantia-blush"
                                        data-edit-coupon
                                        data-action="{{ route('admin.cupones.update', $cupon) }}"
                                        data-code="{{ e($cupon->codigo) }}"
                                        data-type="{{ $cupon->tipo }}"
                                        data-value="{{ $cupon->valor }}"
                                        data-minimum="{{ $cupon->minimo_compra }}"
                                        data-maximum="{{ $cupon->maximo_descuento }}"
                                        data-uses="{{ $cupon->usos_maximos }}"
                                        data-start="{{ optional($cupon->fecha_inicio)->format('Y-m-d\TH:i') }}"
                                        data-end="{{ optional($cupon->fecha_fin)->format('Y-m-d\TH:i') }}"
                                        data-description="{{ e($cupon->descripcion) }}"
                                        data-active="{{ $cupon->activo ? '1' : '0' }}"
                                        data-first-purchase="{{ $cupon->solo_primera_compra ? '1' : '0' }}"
                                    >
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('admin.cupones.destroy', $cupon) }}" onsubmit="return confirm('Eliminar el cupon {{ addslashes($cupon->codigo) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Tipo</p>
                                    <p class="mt-1 font-black text-atlantia-ink">{{ str_replace('_', ' ', $cupon->tipo) }}</p>
                                </div>
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Valor</p>
                                    <p class="mt-1 font-black text-atlantia-wine">{{ number_format((float) $cupon->valor, 2) }}</p>
                                </div>
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Usos</p>
                                    <p class="mt-1 font-black text-atlantia-ink">{{ $cupon->usos_actuales }}{{ $cupon->usos_maximos ? ' / ' . $cupon->usos_maximos : '' }}</p>
                                </div>
                                <div class="rounded-lg border border-atlantia-rose/15 bg-white px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-wide text-atlantia-ink/45">Regla</p>
                                    <p class="mt-1 font-black text-atlantia-ink">{{ $cupon->solo_primera_compra ? 'Primera compra' : 'General' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-atlantia-ink/55">
                                <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Minimo Q{{ number_format((float) $cupon->minimo_compra, 2) }}</span>
                                <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Maximo {{ $cupon->maximo_descuento ? 'Q' . number_format((float) $cupon->maximo_descuento, 2) : 'Sin limite' }}</span>
                                <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Inicio {{ optional($cupon->fecha_inicio)->format('d/m/Y') ?? 'Libre' }}</span>
                                <span class="rounded-md border border-atlantia-rose/20 bg-white px-2.5 py-1">Fin {{ optional($cupon->fecha_fin)->format('d/m/Y') ?? 'Libre' }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-atlantia-rose/35 bg-white px-4 py-14 text-center lg:col-span-2">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 8.5V6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75V8.5a2.5 2.5 0 0 0 0 5v1.75A1.75 1.75 0 0 1 18.25 17H5.75A1.75 1.75 0 0 1 4 15.25V13.5a2.5 2.5 0 0 0 0-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M8.5 14.5l7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <p class="mt-5 text-2xl font-black text-atlantia-ink">Aun no hay cupones.</p>
                            <p class="mt-3 text-base text-atlantia-ink/60">Crea tu primer codigo promocional para activar descuentos en checkout.</p>
                            <button type="button" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-6 py-3 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine-700" data-open-create-modal>
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                </svg>
                                Crear cupon
                            </button>
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $cupones->links() }}
                </div>
            </div>
        </div>
    </section>

    <div
        class="{{ $showCreateModal ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-create-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="coupon-create-title"
    >
        <div class="w-full max-w-5xl rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-5 py-3 sm:px-7">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="coupon-create-title" class="mt-1 text-2xl font-black leading-tight text-atlantia-ink">Crear nuevo cupon</h2>
                    <p class="text-sm text-atlantia-ink/60">Configura codigo, descuento, vigencia y limites de uso.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-atlantia-rose/35 px-4 py-2 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-modal>
                    Cerrar
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.cupones.store') }}" class="px-5 py-3 sm:px-7">
                @csrf

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        Revisa los campos marcados antes de guardar el cupon.
                    </div>
                @endif

                <div class="grid gap-x-4 gap-y-2 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-atlantia-ink">Codigo</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M20 12.5V8.75A2.75 2.75 0 0 0 17.25 6H6.75A2.75 2.75 0 0 0 4 8.75v6.5A2.75 2.75 0 0 0 6.75 18h10.5A2.75 2.75 0 0 0 20 15.25V12.5Z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M8 9.25h.01M15 8.5l-6 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="text" name="codigo" value="{{ old('codigo') }}" placeholder="Ingresa el codigo del cupon" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush" required>
                        </div>
                        @error('codigo') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-atlantia-ink">Tipo</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8.5 9h.01M15.5 15h.01M16 8 8 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <select name="tipo" class="h-10 w-full appearance-none rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-10 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                                @foreach ($couponTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('tipo', 'porcentaje') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-atlantia-ink/60" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Valor</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-black text-atlantia-ink/55">%</span>
                            <input type="number" step="0.01" min="0.01" name="valor" value="{{ old('valor') }}" placeholder="Ej. 10" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-9 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush" required>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Minimo compra</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 7h15l-2 7H8L6 4H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 20h.01M18 20h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="number" step="0.01" min="0" name="minimo_compra" value="{{ old('minimo_compra') }}" placeholder="Ej. 1000.00" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Maximo descuento</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M12 7.5v9M9.75 9.5c.45-.65 1.2-1 2.25-1 1.35 0 2.25.68 2.25 1.75 0 2.4-4.5 1.2-4.5 3.5 0 1.05.9 1.75 2.25 1.75 1.05 0 1.85-.35 2.35-1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="number" step="0.01" min="0" name="maximo_descuento" value="{{ old('maximo_descuento') }}" placeholder="Ej. 500.00" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Usos maximos</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8.5 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM15.5 10a2.5 2.5 0 1 0 0-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M3.5 19c.35-3.1 2.15-5 5-5s4.65 1.9 5 5M14.5 14.25c2.7.25 4.35 1.9 4.65 4.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input type="number" min="1" name="usos_maximos" value="{{ old('usos_maximos') }}" placeholder="Ej. 100" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Fecha inicio</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 3.75v2.5M17 3.75v2.5M4.75 9.5h14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M6.75 5.5h10.5A2.75 2.75 0 0 1 20 8.25v8.5a2.75 2.75 0 0 1-2.75 2.75H6.75A2.75 2.75 0 0 1 4 16.75v-8.5A2.75 2.75 0 0 1 6.75 5.5Z" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-atlantia-ink">Fecha fin</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 3.75v2.5M17 3.75v2.5M4.75 9.5h14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M6.75 5.5h10.5A2.75 2.75 0 0 1 20 8.25v8.5a2.75 2.75 0 0 1-2.75 2.75H6.75A2.75 2.75 0 0 1 4 16.75v-8.5A2.75 2.75 0 0 1 6.75 5.5Z" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-atlantia-ink">Descripcion</label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-3 flex items-center justify-center text-atlantia-ink/45">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 3.75h7l3 3v13.5H7V3.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M14 3.75V7h3M9 11h6M9 14h6M9 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <textarea name="descripcion" rows="1" placeholder="Describe el cupon (opcional)" class="h-10 w-full resize-none rounded-lg border border-atlantia-rose/30 bg-white py-2.5 pl-10 pr-3 text-sm font-semibold text-atlantia-ink outline-none transition placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-atlantia-rose/35 bg-atlantia-blush/25 px-3 py-2 text-sm font-semibold text-atlantia-ink transition hover:border-atlantia-wine/50">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', '1')) class="h-4 w-4 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-wine">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 21s7-3.5 7-10V6l-7-3-7 3v5c0 6.5 7 10 7 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-sm font-black text-atlantia-ink">Activo</span>
                            <span class="mt-0.5 block text-xs font-medium text-atlantia-ink/65">Disponible para clientes</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-atlantia-rose/25 bg-white px-3 py-2 text-sm font-semibold text-atlantia-ink transition hover:border-atlantia-wine/50">
                        <input type="hidden" name="solo_primera_compra" value="0">
                        <input type="checkbox" name="solo_primera_compra" value="1" @checked(old('solo_primera_compra')) class="h-4 w-4 rounded border-atlantia-rose text-atlantia-wine focus:ring-atlantia-wine">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-atlantia-blush text-atlantia-wine">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20c.55-4.15 3.3-6 7.5-6s6.95 1.85 7.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-sm font-black text-atlantia-ink">Solo primera compra</span>
                            <span class="mt-0.5 block text-xs font-medium text-atlantia-ink/65">Valido solo en la primera compra</span>
                        </span>
                    </label>
                </div>

                <div class="mt-3 flex items-center gap-3 rounded-lg border border-atlantia-rose/25 bg-atlantia-blush/20 px-4 py-2 text-xs font-semibold text-atlantia-ink/70">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-atlantia-wine text-atlantia-wine">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 10.5v5M12 7.5h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                    Asegurate de revisar las fechas de vigencia y los limites de uso antes de guardar el cupon.
                </div>

                <div class="mt-3 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-lg border border-atlantia-rose/30 px-6 py-2.5 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush" data-close-create-modal>
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-atlantia-wine px-7 py-2.5 text-sm font-black text-white shadow-lg shadow-atlantia-wine/15 transition hover:bg-atlantia-wine-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 4h12l2 2v14H5V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M8 4v6h8V4M8 20v-6h8v6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                        Guardar cupon
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
        data-edit-coupon-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="coupon-edit-title"
    >
        <div class="max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-2xl border border-atlantia-rose/25 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-atlantia-rose/15 px-6 py-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-atlantia-rose">Atlantia Supermarket</p>
                    <h2 id="coupon-edit-title" class="mt-1 text-2xl font-black text-atlantia-ink">Editar cupon</h2>
                    <p class="mt-1 text-sm text-atlantia-ink/60">Actualiza reglas, fechas y estado del codigo.</p>
                </div>
                <button type="button" class="rounded-md border border-atlantia-rose/30 px-3 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-coupon>
                    Cerrar
                </button>
            </div>

            <form method="POST" action="#" class="max-h-[calc(92vh-92px)] overflow-y-auto px-6 py-5" data-edit-coupon-form>
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Codigo</label>
                        <input type="text" name="codigo" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-code required>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Tipo</label>
                        <select name="tipo" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-type>
                            @foreach ($couponTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Valor</label>
                        <input type="number" step="0.01" min="0.01" name="valor" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-value required>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Minimo compra</label>
                        <input type="number" step="0.01" min="0" name="minimo_compra" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-minimum>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Maximo descuento</label>
                        <input type="number" step="0.01" min="0" name="maximo_descuento" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-maximum>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Usos maximos</label>
                        <input type="number" min="1" name="usos_maximos" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-uses>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Fecha inicio</label>
                        <input type="datetime-local" name="fecha_inicio" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-start>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-atlantia-ink">Fecha fin</label>
                        <input type="datetime-local" name="fecha_fin" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-end>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-atlantia-ink">Descripcion</label>
                        <textarea name="descripcion" rows="3" class="mt-2 w-full rounded-md border border-atlantia-rose/30 px-4 py-3" data-edit-description></textarea>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-3 text-sm font-semibold text-atlantia-ink">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" class="rounded border-atlantia-rose text-atlantia-wine" data-edit-active>
                        Activo
                    </label>
                    <label class="flex items-center gap-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream px-3 py-3 text-sm font-semibold text-atlantia-ink">
                        <input type="hidden" name="solo_primera_compra" value="0">
                        <input type="checkbox" name="solo_primera_compra" value="1" class="rounded border-atlantia-rose text-atlantia-wine" data-edit-first-purchase>
                        Solo primera compra
                    </label>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-atlantia-rose/15 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-md border border-atlantia-rose/30 px-4 py-2 text-sm font-black text-atlantia-wine hover:bg-atlantia-blush" data-close-edit-coupon>
                        Cancelar
                    </button>
                    <x-ui.button type="submit">Actualizar cupon</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    (() => {
        const modal = document.querySelector('[data-edit-coupon-modal]');
        const form = modal?.querySelector('[data-edit-coupon-form]');

        const openModal = () => {
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            document.body.style.overflow = 'hidden';
            modal?.querySelector('[data-edit-code]')?.focus();
        };

        const closeModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-edit-coupon]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action;
                modal.querySelector('[data-edit-code]').value = button.dataset.code || '';
                modal.querySelector('[data-edit-type]').value = button.dataset.type || 'porcentaje';
                modal.querySelector('[data-edit-value]').value = button.dataset.value || '';
                modal.querySelector('[data-edit-minimum]').value = button.dataset.minimum || '';
                modal.querySelector('[data-edit-maximum]').value = button.dataset.maximum || '';
                modal.querySelector('[data-edit-uses]').value = button.dataset.uses || '';
                modal.querySelector('[data-edit-start]').value = button.dataset.start || '';
                modal.querySelector('[data-edit-end]').value = button.dataset.end || '';
                modal.querySelector('[data-edit-description]').value = button.dataset.description || '';
                modal.querySelector('[data-edit-active]').checked = button.dataset.active === '1';
                modal.querySelector('[data-edit-first-purchase]').checked = button.dataset.firstPurchase === '1';
                openModal();
            });
        });

        document.querySelectorAll('[data-close-edit-coupon]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal?.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeModal();
        });
    })();
</script>
@endpush
