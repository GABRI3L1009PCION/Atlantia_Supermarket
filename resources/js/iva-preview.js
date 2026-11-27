/*
 * Vista previa del IVA (12%) en los formularios de producto.
 *
 * Dos modos según data-iva-modo en el campo:
 *  - "sin-iva" (por defecto): se escribe el precio sin IVA y se muestra el precio con IVA.
 *  - "final": se escribe el precio que paga el cliente y se muestra cuánto es de IVA.
 * El servidor hace el mismo cálculo.
 */
const IVA = 0.12;

/* Acepta "Q 149", "149,50", "1,250.50" o "1.250,50": devuelve el número o NaN. */
export function leerMonto(texto) {
    let limpio = String(texto ?? '').replace(/[^\d.,]/g, '');
    if (limpio === '') return NaN;

    const ultimaComa = limpio.lastIndexOf(',');
    const ultimoPunto = limpio.lastIndexOf('.');

    if (ultimaComa !== -1 && ultimoPunto !== -1) {
        /* El último separador es el decimal; el otro es de miles. */
        limpio = ultimaComa > ultimoPunto
            ? limpio.replace(/\./g, '').replace(',', '.')
            : limpio.replace(/,/g, '');
    } else if (ultimaComa !== -1) {
        /* "1,250" (3 cifras después) es miles; "149,5" es decimal. */
        limpio = /^\d+(,\d{3})+$/.test(limpio) ? limpio.replace(/,/g, '') : limpio.replace(',', '.');
    }

    return Number(limpio);
}

const formato = (valor) => `Q ${valor.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function updateIvaPreview(input) {
    const output = input.closest('label, div')?.querySelector('[data-iva-output]');
    if (!output) return;

    const valor = leerMonto(input.value);
    if (!input.value || !Number.isFinite(valor) || valor <= 0) {
        output.textContent = output.dataset.ivaEmpty || (input.dataset.ivaModo === 'final' ? 'Escribe el precio para ver el desglose' : 'Precio de venta con IVA (12%): Q 0.00');
        return;
    }

    if (input.dataset.ivaModo === 'final') {
        const sinIva = Math.round((valor / (1 + IVA)) * 100) / 100;
        const iva = Math.round((valor - sinIva) * 100) / 100;
        output.textContent = `El cliente paga ${formato(valor)} · incluye IVA de ${formato(iva)} · sin IVA: ${formato(sinIva)}`;
        return;
    }

    const conIva = Math.round(valor * (1 + IVA) * 100) / 100;
    output.textContent = `Precio de venta con IVA (12%): ${formato(conIva)}`;
}

document.addEventListener('input', (event) => {
    if (event.target.matches?.('[data-iva-input]')) updateIvaPreview(event.target);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-iva-input]').forEach(updateIvaPreview);
});
