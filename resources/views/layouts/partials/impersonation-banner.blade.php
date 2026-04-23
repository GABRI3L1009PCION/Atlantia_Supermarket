@if (session('impersonation.active'))
    <section class="mb-4 rounded-xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-amber-900 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Modo impersonacion</p>
                <p class="mt-1 text-sm font-semibold">
                    Estas operando como <span class="font-black">{{ auth()->user()?->name }}</span>.
                    La sesion original pertenece a <span class="font-black">{{ session('impersonation.admin_name') }}</span>.
                </p>
            </div>

            <a
                href="{{ route('admin.impersonation.stop') }}"
                class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-black text-white transition hover:bg-amber-700"
            >
                Salir de impersonacion
            </a>
        </div>
    </section>
@endif
