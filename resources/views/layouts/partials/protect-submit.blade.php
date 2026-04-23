<script @nonce>
    (() => {
        const selector = 'form[method]:not([method="GET"]):not([data-disable-submit-guard])';

        const restore = (form) => {
            form.dataset.submitting = 'false';
            form.querySelectorAll('[data-submit-guard]').forEach((button) => {
                button.disabled = false;
                button.classList.remove('opacity-70');
                const label = button.querySelector('[data-submit-label]');
                const spinner = button.querySelector('[data-submit-spinner]');

                if (label && button.dataset.originalLabel) {
                    label.textContent = button.dataset.originalLabel;
                }

                if (spinner) {
                    spinner.classList.add('hidden');
                }
            });
        };

        document.addEventListener('submit', (event) => {
            const form = event.target.closest(selector);

            if (!form) {
                return;
            }

            if (!form.checkValidity()) {
                restore(form);
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                button.dataset.submitGuard = 'true';
                button.disabled = true;
                button.classList.add('opacity-70');

                if (button.tagName === 'BUTTON' && !button.querySelector('[data-submit-label]')) {
                    button.dataset.originalLabel = button.textContent.trim();
                    button.innerHTML = `
                        <span data-submit-spinner class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent align-[-0.125em]"></span>
                        <span data-submit-label>Procesando...</span>
                    `;
                }
            });
        }, true);

        document.addEventListener('invalid', (event) => {
            const form = event.target.closest(selector);

            if (form) {
                restore(form);
            }
        }, true);

        document.addEventListener('livewire:navigated', () => {
            document.querySelectorAll(selector).forEach(restore);
        });
    })();
</script>
