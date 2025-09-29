<?php

/**
 * Plugin Name: G3D Validate & Sign
 * Description: Validación y firma Ed25519 para SKUs. Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-validate-sign
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-validate-sign', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
