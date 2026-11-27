/*
 * Reemplaza los manejadores en linea (onsubmit, onclick, onchange) que la politica de
 * seguridad (CSP con nonce) bloquea en produccion. Se usan atributos data-* en su lugar:
 *
 *   <form data-confirm="¿Eliminar?">        pide confirmacion antes de enviar
 *   <button data-confirm="¿Seguro?">        pide confirmacion antes del clic
 *   <select data-autosubmit>                envia su formulario al cambiar
 *   <button data-print-page>                imprime la pagina
 *   <button data-qty-step="-1" data-qty-target="id">   suma/resta a un input numerico
 */

document.addEventListener('submit', (event) => {
    const form = event.target.closest?.('form[data-confirm]');

    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

document.addEventListener('click', (event) => {
    const confirmar = event.target.closest?.('[data-confirm]:not(form)');

    if (confirmar && !window.confirm(confirmar.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();

        return;
    }

    if (event.target.closest?.('[data-print-page]')) {
        window.print();

        return;
    }

    const paso = event.target.closest?.('[data-qty-step]');

    if (paso) {
        const input = document.getElementById(paso.dataset.qtyTarget || '');

        if (!input) return;

        const minimo = Number(input.min || 1);
        const maximo = input.max === '' ? Infinity : Number(input.max);
        const valor = (Number(input.value) || minimo) + Number(paso.dataset.qtyStep || 0);

        input.value = String(Math.min(maximo, Math.max(minimo, valor)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}, true);

document.addEventListener('change', (event) => {
    const control = event.target.closest?.('[data-autosubmit]');

    if (control?.form) {
        control.form.requestSubmit ? control.form.requestSubmit() : control.form.submit();
    }
});
