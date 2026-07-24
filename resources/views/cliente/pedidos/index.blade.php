@extends('layouts.marketplace')

@section('content')
    @php
        $isGuest = auth()->guest();
        $pedidosCollection = $pedidos instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
            ? $pedidos->getCollection()
            : collect($pedidos);
        $summary = $summary ?? ['total' => 0, 'active' => 0, 'closed' => 0, 'cancelled' => 0];
        $tabs = $isGuest
            ? [
                ['label' => 'Activos', 'value' => 0, 'active' => false],
                ['label' => 'Sin pedidos', 'value' => 0, 'active' => false],
                ['label' => 'Historial', 'value' => 0, 'active' => true],
            ]
            : [
                ['label' => 'Activos', 'value' => (int) ($summary['active'] ?? 0), 'active' => false],
                ['label' => 'Finalizados', 'value' => (int) ($summary['closed'] ?? 0), 'active' => false],
                ['label' => 'Historial', 'value' => (int) ($summary['total'] ?? 0), 'active' => true],
            ];
        $statusLabels = [
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'en_revision' => 'En revision',
            'preparando' => 'Preparando',
            'listo_para_entrega' => 'Listo para entrega',
            'en_ruta' => 'En ruta',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            'rechazado' => 'Rechazado',
        ];
        $statusClasses = [
            'pendiente' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'confirmado' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'en_revision' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'preparando' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'listo_para_entrega' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
            'en_ruta' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
            'entregado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'cancelado' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'rechazado' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
        $headerLinePath = public_path('images/pedidos-header-linea.png');
        $headerLineUrl = file_exists($headerLinePath) ? asset('images/pedidos-header-linea.png') . '?v=' . filemtime($headerLinePath) : null;
        $restrictedIllustrationPath = public_path('images/pedidos-acceso-restringido.png');
        $restrictedIllustrationUrl = file_exists($restrictedIllustrationPath) ? asset('images/pedidos-acceso-restringido.png') . '?v=' . filemtime($restrictedIllustrationPath) : null;
    @endphp

    <style>
        .orders-guest-hero {
            padding: 24px 0 18px;
        }

        .orders-guest-title {
            font-size: clamp(1.8rem, 3vw, 2.55rem);
            line-height: 1.05;
        }

        .orders-header-line {
            position: absolute;
            top: 24px;
            right: 2.5rem;
            display: none;
            width: 340px !important;
            height: 146px !important;
            max-width: 32vw !important;
            object-fit: contain !important;
            opacity: 0.58;
            pointer-events: none;
        }

        .orders-tabs {
            margin-top: 18px;
        }

        .orders-guest-section {
            padding-top: 20px;
            padding-bottom: 24px;
        }

        .orders-access-card {
            width: min(100%, 920px);
            margin-inline: auto;
            overflow: hidden;
            border: 1px solid rgba(157, 27, 70, 0.11);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 20px 48px rgba(116, 15, 48, 0.09);
        }

        .orders-access-main {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            min-height: 292px;
        }

        .orders-access-art {
            position: relative;
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 18px 26px;
            border-right: 1px solid rgba(157, 27, 70, 0.09);
            background: radial-gradient(circle at 50% 42%, rgba(255, 216, 230, 0.78), rgba(255, 247, 250, 0.08) 62%), #fffafb;
        }

        .orders-access-image {
            position: relative;
            z-index: 1;
            display: block;
            width: 245px !important;
            height: 250px !important;
            max-width: 100% !important;
            max-height: 250px !important;
            object-fit: contain !important;
            object-position: center !important;
            filter: drop-shadow(0 14px 26px rgba(157, 27, 70, 0.13));
        }

        .orders-access-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            justify-content: center;
            padding: 28px 34px;
        }

        .orders-access-copy h2 {
            margin-top: 14px;
            max-width: 460px;
            font-size: clamp(1.55rem, 2.4vw, 2rem);
            line-height: 1.12;
        }

        .orders-access-copy p {
            margin-top: 12px;
            max-width: 480px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .orders-access-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .orders-access-actions > a {
            min-width: 152px;
        }

        .orders-benefits {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border-top: 1px solid rgba(157, 27, 70, 0.09);
            background: linear-gradient(180deg, #fff 0%, #fffafc 100%);
        }

        .orders-benefit {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 13px;
            padding: 16px 18px;
        }

        .orders-benefit + .orders-benefit {
            border-left: 1px solid rgba(157, 27, 70, 0.09);
        }

        .orders-benefit-icon {
            display: grid;
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            place-items: center;
            border-radius: 999px;
            background: #fce9ef;
            color: #86183d;
        }

        .orders-benefit h3 {
            font-size: 0.88rem;
            line-height: 1.25;
        }

        .orders-benefit p {
            margin-top: 3px;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        @media (max-width: 900px) {
            .orders-access-main {
                grid-template-columns: 300px minmax(0, 1fr);
            }

            .orders-access-copy {
                padding-inline: 24px;
            }
        }

        @media (max-width: 720px) {
            .orders-guest-hero {
                padding-top: 20px;
            }

            .orders-access-main {
                grid-template-columns: 1fr;
            }

            .orders-access-art {
                padding: 18px;
                border-right: 0;
                border-bottom: 1px solid rgba(157, 27, 70, 0.09);
            }

            .orders-access-image {
                width: 210px !important;
                height: 205px !important;
                max-height: 205px !important;
            }

            .orders-access-copy {
                padding: 24px;
            }

            .orders-access-actions {
                flex-direction: column;
            }

            .orders-benefits {
                grid-template-columns: 1fr;
            }

            .orders-benefit + .orders-benefit {
                border-top: 1px solid rgba(157, 27, 70, 0.09);
                border-left: 0;
            }
        }

        @media (min-width: 1100px) {
            .orders-header-line {
                display: block;
            }
        }

        @media (min-width: 1024px) and (max-height: 760px) {
            .orders-guest-hero {
                padding: 14px 0 10px;
            }

            .orders-guest-eyebrow {
                display: none;
            }

            .orders-guest-title {
                margin-top: 0;
                font-size: 1.9rem;
            }

            .orders-tabs {
                margin-top: 12px;
            }

            .orders-tabs > div {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .orders-guest-section {
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .orders-access-main {
                min-height: 220px;
            }

            .orders-access-art {
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .orders-access-image {
                width: 205px !important;
                height: 205px !important;
                max-height: 205px !important;
            }

            .orders-access-copy {
                padding-top: 18px;
                padding-bottom: 18px;
            }

            .orders-access-copy h2 {
                margin-top: 10px;
                font-size: 1.55rem;
            }

            .orders-access-copy p {
                margin-top: 8px;
                font-size: 0.82rem;
                line-height: 1.45;
            }

            .orders-access-actions {
                margin-top: 14px;
            }

            .orders-access-actions > a {
                min-height: 40px;
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .orders-benefit {
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .orders-benefit-icon {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
            }

            .orders-benefit p {
                display: none;
            }
        }
    </style>

    @guest
    <section class="orders-guest-hero relative overflow-hidden border-b border-atlantia-rose/10 bg-[radial-gradient(circle_at_top_left,_rgba(157,27,70,0.12),_transparent_38%),linear-gradient(180deg,_#ffffff_0%,_#fff9fb_100%)]">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-full bg-[linear-gradient(90deg,transparent_0%,rgba(157,27,70,0.03)_48%,transparent_100%)]"></div>
        @if ($headerLineUrl)
            <img
                src="{{ $headerLineUrl }}"
                alt=""
                aria-hidden="true"
                class="orders-header-line"
                width="340"
                height="146"
            >
        @endif

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="orders-guest-eyebrow text-sm font-black uppercase tracking-[0.18em] text-atlantia-wine/80">Atlantia Delivery</p>
                <h1 class="orders-guest-title mt-2 font-black tracking-tight text-atlantia-ink">
                    Historial de pedidos
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-atlantia-ink/65 sm:text-base">
                    Revisa tus compras anteriores, consulta estados y vuelve a pedir tus productos favoritos.
                </p>
            </div>

            <div class="orders-tabs grid gap-3 rounded-[1.75rem] border border-atlantia-rose/10 bg-white/85 p-2 shadow-[0_18px_45px_rgba(157,27,70,0.08)] sm:grid-cols-3">
                @foreach ($tabs as $tab)
                    <div class="{{ $tab['active'] ? 'bg-[linear-gradient(135deg,#ffffff_0%,#fff7fa_100%)] text-atlantia-wine shadow-sm ring-1 ring-atlantia-rose/15' : 'text-atlantia-ink/70' }} flex items-center justify-center gap-3 rounded-2xl px-4 py-3 text-sm font-black">
                        <span>{{ $tab['label'] }}</span>
                        <span class="{{ $tab['active'] ? 'bg-atlantia-blush text-atlantia-wine' : 'bg-slate-100 text-atlantia-ink/55' }} inline-flex min-w-[1.5rem] items-center justify-center rounded-full px-2 py-0.5 text-xs">
                            {{ number_format((int) $tab['value']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="orders-guest-section relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="orders-access-card">
                <div class="orders-access-main">
                    <div class="orders-access-art">
                        <div class="absolute inset-x-0 bottom-0 h-20 bg-[radial-gradient(circle_at_50%_100%,rgba(157,27,70,0.08),transparent_65%)]"></div>
                        @if ($restrictedIllustrationUrl)
                            <img
                                src="{{ $restrictedIllustrationUrl }}"
                                alt="Acceso restringido a historial de pedidos"
                                class="orders-access-image"
                                width="245"
                                height="250"
                            >
                        @else
                            <div class="relative z-10 h-60 w-60 rounded-full bg-atlantia-blush/70"></div>
                        @endif
                    </div>

                    <div class="orders-access-copy">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-atlantia-blush px-3 py-1 text-xs font-black text-atlantia-wine">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 10V7a4 4 0 1 1 8 0v3m-9 0h10a1 1 0 0 1 1 1v6.5A2.5 2.5 0 0 1 15.5 20h-7A2.5 2.5 0 0 1 6 17.5V11a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Acceso restringido
                            </span>

                            <h2 class="font-black text-atlantia-ink">
                                Inicia sesión para ver tu historial de compras
                            </h2>
                            <p class="text-atlantia-ink/65">
                                Para acceder a tus pedidos anteriores, seguimiento, facturas y detalles de entrega, primero debes autenticarte en tu cuenta.
                            </p>

                            <div class="orders-access-actions">
                                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(135deg,#9d1b46_0%,#740f30_100%)] px-6 py-3 text-sm font-black text-white shadow-[0_16px_34px_rgba(116,15,48,0.24)] transition hover:translate-y-[-1px] hover:shadow-[0_20px_40px_rgba(116,15,48,0.28)]">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                    Iniciar sesión
                                </a>
                                <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-atlantia-rose/30 bg-white px-6 py-3 text-sm font-black text-atlantia-wine transition hover:bg-atlantia-blush">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0M19 8v6M22 11h-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Crear cuenta
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="orders-benefits">
                    <article class="orders-benefit">
                        <span class="orders-benefit-icon">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9m0 0v4m0-4 3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-black text-atlantia-ink">Ver tus pedidos anteriores</h3>
                            <p class="text-atlantia-ink/65">Consulta el estado, detalles y seguimiento de tus compras.</p>
                        </div>
                    </article>
                    <article class="orders-benefit">
                        <span class="orders-benefit-icon">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 7h10l1 10H6L7 7Zm2-2a3 3 0 0 1 6 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-black text-atlantia-ink">Repetir compras fácilmente</h3>
                            <p class="text-atlantia-ink/65">Agrega productos de tus pedidos anteriores al carrito con un clic.</p>
                        </div>
                    </article>
                    <article class="orders-benefit">
                        <span class="orders-benefit-icon">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 4h10v14H7V4Zm0 4h10M10 12h4M10 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M20 14v4a2 2 0 0 1-2 2H8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-black text-atlantia-ink">Consultar facturas y estados</h3>
                            <p class="text-atlantia-ink/65">Accede a tus facturas, comprobantes y estados de entrega.</p>
                        </div>
                    </article>
                </div>
        </div>
    </section>
    @endguest

    @auth
        <livewire:cliente.pedidos-dashboard />
    @endauth
@endsection
