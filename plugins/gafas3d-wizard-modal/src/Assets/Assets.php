<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Assets;

use function add_action;
use function dirname;
use function get_locale;
use function plugins_url;
use function rest_url;
use function wp_create_nonce;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;
use function wp_register_script;
use function wp_register_style;

final class Assets
{
    public const HANDLE_JS = 'g3d-wizard-modal';

    public const HANDLE_CSS = 'g3d-wizard-modal';

    private function __construct()
    {
    }

    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue'], 10);
    }

    public static function enqueue(): void
    {
        $pluginFile = dirname(__DIR__, 2) . '/plugin.php';

        wp_register_script(
            self::HANDLE_JS,
            plugins_url('assets/js/wizard-modal.js', $pluginFile),
            [],
            false,
            true
        );

        wp_register_style(
            self::HANDLE_CSS,
            plugins_url('assets/css/wizard-modal.css', $pluginFile),
            [],
            false
        );

        wp_localize_script(
            self::HANDLE_JS,
            'G3DWIZARD',
            [
                'api' => [
                    'validateSign' => rest_url('g3d/v1/validate-sign'),
                    'verify' => rest_url('g3d/v1/verify'),
                    'rules' => rest_url('g3d/v1/catalog/rules'),
                ],
                'nonce' => wp_create_nonce('wp_rest'),
                'locale' => get_locale(),
            ]
        );

        wp_enqueue_style(self::HANDLE_CSS);
        wp_enqueue_script(self::HANDLE_JS);
    }
}
