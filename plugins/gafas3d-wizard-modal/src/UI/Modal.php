<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\UI;

use function esc_attr;
use function esc_attr__;
use function esc_html;
use function esc_html__;
use function get_locale;
use function printf;

final class Modal
{
    private const STEP_LABELS = [
        'pieza' => 'Pieza',
        'material' => 'Material',
        'modelo' => 'Modelo',
        'color' => 'Color',
        'textura' => 'Textura',
        'acabado' => 'Acabado',
    ];

    private function __construct()
    {
    }

    public static function render(): void
    {
        echo '<button type="button" class="g3d-wizard-modal__open" data-g3d-wizard-modal-open>';
        echo esc_html__(
            'TODO: Definir etiqueta para abrir el wizard. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1.',
            'gafas3d-wizard-modal'
        );
        echo '</button>';

        echo '<div class="g3d-wizard-modal__overlay" data-g3d-wizard-modal-overlay hidden>';

        $locale = esc_attr(get_locale());

        echo '<div class="g3d-wizard-modal" role="dialog" tabindex="-1" aria-modal="true" '
            . 'aria-labelledby="g3d-wizard-modal-title" '
            . 'aria-describedby="g3d-wizard-modal-description" '
            . 'data-snapshot-id="" data-producto-id="" data-locale="' . $locale . '" '
            . 'data-actor-id="" data-what="">';

        echo '<div tabindex="0" data-g3d-wizard-focus-guard="start"></div>';
        echo '<div class="g3d-wizard-modal__content">';

        echo '<header class="g3d-wizard-modal__header">';
        echo '<h1 id="g3d-wizard-modal-title" class="g3d-wizard-modal__title">';
        echo esc_html__(
            'TODO: Título del wizard. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1.',
            'gafas3d-wizard-modal'
        );
        echo '</h1>';

        echo '<p id="g3d-wizard-modal-description" class="g3d-wizard-modal__description">';
        echo esc_html__(
            'TODO: Descripción inicial. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1.',
            'gafas3d-wizard-modal'
        );
        echo '</p>';

        echo '<button type="button" class="g3d-wizard-modal__close" data-g3d-wizard-modal-close aria-label="';
        echo esc_attr__(
            'TODO: Etiqueta aria para cerrar. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1.',
            'gafas3d-wizard-modal'
        );
        echo '">×</button>';
        echo '</header>';

        echo '<nav class="g3d-wizard-modal__steps" aria-label="';
        echo esc_attr__(
            'TODO: Etiqueta navegación de pasos. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.2.',
            'gafas3d-wizard-modal'
        );
        echo '">';

        echo '<ul role="tablist" class="g3d-wizard-modal__step-list">';

        foreach (self::STEP_LABELS as $slug => $label) {
            $tabId = 'g3d-wizard-tab-' . $slug;
            $panelId = 'g3d-wizard-panel-' . $slug;

            echo '<li class="g3d-wizard-modal__step">';

            echo '<button type="button" role="tab" aria-selected="false" tabindex="-1" id="'
                . esc_attr($tabId)
                . '" aria-controls="'
                . esc_attr($panelId)
                . '" class="g3d-wizard-modal__step-button" data-g3d-wizard-step="'
                . esc_attr($slug)
                . '">';

            echo esc_html__($label, 'gafas3d-wizard-modal');
            echo '</button>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</nav>';

        foreach (self::STEP_LABELS as $slug => $label) {
            $panelId = 'g3d-wizard-panel-' . $slug;
            $tabId = 'g3d-wizard-tab-' . $slug;

            echo '<section role="tabpanel" aria-labelledby="'
                . esc_attr($tabId)
                . '" id="'
                . esc_attr($panelId)
                . '" class="g3d-wizard-modal__panel" hidden>';

            echo '<p class="g3d-wizard-modal__panel-placeholder">';
            printf(
                /* translators: %s: nombre del paso definido en docs/plugin-4-gafas3d-wizard-modal.md §5 */
                esc_html__(
                    'TODO: Contenido para el paso %s. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.',
                    'gafas3d-wizard-modal'
                ),
                esc_html($label)
            );
            echo '</p>';
            echo '</section>';
        }

        echo '<section class="g3d-wizard-modal__rules" data-g3d-wizard-rules aria-live="polite"></section>';

        echo '<footer class="g3d-wizard-modal__footer">';
        echo '<div class="g3d-wizard-modal__summary" aria-live="polite">';
        echo esc_html__(
            'TODO: Resumen del estado. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.5.',
            'gafas3d-wizard-modal'
        );
        echo '</div>';

        echo '<div class="g3d-wizard-modal__msg"'
            . ' role="status" aria-live="polite" aria-atomic="true"></div>';

        echo '<button type="button"'
            . ' class="g3d-wizard-modal__verify"'
            . ' data-g3d-wizard-modal-verify>';
        echo esc_html__('Verificar', 'gafas3d-wizard-modal');
        echo '</button>';

        echo '<button type="button" class="g3d-wizard-modal__cta" data-g3d-wizard-modal-cta>';
        echo esc_html__(
            'TODO: Texto del CTA. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.6.',
            'gafas3d-wizard-modal'
        );
        echo '</button>';

        echo '</footer>';

        echo '</div>'; // .g3d-wizard-modal__content
        echo '<div tabindex="0" data-g3d-wizard-focus-guard="end"></div>';
        echo '</div>'; // .g3d-wizard-modal
        echo '</div>'; // .g3d-wizard-modal__overlay
    }
}
