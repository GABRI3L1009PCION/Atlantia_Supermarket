@if (session('impersonation.tienda_atlantia'))
    <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-atlantia-wine/20 bg-atlantia-blush px-4 py-2.5 text-atlantia-ink" data-aviso-tienda-atlantia>
        <p class="text-sm"><strong>Estás manejando Atlantia Store.</strong> <span class="text-atlantia-ink/65">Entraste desde la cuenta de {{ session('impersonation.admin_name') }}.</span></p>
        <form method="POST" action="{{ route('admin.impersonation.stop') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-atlantia-wine px-4 text-xs font-bold text-white hover:bg-atlantia-wine-700">Volver al administrador</button>
        </form>
    </section>
@elseif (session('impersonation.active'))
    <section class="mb-4 rounded-xl border border-amber-300/70 bg-amber-50 px-4 py-3 text-amber-900 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Modo impersonacion</p>
                <p class="mt-1 text-sm font-semibold">
                    Estas operando como <span class="font-black">{{ auth()->user()?->name }}</span>.
                    La sesion original pertenece a <span class="font-black">{{ session('impersonation.admin_name') }}</span>.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.impersonation.stop') }}">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-black text-white transition hover:bg-amber-700"
                >
                    Salir de impersonacion
                </button>
            </form>
        </div>
    </section>
@endif
