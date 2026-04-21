@if (session('success'))
    <x-ui.alert type="success" :message="session('success')" />
@endif

@if (session('error'))
    <x-ui.alert type="error" :message="session('error')" />
@endif

@if ($errors->any())
    <x-ui.alert type="error" message="Revisa los campos marcados antes de continuar." />
@endif

@if (session('success') || session('error') || $errors->any())
    @php
        $modalType = session('success') ? 'success' : 'error';
        $modalTitle = session('success') ? 'Operacion completada' : 'No se pudo completar la accion';
        $modalMessage = session('success')
            ? session('success')
            : (session('error') ?? 'Corrige los detalles marcados y vuelve a intentar.');
    @endphp

    <div
        class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 px-4 py-6"
        data-flash-modal
        role="dialog"
        aria-modal="true"
        aria-labelledby="flash-modal-title"
    >
        <section class="w-full max-w-lg rounded-lg border border-white/10 bg-white p-6 text-atlantia-ink shadow-2xl">
            <div class="flex items-start gap-4">
                <div
                    class="{{ $modalType === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} grid h-11 w-11 shrink-0 place-items-center rounded-lg text-xl font-black"
                    aria-hidden="true"
                >
                    {{ $modalType === 'success' ? '✓' : '!' }}
                </div>

                <div class="min-w-0 flex-1">
                    <h2 id="flash-modal-title" class="text-xl font-black text-atlantia-ink">{{ $modalTitle }}</h2>
                    <p class="mt-2 text-sm leading-6 text-atlantia-ink/70">{{ $modalMessage }}</p>

                    @if ($errors->any())
                        <ul class="mt-4 space-y-2 text-sm text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li class="rounded-md bg-rose-50 px-3 py-2">{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    class="rounded-md bg-atlantia-wine px-5 py-2 text-sm font-bold text-white hover:bg-atlantia-wine-700"
                    data-flash-close
                >
                    Entendido
                </button>
            </div>
        </section>
    </div>
@endif
