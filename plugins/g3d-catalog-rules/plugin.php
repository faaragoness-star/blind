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

use G3D\CatalogRules\Api\RulesReadController;

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'G3D\\CatalogRules\\',
        'G3dCatalogRules\\',
    ];

    foreach ($prefixes as $prefix) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relative);
        $file = __DIR__ . '/src/' . $relativePath . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-catalog-rules', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('rest_api_init', static function (): void {
    (new RulesReadController())->registerRoutes();
});
