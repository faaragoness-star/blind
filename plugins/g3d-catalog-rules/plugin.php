<?php
/**
 * Plugin Name: G3D Catalog & Rules
 * Description: Esqueleto inicial (sin lógica). Ver docs/ para funciones y contratos.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-catalog-rules
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, static function (): void {
    // Placeholder de activación (nop).
});

register_deactivation_hook(__FILE__, static function (): void {
    // Placeholder de desactivación (nop).
});

add_action('init', static function (): void {
    // Cargar traducciones desde plugins/g3d-catalog-rules/languages
    load_plugin_textdomain(
        'g3d-catalog-rules',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
