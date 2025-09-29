<?php
declare(strict_types=1);

namespace Gafas3d\WizardModal\Admin;

use Gafas3d\WizardModal\UI\Modal;
use function add_menu_page;
use function __;

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
        echo '<div id="gafas3d-wizard-modal-root" class="gafas3d-wizard-modal-root">';
        echo '<!-- TODO: Definir IDs y clases exactos del contenedor raíz. Ver docs/plugin-4-gafas3d-wizard-modal.md §5.1. -->';
 Modal::render();
 echo '</div>';
    }
}
