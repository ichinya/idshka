document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-portal-nav-toggle]');
    const nav = document.querySelector('[data-portal-nav]');

    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            nav.classList.toggle('hidden');
        });
    }

    for (const button of document.querySelectorAll('[data-copy-target]')) {
        button.addEventListener('click', async () => {
            const targetId = button.getAttribute('data-copy-target');
            const target = targetId ? document.getElementById(targetId) : null;

            if (!target || !navigator.clipboard) {
                return;
            }

            await navigator.clipboard.writeText(target.textContent || '');
            const originalText = button.textContent;
            button.textContent = 'Copied';

            window.setTimeout(() => {
                button.textContent = originalText;
            }, 1200);
        });
    }

    for (const select of document.querySelectorAll('[data-web-client-site-select]')) {
        const targetSelector = select.getAttribute('data-redirect-target') || '[data-web-client-redirect-uri]';
        const input = document.querySelector(targetSelector);

        if (!input) {
            continue;
        }

        const syncRedirectUri = () => {
            const selected = select.selectedOptions[0];
            const uri = selected?.getAttribute('data-default-redirect-uri') || '';

            input.setAttribute('placeholder', uri);

            if (input.getAttribute('data-autofilled') === '1') {
                input.value = uri;
            }
        };

        input.addEventListener('input', () => {
            input.setAttribute('data-autofilled', '0');
        });

        select.addEventListener('change', syncRedirectUri);
        syncRedirectUri();
    }
});
