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

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, function () {
    // Placeholder de activación (nop).
});
register_deactivation_hook(__FILE__, function () {
    // Placeholder de desactivación (nop).
});

add_action('init', function () {
    load_plugin_textdomain('gafas3d-wizard-modal', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
