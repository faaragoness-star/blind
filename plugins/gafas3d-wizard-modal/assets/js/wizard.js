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

    const docSection = 'docs/plugin-4-gafas3d-wizard-modal.md §5';

    const overlay = document.querySelector('[data-g3d-wizard-modal-overlay]');
    const dialog = overlay ? overlay.querySelector('.g3d-wizard-modal') : null;
    const closeButton = overlay ? overlay.querySelector('[data-g3d-wizard-modal-close]') : null;
    const focusGuards = overlay
        ? Array.from(overlay.querySelectorAll('[data-g3d-wizard-focus-guard]'))
        : [];
    const openTriggers = Array.from(
        document.querySelectorAll('[data-g3d-wizard-modal-open]')
    );

    let lastFocusedElement = null;
    let isOpen = false;

    if (!overlay || !dialog || !closeButton) {
        console.warn(
            'TODO ' + docSection + ': completar estructura accesible del modal.'
        );
        return;
    }

    if (!openTriggers.length) {
        console.warn('TODO ' + docSection + ': definir disparadores para abrir el modal.');
    }

    if (!dialog.hasAttribute('tabindex')) {
        dialog.setAttribute('tabindex', '-1');
    }

    function getFocusableElements() {
        return Array.from(
            dialog.querySelectorAll(focusableSelectors.join(','))
        ).filter(function (element) {
            return !element.hasAttribute('disabled') && element.tabIndex !== -1;
        });
    }

    function focusFirstElement() {
        const focusable = getFocusableElements();

        if (focusable.length > 0) {
            focusable[0].focus({ preventScroll: true });
        } else {
            dialog.focus({ preventScroll: true });
        }
    }

    function trapFocus(event) {
        if (!isOpen || event.key !== 'Tab') {
            return;
        }

        const focusable = getFocusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            dialog.focus({ preventScroll: true });
            return;
        }

        const firstElement = focusable[0];
        const lastElement = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus({ preventScroll: true });
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus({ preventScroll: true });
        }
    }

    function handleFocusGuard(event) {
        if (!isOpen) {
            return;
        }

        const focusable = getFocusableElements();

        if (!focusable.length) {
            dialog.focus({ preventScroll: true });
            return;
        }

        if (focusGuards[0] === event.target) {
            focusable[focusable.length - 1].focus({ preventScroll: true });
        } else {
            focusable[0].focus({ preventScroll: true });
        }
    }

    function onKeydown(event) {
        if (!isOpen) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }

        trapFocus(event);
    }

    function onOverlayClick(event) {
        if (event.target === overlay) {
            close();
        }
    }

    function open(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (isOpen) {
            return;
        }

        isOpen = true;
        lastFocusedElement = document.activeElement;
        overlay.removeAttribute('hidden');

        focusFirstElement();

        document.addEventListener('keydown', onKeydown);
        overlay.addEventListener('click', onOverlayClick);
        focusGuards.forEach(function (guard) {
            guard.addEventListener('focus', handleFocusGuard);
        });
    }

    function close() {
        if (!isOpen) {
            return;
        }

        isOpen = false;
        overlay.setAttribute('hidden', '');

        document.removeEventListener('keydown', onKeydown);
        overlay.removeEventListener('click', onOverlayClick);
        focusGuards.forEach(function (guard) {
            guard.removeEventListener('focus', handleFocusGuard);
        });

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus({ preventScroll: true });
        }
    }

    openTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', open);
    });

    closeButton.addEventListener('click', close);
})();
