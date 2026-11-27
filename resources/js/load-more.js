/*
 * Carga progresiva del catalogo ("Ver mas productos").
 *
 * - Al acercarse al final se cargan los siguientes productos automaticamente,
 *   como maximo AUTO_LOADS veces seguidas; despues el cliente usa el boton,
 *   asi el footer sigue siendo alcanzable.
 * - Mientras carga se muestran tarjetas esqueleto.
 * - Si el cliente abre un producto y vuelve atras, se recuperan las paginas
 *   cargadas y la posicion del scroll.
 * - Sin JavaScript el boton es un enlace normal a la pagina siguiente.
 */

const AUTO_LOADS = 2;
const STORAGE_PREFIX = 'atlantia:catalogo:';

function listingKey() {
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    url.hash = '';
    return STORAGE_PREFIX + url.pathname + url.search;
}

function readState() {
    try {
        return JSON.parse(window.sessionStorage.getItem(listingKey()) || 'null');
    } catch {
        return null;
    }
}

function writeState(state) {
    try {
        window.sessionStorage.setItem(listingKey(), JSON.stringify(state));
    } catch {
        // Modo privado o almacenamiento lleno: la carga sigue funcionando sin recordar la posicion.
    }
}

function skeletonCard() {
    const card = document.createElement('div');
    card.dataset.loadMoreSkeleton = 'true';
    card.setAttribute('aria-hidden', 'true');
    card.className = 'flex animate-pulse flex-col overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white';
    card.innerHTML = `
        <div class="aspect-square w-full bg-atlantia-blush/60 lg:aspect-[5/4]"></div>
        <div class="space-y-2 p-3">
            <div class="h-2.5 w-1/2 rounded bg-atlantia-blush"></div>
            <div class="h-3.5 w-4/5 rounded bg-atlantia-blush"></div>
            <div class="h-3.5 w-3/5 rounded bg-atlantia-blush"></div>
            <div class="h-5 w-2/5 rounded bg-atlantia-blush"></div>
            <div class="h-9 w-full rounded-md bg-atlantia-blush"></div>
        </div>`;
    return card;
}

function columnsOf(grid) {
    const columns = getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length;
    return Math.max(1, columns || 2);
}

function initializeLoadMore() {
    const panel = document.querySelector('[data-load-more]');
    const grid = document.querySelector('[data-storefront-products]');

    if (!panel || !grid || panel.dataset.ready === 'true') {
        return;
    }
    panel.dataset.ready = 'true';

    const button = panel.querySelector('[data-load-more-button]');
    const count = panel.querySelector('[data-load-more-count]');
    const bar = panel.querySelector('[data-load-more-bar]');
    const sentinel = panel.querySelector('[data-load-more-sentinel]');
    const total = Number(panel.dataset.total || 0);
    let next = panel.dataset.next || '';
    let shown = Number(panel.dataset.shown || 0);
    let pagesLoaded = 1;
    let autoLoads = 0;
    let loading = false;

    const render = () => {
        if (count) {
            count.textContent = `Mostrando ${shown.toLocaleString('es-GT')} de ${total.toLocaleString('es-GT')} productos`;
        }
        if (bar && total > 0) {
            bar.style.width = `${Math.min(100, Math.round((shown / total) * 100))}%`;
        }
        if (!next) {
            button?.remove();
            sentinel?.remove();
            if (!panel.querySelector('[data-load-more-done]')) {
                const done = document.createElement('p');
                done.dataset.loadMoreDone = 'true';
                done.className = 'text-xs text-atlantia-ink/50';
                done.textContent = 'Ya viste todos los productos.';
                panel.appendChild(done);
            }
        } else if (button) {
            button.href = `${next}#productos`;
        }
    };

    const loadNext = async () => {
        if (!next || loading) {
            return false;
        }
        loading = true;

        const skeletons = Array.from({ length: columnsOf(grid) * 2 }, skeletonCard);
        skeletons.forEach((skeleton) => grid.appendChild(skeleton));
        if (button) {
            button.textContent = 'Cargando productos...';
            button.setAttribute('aria-disabled', 'true');
        }

        try {
            const response = await fetch(next, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html' },
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const incoming = doc.querySelector('[data-storefront-products]');
            const incomingPanel = doc.querySelector('[data-load-more]');

            incoming?.querySelectorAll(':scope > *').forEach((card) => {
                grid.appendChild(document.importNode(card, true));
            });

            next = incomingPanel?.dataset.next || '';
            shown = Number(incomingPanel?.dataset.shown || grid.querySelectorAll('[data-storefront-product]').length);
            pagesLoaded += 1;
            writeState({ pages: pagesLoaded, scrollY: window.scrollY });
            return true;
        } catch (error) {
            console.warn('No fue posible cargar mas productos.', error);
            if (button) {
                button.textContent = 'Reintentar';
            }
            return false;
        } finally {
            skeletons.forEach((skeleton) => skeleton.remove());
            if (button && button.textContent !== 'Reintentar') {
                button.textContent = 'Ver más productos';
            }
            button?.removeAttribute('aria-disabled');
            loading = false;
            render();
        }
    };

    button?.addEventListener('click', (event) => {
        event.preventDefault();
        autoLoads = 0;
        loadNext();
    });

    if ('IntersectionObserver' in window && sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting) && autoLoads < AUTO_LOADS && next && !loading) {
                autoLoads += 1;
                loadNext();
            }
        }, { rootMargin: '600px 0px' });
        observer.observe(sentinel);
    }

    let scrollTimer = null;
    window.addEventListener('scroll', () => {
        window.clearTimeout(scrollTimer);
        scrollTimer = window.setTimeout(() => writeState({ pages: pagesLoaded, scrollY: window.scrollY }), 150);
    }, { passive: true });

    /* Al volver atras desde un producto, se recuperan las paginas cargadas y la posicion. */
    const navigation = performance.getEntriesByType?.('navigation')?.[0];
    const saved = readState();
    if (navigation?.type === 'back_forward' && saved && saved.pages > 1) {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        (async () => {
            while (pagesLoaded < saved.pages && next) {
                if (!(await loadNext())) {
                    break;
                }
            }
            autoLoads = AUTO_LOADS;
            window.scrollTo(0, saved.scrollY || 0);
        })();
    } else if (navigation?.type !== 'back_forward') {
        writeState({ pages: 1, scrollY: 0 });
    }

    render();
}

document.addEventListener('DOMContentLoaded', initializeLoadMore);
document.addEventListener('livewire:navigated', initializeLoadMore);
