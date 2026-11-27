@props(['pedido' => null, 'titulo' => '¿Te avisamos cuando tu pedido vaya en camino?'])

@if (\App\Services\Notificaciones\PushService::habilitado())
    <div {{ $attributes->merge(['class' => 'hidden rounded-2xl border border-atlantia-rose/20 bg-white p-4 shadow-sm']) }} data-activar-avisos>
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-atlantia-blush text-atlantia-wine" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-black text-atlantia-ink" data-avisos-titulo>{{ $titulo }}</p>
                <p class="mt-0.5 text-sm text-atlantia-ink/60">Te llega una notificación al teléfono, gratis y sin compartir tu número.</p>
                <label class="mt-2 flex items-center gap-2 text-sm text-atlantia-ink/75">
                    <input type="checkbox" checked class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine" data-avisos-ofertas>
                    También avisarme de productos nuevos y ofertas
                </label>
                <p class="mt-2 hidden text-xs font-bold text-amber-700" data-avisos-iphone>En iPhone primero agrega la tienda a tu pantalla de inicio (Compartir → “Agregar a inicio”).</p>
                <p class="mt-2 hidden text-sm font-bold text-emerald-700" data-avisos-listo>Listo, te avisaremos.</p>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="button" class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white hover:bg-atlantia-wine-700" data-avisos-activar>Activar avisos</button>
        </div>
    </div>

    @once
        <script @nonce>
            (() => {
                const clave = @json(\App\Services\Notificaciones\PushService::clavePublica());
                const url = @json(route('push.suscribir'));
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const soporta = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
                const instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                const esIphone = /iphone|ipad|ipod/i.test(navigator.userAgent);

                const aBytes = (base64) => {
                    const relleno = '='.repeat((4 - (base64.length % 4)) % 4);
                    const crudo = atob((base64 + relleno).replace(/-/g, '+').replace(/_/g, '/'));
                    return Uint8Array.from([...crudo].map((c) => c.charCodeAt(0)));
                };

                document.querySelectorAll('[data-activar-avisos]').forEach(async (caja) => {
                    if (esIphone && !instalada) {
                        caja.classList.remove('hidden');
                        caja.querySelector('[data-avisos-iphone]').classList.remove('hidden');
                        caja.querySelector('[data-avisos-activar]').classList.add('hidden');
                        return;
                    }
                    if (!soporta || Notification.permission === 'denied') return;

                    const registro = await navigator.serviceWorker.register('/sw.js').catch(() => null);
                    if (!registro) return;
                    const existente = await registro.pushManager.getSubscription();
                    if (existente && Notification.permission === 'granted') return;
                    caja.classList.remove('hidden');

                    caja.querySelector('[data-avisos-activar]').addEventListener('click', async (evento) => {
                        const boton = evento.currentTarget;
                        boton.disabled = true;
                        try {
                            if (await Notification.requestPermission() !== 'granted') { caja.classList.add('hidden'); return; }
                            const suscripcion = await registro.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: aBytes(clave) });
                            await fetch(url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                                body: JSON.stringify({
                                    ...suscripcion.toJSON(),
                                    ofertas: caja.querySelector('[data-avisos-ofertas]').checked,
                                    pedido_uuid: caja.dataset.pedido || null,
                                }),
                            });
                            boton.classList.add('hidden');
                            caja.querySelector('[data-avisos-listo]').classList.remove('hidden');
                        } catch (_) {
                            boton.disabled = false;
                        }
                    });
                });
            })();
        </script>
    @endonce
@endif
