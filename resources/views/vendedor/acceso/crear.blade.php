@extends('layouts.marketplace', ['title' => 'Crea tu contraseña | Atlantia Store'])

@section('content')
<x-auth.panel titulo="Crea tu contraseña" :subtitulo="'Tienda '.($vendor?->business_name ?? '').' · '.$user->email" etiqueta="Paso 1 de 2">
    <form
        method="POST"
        action="{{ $accion }}"
        class="space-y-3"
        data-protect-submit
        data-crear-acceso
        x-data="{
            clave: '',
            confirmacion: '',
            ver: false,
            get reglas() {
                return [
                    ['8 caracteres o más', this.clave.length >= 8],
                    ['Mayúscula y minúscula', /\p{Ll}/u.test(this.clave) && /\p{Lu}/u.test(this.clave)],
                    ['Al menos un número', /[0-9]/.test(this.clave)],
                    ['Al menos un símbolo (! # $ %)', /[^\p{L}0-9]/u.test(this.clave)],
                ];
            },
            get cumplidas() { return this.reglas.filter(r => r[1]).length; },
            get coinciden() { return this.confirmacion !== '' && this.clave === this.confirmacion; },
        }"
    >
        @csrf

        <div>
            <label for="password" class="block text-sm font-black text-atlantia-ink">Nueva contraseña</label>
            <div class="relative mt-1.5">
                <input id="password" name="password" :type="ver ? 'text' : 'password'" x-model="clave" required autocomplete="new-password" autofocus
                    class="w-full rounded-xl border border-atlantia-rose/35 bg-white px-4 py-2.5 pr-20 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
                <button type="button" x-on:click="ver = !ver" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-black text-atlantia-wine" x-text="ver ? 'Ocultar' : 'Mostrar'">Mostrar</button>
            </div>
            @error('password') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror

            {{-- Indicador de seguridad en vivo --}}
            <div class="mt-2 grid grid-cols-4 gap-1" aria-hidden="true">
                <template x-for="i in 4" :key="i">
                    <span class="h-1.5 rounded-full transition" :class="cumplidas >= i ? (cumplidas === 4 ? 'bg-emerald-500' : (cumplidas >= 2 ? 'bg-amber-400' : 'bg-red-400')) : 'bg-atlantia-rose/15'"></span>
                </template>
            </div>
            <p class="mt-1 text-[11px] font-black" x-show="clave.length > 0"
                :class="cumplidas === 4 ? 'text-emerald-700' : (cumplidas >= 2 ? 'text-amber-700' : 'text-red-600')"
                x-text="cumplidas === 4 ? 'Contraseña segura' : (cumplidas >= 2 ? 'Casi: completa lo que falta' : 'Contraseña débil')"></p>
            <ul class="mt-1.5 grid grid-cols-1 gap-x-3 gap-y-0.5 text-xs sm:grid-cols-2">
                <template x-for="regla in reglas" :key="regla[0]">
                    <li class="flex items-center gap-1.5" :class="regla[1] ? 'font-bold text-emerald-700' : 'text-atlantia-ink/50'">
                        <span x-text="regla[1] ? '✓' : '○'"></span><span x-text="regla[0]"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-black text-atlantia-ink">Repite la contraseña</label>
            <input id="password_confirmation" name="password_confirmation" :type="ver ? 'text' : 'password'" x-model="confirmacion" required autocomplete="new-password"
                class="mt-1.5 w-full rounded-xl border border-atlantia-rose/35 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-rose/20">
            <p class="mt-1 text-xs font-bold" :class="coinciden ? 'text-emerald-700' : 'text-atlantia-ink/45'" x-show="confirmacion !== ''" x-text="coinciden ? '✓ Coinciden' : 'Todavía no coinciden'"></p>
        </div>

        <button type="submit" class="w-full rounded-xl bg-atlantia-wine px-4 py-2.5 text-sm font-black text-white transition hover:bg-atlantia-wine-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="cumplidas < 4 || !coinciden">
            Crear contraseña y continuar
        </button>

        <p class="rounded-xl bg-atlantia-blush/40 px-3 py-2 text-xs leading-snug text-atlantia-ink/70">
            <strong class="text-atlantia-wine">Siguiente paso:</strong> escanearás un QR con <strong>Google Authenticator</strong> para proteger tu tienda y tus pagos. Al enviar también revisamos que esa contraseña no haya sido robada en otros sitios.
        </p>
    </form>
</x-auth.panel>
@endsection
