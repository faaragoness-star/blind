<?php

/**
 * Plugin Name: G3D Admin & Ops
 * Description: Utilidades de administración/operaciones. Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-admin-ops
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-admin-ops', false, dirname(plugin_basename(__FILE__)) . '/languages');
};
