@extends('layouts.app')

@section('content')
    @php
        $campo = 'mt-1 h-11 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $promedio = (float) $perfil->rating;
    @endphp

    <section class="mx-auto max-w-[760px] space-y-4 px-4 pb-24 pt-4 sm:px-0">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Mi perfil</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Tus datos de contacto, tu vehículo y lo que opinan tus clientes.</p>
        </div>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-atlantia-wine text-lg font-black text-white">{{ \App\Support\MenuCuenta::iniciales($user->name) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-base font-black text-atlantia-ink">{{ $user->name }}</p>
                    <p class="truncate text-xs text-atlantia-ink/55">{{ $user->email }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('repartidor.perfil.update') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="text-xs font-bold text-atlantia-ink/70">Teléfono</span>
                    <input type="tel" name="phone" required maxlength="20" autocomplete="tel" value="{{ old('phone', $user->phone) }}" class="{{ $campo }}">
                    @error('phone') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-xs font-bold text-atlantia-ink/70">Vehículo</span>
                    <select name="vehicle_type" required class="{{ $campo }}">
                        @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo }}" @selected(old('vehicle_type', $perfil->vehicle_type ?: 'Moto') === $vehiculo)>{{ $vehiculo }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_type') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex flex-wrap items-center justify-between gap-2 sm:col-span-2">
                    <p class="text-[11px] text-atlantia-ink/50">Para cambiar tu nombre o correo, escribe a soporte.</p>
                    <button type="submit" class="h-11 rounded-lg bg-atlantia-wine px-6 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar</button>
                </div>
            </form>
        </article>

        <article class="rounded-xl border border-atlantia-rose/15 bg-white p-5" data-mis-calificaciones>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-black text-atlantia-ink">Tu calificación</h2>
                    <p class="text-xs text-atlantia-ink/55">Promedio de tus últimas 100 entregas calificadas. Con mejor calificación te llegan más pedidos.</p>
                </div>
                <p class="text-3xl font-black text-atlantia-ink">{{ number_format($promedio, 1) }} <span class="text-amber-400">★</span></p>
            </div>

            @if ($totalCalificaciones > 0)
                <ul class="mt-3 space-y-1">
                    @for ($e = 5; $e >= 1; $e--)
                        @php $n = (int) ($distribucion[$e] ?? 0); @endphp
                        <li class="flex items-center gap-2 text-xs text-atlantia-ink/70">
                            <span class="w-8 shrink-0">{{ $e }} ★</span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-atlantia-blush"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $totalCalificaciones ? round($n / $totalCalificaciones * 100) : 0 }}%"></span></span>
                            <span class="w-8 shrink-0 text-right">{{ $n }}</span>
                        </li>
                    @endfor
                </ul>

                <h3 class="mt-4 text-sm font-black text-atlantia-ink">Lo que dicen tus clientes</h3>
                <ul class="mt-2 divide-y divide-atlantia-rose/10">
                    @forelse ($comentarios as $comentario)
                        <li class="py-2 text-sm">
                            <p class="text-xs"><span class="text-amber-400">{{ str_repeat('★', $comentario->estrellas) }}</span><span class="text-atlantia-ink/20">{{ str_repeat('★', 5 - $comentario->estrellas) }}</span> <span class="text-atlantia-ink/45">· {{ $comentario->created_at?->format('d/m/Y') }}</span></p>
                            <p class="mt-0.5 text-atlantia-ink/80">“{{ $comentario->comentario }}”</p>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-atlantia-ink/55">Aún no hay comentarios escritos.</li>
                    @endforelse
                </ul>
            @else
                <p class="mt-3 text-sm text-atlantia-ink/55">Todavía ningún cliente te ha calificado. Las calificaciones aparecen aquí cuando entregas pedidos.</p>
            @endif
        </article>

        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('repartidor.ganancias.index') }}" class="rounded-xl border border-atlantia-rose/15 bg-white p-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Cuenta bancaria y retiros →</a>
            <a href="{{ route('cuenta.seguridad') }}" class="rounded-xl border border-atlantia-rose/15 bg-white p-4 text-sm font-bold text-atlantia-wine hover:bg-atlantia-blush">Contraseña y seguridad →</a>
        </div>
    </section>
@endsection
