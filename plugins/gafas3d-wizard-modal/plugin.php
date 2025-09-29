<?php

/**
 * Plugin Name: Gafas3D Wizard Modal
 * Description: Esqueleto inicial (sin lógica). Ver docs/ para funciones y contratos.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: gafas3d-wizard-modal
 */

declare(strict_types=1);

use Gafas3d\WizardModal\Admin\Page;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Admin/Page.php';
require_once __DIR__ . '/src/UI/Modal.php';

register_activation_hook(__FILE__, static function (): void {
    // Placeholder de activación (nop).
});

register_deactivation_hook(__FILE__, static function (): void {
    // Placeholder de desactivación (nop).
});

add_action('init', static function (): void {
    load_plugin_textdomain(
        'gafas3d-wizard-modal',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

add_action('admin_menu', static function (): void {
    Page::register();
});

add_action('admin_enqueue_scripts', static function (string $hook): void {
    if ($hook !== 'toplevel_page_g3d-wizard') {
        return;
    }

    wp_enqueue_style(
        'gafas3d-wizard-modal',
        plugins_url('assets/css/wizard.css', __FILE__),
        [],
        '0.1.0'
    );

    wp_enqueue_script(
        'gafas3d-wizard-modal',
        plugins_url('assets/js/wizard.js', __FILE__),
        [],
        '0.1.0',
        true
    );
});
