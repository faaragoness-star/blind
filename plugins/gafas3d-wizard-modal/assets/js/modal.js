(() => {
    const openButton = document.querySelector('[data-g3d-wizard-modal-open]');
    const overlay = document.querySelector('[data-g3d-wizard-modal-overlay]');
    const modal = overlay ? overlay.querySelector('.g3d-wizard-modal') : null;
    const closeButton = overlay ? overlay.querySelector('[data-g3d-wizard-modal-close]') : null;
    const focusGuards = overlay ? overlay.querySelectorAll('[data-g3d-wizard-focus-guard]') : [];

    if (!openButton || !overlay || !modal || !closeButton) {
        return;
    }

    let lastFocusedElement = null;
    let isOpen = false;

    const focusableSelectors = [
        'a[href]',
        'button:not([disabled])',
        'textarea:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ];

    const getFocusableElements = () => {
        const elements = modal.querySelectorAll(focusableSelectors.join(','));

        return Array.from(elements).filter((element) => {
            const guard = element.getAttribute('data-g3d-wizard-focus-guard');

            return guard === null;
        });
    };

    const focusFirstElement = () => {
        const focusable = getFocusableElements();

        if (focusable.length > 0) {
            focusable[0].focus();

            return;
        }

        modal.focus();
    };

    const focusLastElement = () => {
        const focusable = getFocusableElements();

        if (focusable.length > 0) {
            focusable[focusable.length - 1].focus();

            return;
        }

        modal.focus();
    };

    const closeModal = () => {
        if (!isOpen) {
            return;
        }

        overlay.setAttribute('hidden', 'hidden');
        overlay.removeAttribute('data-g3d-wizard-modal-open');
        document.removeEventListener('keydown', handleKeydown);
        isOpen = false;

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    };

    const openModal = () => {
        if (isOpen) {
            return;
        }

        lastFocusedElement = document.activeElement;
        overlay.removeAttribute('hidden');
        overlay.setAttribute('data-g3d-wizard-modal-open', 'true');
        document.addEventListener('keydown', handleKeydown);
        isOpen = true;
        focusFirstElement();
    };

    const handleKeydown = (event) => {
        if (!isOpen) {
            return;
        }

        if (event.key === 'Escape' || event.key === 'Esc') {
            event.preventDefault();
            closeModal();
        }
    };

    const handleFocusGuard = (event) => {
        const guard = event.currentTarget;

        if (!(guard instanceof HTMLElement)) {
            return;
        }

        if (guard.dataset.g3dWizardFocusGuard === 'start') {
            focusLastElement();
        } else {
            focusFirstElement();
        }
    };

    openButton.addEventListener('click', openModal);
    closeButton.addEventListener('click', closeModal);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    focusGuards.forEach((guard) => {
        guard.addEventListener('focus', handleFocusGuard);
    });
})();
