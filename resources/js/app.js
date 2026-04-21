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
