<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Front;

use function add_action;
use function esc_attr;
use function file_exists;
use function filemtime;
use function is_admin;
use function plugin_dir_path;
use function plugin_dir_url;
use function printf;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;

final class Assets
{
    private const HANDLE = 'gafas3d-wizard-modal';
    private const ROOT_ID = 'gafas3d-wizard-modal-root';
    private const ROOT_CLASS = 'gafas3d-wizard-modal-root';

    private static string $pluginFile;

    private function __construct()
    {
    }

    public static function init(string $pluginFile): void
    {
        self::$pluginFile = $pluginFile;

        if (is_admin()) {
            return;
        }

        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
        add_action('wp_footer', [self::class, 'renderRoot']);
    }

    public static function enqueue(): void
    {
        if (is_admin()) {
            return;
        }

        $pluginDir = plugin_dir_path(self::$pluginFile);
        $pluginUrl = plugin_dir_url(self::$pluginFile);

        $stylePath = $pluginDir . 'assets/css/wizard.css';
        $scriptPath = $pluginDir . 'assets/js/wizard.js';

        $styleVersion = self::assetVersion($stylePath);
        $scriptVersion = self::assetVersion($scriptPath);

        wp_register_style(
            self::HANDLE,
            $pluginUrl . 'assets/css/wizard.css',
            [],
            $styleVersion
        );

        wp_register_script(
            self::HANDLE,
            $pluginUrl . 'assets/js/wizard.js',
            [],
            $scriptVersion,
            true
        );

        wp_enqueue_style(self::HANDLE);
        wp_enqueue_script(self::HANDLE);
    }

    /**
     * @return string|false
     */
    private static function assetVersion(string $path): string|false
    {
        if (!file_exists($path)) {
            return false;
        }

        $mtime = filemtime($path);

        if ($mtime === false) {
            return false;
        }

        return (string) $mtime;
    }

    public static function renderRoot(): void
    {
        if (is_admin()) {
            return;
        }

        printf(
            '<div id="%1$s" class="%2$s" data-g3d-wizard-modal-root></div>',
            esc_attr(self::ROOT_ID),
            esc_attr(self::ROOT_CLASS)
        );
    }
}
