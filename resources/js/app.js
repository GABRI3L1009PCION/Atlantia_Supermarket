import QRCode from 'qrcode';
import './load-more';
import './iva-preview';
import './acciones-seguras';

function initializeStorefront() {
    document.querySelectorAll('[data-storefront-carousel]').forEach((carousel) => {
        if (carousel.dataset.ready) return;
        carousel.dataset.ready = 'true';
        const track = carousel.querySelector('[data-storefront-track]');
        const position = carousel.querySelector('[data-storefront-position]');
        const step = () => (track.firstElementChild?.getBoundingClientRect().width || track.clientWidth) + 16;
        const update = () => {
            if (position) position.textContent = `${Math.min(track.children.length, Math.round(track.scrollLeft / step()) + 1)} / ${track.children.length}`;
        };
        carousel.querySelector('[data-storefront-prev]')?.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
        carousel.querySelector('[data-storefront-next]')?.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
        track.addEventListener('scroll', update, { passive: true });
        update();
    });
}

document.addEventListener('DOMContentLoaded', initializeStorefront);
document.addEventListener('livewire:navigated', initializeStorefront);
document.addEventListener('change', (event) => {
    const form = event.target.closest('[data-storefront-filters]');
    if (form) form.requestSubmit();
});

document.addEventListener('click', (event) => {
    const closeButton = event.target.closest('[data-flash-close]');

    if (! closeButton) {
        return;
    }

    closeButton.closest('[data-flash-modal]')?.remove();
});

function initializeFlashModals() {
    document.querySelectorAll('[data-flash-modal][data-flash-auto-close]').forEach((modal) => {
        if (modal.dataset.flashTimerReady === 'true') {
            return;
        }

        modal.dataset.flashTimerReady = 'true';

        const duration = Math.max(1, Number(modal.dataset.flashAutoClose || 3));
        const countdown = modal.querySelector('[data-flash-countdown]');
        const startedAt = Date.now();

        const interval = setInterval(() => {
            if (!document.body.contains(modal)) {
                clearInterval(interval);
                return;
            }

            const elapsed = (Date.now() - startedAt) / 1000;
            const remaining = Math.max(0, Math.ceil(duration - elapsed));

            if (countdown) {
                countdown.textContent = String(remaining);
            }

            if (remaining <= 0) {
                clearInterval(interval);
                modal.remove();
            }
        }, 250);
    });
}

document.addEventListener('DOMContentLoaded', initializeFlashModals);
document.addEventListener('livewire:navigated', initializeFlashModals);

function initializeOfferCountdowns() {
    document.querySelectorAll('[data-offer-countdown]').forEach((counter) => {
        if (counter.dataset.offerCountdownReady === 'true') {
            return;
        }

        counter.dataset.offerCountdownReady = 'true';

        const expiresAt = Date.parse(counter.dataset.offerExpiresAt || '');
        const totalSeconds = Math.max(1, Number(counter.dataset.offerTotalSeconds || 1));
        const modal = counter.closest('[data-offer-modal]');
        const ring = modal?.querySelector('[data-offer-ring]');

        if (!Number.isFinite(expiresAt)) {
            return;
        }

        const render = () => {
            const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
            const progress = Math.max(0, Math.min(100, (remaining / totalSeconds) * 100));

            counter.textContent = String(remaining);

            if (ring) {
                ring.style.background = `conic-gradient(#7f0b18 ${progress}%, rgba(139, 8, 50, .16) 0)`;
            }

            return remaining;
        };

        render();

        const interval = setInterval(() => {
            if (!document.body.contains(counter)) {
                clearInterval(interval);
                return;
            }

            if (render() <= 0) {
                clearInterval(interval);
                modal?.classList.add('hidden');
            }
        }, 250);
    });
}

document.addEventListener('DOMContentLoaded', initializeOfferCountdowns);
document.addEventListener('livewire:navigated', initializeOfferCountdowns);

