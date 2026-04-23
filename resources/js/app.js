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
