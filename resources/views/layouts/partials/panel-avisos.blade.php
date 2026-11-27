{{-- Avisos emergentes de notificaciones nuevas --}}
<div class="pointer-events-none fixed right-4 top-[4.25rem] z-[60] flex w-[22rem] max-w-[calc(100vw-2rem)] flex-col gap-2 print:hidden" data-avisos aria-live="polite"></div>
<script @nonce>
    (() => {
        const campana = document.querySelector('[data-campana]');
        const contenedor = document.querySelector('[data-avisos]');
        if (!campana || !contenedor) return;

        const url = campana.dataset.recientesUrl;
        const contador = campana.querySelector('[data-campana-contador]');
        const clave = 'atlantia.avisosMostrados';
        const leerMostrados = () => { try { return JSON.parse(localStorage.getItem(clave) || '[]'); } catch (e) { return []; } };
        const guardarMostrados = (ids) => { try { localStorage.setItem(clave, JSON.stringify(ids.slice(-80))); } catch (e) {} };

        const actualizarContador = (total) => {
            if (!contador) return;
            contador.textContent = total > 99 ? '99+' : String(total);
            contador.classList.toggle('hidden', total === 0);
        };

        /* Construye el aviso con textContent: los datos nunca se insertan como HTML. */
        const mostrarAviso = (item, extras) => {
            const aviso = document.createElement('div');
            aviso.className = 'pointer-events-auto overflow-hidden rounded-2xl border border-atlantia-rose/20 bg-white shadow-[0_18px_40px_rgba(59,6,12,0.22)] transition duration-300 translate-y-2 opacity-0';
            aviso.setAttribute('role', 'status');

            const cuerpo = document.createElement('div');
            cuerpo.className = 'flex gap-3 p-4';
            const icono = document.createElement('span');
            icono.className = 'grid h-9 w-9 shrink-0 place-items-center rounded-full bg-atlantia-wine text-white';
            icono.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>';
            const texto = document.createElement('div');
            texto.className = 'min-w-0 flex-1';
            const titulo = document.createElement('p');
            titulo.className = 'text-sm font-black text-atlantia-ink';
            titulo.textContent = item.titulo;
            const mensaje = document.createElement('p');
            mensaje.className = 'mt-0.5 text-xs leading-snug text-atlantia-ink/65';
            mensaje.textContent = item.mensaje + (extras > 0 ? ` (y ${extras} ${extras === 1 ? 'aviso más' : 'avisos más'})` : '');
            const acciones = document.createElement('div');
            acciones.className = 'mt-2 flex items-center gap-3';
            const ver = document.createElement('a');
            ver.href = item.url;
            ver.className = 'text-xs font-black text-atlantia-wine hover:underline';
            ver.textContent = 'Revisar ahora →';
            acciones.append(ver);
            texto.append(titulo, mensaje, acciones);
            const cerrar = document.createElement('button');
            cerrar.type = 'button';
            cerrar.className = 'h-6 w-6 shrink-0 rounded-md text-atlantia-ink/40 hover:bg-atlantia-blush/60 hover:text-atlantia-wine';
            cerrar.setAttribute('aria-label', 'Cerrar aviso');
            cerrar.textContent = '✕';
            cuerpo.append(icono, texto, cerrar);

            const barra = document.createElement('div');
            barra.className = 'h-1 bg-atlantia-wine/80';
            barra.style.transition = 'width 7s linear';
            barra.style.width = '100%';
            aviso.append(cuerpo, barra);
            contenedor.append(aviso);

            requestAnimationFrame(() => {
                aviso.classList.remove('translate-y-2', 'opacity-0');
                barra.style.width = '0%';
            });
            const quitar = () => { aviso.classList.add('opacity-0'); setTimeout(() => aviso.remove(), 300); };
            let temporizador = setTimeout(quitar, 7000);
            aviso.addEventListener('mouseenter', () => { clearTimeout(temporizador); barra.style.transition = 'none'; barra.style.width = '100%'; });
            aviso.addEventListener('mouseleave', () => { temporizador = setTimeout(quitar, 3000); });
            cerrar.addEventListener('click', quitar);
        };

        const revisar = async () => {
            try {
                const respuesta = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!respuesta.ok) return;
                const datos = await respuesta.json();
                actualizarContador(datos.no_leidas || 0);

                const mostrados = leerMostrados();
                const nuevos = (datos.items || []).filter((item) => !item.leida && !mostrados.includes(item.id));
                if (nuevos.length > 0) {
                    mostrarAviso(nuevos[0], nuevos.length - 1);
                    guardarMostrados([...mostrados, ...nuevos.map((item) => item.id)]);
                }
            } catch (e) {}
        };

        revisar();
        setInterval(() => { if (!document.hidden) revisar(); }, 30000);
    })();
</script>