function initializeDeliveryCodeInputs() {
    document.querySelectorAll('[data-delivery-code-form]').forEach((form) => {
        if (form.dataset.deliveryCodeReady === 'true') {
            return;
        }

        form.dataset.deliveryCodeReady = 'true';

        const inputs = [...form.querySelectorAll('[data-delivery-code-digit]')];
        const hidden = form.querySelector('[data-delivery-code-hidden]');

        const sync = () => {
            if (hidden) {
                hidden.value = inputs.map((input) => input.value).join('');
            }
        };

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(-1);
                sync();

                if (input.value && inputs[index + 1]) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && inputs[index - 1]) {
                    inputs[index - 1].focus();
                    inputs[index - 1].select();
                }
            });

            input.addEventListener('paste', (event) => {
                const pasted = event.clipboardData?.getData('text')?.replace(/\D/g, '').slice(0, inputs.length);

                if (!pasted) {
                    return;
                }

                event.preventDefault();
                inputs.forEach((target, digitIndex) => {
                    target.value = pasted[digitIndex] || '';
                });
                sync();
                inputs[Math.min(pasted.length, inputs.length) - 1]?.focus();
            });
        });

        form.addEventListener('submit', sync);
    });
}

document.addEventListener('DOMContentLoaded', initializeDeliveryCodeInputs);
document.addEventListener('livewire:navigated', initializeDeliveryCodeInputs);

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelector('[data-flash-modal]')?.remove();
    document.querySelector('[data-create-modal]')?.classList.add('hidden');
    document.querySelector('[data-create-modal]')?.classList.remove('flex');
    document.querySelector('[data-barcode-modal]')?.classList.add('hidden');
    document.querySelector('[data-barcode-modal]')?.classList.remove('flex');
});

document.addEventListener('click', (event) => {
    const openCreateButton = event.target.closest('[data-open-create-modal]');

    if (openCreateButton) {
        const modal = document.querySelector('[data-create-modal]');
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        modal?.querySelector('[name="name"]')?.focus();
        return;
    }

    const closeCreateButton = event.target.closest('[data-close-create-modal]');
    const createModal = event.target.closest('[data-create-modal]');

    if (closeCreateButton || (createModal && event.target === createModal)) {
        const modal = document.querySelector('[data-create-modal]');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    }
});

const toastClasses = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    error: 'border-rose-200 bg-rose-50 text-rose-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    info: 'border-sky-200 bg-sky-50 text-sky-900',
};

function showToast(type, message) {
    const stack = document.getElementById('toast-stack');

    if (!stack || !message) {
        return;
    }

    const tone = toastClasses[type] ?? toastClasses.info;
    const toast = document.createElement('div');

    toast.className = `pointer-events-auto rounded-xl border px-4 py-3 text-sm font-semibold shadow-xl transition ${tone}`;
    toast.setAttribute('role', 'alert');

    const removeToast = () => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 180);
    };

    const content = document.createElement('div');
    content.className = 'flex items-start gap-3';

    const text = document.createElement('div');
    text.className = 'min-w-0 flex-1 leading-6';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'shrink-0 text-current/70 hover:text-current';
    close.setAttribute('aria-label', 'Cerrar notificacion');
    close.textContent = 'x';
    close.addEventListener('click', removeToast);

    content.append(text, close);
    toast.appendChild(content);
    stack.appendChild(toast);
    setTimeout(removeToast, 5000);
}

window.addEventListener('toast', (event) => {
    showToast(event.detail?.type ?? 'info', event.detail?.message ?? '');
});

window.addEventListener('notificacion', (event) => {
    showToast(event.detail?.type ?? 'info', event.detail?.message ?? '');
});

const overlay = document.getElementById('livewire-global-overlay');

function showOverlay() {
    overlay?.classList.remove('hidden');
    overlay?.classList.add('flex');
}

function hideOverlay() {
    overlay?.classList.add('hidden');
    overlay?.classList.remove('flex');
}

document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.hook) {
        return;
    }

    /*
     * La pantalla de carga solo aparece si la peticion tarda mas de 700 ms.
     * Asi las validaciones rapidas (al escribir o marcar casillas) no tapan la pagina.
     */
    let pendingRequests = 0;
    let overlayTimer = null;

    window.Livewire.hook('request', ({ succeed, fail }) => {
        pendingRequests += 1;
        if (overlayTimer === null) {
            overlayTimer = window.setTimeout(showOverlay, 700);
        }

        const done = () => {
            pendingRequests = Math.max(0, pendingRequests - 1);
            if (pendingRequests === 0) {
                window.clearTimeout(overlayTimer);
                overlayTimer = null;
                hideOverlay();
            }
        };

        succeed(done);
        fail(done);
    });
});

document.addEventListener('livewire:navigating', showOverlay);
document.addEventListener('livewire:navigated', hideOverlay);

