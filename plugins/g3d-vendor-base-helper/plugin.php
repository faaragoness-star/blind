<?php

/**
 * Plugin Name: G3D Vendor Base Helper
 * Description: Helpers base para otros plugins. Ver docs/.
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

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'G3D\\VendorBase\\')) {
        return;
    }

    $relative = substr($class, strlen('G3D\\VendorBase\\'));
    $relativePath = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/src/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-vendor-base-helper', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('rest_api_init', static function (): void {
    (new \G3D\VendorBase\Api\HealthController())->registerRoutes();
});

register_activation_hook(__FILE__, static function (): void {
    \G3D\VendorBase\Security\VendorGuard::assertReady();
});

register_deactivation_hook(__FILE__, static function (): void {
    // No-op: placeholder para simetría.
});
