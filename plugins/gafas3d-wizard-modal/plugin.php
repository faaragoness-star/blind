<?php

/**
 * Plugin Name: Gafas3D Wizard Modal
 * Description: UI modal “wizard” para selección de opciones. Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: gafas3d-wizard-modal
 */

declare(strict_types=1);

use Gafas3d\WizardModal\Admin\Assets as AdminAssets;
use Gafas3d\WizardModal\Admin\Page as AdminPage;
use Gafas3d\WizardModal\Shortcode\WizardShortcode;

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Gafas3d\\WizardModal\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
    $file = __DIR__ . '/src/' . $relativePath . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

add_action('init', static function (): void {
    load_plugin_textdomain('gafas3d-wizard-modal', false, dirname(plugin_basename(__FILE__)) . '/languages');
    WizardShortcode::register();
    \Gafas3d\WizardModal\Assets\Assets::register();
});

add_action('admin_menu', static function (): void {
    AdminPage::register();
});

add_action('plugins_loaded', static function (): void {
    (new AdminAssets(__FILE__))->register();
});

