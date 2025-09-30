<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Admin;

use function add_action;
use function is_string;
use function wp_add_inline_script;
use function wp_add_inline_style;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;
use function wp_json_encode;

final class Assets
{
    private string $handle = 'g3d-wizard-modal-admin';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', function (): void {
            $page = $_GET['page'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            if (!is_string($page) || $page !== Page::MENU_SLUG) {
                return;
            }

            $this->enqueueStyle();
            $this->enqueueScript();
        });
    }

    private function enqueueStyle(): void
    {
        wp_register_style($this->handle, false, [], null);
        wp_enqueue_style($this->handle);
        wp_add_inline_style($this->handle, $this->style());
    }

    private function enqueueScript(): void
    {
        wp_register_script($this->handle, '', [], null, true);
        wp_enqueue_script($this->handle);
        wp_add_inline_script($this->handle, $this->script());
    }

    private function style(): string
    {
        return <<<CSS
.g3d-wizard-modal__overlay[hidden] {
    display: none;
}

.g3d-wizard-modal__overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 5vh 2rem;
    background: rgba(0, 0, 0, 0.55);
    overflow-y: auto;
    z-index: 100000;
}

.g3d-wizard-modal {
    width: 100%;
    max-width: 960px;
    margin: 5vh auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
}

.g3d-wizard-modal__content {
    padding: 24px;
}

.g3d-wizard-modal__header,
.g3d-wizard-modal__footer {
    padding: 0 24px 24px;
}
CSS;
    }

    private function script(): string
    {
        $focusableSelector = wp_json_encode(
            'a[href], button:not([disabled]), textarea, input, select, '
            . '[tabindex]:not([tabindex="-1"])'
        );

        return <<<JS
(function () {
    const overlay = document.querySelector('[data-g3d-wizard-modal-overlay]');
    if (!overlay) {
        return;
    }

    const modal = overlay.querySelector('.g3d-wizard-modal');
    if (!modal) {
        return;
    }

    const openers = document.querySelectorAll('[data-g3d-wizard-modal-open]');
    const closers = overlay.querySelectorAll('[data-g3d-wizard-modal-close]');
    const focusGuards = overlay.querySelectorAll('[data-g3d-wizard-focus-guard]');
    const focusableSelector = {$focusableSelector};
    let lastTrigger = null;

    function getFocusableElements() {
        return Array.from(modal.querySelectorAll(focusableSelector))
            .filter(function (element) {
                return !element.hasAttribute('disabled')
                    && element.getAttribute('aria-hidden') !== 'true'
                    && element.offsetParent !== null;
            });
    }

    function focusInitialElement() {
        const focusable = getFocusableElements();
        if (focusable.length > 0) {
            focusable[0].focus();
            return;
        }

        modal.focus();
    }

    function onKeydown(event) {
        if (event.key !== 'Escape' && event.key !== 'Esc') {
            return;
        }

        if (overlay.hasAttribute('hidden')) {
            return;
        }

        event.preventDefault();
        closeModal();
    }

    function openModal(trigger) {
        lastTrigger = trigger || document.activeElement;
        overlay.removeAttribute('hidden');
        focusInitialElement();
        document.addEventListener('keydown', onKeydown);
    }

    function closeModal() {
        overlay.setAttribute('hidden', '');
        document.removeEventListener('keydown', onKeydown);

        if (lastTrigger && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus();
        }
    }

    openers.forEach(function (opener) {
        opener.addEventListener('click', function (event) {
            event.preventDefault();
            openModal(opener);
        });
    });

    closers.forEach(function (closer) {
        closer.addEventListener('click', function (event) {
            event.preventDefault();
            closeModal();
        });
    });

    focusGuards.forEach(function (guard) {
        guard.addEventListener('focus', function () {
            if (overlay.hasAttribute('hidden')) {
                return;
            }

            const focusable = getFocusableElements();

            if (focusable.length === 0) {
                modal.focus();
                return;
            }

            if (guard.dataset.g3dWizardFocusGuard === 'start') {
                focusable[focusable.length - 1].focus();
                return;
            }

            if (guard.dataset.g3dWizardFocusGuard === 'end') {
                focusable[0].focus();
            }
        });
    });
})();
JS;
    }
}
