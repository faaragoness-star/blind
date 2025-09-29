<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Admin;

use Gafas3d\WizardModal\UI\Modal;

use function add_menu_page;
use function __;
use function esc_attr;

final class Page
{
    public const MENU_SLUG = 'g3d-wizard';

    private function __construct()
    {
    }

    public static function register(): void
    {
        add_menu_page(
            __('Gafas3D Wizard Modal', 'gafas3d-wizard-modal'),
            __('Gafas3D Wizard Modal', 'gafas3d-wizard-modal'),
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'render'],
            '',
            82
        );
    }

    public static function render(): void
    {
        printf(
            '<div id="%1$s" class="gafas3d-wizard-modal-root" '
            . 'data-g3d-endpoint-rules="%2$s" '
            . 'data-g3d-endpoint-validate="%3$s" '
            . 'data-g3d-endpoint-verify="%4$s">',
            esc_attr('gafas3d-wizard-modal-root'),
            esc_attr('/wp-json/g3d/v1/catalog/rules'),
            esc_attr('/wp-json/g3d/v1/validate-sign'),
            esc_attr('/wp-json/g3d/v1/verify')
        );

        echo '<!-- TODO: Definir IDs y clases exactos del contenedor raíz. '
            . 'Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1. -->';

        Modal::render();

        echo '</div>';
    }
}
