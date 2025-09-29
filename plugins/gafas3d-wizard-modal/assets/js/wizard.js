(function () {
    const focusableSelectors = [
        'a[href]',
        'area[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ];

    const docRef = 'docs/plugin-4-gafas3d-wizard-modal.md §5';

    const overlay = document.querySelector('[data-g3d-wizard-modal-overlay]');
    const dialog = overlay ? overlay.querySelector('[role="dialog"]') : null;
    const openButton = document.querySelector('[data-g3d-wizard-modal-open]');
    const closeButton = overlay ? overlay.querySelector('[data-g3d-wizard-modal-close]') : null;
    let lastFocusedElement = null;

    if (!overlay || !dialog || !openButton || !closeButton) {
        console.warn('TODO: Completar markup para el modal. Ver ' + docRef);
        return;
    }

    function getFocusableElements() {
        return Array.from(
            dialog.querySelectorAll(focusableSelectors.join(','))
        ).filter(function (element) {
            return element.offsetParent !== null || element === closeButton;
        });
    }

    function trapFocus(event) {
        if (overlay.hasAttribute('hidden') || event.key !== 'Tab') {
            return;
        }

        const focusable = getFocusableElements();
        if (!focusable.length) {
            event.preventDefault();
            dialog.focus({ preventScroll: true });
            return;
        }

        const firstElement = focusable[0];
        const lastElement = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }

    function onKeyDown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        trapFocus(event);
    }

    function openModal() {
        overlay.removeAttribute('hidden');
        lastFocusedElement = document.activeElement;

        const focusable = getFocusableElements();
        if (focusable.length) {
            focusable[0].focus();
        } else {
            dialog.focus({ preventScroll: true });
        }

        document.addEventListener('keydown', onKeyDown);
    }

    function closeModal() {
        overlay.setAttribute('hidden', '');
        document.removeEventListener('keydown', onKeyDown);

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    openButton.addEventListener('click', openModal);
    closeButton.addEventListener('click', closeModal);
})();
