<?php

/**
 * Plugin Name: G3D Vendor Base Helper
 * Description: Esqueleto inicial (sin lógica). Ver docs/ para funciones y contratos.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-vendor-base-helper
 */

declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, function () {
    // Placeholder de activación (nop).
});
register_deactivation_hook(__FILE__, function () {
    // Placeholder de desactivación (nop).
});

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-vendor-base-helper', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
