document.addEventListener('click', (event) => {
    const closeButton = event.target.closest('[data-flash-close]');

    if (! closeButton) {
        return;
    }

    closeButton.closest('[data-flash-modal]')?.remove();
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelector('[data-flash-modal]')?.remove();
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
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1 leading-6">${message}</div>
            <button type="button" class="shrink-0 text-current/70 hover:text-current" aria-label="Cerrar notificacion">&times;</button>
        </div>
    `;

    const removeToast = () => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 180);
    };

    toast.querySelector('button')?.addEventListener('click', removeToast);
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

    window.Livewire.hook('request', ({ succeed, fail }) => {
        showOverlay();
        succeed(() => hideOverlay());
        fail(() => hideOverlay());
    });
});

document.addEventListener('livewire:navigating', showOverlay);
document.addEventListener('livewire:navigated', hideOverlay);

document.querySelectorAll('[data-retry-countdown]').forEach((element) => {
    let seconds = Number(element.dataset.retryCountdown || 30);

    const render = () => {
        element.textContent = `${seconds} segundos`;
    };

    render();

    const interval = setInterval(() => {
        seconds -= 1;
        render();

        if (seconds <= 0) {
            clearInterval(interval);
        }
    }, 1000);
});

const stripeCheckoutState = {
    stripe: null,
    elements: null,
    card: null,
    mountedElement: null,
};

function checkoutUsesCard(form) {
    return new FormData(form).getAll('metodo_pago').includes('tarjeta');
}

function stripeErrorElement(form) {
    return form.querySelector('[data-stripe-card-errors]');
}

function showStripeError(form, message) {
    const error = stripeErrorElement(form);

    if (!error) {
        return;
    }

    error.textContent = message;
    error.classList.remove('hidden');
}

function clearStripeError(form) {
    const error = stripeErrorElement(form);

    if (!error) {
        return;
    }

    error.textContent = '';
    error.classList.add('hidden');
}

function setStripeCheckoutSubmitting(form, submitting) {
    form.dataset.stripeSubmitting = submitting ? 'true' : 'false';

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        button.disabled = submitting;
        button.classList.toggle('opacity-70', submitting);
    });
}

function waitForStripe() {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const interval = setInterval(() => {
            attempts += 1;

            if (window.Stripe) {
                clearInterval(interval);
                resolve(window.Stripe);
            }

            if (attempts >= 80) {
                clearInterval(interval);
                reject(new Error('No fue posible cargar Stripe. Revisa tu conexion e intenta de nuevo.'));
            }
        }, 50);
    });
}

async function mountStripeCard(form) {
    const cardTarget = form.querySelector('[data-stripe-card-element]');

    if (!cardTarget || stripeCheckoutState.mountedElement === cardTarget) {
        return stripeCheckoutState.card;
    }

    const publishableKey = form.dataset.stripePublishableKey;

    if (!publishableKey) {
        showStripeError(form, 'Stripe no tiene clave publica configurada.');
        return null;
    }

    const Stripe = await waitForStripe();

    stripeCheckoutState.stripe = stripeCheckoutState.stripe ?? Stripe(publishableKey);
    stripeCheckoutState.elements = stripeCheckoutState.elements ?? stripeCheckoutState.stripe.elements();

    if (stripeCheckoutState.card) {
        stripeCheckoutState.card.unmount();
    }

    stripeCheckoutState.card = stripeCheckoutState.elements.create('card', {
        hidePostalCode: true,
        style: {
            base: {
                color: '#211920',
                fontFamily: 'Inter, system-ui, sans-serif',
                fontSize: '16px',
                '::placeholder': {
                    color: '#8f828a',
                },
            },
            invalid: {
                color: '#be123c',
            },
        },
    });

    stripeCheckoutState.card.on('change', (event) => {
        if (event.error) {
            showStripeError(form, event.error.message);
        } else {
            clearStripeError(form);
        }
    });

    stripeCheckoutState.card.mount(cardTarget);
    stripeCheckoutState.mountedElement = cardTarget;

    return stripeCheckoutState.card;
}

function initializeStripeCheckout() {
    document.querySelectorAll('[data-stripe-checkout]').forEach((form) => {
        if (checkoutUsesCard(form)) {
            mountStripeCard(form).catch((error) => showStripeError(form, error.message));
        }
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-stripe-checkout]');

    if (!form) {
        return;
    }

    if (!checkoutUsesCard(form)) {
        if (form.dataset.stripeSubmitting === 'true') {
            event.preventDefault();
            return;
        }

        setStripeCheckoutSubmitting(form, true);
        return;
    }

    event.preventDefault();

    if (form.dataset.stripeSubmitting === 'true') {
        return;
    }

    clearStripeError(form);
    setStripeCheckoutSubmitting(form, true);

    try {
        const card = await mountStripeCard(form);
        const tokenInput = form.querySelector('[data-stripe-payment-method]');

        if (!stripeCheckoutState.stripe || !card || !tokenInput) {
            throw new Error('No fue posible preparar el pago con tarjeta.');
        }

        const cardholderName = form.querySelector('[data-stripe-cardholder-name]')?.value ?? '';
        const result = await stripeCheckoutState.stripe.createPaymentMethod({
            type: 'card',
            card,
            billing_details: {
                name: cardholderName,
            },
        });

        if (result.error) {
            throw new Error(result.error.message);
        }

        tokenInput.value = result.paymentMethod.id;
        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        showStripeError(form, error.message || 'No fue posible validar la tarjeta.');
        setStripeCheckoutSubmitting(form, false);
    }
}, true);

document.addEventListener('DOMContentLoaded', initializeStripeCheckout);
document.addEventListener('livewire:navigated', initializeStripeCheckout);

const stripeCheckoutObserver = new MutationObserver(initializeStripeCheckout);
stripeCheckoutObserver.observe(document.documentElement, {
    childList: true,
    subtree: true,
});
