{{-- App instalable: registra el service worker y ofrece instalar la tienda en el telefono. --}}
<div id="instalar-app" class="fixed inset-x-3 bottom-24 z-[70] hidden rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-atlantia-rose/20 sm:left-auto sm:right-6 sm:w-96 lg:bottom-24" role="dialog" aria-label="Instalar Atlantia Store">
    <div class="flex items-center gap-3">
        <img src="{{ asset('icon-192.png') }}" alt="" class="h-12 w-12 rounded-xl">
        <div class="min-w-0 flex-1">
            <p class="font-black text-atlantia-ink">Instala Atlantia en tu teléfono</p>
            <p class="text-xs text-atlantia-ink/60" data-instalar-texto>Ábrela como una app, más rápido y sin buscarla.</p>
        </div>
    </div>
    <div class="mt-3 flex justify-end gap-2">
        <button type="button" data-instalar-cerrar class="rounded-lg px-3 py-2 text-sm font-bold text-atlantia-ink/60">Ahora no</button>
        <button type="button" data-instalar-si class="rounded-lg bg-atlantia-wine px-4 py-2 text-sm font-black text-white">Instalar</button>
    </div>
</div>
<script @nonce>
    (() => {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }

        const caja = document.getElementById('instalar-app');
        const clave = 'atlantia.instalar.descartado';
        const descartado = () => { try { return Number(localStorage.getItem(clave) || 0) > Date.now(); } catch (_) { return false; } };
        const descartar = () => { try { localStorage.setItem(clave, String(Date.now() + 14 * 864e5)); } catch (_) {} caja.classList.add('hidden'); };
        const instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (!caja || instalada || descartado()) return;

        let aviso = null;
        window.addEventListener('beforeinstallprompt', (evento) => {
            evento.preventDefault();
            aviso = evento;
            setTimeout(() => caja.classList.remove('hidden'), 4000);
        });

        /* iPhone no tiene boton de instalar: se explica como agregarla a la pantalla de inicio. */
        const esIphone = /iphone|ipad|ipod/i.test(navigator.userAgent) && /safari/i.test(navigator.userAgent) && !/crios|fxios/i.test(navigator.userAgent);
        if (esIphone) {
            caja.querySelector('[data-instalar-texto]').textContent = 'Toca Compartir y luego “Agregar a pantalla de inicio”.';
            caja.querySelector('[data-instalar-si]').textContent = 'Entendido';
            setTimeout(() => caja.classList.remove('hidden'), 6000);
        }

        caja.querySelector('[data-instalar-cerrar]').addEventListener('click', descartar);
        caja.querySelector('[data-instalar-si]').addEventListener('click', async () => {
            if (aviso) {
                aviso.prompt();
                await aviso.userChoice.catch(() => null);
                aviso = null;
            }
            descartar();
        });
    })();
</script>
