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

use G3D\AdminOps\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'G3D\\AdminOps\\')) {
        return;
    }

    $relative = substr($class, strlen('G3D\\AdminOps\\'));
    $relativePath = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/src/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-admin-ops', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

$plugin = new Plugin();
$plugin->register();

add_action('rest_api_init', static function (): void {
    $reader = new \G3D\AdminOps\Audit\InMemoryEditorialActionLogger();
    // TODO(doc §persistencia): sustituir por almacenamiento persistente cuando esté definido.
    (new \G3D\AdminOps\Api\AuditReadController($reader))->registerRoutes();
    (new \G3D\AdminOps\Api\AuditWriteController($reader))->registerRoutes();
});
