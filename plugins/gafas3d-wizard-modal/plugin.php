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

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('gafas3d-wizard-modal', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
