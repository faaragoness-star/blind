<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\PublicAssets;

use function dirname;
use function is_admin;
use function plugins_url;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;
use function wp_register_script;
use function wp_register_style;

final class Assets
{
    private const HANDLE = 'g3d-wizard-modal';
    private const VERSION = '0.1.0';

    private function __construct()
    {
    }

    public static function register(): void
    {
        if (is_admin()) {
            return;
        }

        $pluginFile = dirname(__DIR__, 2) . '/plugin.php';

        wp_register_style(
            self::HANDLE,
            plugins_url('assets/css/wizard-modal.css', $pluginFile),
            [],
            self::VERSION
        );

        wp_register_script(
            self::HANDLE,
            plugins_url('assets/js/wizard-modal.js', $pluginFile),
            [],
            self::VERSION,
            true
        );

        wp_localize_script(
            self::HANDLE,
            'G3DWIZ',
            [
                'version' => self::VERSION,
            ]
        );

        wp_enqueue_style(self::HANDLE);
        wp_enqueue_script(self::HANDLE);
    }
}