document.querySelectorAll('[data-retry-countdown]').forEach((element) => {
    const fallbackSeconds = Number(element.dataset.retryCountdown || 10);
    const storageKey = element.dataset.retryKey;
    const redirectUrl = element.dataset.retryRedirect;
    const now = Date.now();
    let retryUntil = now + fallbackSeconds * 1000;

    if (storageKey) {
        const storedRetryUntil = Number(sessionStorage.getItem(storageKey) || 0);

        if (storedRetryUntil > now) {
            retryUntil = storedRetryUntil;
        } else {
            sessionStorage.setItem(storageKey, String(retryUntil));
        }
    }

    let seconds = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));

    /* Tiempo legible: "45 segundos", "2 min 05 s", "1 h 03 min". */
    const render = () => {
        if (seconds < 60) {
            element.textContent = `${seconds} ${seconds === 1 ? 'segundo' : 'segundos'}`;
        } else if (seconds < 3600) {
            element.textContent = `${Math.floor(seconds / 60)} min ${String(seconds % 60).padStart(2, '0')} s`;
        } else {
            element.textContent = `${Math.floor(seconds / 3600)} h ${String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')} min`;
        }
    };

    const redirect = () => {
        if (storageKey) {
            sessionStorage.removeItem(storageKey);
        }

        if (redirectUrl) {
            window.location.replace(redirectUrl);
        }
    };

    render();

    const interval = setInterval(() => {
        seconds = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));
        render();

        if (seconds <= 0) {
            clearInterval(interval);
            redirect();
        }
    }, 1000);
});

function ean13CheckDigit(body) {
    const sum = body
        .split('')
        .reduce((total, digit, index) => total + Number(digit) * (index % 2 === 0 ? 1 : 3), 0);

    return String((10 - (sum % 10)) % 10);
}

function previewBarcodeFromSku(sku) {
    let hash = 0;

    for (const character of sku) {
        hash = (hash * 31 + character.charCodeAt(0)) % 1000000000;
    }

    const body = `740${String(hash).padStart(9, '0')}`;

    return `${body}${ean13CheckDigit(body)}`;
}

function productSkuFromName(name) {
    const sku = name
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 56);

    return sku || '';
}

function initializeProductIdentifiers() {
    document.querySelectorAll('[data-product-create-form]').forEach((form) => {
        const nameInput = form.querySelector('[data-product-name]');
        const skuInput = form.querySelector('[data-product-sku]');
        const barcodePreview = form.querySelector('[data-product-barcode-preview]');

        if (!nameInput || !skuInput || !barcodePreview || form.dataset.productIdentifiersReady === 'true') {
            return;
        }

        form.dataset.productIdentifiersReady = 'true';

        const syncIdentifiers = () => {
            const sku = productSkuFromName(nameInput.value);
            skuInput.value = sku;
            barcodePreview.value = sku ? previewBarcodeFromSku(sku) : 'Se asigna al guardar';
        };

        nameInput.addEventListener('input', syncIdentifiers);
        syncIdentifiers();
    });
}

document.addEventListener('DOMContentLoaded', initializeProductIdentifiers);
document.addEventListener('livewire:navigated', initializeProductIdentifiers);

const ean13Patterns = {
    L: ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'],
    G: ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'],
    R: ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'],
};

const ean13Parity = [
    'LLLLLL',
    'LLGLGG',
    'LLGGLG',
    'LLGGGL',
    'LGLLGG',
    'LGGLLG',
    'LGGGLL',
    'LGLGLG',
    'LGLGGL',
    'LGGLGL',
];

let activeBarcodeProduct = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function normalizeEan13(value, fallback = '') {
    const digits = String(value || '').replace(/\D/g, '');

    if (digits.length === 13) {
        return digits;
    }

    if (digits.length === 12) {
        return `${digits}${ean13CheckDigit(digits)}`;
    }

    return previewBarcodeFromSku(fallback || value || 'ATLANTIA');
}

function barcodeSvgMarkup(value, options = {}) {
    const code = normalizeEan13(value);
    const moduleWidth = options.moduleWidth ?? 2;
    const barHeight = options.barHeight ?? 70;
    const textHeight = options.showText === false ? 0 : 18;
    const width = 95 * moduleWidth;
    const height = barHeight + textHeight;
    const parity = ean13Parity[Number(code[0])] || ean13Parity[0];
    let bits = '101';

    for (let index = 1; index <= 6; index += 1) {
        bits += ean13Patterns[parity[index - 1]][Number(code[index])];
    }

    bits += '01010';

    for (let index = 7; index <= 12; index += 1) {
        bits += ean13Patterns.R[Number(code[index])];
    }

    bits += '101';

    let bars = '';

    for (let index = 0; index < bits.length; index += 1) {
        if (bits[index] === '1') {
            bars += `<rect x="${index * moduleWidth}" y="0" width="${moduleWidth}" height="${barHeight}" fill="#111111"/>`;
        }
    }

    const text = options.showText === false
        ? ''
        : `<text x="${width / 2}" y="${barHeight + 14}" text-anchor="middle" font-family="ui-monospace, SFMono-Regular, Menlo, Consolas, monospace" font-size="12" font-weight="700" fill="#2b1722">${code}</text>`;

    return `<svg viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Codigo de barras ${code}">${bars}${text}</svg>`;
}

