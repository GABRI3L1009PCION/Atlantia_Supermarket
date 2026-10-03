@php
    $hasFlashFeedback = session('success') || session('error') || $errors->any();
@endphp

@if (session('error'))
    <x-ui.alert type="error" :message="session('error')" />
@endif

@if ($errors->any())
    <x-ui.alert type="error" message="Revisa los campos marcados antes de continuar." />
@endif

@if ($hasFlashFeedback)
    @php
        $modalType = session('success') ? 'success' : 'error';
        $successMessage = session('success');
        $isLoginSuccess = is_string($successMessage) && str_contains(strtolower($successMessage), 'sesion iniciada');
        $modalTitle = session('success')
            ? ($isLoginSuccess ? 'Inicio de sesion correcto' : 'Operacion completada')
            : 'No se pudo completar la accion';
        $modalMessage = session('success')
            ? $successMessage
            : (session('error') ?? 'Corrige los detalles marcados y vuelve a intentar.');
        $autoCloseSeconds = $modalType === 'success' ? 3 : null;
    @endphp

    <div
        class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/78 px-4 py-6 backdrop-blur-[2px]"
        data-flash-modal
        @if ($autoCloseSeconds) data-flash-auto-close="{{ $autoCloseSeconds }}" @endif
        role="dialog"
        aria-modal="true"
        aria-labelledby="flash-modal-title"
    >
        <section class="w-full max-w-[430px] overflow-hidden rounded-[18px] border border-white/70 bg-white px-8 py-7 text-center text-atlantia-ink shadow-[0_28px_85px_rgba(15,23,42,0.28)]">
            <div class="mx-auto grid h-[172px] w-[172px] place-items-center" aria-hidden="true">
                @if ($modalType === 'success')
                    <div class="flash-success-orbit relative grid h-[148px] w-[148px] place-items-center rounded-full bg-emerald-50">
                        <span class="absolute h-[118px] w-[118px] rounded-full bg-emerald-100/80"></span>
                        <span class="absolute h-[84px] w-[84px] rounded-full bg-emerald-200/70 blur-sm"></span>
                        <span class="relative grid h-[78px] w-[78px] place-items-center rounded-full bg-white text-emerald-500 shadow-[0_16px_36px_rgba(16,185,129,0.22)]">
                            <svg class="flash-success-check h-12 w-12" viewBox="0 0 56 56" fill="none">
                                <path d="M15 29.5L24 38L42 18" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="flash-success-dot absolute right-5 top-6 h-2 w-2 rounded-full bg-emerald-300"></span>
                        <span class="flash-success-dot absolute bottom-8 left-6 h-1.5 w-1.5 rounded-full bg-emerald-200"></span>
                    </div>
                @else
                    <div class="relative grid h-[128px] w-[128px] place-items-center rounded-full bg-rose-50">
                        <span class="absolute h-[96px] w-[96px] rounded-full bg-rose-100/80"></span>
                        <span class="relative grid h-[70px] w-[70px] place-items-center rounded-full bg-white text-rose-600 shadow-[0_16px_36px_rgba(225,29,72,0.18)]">
                            <span class="text-4xl font-black leading-none">!</span>
                        </span>
                    </div>
                @endif
            </div>

            <h2 id="flash-modal-title" class="text-[1.45rem] font-black leading-tight text-atlantia-ink sm:text-[1.55rem]">
                {{ $modalTitle }}
            </h2>
            <p class="mt-3 text-base leading-6 text-atlantia-ink/74">{{ $modalMessage }}</p>

            @if ($errors->any())
                <ul class="mt-5 space-y-2 text-left text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li class="rounded-md bg-rose-50 px-3 py-2">{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($autoCloseSeconds)
                <div class="mt-8 h-1.5 overflow-hidden rounded-full bg-emerald-100">
                    <span
                        class="block h-full rounded-full bg-emerald-500"
                        data-flash-progress
                        style="animation-duration: {{ $autoCloseSeconds }}s;"
                    ></span>
                </div>
                <p class="mt-3 text-sm leading-5 text-atlantia-ink/72">
                    Se cerrara automaticamente en <strong class="font-black text-emerald-600" data-flash-countdown>{{ $autoCloseSeconds }}</strong> segundos
                </p>
            @else
                <div class="mt-7 flex justify-center">
                    <button
                        type="button"
                        class="rounded-md bg-atlantia-wine px-6 py-2.5 text-sm font-bold text-white shadow-[0_12px_24px_rgba(122,31,61,0.22)] transition hover:bg-atlantia-wine-700"
                        data-flash-close
                    >
                        Entendido
                    </button>
                </div>
            @endif
        </section>
    </div>

    <style>
        @keyframes flash-success-pulse {
            0%,
            100% {
                transform: scale(0.96);
            }

            50% {
                transform: scale(1.04);
            }
        }

        @keyframes flash-success-drift {
            0%,
            100% {
                opacity: 0.45;
                transform: translate3d(0, 0, 0);
            }

            50% {
                opacity: 1;
                transform: translate3d(8px, -6px, 0);
            }
        }

        @keyframes flash-success-draw {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes flash-progress {
            from {
                width: 0%;
            }

            to {
                width: 100%;
            }
        }

        .flash-success-orbit {
            animation: flash-success-pulse 1.8s ease-in-out infinite;
        }

        .flash-success-dot {
            animation: flash-success-drift 1.7s ease-in-out infinite;
        }

        .flash-success-check path {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: flash-success-draw 0.58s ease-out 0.18s forwards;
        }

        [data-flash-progress] {
            animation-name: flash-progress;
            animation-timing-function: linear;
            animation-fill-mode: forwards;
            width: 0%;
        }

        @media (prefers-reduced-motion: reduce) {
            .flash-success-orbit,
            .flash-success-dot,
            .flash-success-check path,
            [data-flash-progress] {
                animation: none;
            }

            .flash-success-check path {
                stroke-dashoffset: 0;
            }

            [data-flash-progress] {
                width: 100%;
            }
        }
    </style>
@endif
