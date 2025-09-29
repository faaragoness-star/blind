(function () {
    'use strict';

    const DOC_PLUGIN_MODAL = 'docs/plugin-4-gafas3d-wizard-modal.md §5';
    const DOC_PLUGIN_STEPPER = 'docs/plugin-4-gafas3d-wizard-modal.md §5.2';
    const DOC_CAPA4_STEPPER = 'docs/Capa 4 — Ui_ux Orquestación — Addenda Aplicada 2025-09-27.md §Componentes de UI 2.1';

    const STEP_DEFINITIONS = [
        { slug: 'pieza', name: 'Pieza' },
        { slug: 'material', name: 'Material' },
        { slug: 'modelo', name: 'Modelo' },
        { slug: 'color', name: 'Color' },
        { slug: 'textura', name: 'Textura' },
        { slug: 'acabado', name: 'Acabado' }
    ];

    const FOCUSABLE_SELECTORS = [
        'a[href]',
        'area[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ];

    function buildTemplate() {
        const overlayId = 'g3d-wizard-modal-overlay';
        const dialogId = 'g3d-wizard-modal-dialog';
        const descriptionId = 'g3d-wizard-modal-description';
        const titleId = 'g3d-wizard-modal-title';

        const stepButtons = STEP_DEFINITIONS.map(function (step) {
            const tabId = 'g3d-wizard-tab-' + step.slug;
            const panelId = 'g3d-wizard-panel-' + step.slug;

            return (
                '<li class="g3d-wizard-modal__step">'
                + '<button type="button" role="tab" aria-selected="false" tabindex="-1" id="'
                + tabId
                + '" aria-controls="'
                + panelId
                + '" class="g3d-wizard-modal__step-button" data-g3d-wizard-step="'
                + step.slug
                + '">'
                + 'TODO '
                + DOC_PLUGIN_STEPPER
                + ' — Paso '
                + step.name
                + '</button>'
                + '</li>'
            );
        }).join('');

        const stepPanels = STEP_DEFINITIONS.map(function (step) {
            const panelId = 'g3d-wizard-panel-' + step.slug;
            const tabId = 'g3d-wizard-tab-' + step.slug;

            return (
                '<section role="tabpanel" aria-labelledby="'
                + tabId
                + '" id="'
                + panelId
                + '" class="g3d-wizard-modal__panel" data-g3d-wizard-panel="'
                + step.slug
                + '" hidden aria-hidden="true">'
                + '<p class="g3d-wizard-modal__panel-placeholder">'
                + 'TODO '
                + DOC_PLUGIN_MODAL
                + ' — Contenido pendiente para el paso '
                + step.name
                + '</p>'
                + '</section>'
            );
        }).join('');

        return ''
            + '<button type="button" class="g3d-wizard-modal__open" data-g3d-wizard-modal-open '
            + 'aria-haspopup="dialog" aria-expanded="false" aria-controls="'
            + dialogId
            + '">'
            + 'TODO '
            + DOC_PLUGIN_MODAL
            + ' — Etiqueta para abrir el wizard'
            + '</button>'
            + '<div class="g3d-wizard-modal__overlay" data-g3d-wizard-modal-overlay id="'
            + overlayId
            + '" hidden aria-hidden="true">'
            + '<div class="g3d-wizard-modal" role="dialog" tabindex="-1" aria-modal="true" '
            + 'id="'
            + dialogId
            + '" '
            + 'aria-labelledby="'
            + titleId
            + '" aria-describedby="'
            + descriptionId
            + '" aria-hidden="true" data-g3d-wizard-modal-dialog>'
            + '<div tabindex="0" data-g3d-wizard-focus-guard="start"></div>'
            + '<div class="g3d-wizard-modal__content">'
            + '<header class="g3d-wizard-modal__header">'
            + '<h1 id="'
            + titleId
            + '" class="g3d-wizard-modal__title">TODO '
            + DOC_PLUGIN_MODAL
            + ' — Título del wizard</h1>'
            + '<p id="'
            + descriptionId
            + '" class="g3d-wizard-modal__description">TODO '
            + DOC_PLUGIN_MODAL
            + ' — Descripción inicial</p>'
            + '<button type="button" class="g3d-wizard-modal__close" data-g3d-wizard-modal-close '
            + 'aria-label="TODO '
            + DOC_PLUGIN_MODAL
            + ' — Etiqueta accesible para cerrar">×</button>'
            + '</header>'
            + '<nav class="g3d-wizard-modal__steps" aria-label="TODO '
            + DOC_PLUGIN_STEPPER
            + ' / '
            + DOC_CAPA4_STEPPER
            + ' — Etiqueta de navegación de pasos">'
            + '<ul role="tablist" aria-orientation="horizontal" class="g3d-wizard-modal__step-list" '
            + 'data-g3d-wizard-tablist>'
            + stepButtons
            + '</ul>'
            + '</nav>'
            + stepPanels
            + '<footer class="g3d-wizard-modal__footer">'
            + '<div class="g3d-wizard-modal__summary" aria-live="polite">TODO '
            + DOC_PLUGIN_MODAL
            + ' — Resumen del estado</div>'
            + '<button type="button" class="g3d-wizard-modal__cta" data-g3d-wizard-modal-cta>'
            + 'TODO '
            + DOC_PLUGIN_MODAL
            + ' — Texto del CTA'
            + '</button>'
            + '</footer>'
            + '</div>'
            + '<div tabindex="0" data-g3d-wizard-focus-guard="end"></div>'
            + '</div>'
            + '</div>';
    }

    function ensureMarkup(root) {
        let overlay = root.querySelector('[data-g3d-wizard-modal-overlay]');

        if (!overlay) {
            root.insertAdjacentHTML('beforeend', buildTemplate());
            overlay = root.querySelector('[data-g3d-wizard-modal-overlay]');
        }

        const dialog = overlay ? overlay.querySelector('[data-g3d-wizard-modal-dialog]')
            || overlay.querySelector('.g3d-wizard-modal')
            : null;

        if (!overlay || !dialog) {
            return null;
        }

        if (!overlay.hasAttribute('aria-hidden')) {
            overlay.setAttribute('aria-hidden', overlay.hasAttribute('hidden') ? 'true' : 'false');
        }

        if (!dialog.hasAttribute('tabindex')) {
            dialog.setAttribute('tabindex', '-1');
        }

        if (!dialog.hasAttribute('aria-hidden')) {
            dialog.setAttribute('aria-hidden', overlay.hasAttribute('hidden') ? 'true' : 'false');
        }

        const openTriggers = Array.from(
            root.querySelectorAll('[data-g3d-wizard-modal-open]')
        );

        const closeButton = overlay.querySelector('[data-g3d-wizard-modal-close]');
        const focusGuards = Array.from(
            overlay.querySelectorAll('[data-g3d-wizard-focus-guard]')
        );

        return {
            overlay: overlay,
            dialog: dialog,
            openTriggers: openTriggers,
            closeButton: closeButton,
            focusGuards: focusGuards
        };
    }

    function getFocusableElements(dialog) {
        return Array.from(
            dialog.querySelectorAll(FOCUSABLE_SELECTORS.join(','))
        ).filter(function (element) {
            if (element.hasAttribute('disabled')) {
                return false;
            }

            if (element.getAttribute('tabindex') === '-1') {
                return false;
            }

            if (element.hasAttribute('data-g3d-wizard-focus-guard')) {
                return false;
            }

            return true;
        });
    }

    function setupTabs(dialog) {
        const tabButtons = Array.from(
            dialog.querySelectorAll('[data-g3d-wizard-step]')
        );

        const tabPanels = new Map();

        tabButtons.forEach(function (button) {
            const panelId = button.getAttribute('aria-controls');

            if (!panelId) {
                return;
            }

            const panel = dialog.querySelector('#' + panelId);

            if (panel) {
                tabPanels.set(button, panel);
            }
        });

        function selectTab(targetButton, shouldFocus) {
            tabButtons.forEach(function (button) {
                const isActive = button === targetButton;
                const panel = tabPanels.get(button);

                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.setAttribute('tabindex', isActive ? '0' : '-1');

                if (isActive && shouldFocus) {
                    button.focus({ preventScroll: true });
                }

                if (panel) {
                    if (isActive) {
                        panel.removeAttribute('hidden');
                        panel.setAttribute('aria-hidden', 'false');
                    } else {
                        panel.setAttribute('hidden', '');
                        panel.setAttribute('aria-hidden', 'true');
                    }
                }
            });
        }

        function activateByIndex(index, shouldFocus) {
            if (!tabButtons.length) {
                return;
            }

            const normalizedIndex = (index + tabButtons.length) % tabButtons.length;
            selectTab(tabButtons[normalizedIndex], shouldFocus);
        }

        tabButtons.forEach(function (button, index) {
            button.addEventListener('click', function () {
                selectTab(button, true);
            });

            button.addEventListener('keydown', function (event) {
                switch (event.key) {
                    case 'ArrowLeft':
                        event.preventDefault();
                        activateByIndex(index - 1, true);
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        activateByIndex(index + 1, true);
                        break;
                    case 'Home':
                        event.preventDefault();
                        activateByIndex(0, true);
                        break;
                    case 'End':
                        event.preventDefault();
                        activateByIndex(tabButtons.length - 1, true);
                        break;
                    case 'Enter':
                    case ' ': // Space
                        event.preventDefault();
                        selectTab(button, true);
                        break;
                    default:
                        break;
                }
            });
        });

        activateByIndex(0, false);

        return {
            selectCurrent: function () {
                if (tabButtons.length) {
                    const active = tabButtons.find(function (button) {
                        return button.getAttribute('aria-selected') === 'true';
                    }) || tabButtons[0];

                    selectTab(active, true);
                }
            }
        };
    }

    function init() {
        const root = document.querySelector('[data-g3d-wizard-modal-root]');

        if (!root || root.getAttribute('data-g3d-wizard-modal-initialized') === 'true') {
            return;
        }

        const elements = ensureMarkup(root);

        if (!elements) {
            return;
        }

        const overlay = elements.overlay;
        const dialog = elements.dialog;
        const focusGuards = elements.focusGuards;
        const closeButton = elements.closeButton;
        const tabController = setupTabs(dialog);

        if (!elements.openTriggers.length || !closeButton) {
            return;
        }

        elements.openTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        });

        let isOpen = false;
        let lastFocusedElement = null;

        function trapFocus(event) {
            if (!isOpen || event.key !== 'Tab') {
                return;
            }

            const focusable = getFocusableElements(dialog);

            if (!focusable.length) {
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

            const focusable = getFocusableElements(dialog);

            if (!focusable.length) {
                dialog.focus({ preventScroll: true });
                return;
            }

            if (event.target.getAttribute('data-g3d-wizard-focus-guard') === 'start') {
                focusable[focusable.length - 1].focus({ preventScroll: true });
            } else {
                focusable[0].focus({ preventScroll: true });
            }
        }

        function closeModal() {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            overlay.setAttribute('hidden', '');
            overlay.setAttribute('aria-hidden', 'true');
            dialog.setAttribute('aria-hidden', 'true');

            document.removeEventListener('keydown', onKeydown);
            overlay.removeEventListener('click', onOverlayClick);
            focusGuards.forEach(function (guard) {
                guard.removeEventListener('focus', handleFocusGuard);
            });

            elements.openTriggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            });

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus({ preventScroll: true });
            }
        }

        function onOverlayClick(event) {
            if (event.target === overlay) {
                closeModal();
            }
        }

        function onKeydown(event) {
            if (!isOpen) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
                return;
            }

            trapFocus(event);
        }

        function openModal(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (isOpen) {
                return;
            }

            isOpen = true;
            lastFocusedElement = event && event.currentTarget instanceof HTMLElement
                ? event.currentTarget
                : document.activeElement;

            overlay.removeAttribute('hidden');
            overlay.setAttribute('aria-hidden', 'false');
            dialog.setAttribute('aria-hidden', 'false');

            document.addEventListener('keydown', onKeydown);
            overlay.addEventListener('click', onOverlayClick);
            focusGuards.forEach(function (guard) {
                guard.addEventListener('focus', handleFocusGuard);
            });

            elements.openTriggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', 'true');
            });

            tabController.selectCurrent();
        }

        elements.openTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', openModal);
        });

        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            closeModal();
        });

        root.setAttribute('data-g3d-wizard-modal-initialized', 'true');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
