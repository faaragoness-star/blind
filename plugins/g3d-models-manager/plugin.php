<?php

/**
 * Plugin Name: G3D Models Manager
 * Description: Esqueleto inicial (sin lógica). Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-models-manager
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-models-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