function barcodeLabelMarkup(product, options = {}) {
    const showName = options.showName !== false;
    const showPrice = options.showPrice !== false;
    const showSku = options.showSku === true;
    const code = normalizeEan13(product.barcode, product.sku || product.name);

    if (product.labelType === 'price') {
        return `
            <div class="barcode-print-label price-print-label" style="width: 100%; break-inside: avoid; border: 1px solid #ecd1d3; border-radius: 7px; padding: 7px; text-align: center; background: #ffffff; overflow: hidden;">
                ${showName ? `<div class="label-name" style="margin-bottom: 4px; font-size: 10px; font-weight: 900; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(product.name)}</div>` : ''}
                ${showPrice ? `<div class="label-price" style="font-size: 20px; line-height: 1; font-weight: 900; color: #7f1734;">${escapeHtml(product.price)}</div>` : ''}
                ${showSku ? `<div class="label-sku" style="margin-top: 4px; font-size: 8px; color: #7c6a73;">SKU: ${escapeHtml(product.sku)}</div>` : ''}
            </div>
        `;
    }

    return `
        <div class="barcode-print-label" style="width: 100%; break-inside: avoid; border: 1px solid #ecd1d3; border-radius: 7px; padding: 6px; text-align: center; background: #ffffff; overflow: hidden;">
            ${showName ? `<div class="label-name" style="margin-bottom: 2px; font-size: 9px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(product.name)}</div>` : ''}
            <div class="label-barcode" style="display: block; width: 100%;">${barcodeSvgMarkup(code, { moduleWidth: 1.25, barHeight: 26 })}</div>
            ${showSku ? `<div class="label-sku" style="margin-top: 2px; font-size: 8px; color: #7c6a73;">SKU: ${escapeHtml(product.sku)}</div>` : ''}
            ${showPrice ? `<div class="label-price" style="margin-top: 1px; font-size: 10px; font-weight: 900;">${escapeHtml(product.price)}</div>` : ''}
        </div>
    `;
}

function barcodePrintOptions(modal) {
    return {
        quantity: Math.min(200, Math.max(1, Number(modal.querySelector('[data-barcode-quantity]')?.value || 1))),
        format: modal.querySelector('[data-barcode-format]:checked')?.value || 'thermal',
        size: modal.querySelector('[data-barcode-size]')?.value || '50x30',
        showName: modal.querySelector('[data-barcode-show-name]')?.checked !== false,
        showPrice: modal.querySelector('[data-barcode-show-price]')?.checked !== false,
        showSku: modal.querySelector('[data-barcode-show-sku]')?.checked === true,
    };
}

function updateBarcodePreview() {
    const modal = document.querySelector('[data-barcode-modal]');

    if (!modal || !activeBarcodeProduct) {
        return;
    }

    const options = barcodePrintOptions(modal);
    const code = normalizeEan13(activeBarcodeProduct.barcode, activeBarcodeProduct.sku || activeBarcodeProduct.name);
    const previewMarkup = barcodeLabelMarkup({ ...activeBarcodeProduct, barcode: code }, options);

    modal.querySelector('[data-barcode-svg]').innerHTML = activeBarcodeProduct.labelType === 'price'
        ? `<div class="rounded-xl border border-atlantia-rose/15 bg-atlantia-cream/50 px-4 py-4 text-center">
            <p class="text-sm font-black text-atlantia-ink">${escapeHtml(activeBarcodeProduct.name)}</p>
            <p class="mt-2 text-4xl font-black leading-none text-atlantia-wine">${escapeHtml(activeBarcodeProduct.price)}</p>
            <p class="mt-2 text-xs font-semibold text-atlantia-ink/50">Etiqueta de precio</p>
        </div>`
        : barcodeSvgMarkup(code, { moduleWidth: 1.8, barHeight: 46 });
    modal.querySelector('[data-barcode-label-preview]').innerHTML = previewMarkup;
    modal.querySelector('[data-barcode-sheet-preview]').innerHTML = Array.from({ length: Math.min(4, options.quantity) })
        .map(() => previewMarkup)
        .join('');
}

function openBarcodeModal(button, labelType = 'barcode') {
    const modal = document.querySelector('[data-barcode-modal]');

    if (!modal) {
        return;
    }

    activeBarcodeProduct = {
        name: button.dataset.productName || 'Producto',
        sku: button.dataset.productSku || '',
        barcode: normalizeEan13(button.dataset.productBarcode, button.dataset.productSku || button.dataset.productName),
        price: button.dataset.productPrice || '',
        vendor: button.dataset.productVendor || '',
        image: button.dataset.productImage || '',
        labelType,
    };

    modal.querySelector('#barcode-print-title').textContent = labelType === 'price'
        ? 'Imprimir etiqueta de precio'
        : 'Imprimir codigo de barras';
    modal.querySelector('[data-barcode-product-name]').textContent = activeBarcodeProduct.name;
    modal.querySelector('[data-barcode-product-sku]').textContent = activeBarcodeProduct.sku || activeBarcodeProduct.barcode;
    modal.querySelector('[data-barcode-product-vendor]').textContent = activeBarcodeProduct.vendor || 'Atlantia Supermarket';
    modal.querySelector('[data-barcode-product-price]').textContent = activeBarcodeProduct.price;
    modal.querySelector('[data-barcode-product-initial]').textContent = activeBarcodeProduct.name.slice(0, 1).toUpperCase();

    const image = modal.querySelector('[data-barcode-product-image]');

    if (activeBarcodeProduct.image) {
        image.src = activeBarcodeProduct.image;
        image.alt = activeBarcodeProduct.name;
        image.classList.remove('hidden');
        modal.querySelector('[data-barcode-product-initial]').classList.add('hidden');
    } else {
        image.removeAttribute('src');
        image.classList.add('hidden');
        modal.querySelector('[data-barcode-product-initial]').classList.remove('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    updateBarcodePreview();
}

function closeBarcodeModal() {
    const modal = document.querySelector('[data-barcode-modal]');

    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

function printBarcodeLabels(asPdf = false) {
    const modal = document.querySelector('[data-barcode-modal]');

    if (!modal || !activeBarcodeProduct) {
        return;
    }

    const options = barcodePrintOptions(modal);
    const [labelWidth, labelHeight] = options.size.split('x').map(Number);
    const labels = Array.from({ length: options.quantity })
        .map(() => barcodeLabelMarkup(activeBarcodeProduct, options))
        .join('');
    const pageClass = options.format === 'a4' ? 'sheet a4' : 'sheet thermal';
    const printWindow = window.open('', '_blank', 'width=980,height=720');

    if (!printWindow) {
        showToast('error', 'El navegador bloqueo la ventana de impresion. Permite ventanas emergentes e intenta de nuevo.');
        return;
    }

    printWindow.document.write(`
        <!doctype html>
        <html lang="es">
            <head>
                <meta charset="utf-8">
                <title>${asPdf ? 'Descargar PDF' : 'Imprimir'} - ${escapeHtml(activeBarcodeProduct.name)}</title>
                <style>
                    @page { margin: 8mm; }
                    * { box-sizing: border-box; }
                    body {
                        margin: 0;
                        background: #ffffff;
                        color: #2b1722;
                        font-family: Arial, sans-serif;
                    }
                    .sheet {
                        display: grid;
                        gap: 3mm;
                        padding: 0;
                    }
                    .sheet.a4 {
                        grid-template-columns: repeat(auto-fill, minmax(${labelWidth}mm, 1fr));
                    }
                    .sheet.thermal {
                        grid-template-columns: repeat(auto-fill, ${labelWidth}mm);
                    }
                    .barcode-print-label {
                        width: ${labelWidth}mm;
                        min-height: ${labelHeight}mm;
                        break-inside: avoid;
                        border: 1px solid #ecd1d3;
                        border-radius: 2mm;
                        padding: 2mm;
                        text-align: center;
                        overflow: hidden;
                    }
                    .label-name {
                        margin-bottom: 1mm;
                        font-size: 9px;
                        font-weight: 800;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .label-barcode svg {
                        display: block;
                        width: 100%;
                        height: 14mm;
                    }
                    .label-sku {
                        margin-top: 0.5mm;
                        font-size: 7px;
                        color: #7c6a73;
                    }
                    .label-price {
                        margin-top: 0.5mm;
                        font-size: 11px;
                        font-weight: 900;
                    }
                    @media print {
                        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                <main class="${pageClass}">
                    ${labels}
                </main>
            </body>
        </html>
    `);

    printWindow.document.close();
    /* Un <script> dentro de la ventana lo bloquea la CSP: se imprime desde aqui. */
    setTimeout(() => {
        printWindow.focus();
        printWindow.print();
    }, 350);
}

function initializeBarcodePrinting() {
    const modal = document.querySelector('[data-barcode-modal]');

    if (!modal || modal.dataset.barcodeReady === 'true') {
        return;
    }

    modal.dataset.barcodeReady = 'true';

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-open-barcode-modal], [data-open-price-modal]');

        if (openButton) {
            openBarcodeModal(openButton, openButton.matches('[data-open-price-modal]') ? 'price' : 'barcode');
            return;
        }

        if (event.target.closest('[data-close-barcode-modal]') || event.target === modal) {
            closeBarcodeModal();
        }
    });

    modal.querySelector('[data-barcode-qty-minus]')?.addEventListener('click', () => {
        const input = modal.querySelector('[data-barcode-quantity]');
        input.value = Math.max(1, Number(input.value || 1) - 1);
        updateBarcodePreview();
    });

    modal.querySelector('[data-barcode-qty-plus]')?.addEventListener('click', () => {
        const input = modal.querySelector('[data-barcode-quantity]');
        input.value = Math.min(200, Number(input.value || 1) + 1);
        updateBarcodePreview();
    });

    modal.querySelectorAll('[data-barcode-quantity], [data-barcode-format], [data-barcode-size], [data-barcode-show-name], [data-barcode-show-price], [data-barcode-show-sku]').forEach((input) => {
        input.addEventListener('input', updateBarcodePreview);
        input.addEventListener('change', updateBarcodePreview);
    });

    modal.querySelector('[data-print-barcode-labels]')?.addEventListener('click', () => printBarcodeLabels(false));
    modal.querySelector('[data-download-barcode-pdf]')?.addEventListener('click', () => printBarcodeLabels(true));
}

document.addEventListener('DOMContentLoaded', initializeBarcodePrinting);
document.addEventListener('livewire:navigated', initializeBarcodePrinting);

async function initializeOtpQr() {
    const qrNodes = document.querySelectorAll('[data-otp-qr]');

    for (const node of qrNodes) {
        const otpUri = node.dataset.otpQr;

        if (!otpUri || node.dataset.qrReady === 'true') {
            continue;
        }

        node.dataset.qrReady = 'true';
        node.innerHTML = '';

        try {
            const canvas = document.createElement('canvas');
            await QRCode.toCanvas(canvas, otpUri, {
                width: 168,
                margin: 1,
                color: {
                    dark: '#7f1734',
                    light: '#ffffff',
                },
            });

            canvas.className = 'h-40 w-40 rounded-xl';
            node.appendChild(canvas);
        } catch (error) {
            node.dataset.qrReady = 'false';
            node.innerHTML = '<p class="text-xs font-semibold text-atlantia-ink/60">No fue posible generar el QR.</p>';
            console.error(error);
        }
    }
}

document.addEventListener('DOMContentLoaded', initializeOtpQr);
document.addEventListener('livewire:navigated', initializeOtpQr);

/* Cierra menus desplegables (<details data-close-outside>) al tocar fuera o elegir una opcion. */
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-close-outside][open]').forEach((menu) => {
        if (!menu.contains(event.target) || event.target.closest('a')) {
            menu.open = false;
        }
    });
});

/* Flechas del carrusel de categorias: desplazan casi una pantalla y se ocultan en los extremos. */
function initializeCategoryScroller() {
    document.querySelectorAll('[data-category-scroller]').forEach((scroller) => {
        if (scroller.dataset.ready === 'true') return;
        scroller.dataset.ready = 'true';

        const track = scroller.querySelector('[data-category-track]');
        const prev = scroller.querySelector('[data-category-prev]');
        const next = scroller.querySelector('[data-category-next]');
        if (!track) return;

        const update = () => {
            const max = track.scrollWidth - track.clientWidth;
            if (prev) prev.disabled = track.scrollLeft <= 4;
            if (next) next.disabled = track.scrollLeft >= max - 4;
        };
        const move = (direction) => track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });

        prev?.addEventListener('click', () => move(-1));
        next?.addEventListener('click', () => move(1));
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });
}

document.addEventListener('DOMContentLoaded', initializeCategoryScroller);
document.addEventListener('livewire:navigated', initializeCategoryScroller);
